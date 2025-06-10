<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the admin area functionality of the plugin.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    2.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Only load on EDD download pages or our settings page
        if (!$screen || (!in_array($screen->post_type, array('download')) && 
            strpos($screen->id, 'edd-custom-vat') === false)) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            EDD_CUSTOM_VAT_PLUGIN_URL . 'assets/css/edd-custom-vat-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Font Awesome for icons
        wp_enqueue_style(
            $this->plugin_name . '-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            array(),
            '5.15.4',
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    2.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only load on EDD download pages or our settings page
        if (!$screen || (!in_array($screen->post_type, array('download')) && 
            strpos($screen->id, 'edd-custom-vat') === false)) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            EDD_CUSTOM_VAT_PLUGIN_URL . 'assets/js/edd-custom-vat-admin.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-tooltip'),
            $this->version,
            false
        );
        
        // Localize script with data for JS
        wp_localize_script($this->plugin_name, 'eddCustomVAT', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edd_custom_vat_nonce'),
            'i18n' => array(
                'addRate' => __('Add Rate', 'edd-custom-vat'),
                'deleteRate' => __('Delete Rate', 'edd-custom-vat'),
                'country' => __('Country', 'edd-custom-vat'),
                'taxRate' => __('Tax Rate (%)', 'edd-custom-vat'),
                'confirmDelete' => __('Are you sure you want to delete this tax rate?', 'edd-custom-vat'),
                'loading' => __('Loading...', 'edd-custom-vat'),
                'error' => __('Error', 'edd-custom-vat'),
                'success' => __('Success', 'edd-custom-vat'),
            ),
        ));
    }

    /**
     * Add admin menu items.
     *
     * @since    2.0.0
     */
    public function add_admin_menu() {
        // Add submenu page under Downloads
        add_submenu_page(
            'edit.php?post_type=download',
            __('Custom VAT Settings', 'edd-custom-vat'),
            __('Custom VAT', 'edd-custom-vat'),
            'manage_shop_settings',
            'edd-custom-vat',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display the settings page content.
     *
     * @since    2.0.0
     */
    public function display_settings_page() {
        include_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'admin/partials/edd-custom-vat-admin-display.php';
    }

    /**
     * Display admin notices after activation.
     *
     * @since    2.0.0
     */
    public function display_activation_notice() {
        // Check transient
        if (get_transient('edd_custom_vat_activation_notice')) {
            ?>
            <div class="updated notice is-dismissible">
                <p>
                    <?php _e('Thank you for installing EDD Custom VAT per Country! Please visit the', 'edd-custom-vat'); ?>
                    <a href="<?php echo admin_url('edit.php?post_type=download&page=edd-custom-vat'); ?>">
                        <?php _e('settings page', 'edd-custom-vat'); ?>
                    </a>
                    <?php _e('to configure the plugin.', 'edd-custom-vat'); ?>
                </p>
            </div>
            <?php
            // Delete transient
            delete_transient('edd_custom_vat_activation_notice');
        }
    }

    /**
     * Check for plugin updates.
     *
     * @since    2.0.0
     */
    public function check_for_updates() {
        // This would integrate with your update system
        // For example, with EDD Software Licensing
    }

    /**
     * Add links to the plugin row meta.
     *
     * @since    2.0.0
     * @param    array     $links    Plugin Row Meta.
     * @param    string    $file     Plugin Base file.
     * @return   array               Plugin Row Meta.
     */
    public function plugin_row_meta($links, $file) {
        if (EDD_CUSTOM_VAT_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="' . esc_url('https://itmedialaw.com/docs/edd-custom-vat-per-country/') . '" aria-label="' . esc_attr__('View documentation', 'edd-custom-vat') . '">' . esc_html__('Docs', 'edd-custom-vat') . '</a>',
                'support' => '<a href="' . esc_url('https://itmedialaw.com/support/') . '" aria-label="' . esc_attr__('Visit customer support', 'edd-custom-vat') . '">' . esc_html__('Support', 'edd-custom-vat') . '</a>',
            );

            return array_merge($links, $row_meta);
        }

        return $links;
    }

    /**
     * Add action links to the plugin list table.
     *
     * @since    2.0.0
     * @param    array    $links    Plugin Action links.
     * @return   array              Plugin Action links.
     */
    public function plugin_action_links($links) {
        $action_links = array(
            'settings' => '<a href="' . admin_url('edit.php?post_type=download&page=edd-custom-vat') . '" aria-label="' . esc_attr__('View Custom VAT settings', 'edd-custom-vat') . '">' . esc_html__('Settings', 'edd-custom-vat') . '</a>',
        );

        return array_merge($action_links, $links);
    }
}
