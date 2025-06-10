<?php
/**
 * The PayPal integration functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The PayPal integration functionality of the plugin.
 *
 * Handles integration with PayPal payment gateways.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_PayPal {

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
     * Gateway manager instance.
     *
     * @since    2.0.0
     * @access   private
     * @var      EDD_Custom_VAT_Gateway_Manager    $gateway_manager    Gateway manager instance.
     */
    private $gateway_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->db = new EDD_Custom_VAT_Database();
        $this->logger = new EDD_Custom_VAT_Logger();
        $this->gateway_manager = new EDD_Custom_VAT_Gateway_Manager();
        
        // Register late-priority hooks to ensure we run after EDD Geo Targeting
        add_action('init', array($this, 'register_late_hooks'), 999);
    }
    
    /**
     * Register hooks with late priority to ensure they run after EDD Geo Targeting.
     *
     * @since    2.0.0
     */
    public function register_late_hooks() {
        // We don't need to modify PayPal arguments directly
        // Our tax calculations are already applied through the EDD tax filters
        // This class is mainly for future PayPal-specific customizations if needed
        
        // Log PayPal tax data for debugging
        add_action('edd_paypal_redirect_args', array($this, 'log_paypal_tax_data'), 999, 2);
    }

    /**
     * Log PayPal tax data for debugging.
     *
     * @since    2.0.0
     * @param    array    $paypal_args    The PayPal arguments.
     * @param    array    $payment_data   The payment data.
     * @return   array                    The unmodified PayPal arguments.
     */
    public function log_paypal_tax_data($paypal_args, $payment_data) {
        // Check if debug mode is enabled
        if (!EDD_Custom_VAT_Settings::is_debug_mode()) {
            return $paypal_args;
        }

        // Get payment ID
        $payment_id = isset($payment_data['purchase_data']['payment_id']) ? $payment_data['purchase_data']['payment_id'] : 0;
        
        // Log PayPal tax data
        $this->gateway_manager->log_gateway_tax_data('paypal', $paypal_args, $payment_id);
        
        // Log specific PayPal tax fields
        if (isset($paypal_args['tax']) || isset($paypal_args['tax_cart'])) {
            $tax = isset($paypal_args['tax']) ? $paypal_args['tax'] : $paypal_args['tax_cart'];
            $this->logger->log(
                sprintf(
                    /* translators: %s: tax amount */
                    __('PayPal tax amount: %s', 'edd-custom-vat'),
                    $tax
                )
            );
        }
        
        return $paypal_args;
    }

    /**
     * Ensure tax data is correctly passed to PayPal.
     *
     * @since    2.0.0
     * @param    array    $paypal_args    The PayPal arguments.
     * @param    array    $payment_data   The payment data.
     * @return   array                    The modified PayPal arguments.
     */
    public function modify_paypal_args($paypal_args, $payment_data) {
        // We don't need to modify PayPal arguments directly
        // Our tax calculations are already applied through the EDD tax filters
        // This method is here for future PayPal-specific customizations if needed
        
        return $paypal_args;
    }

    /**
     * Check if the current gateway is PayPal.
     *
     * @since    2.0.0
     * @return   bool    True if the current gateway is PayPal, false otherwise.
     */
    public function is_paypal_gateway() {
        $gateway = edd_get_chosen_gateway();
        
        $paypal_gateways = array(
            'paypal',
            'paypal_commerce',
            'paypal_express'
        );
        
        return in_array($gateway, $paypal_gateways);
    }

    /**
     * Get PayPal gateway type.
     *
     * @since    2.0.0
     * @return   string    The PayPal gateway type.
     */
    public function get_paypal_gateway_type() {
        $gateway = edd_get_chosen_gateway();
        
        switch ($gateway) {
            case 'paypal_commerce':
                return 'commerce';
            case 'paypal_express':
                return 'express';
            case 'paypal':
            default:
                return 'standard';
        }
    }
}
