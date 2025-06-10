<?php
/**
 * The invoice template functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The invoice template functionality of the plugin.
 *
 * Handles invoice template modifications.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Invoice_Template {

    /**
     * Database instance.
     *
     * @since    2.0.0
     * @access   private
     * @var      EDD_Custom_VAT_Database    $db    Database instance.
     */
    private $db;

    /**
     * Logger instance.
     *
     * @since    2.0.0
     * @access   private
     * @var      EDD_Custom_VAT_Logger    $logger    Logger instance.
     */
    private $logger;

    /**
     * Tax display instance.
     *
     * @since    2.0.0
     * @access   private
     * @var      EDD_Custom_VAT_Tax_Display    $tax_display    Tax display instance.
     */
    private $tax_display;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->db = new EDD_Custom_VAT_Database();
        $this->logger = new EDD_Custom_VAT_Logger();
        $this->tax_display = new EDD_Custom_VAT_Tax_Display();
    }

    /**
     * Modify invoice line items to include custom tax rates.
     *
     * @since    2.0.0
     * @param    array     $line_items    The line items.
     * @param    array     $payment_meta  The payment meta.
     * @param    int       $payment_id    The payment ID.
     * @return   array                    The modified line items.
     */
    public function modify_invoice_line_items($line_items, $payment_meta, $payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $line_items;
        }

        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return $line_items;
        }

        // Get bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();

        // Loop through line items
        foreach ($line_items as $key => $item) {
            // Find matching cart item
            $cart_item = $this->find_cart_item_by_name($cart_details, $item['name']);
            if (!$cart_item) {
                continue;
            }
            
            // Get the tax rate
            $tax_rate = isset($cart_item['tax_rate']) ? $cart_item['tax_rate'] : 0;
            
            // Add tax rate to line item
            $line_items[$key]['tax_rate'] = $tax_rate;
            
            // Format tax rate for display
            $line_items[$key]['tax_rate_formatted'] = $tax_rate . '%';
            
            // Handle bundle items
            if (isset($cart_item['item_number']['options']['bundled_products']) && !empty($cart_item['item_number']['options']['bundled_products'])) {
                // This is a bundle
                $bundle_items = $cart_item['item_number']['options']['bundled_products'];
                
                // If detailed or summarized mode, add bundle items with their tax rates
                if ('simple' !== $bundle_display && !empty($bundle_items)) {
                    $bundle_items_data = $this->get_bundle_items_data($bundle_items, $payment_meta, $payment_id);
                    
                    if ('summarized' === $bundle_display) {
                        // Group by tax rate
                        $grouped_items = $this->group_bundle_items_by_tax_rate($bundle_items_data);
                        $line_items[$key]['bundle_items'] = $grouped_items;
                    } else {
                        // Detailed mode - show each item
                        $line_items[$key]['bundle_items'] = $bundle_items_data;
                    }
                }
            }
        }
        
        return $line_items;
    }

    /**
     * Add tax details to payment meta.
     *
     * @since    2.0.0
     * @param    array     $meta         The payment meta.
     * @param    int       $payment_id   The payment ID.
     * @return   array                   The modified payment meta.
     */
    public function add_tax_details_to_meta($meta, $payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $meta;
        }

        // Get tax summary
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
        // If we have a tax summary, add it to the meta
        if (!empty($tax_summary)) {
            $meta['tax_summary'] = $tax_summary;
        }
        
        return $meta;
    }

    /**
     * Get bundle items data with tax rates.
     *
     * @since    2.0.0
     * @param    array     $bundle_items    The bundle items.
     * @param    array     $payment_meta    The payment meta.
     * @param    int       $payment_id      The payment ID.
     * @return   array                      The bundle items data.
     */
    private function get_bundle_items_data($bundle_items, $payment_meta, $payment_id) {
        $items_data = array();
        
        // Get customer country
        $country = $this->get_customer_country_from_payment($payment_meta);
        
        foreach ($bundle_items as $item_id) {
            // Get download
            $download = new EDD_Download($item_id);
            if (!$download) {
                continue;
            }
            
            // Get price
            $price = $download->get_price();
            
            // Get tax rate
            $tax_rate = $this->db->get_tax_rate($item_id, $country);
            if (false === $tax_rate) {
                $tax_rate = function_exists('edd_get_tax_rate') ? edd_get_tax_rate($country) : 0;
            }
            
            // Calculate tax
            $tax = $price * ($tax_rate / 100);
            
            // Add to items data
            $items_data[] = array(
                'id' => $item_id,
                'name' => $download->get_name(),
                'price' => $price,
                'tax_rate' => $tax_rate,
                'tax' => $tax,
                'total' => $price + $tax
            );
        }
        
        return $items_data;
    }

    /**
     * Group bundle items by tax rate.
     *
     * @since    2.0.0
     * @param    array     $bundle_items    The bundle items.
     * @return   array                      The grouped items.
     */
    private function group_bundle_items_by_tax_rate($bundle_items) {
        $grouped_items = array();
        
        foreach ($bundle_items as $item) {
            $tax_rate = $item['tax_rate'];
            $rate_key = number_format($tax_rate, 2);
            
            if (!isset($grouped_items[$rate_key])) {
                $grouped_items[$rate_key] = array(
                    'rate' => $tax_rate,
                    'items' => array(),
                    'total' => 0,
                    'tax' => 0
                );
            }
            
            $grouped_items[$rate_key]['items'][] = $item;
            $grouped_items[$rate_key]['total'] += $item['price'];
            $grouped_items[$rate_key]['tax'] += $item['tax'];
        }
        
        return $grouped_items;
    }

    /**
     * Find cart item by name.
     *
     * @since    2.0.0
     * @param    array     $cart_details    The cart details.
     * @param    string    $name            The item name.
     * @return   array|false                The cart item or false if not found.
     */
    private function find_cart_item_by_name($cart_details, $name) {
        foreach ($cart_details as $item) {
            if (isset($item['name']) && $item['name'] === $name) {
                return $item;
            }
        }
        
        return false;
    }

    /**
     * Get the customer's country from payment meta.
     *
     * @since    2.0.0
     * @param    array     $payment_meta    The payment meta.
     * @return   string                     The customer's country code.
     */
    private function get_customer_country_from_payment($payment_meta) {
        // Get user info
        $user_info = isset($payment_meta['user_info']) ? $payment_meta['user_info'] : array();
        if (empty($user_info)) {
            return '';
        }

        // Get address
        $address = isset($user_info['address']) ? $user_info['address'] : array();
        if (empty($address)) {
            return '';
        }

        // Get country
        $country = isset($address['country']) ? $address['country'] : '';
        
        return $country;
    }
}
