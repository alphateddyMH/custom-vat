<?php
/**
 * The gateway manager functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The gateway manager functionality of the plugin.
 *
 * Handles integration with payment gateways.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Gateway_Manager {

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
        // We don't need to modify gateway arguments directly
        // Our tax calculations are already applied through the EDD tax filters
        // This class is mainly for future gateway-specific customizations if needed
    }

    /**
     * Ensure tax data is correctly passed to gateways.
     *
     * @since    2.0.0
     * @param    array     $payment_data    The payment data.
     * @return   array                      The modified payment data.
     */
    public function ensure_tax_data_in_payment($payment_data) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $payment_data;
        }

        // Get cart contents
        $cart_contents = edd_get_cart_content_details();
        if (empty($cart_contents)) {
            return $payment_data;
        }

        // Get the customer's country
        $country = isset($payment_data['user_info']['address']['country']) ? $payment_data['user_info']['address']['country'] : '';
        if (empty($country)) {
            $country = EDD_Custom_VAT_Helpers::get_customer_country();
        }
        
        if (empty($country)) {
            return $payment_data;
        }

        // Get tax summary from session
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // Store tax summary in payment data
        if (!empty($tax_summary)) {
            $payment_data['tax_summary'] = $tax_summary;
        }
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: %s: country code */
                    __('Ensured tax data in payment for country %s', 'edd-custom-vat'),
                    $country
                )
            );
        }
        
        return $payment_data;
    }

    /**
     * Get gateway-specific tax data.
     *
     * @since    2.0.0
     * @param    string    $gateway    The gateway ID.
     * @param    array     $data       The payment data.
     * @return   array                 The gateway-specific tax data.
     */
    public function get_gateway_tax_data($gateway, $data) {
        // This method can be extended for gateway-specific tax data formatting
        // Currently, we rely on EDD's default tax handling for gateways
        
        return array();
    }

    /**
     * Check if a gateway supports itemized tax rates.
     *
     * @since    2.0.0
     * @param    string    $gateway    The gateway ID.
     * @return   bool                  True if the gateway supports itemized tax rates, false otherwise.
     */
    public function gateway_supports_itemized_taxes($gateway) {
        $supported_gateways = array(
            'paypal_commerce', // PayPal Commerce
            'stripe',          // Stripe
            'paypal',          // PayPal Standard
            'paypal_express',  // PayPal Express
        );
        
        return in_array($gateway, $supported_gateways);
    }

    /**
     * Log payment gateway tax data.
     *
     * @since    2.0.0
     * @param    string    $gateway       The gateway ID.
     * @param    array     $gateway_args  The gateway arguments.
     * @param    int       $payment_id    The payment ID.
     */
    public function log_gateway_tax_data($gateway, $gateway_args, $payment_id) {
        // Check if debug mode is enabled
        if (!EDD_Custom_VAT_Settings::is_debug_mode()) {
            return;
        }

        // Get payment tax data
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        $tax_summary = isset($payment_meta['tax_summary']) ? $payment_meta['tax_summary'] : array();
        
        // Log gateway tax data
        $this->logger->log(
            sprintf(
                /* translators: 1: gateway ID, 2: payment ID */
                __('Gateway tax data for %1$s (Payment #%2$d):', 'edd-custom-vat'),
                $gateway,
                $payment_id
            )
        );
        
        if (!empty($tax_summary)) {
            foreach ($tax_summary as $rate_key => $rate_data) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: tax rate, 2: tax amount */
                        __('- Rate: %1$s%%, Amount: %2$s', 'edd-custom-vat'),
                        $rate_data['rate'],
                        edd_currency_filter(edd_format_amount($rate_data['amount']))
                    )
                );
            }
        }
    }
}
