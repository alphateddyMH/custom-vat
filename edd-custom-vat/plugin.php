<?php
/**
 * Plugin Name: Custom VAT
 * Plugin URI: https://github.com/alphateddyMH/custom-vat
 * Description: Customize VAT rates for Easy Digital Downloads products.
 * Version: 2.0.0
 * Author: alphateddyMH
 * Author URI: https://github.com/alphateddyMH
 * Text Domain: custom-vat
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Custom_VAT
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('CUSTOM_VAT_VERSION', '2.0.0');
define('CUSTOM_VAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_VAT_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_custom_vat() {
    require_once CUSTOM_VAT_PLUGIN_DIR . 'includes/class-custom-vat-activator.php';
    Custom_VAT_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_custom_vat() {
    require_once CUSTOM_VAT_PLUGIN_DIR . 'includes/class-custom-vat-deactivator.php';
    Custom_VAT_Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 */
function uninstall_custom_vat() {
    require_once CUSTOM_VAT_PLUGIN_DIR . 'includes/class-custom-vat-uninstaller.php';
    Custom_VAT_Uninstaller::uninstall();
}

register_activation_hook(__FILE__, 'activate_custom_vat');
register_deactivation_hook(__FILE__, 'deactivate_custom_vat');
register_uninstall_hook(__FILE__, 'uninstall_custom_vat');

/**
 * The core plugin class.
 */
require_once CUSTOM_VAT_PLUGIN_DIR . 'includes/class-custom-vat.php';

/**
 * Begins execution of the plugin.
 *
 * @since 2.0.0
 */
function run_custom_vat() {
    // Check if EDD is active
    if (!class_exists('Easy_Digital_Downloads')) {
        add_action('admin_notices', 'custom_vat_edd_missing_notice');
        return;
    }

    $plugin = new Custom_VAT();
    $plugin->run();
}

/**
 * Admin notice for missing EDD.
 */
function custom_vat_edd_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('Custom VAT requires Easy Digital Downloads to be installed and active.', 'custom-vat'); ?></p>
    </div>
    <?php
}

/**
 * Load plugin textdomain.
 */
function custom_vat_load_textdomain() {
    load_plugin_textdomain('custom-vat', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'custom_vat_load_textdomain');

/**
 * Run the plugin after all plugins are loaded.
 */
add_action('plugins_loaded', 'run_custom_vat');

/**
 * Logger function for debugging.
 *
 * @param mixed  $message The message to log.
 * @param string $level   The log level (info, warning, error).
 */
function custom_vat_log($message, $level = 'info') {
    if (!get_option('custom_vat_debug_mode', false) && $level !== 'error') {
        return;
    }
    
    $log_file = WP_CONTENT_DIR . '/custom-vat-logs/custom-vat-' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    error_log($log_entry, 3, $log_file);
}
