<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://itmedialaw.com
 * @since      2.0.0
 *
 * @package    EDD_Custom_VAT
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the database class to access the database methods
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';

/**
 * Uninstall the plugin.
 * 
 * This function is responsible for cleaning up the plugin data when it's uninstalled.
 * It checks the plugin settings to determine whether to delete all data.
 */
function edd_custom_vat_uninstall() {
    global $wpdb;
    
    // Check if we should delete all data
    $general_settings = get_option('edd_custom_vat_general', array());
    $delete_data = isset($general_settings['delete_data']) ? (bool) $general_settings['delete_data'] : false;
    
    if (!$delete_data) {
        return;
    }
    
    // Delete database table
    $table_name = $wpdb->prefix . 'edd_custom_vat_rates';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Delete options
    delete_option('edd_custom_vat_general');
    delete_option('edd_custom_vat_countries');
    delete_option('edd_custom_vat_advanced');
    
    // Delete transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_edd_custom_vat_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_edd_custom_vat_%'");
    
    // Delete post meta
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%_edd_custom_vat_%'");
    
    // Delete user meta
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '%_edd_custom_vat_%'");
    
    // Clear any cached data
    wp_cache_flush();
}

// Run the uninstall function
edd_custom_vat_uninstall();
