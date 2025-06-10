<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Clean up temporary data and flush caches.
     *
     * @since    2.0.0
     */
    public static function deactivate() {
        // Clear any transients and caches
        self::clear_caches();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clear any transients and caches used by the plugin.
     *
     * @since    2.0.0
     */
    private static function clear_caches() {
        global $wpdb;
        
        // Delete all transients with our prefix
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_edd_custom_vat_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_edd_custom_vat_%'");
        
        // If object caching is being used, clear our groups
        wp_cache_flush();
    }
}
