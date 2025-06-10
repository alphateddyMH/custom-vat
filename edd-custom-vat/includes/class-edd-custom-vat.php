<?php
/**
 * The core plugin class.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The core plugin class.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    2.0.0
     * @access   protected
     * @var      EDD_Custom_VAT_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    2.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    2.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->version = EDD_CUSTOM_VAT_VERSION;
        $this->plugin_name = 'edd-custom-vat';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_tax_hooks();
        $this->define_integration_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    2.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/class-i18n.php';

        /**
         * The class responsible for defining all database operations.
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/class-database.php';

        /**
         * Core admin classes
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-settings.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-settings-general.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-settings-countries.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-settings-advanced.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-product-ui.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-import-export.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/admin/class-documentation.php';

        /**
         * Core public classes
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/public/class-public.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/public/class-checkout.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/public/class-receipt.php';

        /**
         * Tax management classes
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/tax/class-tax-manager.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/tax/class-tax-rates.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/tax/class-tax-calculation.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/tax/class-tax-display.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/tax/class-bundle-tax.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/tax/class-recurring-tax.php';

        /**
         * Invoice and receipt classes
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/invoices/class-invoice-manager.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/invoices/class-invoice-template.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/invoices/class-pdf-invoice.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/invoices/class-email-tags.php';

        /**
         * Integration classes
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/integrations/class-gateway-manager.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/integrations/class-paypal.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/integrations/class-stripe.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/integrations/class-wpml.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/integrations/class-recurring.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/integrations/class-software-licensing.php';

        /**
         * Utility classes
         */
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/utilities/class-cache.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/utilities/class-logger.php';
        require_once EDD_CUSTOM_VAT_PLUGIN_DIR . 'includes/utilities/class-helpers.php';

        $this->loader = new EDD_Custom_VAT_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    2.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new EDD_Custom_VAT_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $admin = new EDD_Custom_VAT_Admin($this->get_plugin_name(), $this->get_version());
        $settings = new EDD_Custom_VAT_Settings();
        $product_ui = new EDD_Custom_VAT_Product_UI();
        $import_export = new EDD_Custom_VAT_Import_Export();
        $documentation = new EDD_Custom_VAT_Documentation();
        $cache = new EDD_Custom_VAT_Cache();

        // Admin assets
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

        // Admin menu and settings
        $this->loader->add_action('admin_menu', $settings, 'add_settings_menu', 10);
        $this->loader->add_action('admin_init', $settings, 'register_settings');
        
        // Settings sections
        $this->loader->add_action('edd_custom_vat_settings_general', new EDD_Custom_VAT_Settings_General(), 'render');
        $this->loader->add_action('edd_custom_vat_settings_countries', new EDD_Custom_VAT_Settings_Countries(), 'render');
        $this->loader->add_action('edd_custom_vat_settings_advanced', new EDD_Custom_VAT_Settings_Advanced(), 'render');

        // Product editor UI
        $this->loader->add_action('edd_meta_box_fields', $product_ui, 'add_tax_rate_fields', 30);
        $this->loader->add_action('edd_save_download', $product_ui, 'save_tax_rate_fields', 10, 2);
        $this->loader->add_action('wp_ajax_edd_custom_vat_add_tax_rate', $product_ui, 'ajax_add_tax_rate');
        $this->loader->add_action('wp_ajax_edd_custom_vat_remove_tax_rate', $product_ui, 'ajax_remove_tax_rate');
        $this->loader->add_action('wp_ajax_edd_custom_vat_get_country_rates', $product_ui, 'ajax_get_country_rates');

        // Import/Export hooks
        $this->loader->add_action('admin_init', $import_export, 'process_export');
        $this->loader->add_action('admin_init', $import_export, 'process_import');
        $this->loader->add_action('edd_custom_vat_settings_import_export', $import_export, 'render_import_export_section');

        // Documentation hooks
        $this->loader->add_action('admin_menu', $documentation, 'add_documentation_page', 20);
        
        // Cache management
        $this->loader->add_action('save_post', $cache, 'invalidate_product_cache', 10, 3);
        $this->loader->add_action('updated_option', $cache, 'invalidate_settings_cache', 10, 3);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $public = new EDD_Custom_VAT_Public($this->get_plugin_name(), $this->get_version());
        $checkout = new EDD_Custom_VAT_Checkout();
        $receipt = new EDD_Custom_VAT_Receipt();

        // Public assets
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');

        // Checkout hooks
        $this->loader->add_filter('edd_checkout_cart_columns', $checkout, 'modify_checkout_cart_columns', 10, 1);
        $this->loader->add_filter('edd_get_cart_item_template', $checkout, 'modify_cart_item_template', 10, 2);
        $this->loader->add_filter('edd_cart_item_price_label', $checkout, 'modify_cart_item_price_label', 10, 3);
    }

    /**
     * Register all of the hooks related to tax calculations and management.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_tax_hooks() {
        // Tax classes will register their own hooks with late priority
        // to ensure they run after EDD Geo Targeting
    }

    /**
     * Register all of the hooks related to integrations with other plugins and services.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_integration_hooks() {
        $invoice_manager = new EDD_Custom_VAT_Invoice_Manager();
        $invoice_template = new EDD_Custom_VAT_Invoice_Template();
        $pdf_invoice = new EDD_Custom_VAT_PDF_Invoice();
        $email_tags = new EDD_Custom_VAT_Email_Tags();
        
        $gateway_manager = new EDD_Custom_VAT_Gateway_Manager();
        $wpml = new EDD_Custom_VAT_WPML();
        $recurring = new EDD_Custom_VAT_Recurring();
        $software_licensing = new EDD_Custom_VAT_Software_Licensing();

        // Invoice hooks
        $this->loader->add_filter('edd_invoice_line_items', $invoice_template, 'modify_invoice_line_items', 10, 3);
        $this->loader->add_filter('edd_invoice_get_payment_meta', $invoice_template, 'add_tax_details_to_meta', 10, 2);
        
        // PDF Invoice hooks
        $this->loader->add_filter('edd_pdf_invoice_template', $pdf_invoice, 'modify_pdf_invoice_template', 10, 2);
        $this->loader->add_filter('edd_pdf_invoice_footer', $pdf_invoice, 'add_tax_details_to_footer', 10, 2);
        
        // Payment hooks
        $this->loader->add_filter('edd_insert_payment_args', $gateway_manager, 'ensure_tax_data_in_payment', 999);
        
        // WPML integration
        $this->loader->add_action('wpml_after_save_post', $wpml, 'sync_tax_rates', 10, 3);
        $this->loader->add_filter('edd_custom_vat_product_tax_rates', $wpml, 'get_translated_tax_rates', 10, 2);
        
        // Recurring payments integration
        $this->loader->add_filter('edd_recurring_subscription_pre_gateway_args', $recurring, 'modify_subscription_args', 10, 2);
        $this->loader->add_action('edd_recurring_post_create_payment_profiles', $recurring, 'store_subscription_tax_rates', 10, 4);
        
        // Software licensing integration
        $this->loader->add_filter('edd_sl_license_renewal_args', $software_licensing, 'modify_renewal_args', 10, 2);
        $this->loader->add_filter('edd_sl_get_renewal_cart_item_details', $software_licensing, 'modify_renewal_cart_item', 10, 3);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    2.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     2.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     2.0.0
     * @return    EDD_Custom_VAT_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     2.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
