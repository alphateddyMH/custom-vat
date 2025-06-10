<?php
/**
 * The tax rates functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The tax rates functionality of the plugin.
 *
 * Manages tax rates storage and retrieval.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/tax
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Tax_Rates {

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
    }

    /**
     * Get custom tax rates for all products.
     *
     * @since    2.0.0
     * @param    array    $rates    The original tax rates.
     * @return   array              The modified tax rates.
     */
    public function get_custom_tax_rates($rates) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $rates;
        }

        // Get all custom tax rates
        $custom_rates = $this->db->get_all_tax_rates();
        
        // If no custom rates, return original rates
        if (empty($custom_rates)) {
            return $rates;
        }

        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            $this->logger->log(__('Retrieved custom tax rates for all products', 'edd-custom-vat'));
        }
        
        // Merge custom rates with original rates
        // We don't actually modify the EDD rates array here, as we handle the rate modification
        // in the modify_tax_rate method of the Tax_Manager class
        
        return $rates;
    }

    /**
     * Check if a tax rate exists for a specific product and country.
     *
     * @since    2.0.0
     * @param    bool      $exists        Whether the tax rate exists.
     * @param    string    $country       The country code.
     * @param    string    $state         The state code.
     * @return   bool                     Whether the tax rate exists.
     */
    public function check_tax_rate_exists($exists, $country, $state) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return $exists;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return $exists;
        }

        // Get current product ID if we're in the product context
        $download_id = $this->get_current_product_id();
        if (!$download_id) {
            return $exists;
        }

        // Check if a custom rate exists for this product and country
        $rate = $this->db->get_tax_rate($download_id, $country);
        
        if (false !== $rate) {
            // Log if debug mode is enabled
            if (EDD_Custom_VAT_Settings::is_debug_mode()) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: country code, 3: tax rate */
                        __('Found custom tax rate for product #%1$d in country %2$s: %3$f%%', 'edd-custom-vat'),
                        $download_id,
                        $country,
                        $rate
                    )
                );
            }
            
            return true;
        }

        return $exists;
    }

    /**
     * Get tax rates for a specific product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   array                     Array of tax rates.
     */
    public function get_product_tax_rates($download_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return array();
        }

        // Get tax rates from database
        $rates = $this->db->get_product_tax_rates($download_id);
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode() && !empty($rates)) {
            $this->logger->log(
                sprintf(
                    /* translators: 1: download ID, 2: number of rates */
                    __('Retrieved %2$d custom tax rates for product #%1$d', 'edd-custom-vat'),
                    $download_id,
                    count($rates)
                )
            );
        }
        
        // Allow filtering of the rates
        $rates = apply_filters('edd_custom_vat_product_tax_rates', $rates, $download_id);
        
        return $rates;
    }

    /**
     * Set a tax rate for a specific product and country.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @param    string    $country        The country code.
     * @param    float     $rate           The tax rate.
     * @return   bool                      True on success, false on failure.
     */
    public function set_product_tax_rate($download_id, $country, $rate) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return false;
        }

        // Check if country is enabled
        if (!EDD_Custom_VAT_Settings::is_country_enabled($country)) {
            return false;
        }

        // Update tax rate in database
        $result = $this->db->update_tax_rate($download_id, $country, $rate);
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            if ($result) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: country code, 3: tax rate */
                        __('Set custom tax rate for product #%1$d in country %2$s: %3$f%%', 'edd-custom-vat'),
                        $download_id,
                        $country,
                        $rate
                    )
                );
            } else {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: country code */
                        __('Failed to set custom tax rate for product #%1$d in country %2$s', 'edd-custom-vat'),
                        $download_id,
                        $country
                    )
                );
            }
        }
        
        return $result;
    }

    /**
     * Delete a tax rate for a specific product and country.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @param    string    $country        The country code.
     * @return   bool                      True on success, false on failure.
     */
    public function delete_product_tax_rate($download_id, $country) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return false;
        }

        // Delete tax rate from database
        $result = $this->db->delete_tax_rate($download_id, $country);
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            if ($result) {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: country code */
                        __('Deleted custom tax rate for product #%1$d in country %2$s', 'edd-custom-vat'),
                        $download_id,
                        $country
                    )
                );
            } else {
                $this->logger->log(
                    sprintf(
                        /* translators: 1: download ID, 2: country code */
                        __('Failed to delete custom tax rate for product #%1$d in country %2$s', 'edd-custom-vat'),
                        $download_id,
                        $country
                    )
                );
            }
        }
        
        return $result;
    }

    /**
     * Delete all tax rates for a specific product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   bool                      True on success, false on failure.
     */
    public function delete_all_product_tax_rates($download_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return false;
        }

        // Delete all tax rates for this product
        $result = $this->db->delete_product_tax_rates($download_id);
        
        // Log if debug mode is enabled
        if (EDD_Custom_VAT_Settings::is_debug_mode()) {
            if ($result) {
                $this->logger->log(
                    sprintf(
                        /* translators: %d: download ID */
                        __('Deleted all custom tax rates for product #%d', 'edd-custom-vat'),
                        $download_id
                    )
                );
            } else {
                $this->logger->log(
                    sprintf(
                        /* translators: %d: download ID */
                        __('Failed to delete all custom tax rates for product #%d', 'edd-custom-vat'),
                        $download_id
                    )
                );
            }
        }
        
        return $result;
    }

    /**
     * Get the current product ID.
     *
     * @since    2.0.0
     * @return   int|bool    The product ID or false if not in product context.
     */
    private function get_current_product_id() {
        global $post;
        
        // Check if we're in the admin area
        if (is_admin() && isset($post) && 'download' === $post->post_type) {
            return $post->ID;
        }
        
        // Check if we're in a single download page
        if (is_singular('download')) {
            return get_the_ID();
        }
        
        // Check if we're in a cart or checkout context
        if (function_exists('edd_is_checkout') && edd_is_checkout()) {
            // Try to get the product ID from the cart
            $cart_contents = function_exists('edd_get_cart_contents') ? edd_get_cart_contents() : array();
            if (!empty($cart_contents)) {
                // Return the ID of the first item in the cart
                // This is not ideal, but it's the best we can do without more context
                return $cart_contents[0]['id'];
            }
        }
        
        return false;
    }
}
