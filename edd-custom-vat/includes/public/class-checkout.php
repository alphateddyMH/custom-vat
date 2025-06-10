<?php
/**
 * The checkout functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/public
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The checkout functionality of the plugin.
 *
 * Handles checkout modifications for tax display.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/public
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Checkout {

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
        add_action('init', array($this, 'register_hooks'), 999);
    }
    
    /**
     * Register hooks for checkout functionality.
     * Using late priority to ensure we run after EDD Geo Targeting.
     *
     * @since    2.0.0
     */
    public function register_hooks() {
        // Modify checkout cart display
        add_filter('edd_checkout_cart_columns', array($this, 'modify_checkout_cart_columns'), 999, 1);
        add_filter('edd_get_cart_item_template', array($this, 'modify_cart_item_template'), 999, 2);
        add_filter('edd_cart_item_price_label', array($this, 'modify_cart_item_price_label'), 999, 3);
        
        // Add tax breakdown to checkout
        add_action('edd_checkout_cart_after', array($this, 'add_tax_breakdown_to_checkout'), 999);
        
        // Add country change handler
        add_action('wp_ajax_edd_custom_vat_update_country', array($this, 'ajax_update_country'));
        add_action('wp_ajax_nopriv_edd_custom_vat_update_country', array($this, 'ajax_update_country'));
        
        // Add country change listener
        add_action('edd_after_cc_fields', array($this, 'add_country_change_listener'), 999);
        add_action('edd_after_cc_fields_ajax', array($this, 'add_country_change_listener'), 999);
    }

    /**
     * Modify checkout cart columns to add tax rate column.
     *
     * @since    2.0.0
     * @param    array    $columns    The cart columns.
     * @return   array                The modified cart columns.
     */
    public function modify_checkout_cart_columns($columns) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $columns;
        }

        // Get tax summary
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // If we don't have a tax summary or only have one tax rate, return original columns
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $columns;
        }

        // Add tax rate column
        $new_columns = array();
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Add tax rate column before tax column
            if ($key === 'tax') {
                $new_columns['tax_rate'] = __('Tax Rate', 'edd-custom-vat');
            }
        }
        
        return $new_columns;
    }

    /**
     * Modify cart item template to add tax rate column.
     *
     * @since    2.0.0
     * @param    string    $template    The cart item template.
     * @param    array     $item        The cart item.
     * @return   string                 The modified cart item template.
     */
    public function modify_cart_item_template($template, $item) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $template;
        }

        // Get tax summary
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // If we don't have a tax summary or only have one tax rate, return original template
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $template;
        }

        // Get the download ID
        $download_id = isset($item['id']) ? $item['id'] : 0;
        if (empty($download_id)) {
            return $template;
        }

        // Get the customer's country
        $country = EDD_Custom_VAT_Helpers::get_customer_country();
        if (empty($country)) {
            return $template;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, try to get the default rate
        if (false === $custom_rate) {
            $custom_rate = function_exists('edd_get_tax_rate') ? edd_get_tax_rate($country) : 0;
        }

        // Add tax rate column to template
        $tax_rate_column = '<td class="edd_cart_tax_rate">' . $custom_rate . '%</td>';
        
        // Insert tax rate column before tax column
        $template = str_replace(
            '<td class="edd_cart_tax">',
            $tax_rate_column . '<td class="edd_cart_tax">',
            $template
        );
        
        return $template;
    }

    /**
     * Modify cart item price label to add tax rate.
     *
     * @since    2.0.0
     * @param    string    $label       The price label.
     * @param    int       $item_id     The item ID.
     * @param    array     $options     The item options.
     * @return   string                 The modified price label.
     */
    public function modify_cart_item_price_label($label, $item_id, $options) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $label;
        }

        // Check if prices include tax
        $prices_include_tax = edd_prices_include_tax();
        
        // Get the customer's country
        $country = EDD_Custom_VAT_Helpers::get_customer_country();
        if (empty($country)) {
            return $label;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $label;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($item_id, $country);
        
        // If no custom rate, return original label
        if (false === $custom_rate) {
            return $label;
        }

        // Add tax rate to label
        if ($prices_include_tax) {
            $label .= ' <span class="edd-custom-vat-tax-info">' . sprintf(__('(incl. %s%% VAT)', 'edd-custom-vat'), $custom_rate) . '</span>';
        } else {
            $label .= ' <span class="edd-custom-vat-tax-info">' . sprintf(__('(excl. %s%% VAT)', 'edd-custom-vat'), $custom_rate) . '</span>';
        }
        
        return $label;
    }

    /**
     * Add tax breakdown to checkout.
     *
     * @since    2.0.0
     */
    public function add_tax_breakdown_to_checkout() {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Get tax summary
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // If we don't have a tax summary or only have one tax rate, return
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return;
        }

        // Display tax breakdown
        ?>
        <div class="edd-custom-vat-tax-breakdown">
            <h4><?php _e('Tax Breakdown', 'edd-custom-vat'); ?></h4>
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
     * AJAX handler for country change.
     * This ensures tax rates are updated when the customer changes their country.
     *
     * @since    2.0.0
     */
    public function ajax_update_country() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edd_custom_vat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check if we have a country
        if (!isset($_POST['country']) || empty($_POST['country'])) {
            wp_send_json_error(__('No country specified.', 'edd-custom-vat'));
        }

        // Get the country
        $country = sanitize_text_field($_POST['country']);

        // Update the customer's country in the session
        if (function_exists('edd_set_shop_country')) {
            edd_set_shop_country($country);
        }

        // Force EDD to recalculate taxes with the new country
        if (function_exists('edd_recalculate_taxes')) {
            edd_recalculate_taxes();
        }

        // Clear tax summary from session to force recalculation
        EDD()->session->set('edd_custom_vat_tax_summary', null);

        // Get cart contents to check if we have custom tax rates for the new country
        $cart_contents = edd_get_cart_content_details();
        $has_custom_rates = false;
        $default_rate = function_exists('edd_get_tax_rate') ? edd_get_tax_rate($country) : 0;
        
        if (!empty($cart_contents)) {
            foreach ($cart_contents as $item) {
                $download_id = isset($item['id']) ? $item['id'] : 0;
                if (empty($download_id)) {
                    continue;
                }
                
                // Check if we have a custom tax rate for this product and country
                $custom_rate = $this->db->get_tax_rate($download_id, $country);
                if (false !== $custom_rate) {
                    $has_custom_rates = true;
                    break;
                }
            }
        }

        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            if ($has_custom_rates) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: country code */
                        __('Country changed to %1$s via AJAX, custom tax rates found and applied', 'edd-custom-vat'),
                        $country
                    )
                );
            } else {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: country code, 2: default tax rate */
                        __('Country changed to %1$s via AJAX, no custom tax rates found, using EDD default rate: %2$f%%', 'edd-custom-vat'),
                        $country,
                        $default_rate
                    )
                );
            }
        }

        wp_send_json_success(array(
            'message' => __('Country updated and taxes recalculated.', 'edd-custom-vat'),
            'country' => $country,
            'has_custom_rates' => $has_custom_rates,
            'default_rate' => $default_rate
        ));
    }

    /**
     * Add country change listener to checkout.
     * This ensures tax rates are updated when the customer changes their country.
     *
     * @since    2.0.0
     */
    public function add_country_change_listener() {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }
        
        // Add JavaScript to listen for country changes
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Listen for country changes
                $(document.body).on('change', '#billing_country', function() {
                    var country = $(this).val();
                    
                    // Show loading spinner
                    $('#edd-custom-vat-loading').show();
                    
                    // Send AJAX request
                    $.ajax({
                        url: eddCustomVAT.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'edd_custom_vat_update_country',
                            country: country,
                            nonce: eddCustomVAT.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Reload the checkout
                                window.location.reload();
                            } else {
                                console.error('Error updating country:', response.data);
                            }
                        },
                        error: function() {
                            console.error('AJAX error when updating country');
                        },
                        complete: function() {
                            // Hide loading spinner
                            $('#edd-custom-vat-loading').hide();
                        }
                    });
                });
                
                // Add loading spinner
                if ($('#edd-custom-vat-loading').length === 0) {
                    $('body').append('<div id="edd-custom-vat-loading" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:9999;"><div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"><i class="fas fa-spinner fa-spin fa-3x"></i><p>' + eddCustomVAT.i18n.loading + '</p></div></div>');
                }
            });
        </script>
        <?php
    }
}
