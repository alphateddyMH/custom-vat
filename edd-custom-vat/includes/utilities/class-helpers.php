<?php
/**
 * Helper functions for the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/utilities
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Helper functions for the plugin.
 *
 * Provides utility functions used throughout the plugin.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/utilities
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Helpers {

    /**
     * Get all countries.
     *
     * @since    2.0.0
     * @return   array    Array of countries.
     */
    public static function get_countries() {
        if (function_exists('edd_get_country_list')) {
            return edd_get_country_list();
        }
        
        // Fallback if EDD function is not available
        return array(
            'US' => __('United States', 'edd-custom-vat'),
            'CA' => __('Canada', 'edd-custom-vat'),
            'GB' => __('United Kingdom', 'edd-custom-vat'),
            'AU' => __('Australia', 'edd-custom-vat'),
            'BR' => __('Brazil', 'edd-custom-vat'),
            'DE' => __('Germany', 'edd-custom-vat'),
            'FR' => __('France', 'edd-custom-vat'),
            'NL' => __('Netherlands', 'edd-custom-vat'),
            'ES' => __('Spain', 'edd-custom-vat'),
            'IT' => __('Italy', 'edd-custom-vat'),
            // Add more countries as needed
        );
    }

    /**
     * Get EU countries.
     *
     * @since    2.0.0
     * @return   array    Array of EU country codes.
     */
    public static function get_eu_countries() {
        return array(
            'AT', // Austria
            'BE', // Belgium
            'BG', // Bulgaria
            'HR', // Croatia
            'CY', // Cyprus
            'CZ', // Czech Republic
            'DK', // Denmark
            'EE', // Estonia
            'FI', // Finland
            'FR', // France
            'DE', // Germany
            'GR', // Greece
            'HU', // Hungary
            'IE', // Ireland
            'IT', // Italy
            'LV', // Latvia
            'LT', // Lithuania
            'LU', // Luxembourg
            'MT', // Malta
            'NL', // Netherlands
            'PL', // Poland
            'PT', // Portugal
            'RO', // Romania
            'SK', // Slovakia
            'SI', // Slovenia
            'ES', // Spain
            'SE', // Sweden
        );
    }

    /**
     * Check if a country is in the EU.
     *
     * @since    2.0.0
     * @param    string    $country    The country code.
     * @return   bool                  True if the country is in the EU, false otherwise.
     */
    public static function is_eu_country($country) {
        return in_array($country, self::get_eu_countries());
    }

    /**
     * Get the customer's country.
     *
     * @since    2.0.0
     * @return   string    The customer's country code.
     */
    public static function get_customer_country() {
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
     * Format a tax rate for display.
     *
     * @since    2.0.0
     * @param    float     $rate    The tax rate.
     * @return   string             The formatted tax rate.
     */
    public static function format_tax_rate($rate) {
        return number_format($rate, 2) . '%';
    }

    /**
     * Format a price for display.
     *
     * @since    2.0.0
     * @param    float     $price    The price.
     * @return   string              The formatted price.
     */
    public static function format_price($price) {
        if (function_exists('edd_currency_filter')) {
            return edd_currency_filter(edd_format_amount($price));
        }
        
        return number_format($price, 2);
    }

    /**
     * Check if a product is a bundle.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   bool                      True if the product is a bundle, false otherwise.
     */
    public static function is_bundle($download_id) {
        if (function_exists('edd_is_bundled_product')) {
            return edd_is_bundled_product($download_id);
        }
        
        // Fallback if EDD function is not available
        $download = get_post($download_id);
        if (!$download) {
            return false;
        }
        
        $is_bundle = get_post_meta($download_id, '_edd_product_type', true) === 'bundle';
        
        return $is_bundle;
    }

    /**
     * Get bundle products.
     *
     * @since    2.0.0
     * @param    int       $bundle_id    The bundle ID.
     * @return   array                   Array of product IDs in the bundle.
     */
    public static function get_bundle_products($bundle_id) {
        if (function_exists('edd_get_bundled_products')) {
            return edd_get_bundled_products($bundle_id);
        }
        
        // Fallback if EDD function is not available
        $products = get_post_meta($bundle_id, '_edd_bundled_products', true);
        
        if (!empty($products) && is_array($products)) {
            return $products;
        }
        
        return array();
    }

    /**
     * Check if EDD Recurring is active.
     *
     * @since    2.0.0
     * @return   bool    True if EDD Recurring is active, false otherwise.
     */
    public static function is_recurring_active() {
        return class_exists('EDD_Recurring');
    }

    /**
     * Check if a product is recurring.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   bool                      True if the product is recurring, false otherwise.
     */
    public static function is_recurring($download_id) {
        if (!self::is_recurring_active()) {
            return false;
        }
        
        $is_recurring = get_post_meta($download_id, 'edd_recurring', true);
        
        return !empty($is_recurring);
    }

    /**
     * Check if EDD Software Licensing is active.
     *
     * @since    2.0.0
     * @return   bool    True if EDD Software Licensing is active, false otherwise.
     */
    public static function is_software_licensing_active() {
        return class_exists('EDD_Software_Licensing');
    }

    /**
     * Check if WPML is active.
     *
     * @since    2.0.0
     * @return   bool    True if WPML is active, false otherwise.
     */
    public static function is_wpml_active() {
        return function_exists('icl_object_id');
    }

    /**
     * Get the original product ID if it's a translation.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   int                       The original download ID.
     */
    public static function get_original_product_id($download_id) {
        if (!self::is_wpml_active()) {
            return $download_id;
        }
        
        global $sitepress;
        
        if (!$sitepress) {
            return $download_id;
        }
        
        $default_language = $sitepress->get_default_language();
        $original_id = icl_object_id($download_id, 'download', true, $default_language);
        
        return $original_id ? $original_id : $download_id;
    }

    /**
     * Get all translations of a product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   array                     Array of translation IDs.
     */
    public static function get_product_translations($download_id) {
        if (!self::is_wpml_active()) {
            return array($download_id);
        }
        
        global $sitepress;
        
        if (!$sitepress) {
            return array($download_id);
        }
        
        $trid = $sitepress->get_element_trid($download_id, 'post_download');
        $translations = $sitepress->get_element_translations($trid, 'post_download');
        
        $translation_ids = array();
        
        foreach ($translations as $translation) {
            $translation_ids[] = $translation->element_id;
        }
        
        return $translation_ids;
    }

    /**
     * Sanitize a tax rate.
     *
     * @since    2.0.0
     * @param    mixed     $rate    The tax rate to sanitize.
     * @return   float              The sanitized tax rate.
     */
    public static function sanitize_tax_rate($rate) {
        $rate = (float) $rate;
        
        // Ensure rate is between 0 and 100
        $rate = max(0, min(100, $rate));
        
        return $rate;
    }

    /**
     * Check if the current page is an EDD checkout page.
     *
     * @since    2.0.0
     * @return   bool    True if the current page is an EDD checkout page, false otherwise.
     */
    public static function is_checkout_page() {
        if (function_exists('edd_is_checkout')) {
            return edd_is_checkout();
        }
        
        // Fallback if EDD function is not available
        global $post;
        
        if (!$post) {
            return false;
        }
        
        $checkout_page = edd_get_option('purchase_page');
        
        return $post->ID == $checkout_page;
    }

    /**
     * Check if the current page is an EDD success page.
     *
     * @since    2.0.0
     * @return   bool    True if the current page is an EDD success page, false otherwise.
     */
    public static function is_success_page() {
        if (function_exists('edd_is_success_page')) {
            return edd_is_success_page();
        }
        
        // Fallback if EDD function is not available
        global $post;
        
        if (!$post) {
            return false;
        }
        
        $success_page = edd_get_option('success_page');
        
        return $post->ID == $success_page;
    }

    /**
     * Get the plugin version.
     *
     * @since    2.0.0
     * @return   string    The plugin version.
     */
    public static function get_plugin_version() {
        return EDD_CUSTOM_VAT_VERSION;
    }

    /**
     * Get the plugin URL.
     *
     * @since    2.0.0
     * @return   string    The plugin URL.
     */
    public static function get_plugin_url() {
        return EDD_CUSTOM_VAT_PLUGIN_URL;
    }

    /**
     * Get the plugin directory.
     *
     * @since    2.0.0
     * @return   string    The plugin directory.
     */
    public static function get_plugin_dir() {
        return EDD_CUSTOM_VAT_PLUGIN_DIR;
    }
}
