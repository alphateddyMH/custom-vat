<?php
/**
 * The recurring tax functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The recurring tax functionality of the plugin.
 *
 * Handles tax calculations for recurring payments.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Recurring_Tax {

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
    }

    /**
     * Modify recurring signup arguments to include custom tax rates.
     *
     * @since    2.0.0
     * @param    array     $args         The signup arguments.
     * @param    array     $recurring    The recurring data.
     * @return   array                   The modified signup arguments.
     */
    public function modify_recurring_signup_args($args, $recurring) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $args;
        }

        // Check if we have a download ID
        if (!isset($recurring['id']) || empty($recurring['id'])) {
            return $args;
        }

        // Get the download ID
        $download_id = $recurring['id'];

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $args;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $args;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return original args
        if (false === $custom_rate) {
            return $args;
        }

        // Store the custom tax rate in the subscription meta
        if (!isset($args['meta'])) {
            $args['meta'] = array();
        }
        
        $args['meta']['tax_rate'] = $custom_rate;
        
        // Recalculate tax amount
        $initial_amount = isset($args['initial_amount']) ? $args['initial_amount'] : 0;
        $recurring_amount = isset($args['recurring_amount']) ? $args['recurring_amount'] : 0;
        
        // Calculate initial tax
        $initial_tax = $initial_amount * ($custom_rate / 100);
        
        // Calculate recurring tax
        $recurring_tax = $recurring_amount * ($custom_rate / 100);
        
        // Update tax amounts
        $args['initial_tax'] = $initial_tax;
        $args['recurring_tax'] = $recurring_tax;
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: country code, 3: tax rate, 4: initial tax, 5: recurring tax */
                    __('Applied custom tax rate of %3$f%% to recurring subscription for product #%1$d in country %2$s. Initial tax: %4$f, Recurring tax: %5$f', 'edd-custom-vat'),
                    $download_id,
                    $country,
                    $custom_rate,
                    $initial_tax,
                    $recurring_tax
                )
            );
        }
        
        return $args;
    }

    /**
     * Modify recurring payment arguments to include custom tax rates.
     *
     * @since    2.0.0
     * @param    array     $args          The payment arguments.
     * @param    object    $subscription  The subscription object.
     * @return   array                    The modified payment arguments.
     */
    public function modify_recurring_payment_args($args, $subscription) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $args;
        }

        // Check if we have a subscription
        if (!is_object($subscription)) {
            return $args;
        }

        // Get the subscription meta
        $subscription_meta = $this->get_subscription_meta($subscription);
        
        // Check if we have a stored tax rate
        if (!isset($subscription_meta['tax_rate']) || empty($subscription_meta['tax_rate'])) {
            return $args;
        }

        // Get the tax rate
        $tax_rate = $subscription_meta['tax_rate'];
        
        // Recalculate tax amount
        $amount = isset($args['amount']) ? $args['amount'] : 0;
        
        // Calculate tax
        $tax = $amount * ($tax_rate / 100);
        
        // Update tax amount
        $args['tax'] = $tax;
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: subscription ID, 2: tax rate, 3: tax amount */
                    __('Applied stored tax rate of %2$f%% to recurring payment for subscription #%1$d. Tax amount: %3$f', 'edd-custom-vat'),
                    $subscription->id,
                    $tax_rate,
                    $tax
                )
            );
        }
        
        return $args;
    }

    /**
     * Store subscription tax rates when creating payment profiles.
     *
     * @since    2.0.0
     * @param    int       $subscription_id    The subscription ID.
     * @param    array     $args               The subscription arguments.
     * @param    array     $recurring          The recurring data.
     * @param    int       $payment_id         The payment ID.
     */
    public function store_subscription_tax_rates($subscription_id, $args, $recurring, $payment_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Check if we have a download ID
        if (!isset($recurring['id']) || empty($recurring['id'])) {
            return;
        }

        // Get the download ID
        $download_id = $recurring['id'];

        // Get the customer's country
        $country = $this->get_customer_country_from_payment($payment_id);
        if (empty($country)) {
            return;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, try to get the default rate
        if (false === $custom_rate) {
            $custom_rate = function_exists('edd_get_tax_rate') ? edd_get_tax_rate($country) : 0;
        }

        // Store the tax rate in the subscription meta
        $this->update_subscription_meta($subscription_id, 'tax_rate', $custom_rate);
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: subscription ID, 2: download ID, 3: country code, 4: tax rate */
                    __('Stored tax rate of %4$f%% for subscription #%1$d (product #%2$d) in country %3$s', 'edd-custom-vat'),
                    $subscription_id,
                    $download_id,
                    $country,
                    $custom_rate
                )
            );
        }
    }

    /**
     * Modify subscription args before sending to gateway.
     *
     * @since    2.0.0
     * @param    array     $args          The subscription arguments.
     * @param    array     $subscription  The subscription data.
     * @return   array                    The modified subscription arguments.
     */
    public function modify_subscription_args($args, $subscription) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $args;
        }

        // Check if we have a download ID
        if (!isset($subscription['id']) || empty($subscription['id'])) {
            return $args;
        }

        // Get the download ID
        $download_id = $subscription['id'];

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $args;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $args;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return original args
        if (false === $custom_rate) {
            return $args;
        }

        // Recalculate tax amount
        $amount = isset($args['amount']) ? $args['amount'] : 0;
        
        // Calculate tax
        $tax = $amount * ($custom_rate / 100);
        
        // Update tax amount
        $args['tax'] = $tax;
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: country code, 3: tax rate, 4: tax amount */
                    __('Applied custom tax rate of %3$f%% to subscription gateway args for product #%1$d in country %2$s. Tax amount: %4$f', 'edd-custom-vat'),
                    $download_id,
                    $country,
                    $custom_rate,
                    $tax
                )
            );
        }
        
        return $args;
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
     * Get the customer's country from a payment.
     *
     * @since    2.0.0
     * @param    int       $payment_id    The payment ID.
     * @return   string                   The customer's country code.
     */
    private function get_customer_country_from_payment($payment_id) {
        // Get payment meta
        $payment_meta = function_exists('edd_get_payment_meta') ? edd_get_payment_meta($payment_id) : array();
        if (empty($payment_meta)) {
            return '';
        }

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

    /**
     * Get subscription meta.
     *
     * @since    2.0.0
     * @param    object    $subscription    The subscription object.
     * @return   array                      The subscription meta.
     */
    private function get_subscription_meta($subscription) {
        // Check if we have a subscription
        if (!is_object($subscription)) {
            return array();
        }

        // Check if we have a meta property
        if (!property_exists($subscription, 'meta') || empty($subscription->meta)) {
            return array();
        }

        // Return meta
        return $subscription->meta;
    }

    /**
     * Update subscription meta.
     *
     * @since    2.0.0
     * @param    int       $subscription_id    The subscription ID.
     * @param    string    $key                The meta key.
     * @param    mixed     $value              The meta value.
     * @return   bool                          True on success, false on failure.
     */
    private function update_subscription_meta($subscription_id, $key, $value) {
        // Check if EDD Recurring is active
        if (!function_exists('EDD_Recurring')) {
            return false;
        }

        // Get the subscription
        $subscription = EDD_Recurring()->subscriptions->get_subscription($subscription_id);
        if (!$subscription) {
            return false;
        }

        // Update meta
        return $subscription->update_meta($key, $value);
    }
}
