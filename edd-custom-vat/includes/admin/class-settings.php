<?php
/**
 * The settings functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The settings functionality of the plugin.
 *
 * Defines and manages the settings pages and options.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Settings {

    /**
     * The active tab.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $active_tab    The active settings tab.
     */
    private $active_tab;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    }

    /**
     * Add settings menu item.
     *
     * @since    2.0.0
     */
    public function add_settings_menu() {
        add_submenu_page(
            'edit.php?post_type=download',
            __('Custom VAT Settings', 'edd-custom-vat'),
            __('Custom VAT', 'edd-custom-vat'),
            'manage_shop_settings',
            'edd-custom-vat',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    2.0.0
     */
    public function register_settings() {
        // Register general settings
        register_setting(
            'edd_custom_vat_general',
            'edd_custom_vat_general',
            array($this, 'sanitize_general_settings')
        );

        // Register countries settings
        register_setting(
            'edd_custom_vat_countries',
            'edd_custom_vat_countries',
            array($this, 'sanitize_countries_settings')
        );

        // Register advanced settings
        register_setting(
            'edd_custom_vat_advanced',
            'edd_custom_vat_advanced',
            array($this, 'sanitize_advanced_settings')
        );
    }

    /**
     * Sanitize general settings.
     *
     * @since    2.0.0
     * @param    array    $input    The input array.
     * @return   array              The sanitized input.
     */
    public function sanitize_general_settings($input) {
        $new_input = array();

        if (isset($input['enabled'])) {
            $new_input['enabled'] = absint($input['enabled']);
        }

        if (isset($input['delete_data'])) {
            $new_input['delete_data'] = absint($input['delete_data']);
        }

        if (isset($input['debug_mode'])) {
            $new_input['debug_mode'] = absint($input['debug_mode']);
        }

        return $new_input;
    }

    /**
     * Sanitize countries settings.
     *
     * @since    2.0.0
     * @param    array    $input    The input array.
     * @return   array              The sanitized input.
     */
    public function sanitize_countries_settings($input) {
        $new_input = array();

        if (isset($input['enabled_countries']) && is_array($input['enabled_countries'])) {
            $new_input['enabled_countries'] = array_map('sanitize_text_field', $input['enabled_countries']);
        } else {
            $new_input['enabled_countries'] = array();
        }

        return $new_input;
    }

    /**
     * Sanitize advanced settings.
     *
     * @since    2.0.0
     * @param    array    $input    The input array.
     * @return   array              The sanitized input.
     */
    public function sanitize_advanced_settings($input) {
        $new_input = array();

        if (isset($input['cache_duration'])) {
            $new_input['cache_duration'] = absint($input['cache_duration']);
        }

        if (isset($input['bundle_display'])) {
            $new_input['bundle_display'] = sanitize_text_field($input['bundle_display']);
        }

        return $new_input;
    }

    /**
     * Render the settings page.
     *
     * @since    2.0.0
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo admin_url('edit.php?post_type=download&page=edd-custom-vat&tab=general'); ?>" class="nav-tab <?php echo $this->active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <i class="fas fa-cog"></i> <?php _e('General', 'edd-custom-vat'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=download&page=edd-custom-vat&tab=countries'); ?>" class="nav-tab <?php echo $this->active_tab === 'countries' ? 'nav-tab-active' : ''; ?>">
                    <i class="fas fa-globe"></i> <?php _e('Countries', 'edd-custom-vat'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=download&page=edd-custom-vat&tab=import-export'); ?>" class="nav-tab <?php echo $this->active_tab === 'import-export' ? 'nav-tab-active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> <?php _e('Import/Export', 'edd-custom-vat'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=download&page=edd-custom-vat&tab=advanced'); ?>" class="nav-tab <?php echo $this->active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <i class="fas fa-sliders-h"></i> <?php _e('Advanced', 'edd-custom-vat'); ?>
                </a>
            </h2>

            <div class="edd-custom-vat-settings-wrapper">
                <form method="post" action="options.php">
                    <?php
                    switch ($this->active_tab) {
                        case 'countries':
                            settings_fields('edd_custom_vat_countries');
                            do_action('edd_custom_vat_settings_countries');
                            break;
                        case 'import-export':
                            do_action('edd_custom_vat_settings_import_export');
                            break;
                        case 'advanced':
                            settings_fields('edd_custom_vat_advanced');
                            do_action('edd_custom_vat_settings_advanced');
                            break;
                        default: // 'general'
                            settings_fields('edd_custom_vat_general');
                            do_action('edd_custom_vat_settings_general');
                            break;
                    }
                    
                    // Only show submit button for tabs with settings
                    if (in_array($this->active_tab, array('general', 'countries', 'advanced'))) {
                        submit_button();
                    }
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Get general settings.
     *
     * @since    2.0.0
     * @return   array    The general settings.
     */
    public static function get_general_settings() {
        $defaults = array(
            'enabled' => 1,
            'delete_data' => 0,
            'debug_mode' => 0,
        );

        $settings = get_option('edd_custom_vat_general', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get countries settings.
     *
     * @since    2.0.0
     * @return   array    The countries settings.
     */
    public static function get_countries_settings() {
        $defaults = array(
            'enabled_countries' => array('DE'),
        );

        $settings = get_option('edd_custom_vat_countries', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get advanced settings.
     *
     * @since    2.0.0
     * @return   array    The advanced settings.
     */
    public static function get_advanced_settings() {
        $defaults = array(
            'cache_duration' => 3600,
            'bundle_display' => 'detailed',
        );

        $settings = get_option('edd_custom_vat_advanced', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Check if plugin is enabled.
     *
     * @since    2.0.0
     * @return   bool    True if enabled, false otherwise.
     */
    public static function is_enabled() {
        $settings = self::get_general_settings();
        return (bool) $settings['enabled'];
    }

    /**
     * Get enabled countries.
     *
     * @since    2.0.0
     * @return   array    Array of enabled country codes.
     */
    public static function get_enabled_countries() {
        $settings = self::get_countries_settings();
        return $settings['enabled_countries'];
    }

    /**
     * Check if a country is enabled.
     *
     * @since    2.0.0
     * @param    string    $country    Country code.
     * @return   bool                  True if enabled, false otherwise.
     */
    public static function is_country_enabled($country) {
        return in_array($country, self::get_enabled_countries());
    }

    /**
     * Get bundle display mode.
     *
     * @since    2.0.0
     * @return   string    Bundle display mode.
     */
    public static function get_bundle_display_mode() {
        $settings = self::get_advanced_settings();
        return $settings['bundle_display'];
    }

    /**
     * Check if debug mode is enabled.
     *
     * @since    2.0.0
     * @return   bool    True if debug mode is enabled, false otherwise.
     */
    public static function is_debug_mode() {
        $settings = self::get_general_settings();
        return (bool) $settings['debug_mode'];
    }

    /**
     * Check if data should be deleted on uninstall.
     *
     * @since    2.0.0
     * @return   bool    True if data should be deleted, false otherwise.
     */
    public static function should_delete_data() {
        $settings = self::get_general_settings();
        return (bool) $settings['delete_data'];
    }

    /**
     * Get cache duration.
     *
     * @since    2.0.0
     * @return   int    Cache duration in seconds.
     */
    public static function get_cache_duration() {
        $settings = self::get_advanced_settings();
        return absint($settings['cache_duration']);
    }
}
