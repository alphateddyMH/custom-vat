<?php
/**
 * The tax display functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The tax display functionality of the plugin.
 *
 * Handles the display of tax information in the frontend.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Tax_Display {

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
        add_filter('edd_cart_tax_summary', array($this, 'get_cart_tax_summary'), 999, 2);
        add_filter('edd_payment_tax_summary', array($this, 'get_payment_tax_summary'), 999, 2);
        
        // Add filter for tax data in reports and exports
        add_filter('edd_export_csv_cols_payments', array($this, 'add_tax_data_to_export_cols'), 999);
        add_filter('edd_export_get_data_payments', array($this, 'add_tax_data_to_export_data'), 999, 3);
        
        // Register email tag for tax breakdown
        add_filter('edd_email_tags', array($this, 'register_email_tax_tags'), 999, 1);
        
        // Add tax breakdown to receipt using existing EDD hooks
        add_action('edd_payment_receipt_after_table', array($this, 'add_tax_details_to_receipt'), 999, 1);
    }

    /**
     * Get tax summary for the cart.
     *
     * @since    2.0.0
     * @param    array     $summary    The original tax summary.
     * @param    array     $cart       The cart data.
     * @return   array                 The modified tax summary.
     */
    public function get_cart_tax_summary($summary, $cart) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $summary;
        }

        // Get tax summary from session
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // If we have a tax summary, use it
        if (!empty($tax_summary)) {
            return $tax_summary;
        }

        // Otherwise, calculate tax summary
        $tax_calculation = new EDD_Custom_VAT_Tax_Calculation();
        $tax_calculation->calculate_cart_tax(0, 0); // This will store the tax summary in session
        
        // Get the updated tax summary
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        if (!empty($tax_summary)) {
            return $tax_summary;
        }
        
        return $summary;
    }

    /**
     * Get tax summary for a payment.
     *
     * @since    2.0.0
     * @param    array     $summary      The original tax summary.
     * @param    int       $payment_id   The payment ID.
     * @return   array                   The modified tax summary.
     */
    public function get_payment_tax_summary($summary, $payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $summary;
        }

        // Get payment meta
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        if (empty($payment_meta)) {
            return $summary;
        }

        // Check if we have a stored tax summary
        if (isset($payment_meta['tax_summary']) && !empty($payment_meta['tax_summary'])) {
            return $payment_meta['tax_summary'];
        }

        // Otherwise, calculate tax summary from cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return $summary;
        }

        // Initialize tax summary
        $tax_summary = array();

        // Loop through cart items
        foreach ($cart_details as $item) {
            // Get the tax rate
            $tax_rate = isset($item['tax_rate']) ? $item['tax_rate'] : 0;
            
            // Format the rate for the summary key
            $rate_key = number_format($tax_rate, 2);
            
            // Get the tax amount
            $tax = isset($item['tax']) ? $item['tax'] : 0;
            
            // Add to summary
            if (!isset($tax_summary[$rate_key])) {
                $tax_summary[$rate_key] = array(
                    'rate' => $tax_rate,
                    'amount' => 0,
                );
            }
            
            $tax_summary[$rate_key]['amount'] += $tax;
        }
        
        // Store the calculated summary in payment meta for future use
        if (!empty($tax_summary)) {
            $payment_meta['tax_summary'] = $tax_summary;
            update_post_meta($payment_id, '_edd_payment_meta', $payment_meta);
        }
        
        return $tax_summary;
    }

    /**
     * Add tax details to receipt using existing EDD hooks.
     *
     * @since    2.0.0
     * @param    int    $payment_id    The payment ID.
     */
    public function add_tax_details_to_receipt($payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Get tax summary
        $tax_summary = $this->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary or only have one tax rate, return
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return;
        }

        // Use existing EDD styling for consistency
        ?>
        <div class="edd_purchase_receipt_files">
            <h3><?php _e('Tax Breakdown', 'edd-custom-vat'); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Rate', 'edd-custom-vat'); ?></th>
                        <th><?php _e('Amount', 'edd-custom-vat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tax_summary as $rate_data) : ?>
                        <tr>
                            <td><?php echo esc_html($rate_data['rate']); ?>%</td>
                            <td><?php echo edd_currency_filter(edd_format_amount($rate_data['amount'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Register email tags for tax details.
     *
     * @since    2.0.0
     * @param    array    $email_tags    The email tags.
     * @return   array                   The modified email tags.
     */
    public function register_email_tax_tags($email_tags) {
        // Add a new email tag for tax breakdown
        $email_tags[] = array(
            'tag'     => 'tax_breakdown',
            'description'  => __('Displays a breakdown of tax rates if multiple rates apply', 'edd-custom-vat'),
            'function' => array($this, 'email_tag_tax_breakdown')
        );
        
        return $email_tags;
    }

    /**
     * Email tag function for tax breakdown.
     *
     * @since    2.0.0
     * @param    int    $payment_id    The payment ID.
     * @return   string                The tax breakdown HTML.
     */
    public function email_tag_tax_breakdown($payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return '';
        }

        // Get tax summary
        $tax_summary = $this->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary or only have one tax rate, return empty
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return '';
        }

        // Build HTML output
        $output = '<h3>' . __('Tax Breakdown', 'edd-custom-vat') . '</h3>';
        $output .= '<table style="width: 100%; border-collapse: collapse;">';
        $output .= '<thead><tr>';
        $output .= '<th style="text-align: left; padding: 5px; border: 1px solid #eee;">' . __('Rate', 'edd-custom-vat') . '</th>';
        $output .= '<th style="text-align: right; padding: 5px; border: 1px solid #eee;">' . __('Amount', 'edd-custom-vat') . '</th>';
        $output .= '</tr></thead>';
        $output .= '<tbody>';
        
        foreach ($tax_summary as $rate_data) {
            $output .= '<tr>';
            $output .= '<td style="text-align: left; padding: 5px; border: 1px solid #eee;">' . esc_html($rate_data['rate']) . '%</td>';
            $output .= '<td style="text-align: right; padding: 5px; border: 1px solid #eee;">' . edd_currency_filter(edd_format_amount($rate_data['amount'])) . '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody></table>';
        
        return $output;
    }

    /**
     * Add tax data to export columns.
     *
     * @since    2.0.0
     * @param    array    $cols    The export columns.
     * @return   array             The modified export columns.
     */
    public function add_tax_data_to_export_cols($cols) {
        // Add a column for tax breakdown
        $cols['tax_breakdown'] = __('Tax Breakdown', 'edd-custom-vat');
        
        return $cols;
    }

    /**
     * Add tax data to export data.
     *
     * @since    2.0.0
     * @param    array    $data         The export data.
     * @param    array    $payment_meta The payment meta.
     * @param    array    $payment_data The payment data.
     * @return   array                  The modified export data.
     */
    public function add_tax_data_to_export_data($data, $payment_meta, $payment_data) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $data;
        }

        // Get tax summary
        $tax_summary = isset($payment_meta['tax_summary']) ? $payment_meta['tax_summary'] : array();
        
        // If we don't have a tax summary, try to calculate it
        if (empty($tax_summary)) {
            $tax_summary = $this->get_payment_tax_summary(array(), $payment_data['ID']);
        }
        
        // Format tax breakdown for CSV
        $tax_breakdown = '';
        if (!empty($tax_summary)) {
            $breakdown_parts = array();
            foreach ($tax_summary as $rate_data) {
                $breakdown_parts[] = $rate_data['rate'] . '% = ' . edd_format_amount($rate_data['amount']);
            }
            $tax_breakdown = implode('; ', $breakdown_parts);
        }
        
        // Add to export data
        $data['tax_breakdown'] = $tax_breakdown;
        
        return $data;
    }

    /**
     * Get formatted tax summary for display.
     *
     * @since    2.0.0
     * @param    array     $tax_summary    The tax summary.
     * @param    bool      $html           Whether to return HTML or plain text.
     * @return   string                    The formatted tax summary.
     */
    public function get_formatted_tax_summary($tax_summary, $html = true) {
        if (empty($tax_summary)) {
            return '';
        }

        $output = '';
        
        if ($html) {
            $output .= '<div class="edd-custom-vat-tax-summary">';
            
            foreach ($tax_summary as $rate_data) {
                $output .= '<div class="edd-custom-vat-tax-summary-item">';
                $output .= '<span class="edd-custom-vat-tax-rate">' . esc_html($rate_data['rate']) . '%</span>: ';
                $output .= '<span class="edd-custom-vat-tax-amount">' . edd_currency_filter(edd_format_amount($rate_data['amount'])) . '</span>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
        } else {
            $items = array();
            
            foreach ($tax_summary as $rate_data) {
                $items[] = $rate_data['rate'] . '%: ' . edd_currency_filter(edd_format_amount($rate_data['amount']));
            }
            
            $output = implode(', ', $items);
        }
        
        return $output;
    }
}
