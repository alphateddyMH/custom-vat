<?php
/**
 * The email tags functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The email tags functionality of the plugin.
 *
 * Handles email tags for tax information.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/invoices
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Email_Tags {

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
        
        // Register hooks for email tags
        add_action('init', array($this, 'register_hooks'), 20);
    }
    
    /**
     * Register hooks for email tags.
     *
     * @since    2.0.0
     */
    public function register_hooks() {
        // Register email tags
        add_filter('edd_email_tags', array($this, 'register_email_tags'), 100);
        
        // Add email tags to the list in admin
        add_action('edd_email_settings_before', array($this, 'add_email_tags_to_admin_list'));
    }

    /**
     * Register email tags for tax details.
     *
     * @since    2.0.0
     * @param    array    $email_tags    The email tags.
     * @return   array                   The modified email tags.
     */
    public function register_email_tags($email_tags) {
        // Add a new email tag for tax breakdown
        $email_tags[] = array(
            'tag'     => 'tax_breakdown',
            'description'  => __('Displays a breakdown of tax rates if multiple rates apply', 'edd-custom-vat'),
            'function' => array($this, 'email_tag_tax_breakdown')
        );
        
        // Add a new email tag for tax summary
        $email_tags[] = array(
            'tag'     => 'tax_summary',
            'description'  => __('Displays a summary of tax rates and amounts', 'edd-custom-vat'),
            'function' => array($this, 'email_tag_tax_summary')
        );
        
        // Add a new email tag for bundle tax details
        $email_tags[] = array(
            'tag'     => 'bundle_tax_details',
            'description'  => __('Displays tax details for bundles with different tax rates', 'edd-custom-vat'),
            'function' => array($this, 'email_tag_bundle_tax_details')
        );
        
        return $email_tags;
    }

    /**
     * Add email tags to the admin list.
     *
     * @since    2.0.0
     */
    public function add_email_tags_to_admin_list() {
        // Check if we're on the EDD email settings page
        if (!isset($_GET['section']) || $_GET['section'] !== 'email-settings') {
            return;
        }
        
        // Add script to inject our tags into the email tags list
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Find the email tags list
                var $emailTagsList = $('.edd-email-tags-list');
                
                if ($emailTagsList.length) {
                    // Add our custom tags to the list
                    $emailTagsList.append('<li><code>{tax_breakdown}</code> - <?php echo esc_js(__('Displays a breakdown of tax rates if multiple rates apply', 'edd-custom-vat')); ?></li>');
                    $emailTagsList.append('<li><code>{tax_summary}</code> - <?php echo esc_js(__('Displays a summary of tax rates and amounts', 'edd-custom-vat')); ?></li>');
                    $emailTagsList.append('<li><code>{bundle_tax_details}</code> - <?php echo esc_js(__('Displays tax details for bundles with different tax rates', 'edd-custom-vat')); ?></li>');
                }
            });
        </script>
        <?php
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
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
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
     * Email tag function for tax summary.
     *
     * @since    2.0.0
     * @param    int    $payment_id    The payment ID.
     * @return   string                The tax summary text.
     */
    public function email_tag_tax_summary($payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return '';
        }

        // Get tax summary
        $tax_summary = $this->tax_display->get_payment_tax_summary(array(), $payment_id);
        
        // If we don't have a tax summary, return empty
        if (empty($tax_summary)) {
            return '';
        }

        // Build text output
        $output = __('Tax Summary:', 'edd-custom-vat') . ' ';
        
        $summary_parts = array();
        foreach ($tax_summary as $rate_data) {
            $summary_parts[] = $rate_data['rate'] . '% = ' . edd_currency_filter(edd_format_amount($rate_data['amount']));
        }
        
        $output .= implode(', ', $summary_parts);
        
        return $output;
    }

    /**
     * Email tag function for bundle tax details.
     *
     * @since    2.0.0
     * @param    int    $payment_id    The payment ID.
     * @return   string                The bundle tax details HTML.
     */
    public function email_tag_bundle_tax_details($payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return '';
        }

        // Get bundle display mode
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        // If simple mode, no need to show bundle details
        if ('simple' === $bundle_display) {
            return '';
        }

        // Get payment meta
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        
        // Get cart details
        $cart_details = isset($payment_meta['cart_details']) ? $payment_meta['cart_details'] : array();
        if (empty($cart_details)) {
            return '';
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
                $output .= '<h4>' . sprintf(__('Tax Details for %s', 'edd-custom-vat'), $bundle_name) . '</h4>';
                $output .= '<table style="width: 100%; border-collapse: collapse;">';
                $output .= '<thead><tr>';
                $output .= '<th style="text-align: left; padding: 5px; border: 1px solid #eee;">' . __('Tax Rate', 'edd-custom-vat') . '</th>';
                $output .= '<th style="text-align: right; padding: 5px; border: 1px solid #eee;">' . __('Subtotal', 'edd-custom-vat') . '</th>';
                $output .= '<th style="text-align: right; padding: 5px; border: 1px solid #eee;">' . __('Tax', 'edd-custom-vat') . '</th>';
                $output .= '</tr></thead>';
                $output .= '<tbody>';
                
                foreach ($bundle_items_data as $rate_key => $group) {
                    $output .= '<tr>';
                    $output .= '<td style="text-align: left; padding: 5px; border: 1px solid #eee;">' . $group['rate'] . '%</td>';
                    $output .= '<td style="text-align: right; padding: 5px; border: 1px solid #eee;">' . edd_currency_filter(edd_format_amount($group['total'])) . '</td>';
                    $output .= '<td style="text-align: right; padding: 5px; border: 1px solid #eee;">' . edd_currency_filter(edd_format_amount($group['tax'])) . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody></table>';
            } else {
                // Generate HTML for detailed bundle items
                $output .= '<h4>' . sprintf(__('Tax Details for %s', 'edd-custom-vat'), $bundle_name) . '</h4>';
                $output .= '<table style="width: 100%; border-collapse: collapse;">';
                $output .= '<thead><tr>';
                $output .= '<th style="text-align: left; padding: 5px; border: 1px solid #eee;">' . __('Item', 'edd-custom-vat') . '</th>';
                $output .= '<th style="text-align: right; padding: 5px; border: 1px solid #eee;">' . __('Price', 'edd-custom-vat') . '</th>';
                $output .= '<th style="text-align: right; padding: 5px; border: 1px solid #eee;">' . __('Tax Rate', 'edd-custom-vat') . '</th>';
                $output .= '<th style="text-align: right; padding: 5px; border: 1px solid #eee;">' . __('Tax', 'edd-custom-vat') . '</th>';
                $output .= '</tr></thead>';
                $output .= '<tbody>';
                
                foreach ($bundle_items_data as $item) {
                    $output .= '<tr>';
                    $output .= '<td style="text-align: left; padding: 5px; border: 1px solid #eee;">' . $item['name'] . '</td>';
                    $output .= '<td style="text-align: right; padding: 5px; border: 1px solid #eee;">' . edd_currency_filter(edd_format_amount($item['price'])) . '</td>';
                    $output .= '<td style="text-align: right; padding: 5px; border: 1px solid #eee;">' . $item['tax_rate'] . '%</td>';
                    $output .= '<td style="text-align: right; padding: 5px; border: 1px solid #eee;">' . edd_currency_filter(edd_format_amount($item['tax'])) . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody></table>';
            }
        }
        
        return $output;
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
