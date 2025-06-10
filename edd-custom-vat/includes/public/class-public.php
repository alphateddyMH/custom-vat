<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/public
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the public-facing side of the site.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/public
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Public {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new EDD_Custom_VAT_Database();
        $this->logger = new EDD_Custom_VAT_Logger();
        
        // Register hooks with late priority to ensure we run after EDD Geo Targeting
        add_action('init', array($this, 'register_hooks'), 999);
    }
    
    /**
     * Register hooks for the public-facing functionality.
     * Using late priority to ensure we run after EDD Geo Targeting.
     *
     * @since    2.0.0
     */
    public function register_hooks() {
        // Register filter hooks for adding tax information with high priority
        add_filter('edd_download_price_after_html', array($this, 'add_tax_info_to_price'), 999, 3);
        add_filter('edd_cart_item_price_html', array($this, 'add_tax_info_to_cart_item'), 999, 3);
        add_filter('edd_cart_subtotal', array($this, 'add_tax_info_to_cart_subtotal'), 999, 1);
        add_filter('edd_cart_total', array($this, 'add_tax_info_to_cart_total'), 999, 1);
        
        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add AJAX handler for country change
        add_action('wp_ajax_edd_custom_vat_update_country', array($this, 'ajax_update_country'));
        add_action('wp_ajax_nopriv_edd_custom_vat_update_country', array($this, 'ajax_update_country'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    2.0.0
     */
    public function enqueue_styles() {
        // Only load on EDD pages
        if (!$this->is_edd_page()) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            EDD_CUSTOM_VAT_PLUGIN_URL . 'assets/css/edd-custom-vat-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    2.0.0
     */
    public function enqueue_scripts() {
        // Only load on EDD pages
        if (!$this->is_edd_page()) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            EDD_CUSTOM_VAT_PLUGIN_URL . 'assets/js/edd-custom-vat-public.js',
            array('jquery'),
            $this->version,
            false
        );
        
        // Localize script with data for JS
        wp_localize_script($this->plugin_name, 'eddCustomVAT', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('edd_custom_vat_public_nonce'),
            'i18n' => array(
                'loading' => __('Loading...', 'edd-custom-vat'),
                'error' => __('Error', 'edd-custom-vat'),
                'success' => __('Success', 'edd-custom-vat'),
            ),
        ));
    }

    /**
     * Check if the current page is an EDD page.
     *
     * @since    2.0.0
     * @return   bool    True if the current page is an EDD page, false otherwise.
     */
    private function is_edd_page() {
        // Check if we're on an EDD page
        if (function_exists('edd_is_checkout') && (
            edd_is_checkout() || 
            edd_is_success_page() || 
            edd_is_failed_transaction_page() || 
            edd_is_purchase_history_page()
        )) {
            return true;
        }
        
        // Check if we're on a download page
        if (is_singular('download')) {
            return true;
        }
        
        // Check if we're on a download category or tag page
        if (is_tax('download_category') || is_tax('download_tag')) {
            return true;
        }
        
        return false;
    }

    /**
     * Add tax information to download price.
     *
     * @since    2.0.0
     * @param    string    $price_html    The price HTML.
     * @param    int       $download_id   The download ID.
     * @param    array     $options       The price options.
     * @return   string                   The modified price HTML.
     */
    public function add_tax_info_to_price($price_html, $download_id, $options = array()) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $price_html;
        }

        // Check if prices include tax
        $prices_include_tax = edd_prices_include_tax();
        
        // Get the customer's country - use the helper which is compatible with EDD Geo Targeting
        $country = EDD_Custom_VAT_Helpers::get_customer_country();
        if (empty($country)) {
            return $price_html;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $price_html;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return original price HTML
        if (false === $custom_rate) {
            return $price_html;
        }

        // Add tax information to price HTML
        if ($prices_include_tax) {
            $price_html .= ' <span class="edd-custom-vat-tax-info">' . sprintf(__('(incl. %s%% VAT)', 'edd-custom-vat'), $custom_rate) . '</span>';
        } else {
            $price_html .= ' <span class="edd-custom-vat-tax-info">' . sprintf(__('(excl. %s%% VAT)', 'edd-custom-vat'), $custom_rate) . '</span>';
        }
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: country code, 3: tax rate */
                    __('Applied tax rate of %3$f%% to product #%1$d for country %2$s in frontend display', 'edd-custom-vat'),
                    $download_id,
                    $country,
                    $custom_rate
                )
            );
        }
        
        return $price_html;
    }

    /**
     * Add tax information to cart item price.
     *
     * @since    2.0.0
     * @param    string    $price_html    The price HTML.
     * @param    array     $cart_item     The cart item.
     * @param    bool      $is_checkout   Whether this is the checkout page.
     * @return   string                   The modified price HTML.
     */
    public function add_tax_info_to_cart_item($price_html, $cart_item, $is_checkout = false) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $price_html;
        }

        // Check if prices include tax
        $prices_include_tax = edd_prices_include_tax();
        
        // Get the download ID
        $download_id = isset($cart_item['id']) ? $cart_item['id'] : 0;
        if (empty($download_id)) {
            return $price_html;
        }

        // Get the customer's country - use the helper which is compatible with EDD Geo Targeting
        $country = EDD_Custom_VAT_Helpers::get_customer_country();
        if (empty($country)) {
            return $price_html;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $price_html;
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return original price HTML
        if (false === $custom_rate) {
            return $price_html;
        }

        // Add tax information to price HTML
        if ($prices_include_tax) {
            $price_html .= ' <span class="edd-custom-vat-tax-info">' . sprintf(__('(incl. %s%% VAT)', 'edd-custom-vat'), $custom_rate) . '</span>';
        } else {
            $price_html .= ' <span class="edd-custom-vat-tax-info">' . sprintf(__('(excl. %s%% VAT)', 'edd-custom-vat'), $custom_rate) . '</span>';
        }
        
        return $price_html;
    }

    /**
     * Add tax information to the cart subtotal.
     *
     * @since    2.0.0
     * @param    string    $subtotal    The cart subtotal.
     * @return   string                 The modified cart subtotal.
     */
    public function add_tax_info_to_cart_subtotal($subtotal) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $subtotal;
        }

        // Check if prices include tax
        $prices_include_tax = edd_prices_include_tax();
        
        // Get tax summary
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // If we don't have a tax summary or only have one tax rate, return original subtotal
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $subtotal;
        }

        // Add tax information to subtotal
        if ($prices_include_tax) {
            $subtotal .= ' <span class="edd-custom-vat-tax-info">' . __('(incl. VAT)', 'edd-custom-vat') . '</span>';
        } else {
            $subtotal .= ' <span class="edd-custom-vat-tax-info">' . __('(excl. VAT)', 'edd-custom-vat') . '</span>';
        }
        
        return $subtotal;
    }

    /**
     * Add tax information to the cart total.
     *
     * @since    2.0.0
     * @param    string    $total    The cart total.
     * @return   string              The modified cart total.
     */
    public function add_tax_info_to_cart_total($total) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $total;
        }

        // Get tax summary
        $tax_summary = EDD()->session->get('edd_custom_vat_tax_summary');
        
        // If we don't have a tax summary or only have one tax rate, return original total
        if (empty($tax_summary) || count($tax_summary) <= 1) {
            return $total;
        }

        // Add tax information to total
        $total .= ' <span class="edd-custom-vat-tax-info">' . __('(incl. VAT)', 'edd-custom-vat') . '</span>';
        
        return $total;
    }

    /**
     * AJAX handler for country change.
     * This ensures tax rates are updated when the customer changes their country.
     *
     * @since    2.0.0
     */
    public function ajax_update_country() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edd_custom_vat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check if we have a country
        if (!isset($_POST['country']) || empty($_POST['country'])) {
            wp_send_json_error(__('No country specified.', 'edd-custom-vat'));
        }

        // Get the country
        $country = sanitize_text_field($_POST['country']);

        // Force EDD to recalculate taxes with the new country
        if (function_exists('edd_recalculate_taxes')) {
            edd_recalculate_taxes();
        }

        // Clear tax summary from session
        EDD()->session->set('edd_custom_vat_tax_summary', null);

        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: %s: country code */
                    __('Country changed to %s via AJAX, taxes recalculated', 'edd-custom-vat'),
                    $country
                )
            );
        }

        wp_send_json_success(array(
            'message' => __('Country updated and taxes recalculated.', 'edd-custom-vat'),
            'country' => $country
        ));
    }
}
