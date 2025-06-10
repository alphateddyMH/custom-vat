<?php
/**
 * The Stripe integration functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The Stripe integration functionality of the plugin.
 *
 * Handles integration with Stripe payment gateway.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Stripe {

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
        // We don't need to modify Stripe arguments directly
        // Our tax calculations are already applied through the EDD tax filters
        // This class is mainly for future Stripe-specific customizations if needed
        
        // Log Stripe tax data for debugging
        add_filter('edds_create_payment_intent_args', array($this, 'log_stripe_tax_data'), 999, 2);
        add_filter('edds_create_setup_intent_args', array($this, 'log_stripe_tax_data'), 999, 2);
    }

    /**
     * Log Stripe tax data for debugging.
     *
     * @since    2.0.0
     * @param    array    $intent_args    The Stripe intent arguments.
     * @param    array    $payment_data   The payment data.
     * @return   array                    The unmodified Stripe intent arguments.
     */
    public function log_stripe_tax_data($intent_args, $payment_data) {
        // Check if debug mode is enabled
        if (!EDD_Custom_VAT_Settings::is_debug_mode()) {
            return $intent_args;
        }

        // Get payment ID
        $payment_id = isset($payment_data['purchase_data']['payment_id']) ? $payment_data['purchase_data']['payment_id'] : 0;
        
        // Log Stripe tax data
        $this->gateway_manager->log_gateway_tax_data('stripe', $intent_args, $payment_id);
        
        // Log specific Stripe tax fields
        if (isset($intent_args['amount'])) {
            $this->logger->log(
                sprintf(
                    /* translators: %s: amount */
                    __('Stripe total amount: %s', 'edd-custom-vat'),
                    $intent_args['amount']
                )
            );
        }
        
        return $intent_args;
    }

    /**
     * Modify Stripe payment intent arguments.
     *
     * @since    2.0.0
     * @param    array    $intent_args    The Stripe intent arguments.
     * @param    array    $payment_data   The payment data.
     * @return   array                    The modified Stripe intent arguments.
     */
    public function modify_stripe_args($intent_args, $payment_data) {
        // We don't need to modify Stripe arguments directly
        // Our tax calculations are already applied through the EDD tax filters
        // This method is here for future Stripe-specific customizations if needed
        
        return $intent_args;
    }

    /**
     * Modify Stripe invoice arguments.
     *
     * @since    2.0.0
     * @param    array    $invoice_args    The Stripe invoice arguments.
     * @param    array    $payment_data    The payment data.
     * @return   array                     The modified Stripe invoice arguments.
     */
    public function modify_stripe_invoice_args($invoice_args, $payment_data) {
        // We don't need to modify Stripe invoice arguments directly
        // Our tax calculations are already applied through the EDD tax filters
        // This method is here for future Stripe-specific customizations if needed
        
        return $invoice_args;
    }

    /**
     * Check if the current gateway is Stripe.
     *
     * @since    2.0.0
     * @return   bool    True if the current gateway is Stripe, false otherwise.
     */
    public function is_stripe_gateway() {
        $gateway = edd_get_chosen_gateway();
        
        return 'stripe' === $gateway;
    }

    /**
     * Check if Stripe is active.
     *
     * @since    2.0.0
     * @return   bool    True if Stripe is active, false otherwise.
     */
    public function is_stripe_active() {
        return function_exists('edd_stripe');
    }
}
