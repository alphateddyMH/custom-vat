<?php
/**
 * The receipt functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/public
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The receipt functionality of the plugin.
 *
 * Handles receipt modifications for tax display.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/public
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Receipt {

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
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Register hooks for receipt functionality.
     *
     * @since    2.0.0
     */
    private function register_hooks() {
        // Modify receipt display
        add_filter('edd_receipt_show_tax_rate', array($this, 'show_detailed_tax_rates'), 10, 1);
        add_action('edd_payment_receipt_after_table', array($this, 'display_detailed_tax_breakdown'), 10, 2);
        add_filter('edd_receipt_tax_rate', array($this, 'modify_receipt_tax_rate'), 10, 2);
        
        // Add bundle tax details to receipt
        add_action('edd_payment_receipt_after', array($this, 'add_bundle_tax_details_to_receipt'), 10, 2);
    }

    /**
     * Show detailed tax rates in receipt.
     *
     * @since    2.0.0
     * @param    bool    $show    Whether to show tax rate.
     * @return   bool             Whether to show tax rate.
     */
    public function show_detailed_tax_rates($show) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $show;
        }

        // Always show tax rate if plugin is enabled
        return true;
    }

    /**
     * Display detailed tax breakdown in receipt.
     *
     * @since    2.0.0
     * @param    object    $payment    The payment object.
     * @param    array     $args       The receipt arguments.
     */
    public function display_detailed_tax_breakdown($payment, $args) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Get payment ID
        $payment_id = $payment->ID;

        // Get tax summary
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary or only have one tax rate, return
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return;
        }

        // Display tax breakdown
        ?>
        <div class="edd-custom-vat-tax-breakdown">
            <h3><?php _e('Tax Breakdown', 'edd-custom-vat'); ?></h3>
            <table class="edd-custom-vat-tax-breakdown-table">
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
     * Modify receipt tax rate display.
     *
     * @since    2.0.0
     * @param    string    $rate       The tax rate.
     * @param    object    $payment    The payment object.
     * @return   string                The modified tax rate.
     */
    public function modify_receipt_tax_rate($rate, $payment) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $rate;
        }

        // Get payment ID
        $payment_id = $payment->ID;

        // Get tax summary
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary or only have one tax rate, return original rate
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $rate;
        }

        // Build a new rate string with all tax rates
        $rates = array();
        foreach ($tax_summary as $rate_data) {
            $rates[] = $rate_data['rate'] . '%';
        }
        
        $rate = implode(', ', $rates);
        
        return $rate;
    }

    /**
     * Add bundle tax details to receipt.
     *
     * @since    2.0.0
     * @param    object    $payment    The payment object.
     * @param    array     $args       The receipt arguments.
     */
    public function add_bundle_tax_details_to_receipt($payment, $args) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Get bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, no need to show bundle details
        if ('simple' === $bundle_display) {
            return;
        }

        // Get payment ID
        $payment_id = $payment->ID;

        // Get payment meta
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        
        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return;
        }

        // Get customer country
        $country = $this->get_customer_country_from_payment($payment_meta);
        
        // Initialize output
        $output = '';
        
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
                
                // Generate HTML for summarized bundle items
                $output .= '<div class="edd-custom-vat-bundle-tax-details">';
                $output .= '<h4>' . sprintf(__('Tax Details for %s', 'edd-custom-vat'), $bundle_name) . '</h4>';
                $output .= '<table class="edd-custom-vat-bundle-tax-table">';
                $output .= '<thead><tr>';
                $output .= '<th>' . __('Tax Rate', 'edd-custom-vat') . '</th>';
                $output .= '<th>' . __('Subtotal', 'edd-custom-vat') . '</th>';
                $output .= '<th>' . __('Tax', 'edd-custom-vat') . '</th>';
                $output .= '</tr></thead>';
                $output .= '<tbody>';
                
                foreach ($bundle_items_data as $rate_key => $group) {
                    $output .= '<tr>';
                    $output .= '<td>' . $group['rate'] . '%</td>';
                    $output .= '<td>' . edd_currency_filter(edd_format_amount($group['total'])) . '</td>';
                    $output .= '<td>' . edd_currency_filter(edd_format_amount($group['tax'])) . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody></table>';
                $output .= '</div>';
            } else {
                // Generate HTML for detailed bundle items
                $output .= '<div class="edd-custom-vat-bundle-tax-details">';
                $output .= '<h4>' . sprintf(__('Tax Details for %s', 'edd-custom-vat'), $bundle_name) . '</h4>';
                $output .= '<table class="edd-custom-vat-bundle-tax-table">';
                $output .= '<thead><tr>';
                $output .= '<th>' . __('Item', 'edd-custom-vat') . '</th>';
                $output .= '<th>' . __('Price', 'edd-custom-vat') . '</th>';
                $output .= '<th>' . __('Tax Rate', 'edd-custom-vat') . '</th>';
                $output .= '<th>' . __('Tax', 'edd-custom-vat') . '</th>';
                $output .= '</tr></thead>';
                $output .= '<tbody>';
                
                foreach ($bundle_items_data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . $item['name'] . '</td>';
                    $output .= '<td>' . edd_currency_filter(edd_format_amount($item['price'])) . '</td>';
                    $output .= '<td>' . $item['tax_rate'] . '%</td>';
                    $output .= '<td>' . edd_currency_filter(edd_format_amount($item['tax'])) . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody></table>';
                $output .= '</div>';
            }
        }
        
        // Output bundle tax details
        if (!empty($output)) {
            echo '<div class="edd-custom-vat-bundle-details">';
            echo '<h3>' . __('Bundle Tax Details', 'edd-custom-vat') . '</h3>';
            echo $output;
            echo '</div>';
        }
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
