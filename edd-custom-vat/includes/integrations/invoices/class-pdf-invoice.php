<?php
/**
 * The PDF invoice functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The PDF invoice functionality of the plugin.
 *
 * Handles PDF invoice modifications.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_PDF_Invoice {

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
     * Modify PDF invoice template to include custom tax rates.
     *
     * @since    2.0.0
     * @param    string    $template      The invoice template.
     * @param    array     $payment_data  The payment data.
     * @return   string                   The modified invoice template.
     */
    public function modify_pdf_invoice_template($template, $payment_data) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $template;
        }

        // Get payment ID
        $payment_id = isset($payment_data['id']) ? $payment_data['id'] : 0;
        if (empty($payment_id)) {
            return $template;
        }

        // Get tax summary
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary or only have one tax rate, return original template
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $template;
        }

        // Add tax breakdown to template
        $tax_breakdown = $this->get_tax_breakdown_html($tax_summary);
        
        // Replace the tax line with our tax breakdown
        $template = preg_replace(
            '/<tr class="edd_cart_footer_item edd_cart_tax_row">(.*?)<\/tr>/s',
            '<tr class="edd_cart_footer_item edd_cart_tax_row">$1</tr>' . $tax_breakdown,
            $template
        );
        
        // Add tax rate column to header if not already present
        if (strpos($template, 'edd_cart_header_item_tax_rate') === false) {
            $template = preg_replace(
                '/<tr class="edd_cart_header_row">(.*?)<th class="edd_cart_tax">(.*?)<\/th>(.*?)<\/tr>/s',
                '<tr class="edd_cart_header_row">$1<th class="edd_cart_tax_rate">' . __('Tax Rate', 'edd-custom-vat') . '</th><th class="edd_cart_tax">$2</th>$3</tr>',
                $template
            );
        }
        
        // Add tax rate column to each item
        $template = preg_replace_callback(
            '/<tr class="edd_cart_item">(.*?)<td class="edd_cart_tax">(.*?)<\/td>(.*?)<\/tr>/s',
            function($matches) use ($payment_data) {
                // Extract item name from the row
                preg_match('/<td class="edd_cart_item_name">(.*?)<\/td>/s', $matches[1], $name_matches);
                $item_name = strip_tags($name_matches[1]);
                
                // Get tax rate for this item
                $tax_rate = $this->get_item_tax_rate($item_name, $payment_data);
                
                // Add tax rate column
                return '<tr class="edd_cart_item">' . $matches[1] . 
                       '<td class="edd_cart_tax_rate">' . $tax_rate . '%</td>' . 
                       '<td class="edd_cart_tax">' . $matches[2] . '</td>' . 
                       $matches[3] . '</tr>';
            },
            $template
        );
        
        // Add bundle items if needed
        $template = $this->add_bundle_items_to_template($template, $payment_data);
        
        return $template;
    }

    /**
     * Add tax details to PDF invoice footer.
     *
     * @since    2.0.0
     * @param    string    $footer        The invoice footer.
     * @param    array     $payment_data  The payment data.
     * @return   string                   The modified invoice footer.
     */
    public function add_tax_details_to_footer($footer, $payment_data) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $footer;
        }

        // Get payment ID
        $payment_id = isset($payment_data['id']) ? $payment_data['id'] : 0;
        if (empty($payment_id)) {
            return $footer;
        }

        // Get tax summary
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary or only have one tax rate, return original footer
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $footer;
        }

        // Add tax information to footer
        $tax_info = '<p>' . __('Tax Breakdown:', 'edd-custom-vat') . ' ';
        
        $tax_parts = array();
        foreach ($tax_summary as $rate_data) {
            $tax_parts[] = $rate_data['rate'] . '% = ' . edd_currency_filter(edd_format_amount($rate_data['amount']));
        }
        
        $tax_info .= implode(', ', $tax_parts) . '</p>';
        
        // Add GoBD compliance notice for German invoices
        $country = $this->get_customer_country_from_payment($payment_data);
        if ('DE' === $country) {
            $tax_info .= '<p>' . __('This invoice complies with GoBD requirements.', 'edd-custom-vat') . '</p>';
        }
        
        return $footer . $tax_info;
    }

    /**
     * Get tax breakdown HTML for PDF invoices.
     *
     * @since    2.0.0
     * @param    array     $tax_summary    The tax summary.
     * @return   string                    The tax breakdown HTML.
     */
    private function get_tax_breakdown_html($tax_summary) {
        if (empty($tax_summary)) {
            return '';
        }

        $html = '<tr class="edd_cart_footer_item edd_cart_tax_breakdown">';
        $html .= '<td colspan="3" class="edd_cart_tax_breakdown_label">' . __('Tax Breakdown:', 'edd-custom-vat') . '</td>';
        $html .= '<td class="edd_cart_tax_breakdown_rates">';
        
        foreach ($tax_summary as $rate_data) {
            $html .= '<div class="edd_cart_tax_rate">';
            $html .= '<span class="edd_cart_tax_rate_label">' . $rate_data['rate'] . '%:</span> ';
            $html .= '<span class="edd_cart_tax_rate_amount">' . edd_currency_filter(edd_format_amount($rate_data['amount'])) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</td>';
        $html .= '</tr>';
        
        return $html;
    }

    /**
     * Add bundle items to PDF invoice template.
     *
     * @since    2.0.0
     * @param    string    $template      The invoice template.
     * @param    array     $payment_data  The payment data.
     * @return   string                   The modified invoice template.
     */
    private function add_bundle_items_to_template($template, $payment_data) {
        // Get bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, no need to add bundle items
        if ('simple' === $bundle_display) {
            return $template;
        }

        // Get payment meta
        $payment_meta = isset($payment_data['payment_meta']) ? $payment_data['payment_meta'] : array();
        
        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return $template;
        }

        // Get customer country
        $country = $this->get_customer_country_from_payment($payment_data);
        
        // Loop through cart items to find bundles
        foreach ($cart_details as $item) {
            // Check if this is a bundle
            if (!isset($item['item_number']['options']['bundled_products']) || empty($item['item_number']['options']['bundled_products'])) {
                continue;
            }
            
            // Get bundle items
            $bundle_items = $item['item_number']['options']['bundled_products'];
            if (empty($bundle_items)) {
                continue;
            }
            
            // Get bundle name
            $bundle_name = isset($item['name']) ? $item['name'] : '';
            if (empty($bundle_name)) {
                continue;
            }
            
            // Get bundle items data
            $bundle_items_data = $this->get_bundle_items_data($bundle_items, $country);
            
            // If summarized mode, group by tax rate
            if ('summarized' === $bundle_display) {
                $bundle_items_data = $this->group_bundle_items_by_tax_rate($bundle_items_data);
            }
            
            // Generate HTML for bundle items
            $bundle_items_html = $this->get_bundle_items_html($bundle_items_data, $bundle_name, $bundle_display);
            
            // Add bundle items HTML after the bundle item
            $template = preg_replace(
                '/(<tr class="edd_cart_item">(.*?)' . preg_quote($bundle_name, '/') . '(.*?)<\/tr>)/s',
                '$1' . $bundle_items_html,
                $template
            );
        }
        
        return $template;
    }

    /**
     * Get bundle items data with tax rates.
     *
     * @since    2.0.0
     * @param    array     $bundle_items    The bundle items.
     * @param    string    $country         The country code.
     * @return   array                      The bundle items data.
     */
    private function get_bundle_items_data($bundle_items, $country) {
        $items_data = array();
        
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
     * Get HTML for bundle items in PDF invoice.
     *
     * @since    2.0.0
     * @param    array     $bundle_items_data    The bundle items data.
     * @param    string    $bundle_name          The bundle name.
     * @param    string    $display_mode         The display mode.
     * @return   string                          The bundle items HTML.
     */
    private function get_bundle_items_html($bundle_items_data, $bundle_name, $display_mode) {
        $html = '';
        
        if ('summarized' === $display_mode) {
            // Summarized mode - group by tax rate
            foreach ($bundle_items_data as $rate_key => $group) {
                $html .= '<tr class="edd_cart_item edd_cart_bundle_item">';
                $html .= '<td class="edd_cart_item_name">';
                $html .= '&nbsp;&nbsp;&nbsp;' . sprintf(__('Items in %s (Tax Rate: %s%%)', 'edd-custom-vat'), $bundle_name, $group['rate']);
                $html .= '</td>';
                $html .= '<td class="edd_cart_item_price">' . edd_currency_filter(edd_format_amount($group['total'])) . '</td>';
                $html .= '<td class="edd_cart_tax_rate">' . $group['rate'] . '%</td>';
                $html .= '<td class="edd_cart_tax">' . edd_currency_filter(edd_format_amount($group['tax'])) . '</td>';
                $html .= '<td class="edd_cart_actions"></td>';
                $html .= '</tr>';
            }
        } else {
            // Detailed mode - show each item
            foreach ($bundle_items_data as $item) {
                $html .= '<tr class="edd_cart_item edd_cart_bundle_item">';
                $html .= '<td class="edd_cart_item_name">';
                $html .= '&nbsp;&nbsp;&nbsp;' . $item['name'];
                $html .= '</td>';
                $html .= '<td class="edd_cart_item_price">' . edd_currency_filter(edd_format_amount($item['price'])) . '</td>';
                $html .= '<td class="edd_cart_tax_rate">' . $item['tax_rate'] . '%</td>';
                $html .= '<td class="edd_cart_tax">' . edd_currency_filter(edd_format_amount($item['tax'])) . '</td>';
                $html .= '<td class="edd_cart_actions"></td>';
                $html .= '</tr>';
            }
        }
        
        return $html;
    }

    /**
     * Get tax rate for an item by name.
     *
     * @since    2.0.0
     * @param    string    $item_name      The item name.
     * @param    array     $payment_data   The payment data.
     * @return   float                     The tax rate.
     */
    private function get_item_tax_rate($item_name, $payment_data) {
        // Get payment meta
        $payment_meta = isset($payment_data['payment_meta']) ? $payment_data['payment_meta'] : array();
        
        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return 0;
        }

        // Find cart item by name
        foreach ($cart_details as $item) {
            if (isset($item['name']) && $item['name'] === $item_name) {
                return isset($item['tax_rate']) ? $item['tax_rate'] : 0;
            }
        }
        
        return 0;
    }

    /**
     * Get the customer's country from payment data.
     *
     * @since    2.0.0
     * @param    array     $payment_data    The payment data.
     * @return   string                     The customer's country code.
     */
    private function get_customer_country_from_payment($payment_data) {
        // Get payment meta
        $payment_meta = isset($payment_data['payment_meta']) ? $payment_data['payment_meta'] : array();
        
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
