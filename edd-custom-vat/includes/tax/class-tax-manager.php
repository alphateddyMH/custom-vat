<?php
/**
 * The tax manager functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The tax manager functionality of the plugin.
 *
 * Manages tax rates and calculations.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Tax_Manager {

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
     */
    public function __construct() {
        $this->db = new EDD_Custom_VAT_Database();
        $this->logger = new EDD_Custom_VAT_Logger();
        
        // Register late-priority hooks to ensure we run after EDD Geo Targeting
        add_action('init', array($this, 'register_late_hooks'), 999);
    }
    
    /**
     * Register hooks with late priority to ensure they run after EDD Geo Targeting.
     *
     * @since    2.0.0
     */
    public function register_late_hooks() {
        // Use a very high priority (999) to ensure our filter runs after EDD's geo targeting
        add_filter('edd_get_tax_rate', array($this, 'modify_tax_rate'), 999, 3);
        add_filter('edd_cart_tax', array($this, 'calculate_cart_tax'), 999, 2);
        add_filter('edd_get_cart_content_details', array($this, 'modify_cart_content_details'), 999, 1);
        add_filter('edd_cart_item_tax', array($this, 'calculate_item_tax'), 999, 3);
    }

    /**
     * Modify the tax rate for a specific product and country.
     *
     * @since    2.0.0
     * @param    float     $rate         The original tax rate.
     * @param    string    $country      The country code.
     * @param    array     $cart_item    The cart item data.
     * @return   float                   The modified tax rate.
     */
    public function modify_tax_rate($rate, $country, $cart_item = null) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $rate;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $rate;
        }

        // If no cart item, return the original rate
        if (empty($cart_item) || !isset($cart_item['id'])) {
            return $rate;
        }

        $download_id = $cart_item['id'];

        // Check if this is a bundle item
        if (isset($cart_item['parent']) && !empty($cart_item['parent'])) {
            // For bundle items, we need to check if we should use the bundle's tax rate
            $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
            
            if ('simple' === $bundle_display) {
                // Use the bundle's tax rate
                $download_id = $cart_item['parent'];
            }
        }

        // Get custom tax rate for this product and country
        $custom_rate = $this->db->get_tax_rate($download_id, $country);

        // If a custom rate exists, use it
        if (false !== $custom_rate) {
            // Log the tax rate change if debug mode is enabled
            if (EDD_Custom_VAT_Settings::is_debug_mode()) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: country code, 3: original rate, 4: custom rate */
                        __('Modified tax rate for product #%1$d in country %2$s: %3$f%% -> %4$f%%', 'edd-custom-vat'),
                        $download_id,
                        $country,
                        $rate,
                        $custom_rate
                    )
                );
            }
            
            return $custom_rate;
        }

        return $rate;
    }

    /**
     * Calculate tax for a cart item.
     *
     * @since    2.0.0
     * @param    float     $tax          The original tax amount.
     * @param    float     $price        The item price.
     * @param    array     $cart_item    The cart item data.
     * @return   float                   The calculated tax amount.
     */
    public function calculate_item_tax($tax, $price, $cart_item) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $tax;
        }

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $tax;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $tax;
        }

        // Get the download ID
        $download_id = isset($cart_item['id']) ? $cart_item['id'] : 0;
        if (empty($download_id)) {
            return $tax;
        }

        // Check if this is a bundle item
        $is_bundle_item = isset($cart_item['parent']) && !empty($cart_item['parent']);
        $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
        
        if ($is_bundle_item && 'simple' === $bundle_display) {
            // Use the bundle's tax rate
            $download_id = $cart_item['parent'];
        }

        // Get custom tax rate
        $custom_rate = $this->db->get_tax_rate($download_id, $country);
        
        // If no custom rate, return the original tax
        if (false === $custom_rate) {
            return $tax;
        }

        // Calculate tax based on custom rate
        $tax_amount = $price * ($custom_rate / 100);
        
        // Log the calculation if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: country code, 3: price, 4: tax rate, 5: tax amount */
                    __('Calculated tax for product #%1$d in country %2$s: %3$f * %4$f%% = %5$f', 'edd-custom-vat'),
                    $download_id,
                    $country,
                    $price,
                    $custom_rate,
                    $tax_amount
                )
            );
        }
        
        return $tax_amount;
    }

    /**
     * Calculate the total tax for the cart.
     *
     * @since    2.0.0
     * @param    float    $tax         The original tax amount.
     * @param    float    $subtotal    The cart subtotal.
     * @return   float                 The calculated tax amount.
     */
    public function calculate_cart_tax($tax, $subtotal) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $tax;
        }

        // Get cart contents
        $cart_contents = edd_get_cart_content_details();
        if (empty($cart_contents)) {
            return $tax;
        }

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $tax;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $tax;
        }

        // Calculate tax for each item
        $total_tax = 0;
        foreach ($cart_contents as $item) {
            // Get the item price
            $item_price = isset($item['price']) ? $item['price'] : 0;
            
            // Calculate tax for this item
            $item_tax = $this->calculate_item_tax(0, $item_price, $item);
            
            // Add to total
            $total_tax += $item_tax;
        }
        
        // Log the calculation if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: country code, 2: total tax amount */
                    __('Calculated total cart tax for country %1$s: %2$f', 'edd-custom-vat'),
                    $country,
                    $total_tax
                )
            );
        }
        
        return $total_tax;
    }

    /**
     * Modify cart content details to include custom tax rates.
     *
     * @since    2.0.0
     * @param    array    $cart_items    The cart items.
     * @return   array                   The modified cart items.
     */
    public function modify_cart_content_details($cart_items) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $cart_items;
        }

        // Get the customer's country
        $country = $this->get_customer_country();
        if (empty($country)) {
            return $cart_items;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $cart_items;
        }

        // Loop through cart items
        foreach ($cart_items as $key => $item) {
            // Get the download ID
            $download_id = isset($item['id']) ? $item['id'] : 0;
            if (empty($download_id)) {
                continue;
            }

            // Check if this is a bundle item
            $is_bundle_item = isset($item['parent']) && !empty($item['parent']);
            $bundle_display = EDD_Custom_VAT_Settings::get_bundle_display_mode();
            
            if ($is_bundle_item && 'simple' === $bundle_display) {
                // Use the bundle's tax rate
                $download_id = $item['parent'];
            }

            // Get custom tax rate
            $custom_rate = $this->db->get_tax_rate($download_id, $country);
            
            // If a custom rate exists, store it in the item data
            if (false !== $custom_rate) {
                $cart_items[$key]['tax_rate'] = $custom_rate;
                
                // Recalculate tax
                $price = isset($item['price']) ? $item['price'] : 0;
                $cart_items[$key]['tax'] = $price * ($custom_rate / 100);
            }
        }
        
        return $cart_items;
    }

    /**
     * Get the customer's country.
     *
     * @since    2.0.0
     * @return   string    The customer's country code.
     */
    private function get_customer_country() {
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
}
