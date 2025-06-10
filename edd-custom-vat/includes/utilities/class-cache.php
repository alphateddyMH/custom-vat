<?php
/**
 * The cache functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/utilities
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The cache functionality of the plugin.
 *
 * Handles caching and cache invalidation.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/utilities
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Cache {

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
    }

    /**
     * Invalidate product cache when a product is saved.
     *
     * @since    2.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     * @param    bool      $update     Whether this is an existing post being updated.
     */
    public function invalidate_product_cache($post_id, $post, $update) {
        // Check if this is a download
        if ('download' !== $post->post_type) {
            return;
        }

        // Clear product-specific caches
        $this->clear_product_cache($post_id);
        
        // Clear general caches that might include this product
        $this->clear_general_caches();
    }

    /**
     * Invalidate settings cache when settings are updated.
     *
     * @since    2.0.0
     * @param    string    $option_name    The option name.
     * @param    mixed     $old_value      The old option value.
     * @param    mixed     $new_value      The new option value.
     */
    public function invalidate_settings_cache($option_name, $old_value, $new_value) {
        // Check if this is one of our settings
        if (strpos($option_name, 'edd_custom_vat_') === 0) {
            $this->clear_all_caches();
        }
    }

    /**
     * Clear cache for a specific product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     */
    public function clear_product_cache($download_id) {
        // Delete product-specific transients
        delete_transient('edd_custom_vat_rates_' . $download_id);
        
        // Delete product-specific object cache
        wp_cache_delete('edd_custom_vat_rates_' . $download_id, 'edd_custom_vat');
        
        // Delete country-specific rates for this product
        global $wpdb;
        $countries = $this->get_all_countries();
        
        foreach ($countries as $country) {
            $cache_key = 'edd_custom_vat_rate_' . $download_id . '_' . $country;
            delete_transient($cache_key);
            wp_cache_delete($cache_key, 'edd_custom_vat');
        }
    }

    /**
     * Clear general caches.
     *
     * @since    2.0.0
     */
    public function clear_general_caches() {
        // Delete all rates cache
        delete_transient('edd_custom_vat_all_rates');
        wp_cache_delete('edd_custom_vat_all_rates', 'edd_custom_vat');
        
        // Delete cart tax summary
        if (function_exists('EDD') && isset(EDD()->session)) {
            EDD()->session->set('edd_custom_vat_tax_summary', null);
        }
    }

    /**
     * Clear all caches.
     *
     * @since    2.0.0
     */
    public function clear_all_caches() {
        global $wpdb;
        
        // Delete all transients with our prefix
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_edd_custom_vat_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_edd_custom_vat_%'");
        
        // Clear object cache group
        wp_cache_flush_group('edd_custom_vat');
        
        // If wp_cache_flush_group is not available (older WordPress versions)
        if (function_exists('wp_cache_flush_runtime') && !function_exists('wp_cache_flush_group')) {
            wp_cache_flush_runtime();
        }
        
        // Delete cart tax summary
        if (function_exists('EDD') && isset(EDD()->session)) {
            EDD()->session->set('edd_custom_vat_tax_summary', null);
        }
    }

    /**
     * Get all countries.
     *
     * @since    2.0.0
     * @return   array    Array of country codes.
     */
    private function get_all_countries() {
        if (function_exists('edd_get_country_list')) {
            return array_keys(edd_get_country_list());
        }
        
        // Fallback if EDD function is not available
        return array(
            'US', 'CA', 'GB', 'AU', 'BR', 'DE', 'FR', 'NL', 'ES', 'IT', 
            'DK', 'SE', 'NO', 'JP', 'CN', 'IN', 'RU', 'ZA', 'MX', 'AR'
        );
    }

    /**
     * Check if object caching is enabled.
     *
     * @since    2.0.0
     * @return   bool    True if object caching is enabled, false otherwise.
     */
    public function is_object_cache_enabled() {
        return wp_using_ext_object_cache();
    }

    /**
     * Get cache duration.
     *
     * @since    2.0.0
     * @return   int    Cache duration in seconds.
     */
    public function get_cache_duration() {
        return EDD_Custom_VAT_Settings::get_cache_duration();
    }

    /**
     * Set cache with appropriate expiration.
     *
     * @since    2.0.0
     * @param    string    $key      The cache key.
     * @param    mixed     $value    The value to cache.
     * @param    string    $group    The cache group.
     */
    public function set_cache($key, $value, $group = 'edd_custom_vat') {
        $duration = $this->get_cache_duration();
        
        // Skip if caching is disabled
        if ($duration <= 0) {
            return;
        }
        
        // Set transient
        set_transient($key, $value, $duration);
        
        // Set object cache if available
        wp_cache_set($key, $value, $group, $duration);
    }

    /**
     * Get cached value.
     *
     * @since    2.0.0
     * @param    string    $key      The cache key.
     * @param    string    $group    The cache group.
     * @return   mixed               The cached value or false if not found.
     */
    public function get_cache($key, $group = 'edd_custom_vat') {
        // Try object cache first
        $value = wp_cache_get($key, $group);
        
        // If not in object cache, try transient
        if (false === $value) {
            $value = get_transient($key);
            
            // If found in transient, also set in object cache
            if (false !== $value) {
                $duration = $this->get_cache_duration();
                wp_cache_set($key, $value, $group, $duration);
            }
        }
        
        return $value;
    }
}
