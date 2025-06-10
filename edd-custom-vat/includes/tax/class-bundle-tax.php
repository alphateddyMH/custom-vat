<?php
/**
 * The bundle tax functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The bundle tax functionality of the plugin.
 *
 * Handles tax calculations and display for bundle products.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Bundle_Tax {

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
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->db = new EDD_Custom_VAT_Database();
        $this->logger = new EDD_Custom_VAT_Logger();
        
        // Register late-priority hooks to ensure we run after EDD Geo Targeting
        add_action('init', array($this, 'register_late_hooks'), 999);
    }
    
    /**
     * Register hooks with late priority to ensure they run after EDD Geo Targeting.
     *
     * @since    2.0.0
     */
    public function register_late_hooks() {
        // Use a very high priority (999) to ensure our filters run after EDD's geo targeting
        add_filter('edd_bundle_item_tax_rate', array($this, 'get_bundle_item_tax_rate'), 999, 3);
        add_filter('edd_order_items', array($this, 'modify_order_items_for_bundles'), 999, 2);
        add_filter('edd_get_bundle_data', array($this, 'prepare_bundle_items_for_checkout'), 999, 2);
        add_filter('edd_bundle_item_row', array($this, 'format_bundle_items_for_display'), 999, 3);
    }

    /**
     * Get the tax rate for a bundle item.
     *
     * @since    2.0.0
     * @param    float     $rate           The original tax rate.
     * @param    int       $download_id    The download ID.
     * @param    int       $bundle_id      The bundle ID.
     * @return   float                     The modified tax rate.
     */
    public function get_bundle_item_tax_rate($rate, $download_id, $bundle_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $rate;
        }

        // Get the bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, use the bundle's tax rate
        if ('simple' === $bundle_display) {
            return $rate;
        }

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $rate;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $rate;
        }

        // Get custom tax rate for the bundle item
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If a custom rate exists, use it
        if (false !== $custom_rate) {
            // Log the tax rate change if debug mode is enabled
            if (EDD_Custom_VAT_Settings::is_debug_mode()) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: bundle ID, 3: country code, 4: original rate, 5: custom rate */
                        __('Modified tax rate for bundle item #%1$d in bundle #%2$d for country %3$s: %4$f%% -> %5$f%%', 'edd-custom-vat'),
                        $download_id,
                        $bundle_id,
                        $country,
                        $rate,
                        $custom_rate
                    )
                );
            }
            
            return $custom_rate;
        }

        return $rate;
    }

    /**
     * Modify order items for bundles to include correct tax rates.
     *
     * @since    2.0.0
     * @param    array     $order_items    The order items.
     * @param    int       $payment_id     The payment ID.
     * @return   array                     The modified order items.
     */
    public function modify_order_items_for_bundles($order_items, $payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $order_items;
        }

        // Get the bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, no need to modify
        if ('simple' === $bundle_display) {
            return $order_items;
        }

        // Get payment meta
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        if (empty($payment_meta)) {
            return $order_items;
        }

        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return $order_items;
        }

        // Loop through order items
        foreach ($order_items as $key => $item) {
            // Check if this is a bundle
            if (!isset($item['bundle_items']) || empty($item['bundle_items'])) {
                continue;
            }

            // Get the bundle ID
            $bundle_id = isset($item['id']) ? $item['id'] : 0;
            if (empty($bundle_id)) {
                continue;
            }

            // Get the bundle price
            $bundle_price = isset($item['price']) ? $item['price'] : 0;
            
            // Get the bundle tax rate
            $bundle_tax_rate = isset($item['tax_rate']) ? $item['tax_rate'] : 0;
            
            // Initialize bundle items tax data
            $bundle_items_tax = array();
            $total_bundle_tax = 0;
            
            // Loop through bundle items
            foreach ($item['bundle_items'] as $bundle_item_key => $bundle_item) {
                // Get the bundle item ID
                $bundle_item_id = isset($bundle_item['id']) ? $bundle_item['id'] : 0;
                if (empty($bundle_item_id)) {
                    continue;
                }
                
                // Get the bundle item price
                $bundle_item_price = isset($bundle_item['price']) ? $bundle_item['price'] : 0;
                
                // Get the bundle item tax rate
                $bundle_item_tax_rate = $this->get_bundle_item_tax_rate_from_payment($payment_id, $bundle_item_id, $bundle_tax_rate);
                
                // Calculate tax
                $bundle_item_tax = $bundle_item_price * ($bundle_item_tax_rate / 100);
                
                // Store tax data
                $bundle_items_tax[$bundle_item_key] = array(
                    'rate' => $bundle_item_tax_rate,
                    'amount' => $bundle_item_tax
                );
                
                // Add to total
                $total_bundle_tax += $bundle_item_tax;
                
                // Update bundle item in order items
                $order_items[$key]['bundle_items'][$bundle_item_key]['tax_rate'] = $bundle_item_tax_rate;
                $order_items[$key]['bundle_items'][$bundle_item_key]['tax'] = $bundle_item_tax;
            }
            
            // Store bundle items tax data
            $order_items[$key]['bundle_items_tax'] = $bundle_items_tax;
            
            // Update bundle tax
            $order_items[$key]['tax'] = $total_bundle_tax;
            
            // Log if debug mode is enabled
            if (EDD_Custom_VAT_Settings::is_debug_mode()) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: bundle ID, 2: total tax amount */
                        __('Calculated total tax for bundle #%1$d: %2$f', 'edd-custom-vat'),
                        $bundle_id,
                        $total_bundle_tax
                    )
                );
            }
        }
        
        return $order_items;
    }

    /**
     * Get the tax rate for a bundle item from payment meta.
     *
     * @since    2.0.0
     * @param    int       $payment_id           The payment ID.
     * @param    int       $bundle_item_id       The bundle item ID.
     * @param    float     $default_tax_rate     The default tax rate.
     * @return   float                           The tax rate.
     */
    private function get_bundle_item_tax_rate_from_payment($payment_id, $bundle_item_id, $default_tax_rate) {
        // Get payment meta
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        if (empty($payment_meta)) {
            return $default_tax_rate;
        }

        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return $default_tax_rate;
        }

        // Loop through cart items
        foreach ($cart_details as $item) {
            // Check if this is a bundle
            if (!isset($item['item_number']['options']['bundled_products']) || empty($item['item_number']['options']['bundled_products'])) {
                continue;
            }

            // Check if this bundle contains the bundle item
            if (!in_array($bundle_item_id, $item['item_number']['options']['bundled_products'])) {
                continue;
            }

            // Get the bundle item price
            $bundle_items_prices = isset($item['item_number']['options']['bundle_items_prices']) ? $item['item_number']['options']['bundle_items_prices'] : array();
            
            // Get the bundle item tax rate
            $bundle_items_tax_rates = isset($item['item_number']['options']['bundle_items_tax_rates']) ? $item['item_number']['options']['bundle_items_tax_rates'] : array();
            
            // If we have a tax rate for this bundle item, use it
            if (isset($bundle_items_tax_rates[$bundle_item_id])) {
                return $bundle_items_tax_rates[$bundle_item_id];
            }
        }

        // Get the customer's country
        $country = isset($payment_meta['user_info']['address']['country']) ? $payment_meta['user_info']['address']['country'] : '';
        if (empty($country)) {
            return $default_tax_rate;
        }

        // Get custom tax rate for the bundle item
        $custom_rate = $this->db->get_tax_rate($bundle_item_id, $country);
        
        // If a custom rate exists, use it
        if (false !== $custom_rate) {
            return $custom_rate;
        }

        return $default_tax_rate;
    }

    /**
     * Prepare bundle items for checkout.
     *
     * @since    2.0.0
     * @param    array     $bundle_data    The bundle data.
     * @param    int       $bundle_id      The bundle ID.
     * @return   array                     The modified bundle data.
     */
    public function prepare_bundle_items_for_checkout($bundle_data, $bundle_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $bundle_data;
        }

        // Get the bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, no need to modify
        if ('simple' === $bundle_display) {
            return $bundle_data;
        }

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $bundle_data;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $bundle_data;
        }

        // Get bundle items
        $bundle_items = isset($bundle_data['products']) ? $bundle_data['products'] : array();
        if (empty($bundle_items)) {
            return $bundle_data;
        }

        // Initialize tax data
        $bundle_items_tax_rates = array();
        
        // Loop through bundle items
        foreach ($bundle_items as $bundle_item_id => $bundle_item) {
            // Get custom tax rate for the bundle item
            $custom_rate = $this->db->get_tax_rate($bundle_item_id, $country);
            
            // If a custom rate exists, store it
            if (false !== $custom_rate) {
                $bundle_items_tax_rates[$bundle_item_id] = $custom_rate;
            } else {
                // Use the default rate
                $bundle_items_tax_rates[$bundle_item_id] = function_exists('edd_get_tax_rate') ? edd_get_tax_rate($country) : 0;
            }
        }
        
        // Store tax rates in bundle data
        $bundle_data['bundle_items_tax_rates'] = $bundle_items_tax_rates;
        
        return $bundle_data;
    }

    /**
     * Get the customer's country.
     *
     * @since    2.0.0
     * @return   string    The customer's country code.
     */
    private function get_customer_country() {
        // Check if we're in the admin area
        if (is_admin() && !wp_doing_ajax()) {
            return '';
        }

        // Try to get country from EDD customer
        $country = function_exists('edd_get_customer_address') ? edd_get_customer_address('country') : '';
        
        // If no country from customer, try to get from session
        if (empty($country)) {
            $country = function_exists('edd_get_shop_country') ? edd_get_shop_country() : '';
        }
        
        // If still no country, try to get from IP geolocation
        if (empty($country) && function_exists('edd_get_country_from_ip')) {
            $country = edd_get_country_from_ip();
        }
        
        return $country;
    }

    /**
     * Format bundle items for display in receipts and invoices.
     *
     * @since    2.0.0
     * @param    array     $formatted_items    The formatted items.
     * @param    array     $bundle_items       The bundle items.
     * @param    array     $bundle             The bundle data.
     * @return   array                         The modified formatted items.
     */
    public function format_bundle_items_for_display($formatted_items, $bundle_items, $bundle) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $formatted_items;
        }

        // Get the bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, no need to modify
        if ('simple' === $bundle_display) {
            return $formatted_items;
        }

        // Check if we have bundle items tax data
        if (!isset($bundle['bundle_items_tax']) || empty($bundle['bundle_items_tax'])) {
            return $formatted_items;
        }

        // Loop through formatted items
        foreach ($formatted_items as $key => $item) {
            // Get the bundle item ID
            $bundle_item_id = isset($item['id']) ? $item['id'] : 0;
            if (empty($bundle_item_id)) {
                continue;
            }
            
            // Get the bundle item tax data
            $bundle_item_tax_data = isset($bundle['bundle_items_tax'][$key]) ? $bundle['bundle_items_tax'][$key] : null;
            if (empty($bundle_item_tax_data)) {
                continue;
            }
            
            // Add tax rate to the item
            $formatted_items[$key]['tax_rate'] = $bundle_item_tax_data['rate'];
            
            // Add tax amount to the item
            $formatted_items[$key]['tax'] = $bundle_item_tax_data['amount'];
            
            // Update price to include tax if prices include tax
            if (edd_prices_include_tax()) {
                $formatted_items[$key]['price'] += $bundle_item_tax_data['amount'];
            }
        }
        
        return $formatted_items;
    }

    /**
     * Group bundle items by tax rate for summarized display.
     *
     * @since    2.0.0
     * @param    array     $bundle_items    The bundle items.
     * @param    array     $bundle          The bundle data.
     * @return   array                      The grouped items.
     */
    public function group_bundle_items_by_tax_rate($bundle_items, $bundle) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $bundle_items;
        }

        // Get the bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If not summarized mode, return original items
        if ('summarized' !== $bundle_display) {
            return $bundle_items;
        }

        // Check if we have bundle items tax data
        if (!isset($bundle['bundle_items_tax']) || empty($bundle['bundle_items_tax'])) {
            return $bundle_items;
        }

        // Initialize grouped items
        $grouped_items = array();
        
        // Loop through bundle items
        foreach ($bundle_items as $key => $item) {
            // Get the bundle item tax data
            $bundle_item_tax_data = isset($bundle['bundle_items_tax'][$key]) ? $bundle['bundle_items_tax'][$key] : null;
            if (empty($bundle_item_tax_data)) {
                continue;
            }
            
            // Get the tax rate
            $tax_rate = $bundle_item_tax_data['rate'];
            
            // Format the rate for the group key
            $rate_key = number_format($tax_rate, 2);
            
            // Initialize group if not exists
            if (!isset($grouped_items[$rate_key])) {
                $grouped_items[$rate_key] = array(
                    'rate' => $tax_rate,
                    'items' => array(),
                    'total' => 0,
                    'tax' => 0
                );
            }
            
            // Add item to group
            $grouped_items[$rate_key]['items'][] = $item;
            
            // Add to group total
            $grouped_items[$rate_key]['total'] += $item['price'];
            
            // Add to group tax
            $grouped_items[$rate_key]['tax'] += $bundle_item_tax_data['amount'];
        }
        
        return $grouped_items;
    }
}
