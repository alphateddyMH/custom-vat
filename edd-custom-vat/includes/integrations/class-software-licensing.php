<?php
/**
 * The software licensing integration functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The software licensing integration functionality of the plugin.
 *
 * Handles integration with EDD Software Licensing.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/integrations
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Software_Licensing {

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
        add_filter('edd_sl_license_renewal_args', array($this, 'modify_renewal_args'), 999, 2);
        add_filter('edd_sl_get_renewal_cart_item_details', array($this, 'modify_renewal_cart_item'), 999, 3);
    }

    /**
     * Modify license renewal arguments to include custom tax rates.
     *
     * @since    2.0.0
     * @param    array     $args        The renewal arguments.
     * @param    object    $license     The license object.
     * @return   array                  The modified renewal arguments.
     */
    public function modify_renewal_args($args, $license) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $args;
        }

        // Check if we have a download ID
        if (!isset($license->download_id) || empty($license->download_id)) {
            return $args;
        }

        // Get the download ID
        $download_id = $license->download_id;

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

        // Store the custom tax rate in the args
        $args['tax_rate'] = $custom_rate;
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: country code, 3: tax rate */
                    __('Applied custom tax rate of %3$f%% to license renewal for product #%1$d in country %2$s', 'edd-custom-vat'),
                    $download_id,
                    $country,
                    $custom_rate
                )
            );
        }
        
        return $args;
    }

    /**
     * Modify renewal cart item to include custom tax rates.
     *
     * @since    2.0.0
     * @param    array     $item        The cart item.
     * @param    int       $license_id  The license ID.
     * @param    object    $license     The license object.
     * @return   array                  The modified cart item.
     */
    public function modify_renewal_cart_item($item, $license_id, $license) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $item;
        }

        // Check if we have a download ID
        if (!isset($license->download_id) || empty($license->download_id)) {
            return $item;
        }

        // Get the download ID
        $download_id = $license->download_id;

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $item;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $item;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return original item
        if (false === $custom_rate) {
            return $item;
        }

        // Store the custom tax rate in the item
        $item['tax_rate'] = $custom_rate;
        
        // Recalculate tax
        $price = isset($item['price']) ? $item['price'] : 0;
        $item['tax'] = $price * ($custom_rate / 100);
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: country code, 3: tax rate, 4: tax amount */
                    __('Applied custom tax rate of %3$f%% to renewal cart item for product #%1$d in country %2$s. Tax amount: %4$f', 'edd-custom-vat'),
                    $download_id,
                    $country,
                    $custom_rate,
                    $item['tax']
                )
            );
        }
        
        return $item;
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
     * Check if EDD Software Licensing is active.
     *
     * @since    2.0.0
     * @return   bool    True if EDD Software Licensing is active, false otherwise.
     */
    public function is_software_licensing_active() {
        return class_exists('EDD_Software_Licensing');
    }

    /**
     * Check if a product has licensing enabled.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   bool                      True if the product has licensing enabled, false otherwise.
     */
    public function has_licensing($download_id) {
        if (!$this->is_software_licensing_active()) {
            return false;
        }
        
        $is_licensed = get_post_meta($download_id, '_edd_sl_enabled', true);
        
        return !empty($is_licensed);
    }

    /**
     * Get license renewal URL with custom tax rate.
     *
     * @since    2.0.0
     * @param    string    $url         The renewal URL.
     * @param    int       $license_id  The license ID.
     * @param    string    $country     The country code.
     * @return   string                 The modified renewal URL.
     */
    public function get_renewal_url_with_tax_rate($url, $license_id, $country) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $url;
        }

        // Check if EDD Software Licensing is active
        if (!$this->is_software_licensing_active()) {
            return $url;
        }

        // Get license
        $license = $this->get_license($license_id);
        if (!$license) {
            return $url;
        }

        // Get the download ID
        $download_id = $license->download_id;

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $url;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return original URL
        if (false === $custom_rate) {
            return $url;
        }

        // Add tax rate to URL
        $url = add_query_arg('tax_rate', $custom_rate, $url);
        
        return $url;
    }

    /**
     * Get license by ID.
     *
     * @since    2.0.0
     * @param    int       $license_id    The license ID.
     * @return   object|false             The license object or false if not found.
     */
    private function get_license($license_id) {
        if (!$this->is_software_licensing_active()) {
            return false;
        }
        
        $license = EDD_Software_Licensing()->get_license($license_id);
        
        return $license;
    }
}
