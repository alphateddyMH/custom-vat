<?php
/**
 * The invoice manager functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The invoice manager functionality of the plugin.
 *
 * Handles invoice generation and formatting.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Invoice_Manager {

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
        add_filter('edd_invoice_line_items', array($this, 'modify_invoice_line_items'), 999, 3);
        add_filter('edd_invoice_get_payment_meta', array($this, 'add_tax_details_to_meta'), 999, 2);
        
        // Add hooks for PDF invoices if the extension is active
        if ($this->is_pdf_invoices_active()) {
            add_filter('edd_pdf_invoice_template', array($this, 'modify_pdf_invoice_template'), 999, 2);
            add_filter('edd_pdf_invoice_footer', array($this, 'add_tax_details_to_footer'), 999, 2);
        }
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
        
        return $footer . $tax_info;
    }

    /**
     * Get tax breakdown HTML for invoices.
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
     * Check if EDD Invoices is active.
     *
     * @since    2.0.0
     * @return   bool    True if EDD Invoices is active, false otherwise.
     */
    public function is_invoices_active() {
        return class_exists('EDD_Invoices');
    }

    /**
     * Check if EDD PDF Invoices is active.
     *
     * @since    2.0.0
     * @return   bool    True if EDD PDF Invoices is active, false otherwise.
     */
    public function is_pdf_invoices_active() {
        return class_exists('EDD_PDF_Invoices');
    }
}
