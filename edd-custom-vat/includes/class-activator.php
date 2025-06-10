<?php
/**
 * Fired during plugin activation.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Activator {

    /**
     * Activate the plugin.
     *
     * Creates necessary database tables and sets default options.
     *
     * @since    2.0.0
     */
    public static function activate() {
        // Create database tables if needed
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag for admin notices
        set_transient('edd_custom_vat_activation_notice', true, 5);
    }
    
    /**
     * Create necessary database tables.
     *
     * @since    2.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing product tax rates
        $table_name = $wpdb->prefix . 'edd_custom_vat_rates';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            download_id bigint(20) NOT NULL,
            country varchar(10) NOT NULL,
            tax_rate decimal(10,4) NOT NULL,
            created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY download_id (download_id),
            KEY country (country),
            UNIQUE KEY download_country (download_id,country)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options for the plugin.
     *
     * @since    2.0.0
     */
    private static function set_default_options() {
        // General settings
        $general_settings = array(
            'enabled' => 1,
            'delete_data' => 0,
            'debug_mode' => 0,
        );
        
        // Only add if not already exists
        if (!get_option('edd_custom_vat_general')) {
            update_option('edd_custom_vat_general', $general_settings);
        }
        
        // Countries settings - enable Germany by default
        $countries_settings = array(
            'enabled_countries' => array('DE'),
        );
        
        // Only add if not already exists
        if (!get_option('edd_custom_vat_countries')) {
            update_option('edd_custom_vat_countries', $countries_settings);
        }
        
        // Advanced settings
        $advanced_settings = array(
            'cache_duration' => 3600,
            'bundle_display' => 'detailed',
        );
        
        // Only add if not already exists
        if (!get_option('edd_custom_vat_advanced')) {
            update_option('edd_custom_vat_advanced', $advanced_settings);
        }
    }
}
