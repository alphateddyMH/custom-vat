<?php
/**
 * Database operations for the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Database operations for the plugin.
 *
 * Handles all database interactions for storing and retrieving tax rates.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Database {

    /**
     * The table name for tax rates.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $table_name    The table name for tax rates.
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'edd_custom_vat_rates';
    }

    /**
     * Get tax rate for a specific product and country.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @param    string    $country        The country code.
     * @return   float|bool                The tax rate or false if not found.
     */
    public function get_tax_rate($download_id, $country) {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'edd_custom_vat_rate_' . $download_id . '_' . $country;
        $cached_rate = wp_cache_get($cache_key, 'edd_custom_vat');
        
        if (false !== $cached_rate) {
            return $cached_rate;
        }
        
        $sql = $wpdb->prepare(
            "SELECT tax_rate FROM {$this->table_name} WHERE download_id = %d AND country = %s",
            $download_id,
            $country
        );
        
        $rate = $wpdb->get_var($sql);
        
        if (null !== $rate) {
            // Cache the result
            wp_cache_set($cache_key, (float) $rate, 'edd_custom_vat', 3600);
            return (float) $rate;
        }
        
        return false;
    }

    /**
     * Get all tax rates for a specific product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   array                     Array of tax rates.
     */
    public function get_product_tax_rates($download_id) {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'edd_custom_vat_rates_' . $download_id;
        $cached_rates = wp_cache_get($cache_key, 'edd_custom_vat');
        
        if (false !== $cached_rates) {
            return $cached_rates;
        }
        
        $sql = $wpdb->prepare(
            "SELECT country, tax_rate FROM {$this->table_name} WHERE download_id = %d",
            $download_id
        );
        
        $results = $wpdb->get_results($sql);
        
        $rates = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $rates[$row->country] = (float) $row->tax_rate;
            }
        }
        
        // Cache the result
        wp_cache_set($cache_key, $rates, 'edd_custom_vat', 3600);
        
        return $rates;
    }

    /**
     * Add or update a tax rate for a specific product and country.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @param    string    $country        The country code.
     * @param    float     $tax_rate       The tax rate.
     * @return   bool                      True on success, false on failure.
     */
    public function update_tax_rate($download_id, $country, $tax_rate) {
        global $wpdb;
        
        // Sanitize inputs
        $download_id = absint($download_id);
        $country = sanitize_text_field($country);
        $tax_rate = (float) $tax_rate;
        
        // Check if rate already exists
        $existing_rate = $this->get_tax_rate($download_id, $country);
        
        if (false === $existing_rate) {
            // Insert new rate
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'download_id' => $download_id,
                    'country' => $country,
                    'tax_rate' => $tax_rate,
                    'created' => current_time('mysql'),
                    'modified' => current_time('mysql')
                ),
                array('%d', '%s', '%f', '%s', '%s')
            );
        } else {
            // Update existing rate
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'tax_rate' => $tax_rate,
                    'modified' => current_time('mysql')
                ),
                array(
                    'download_id' => $download_id,
                    'country' => $country
                ),
                array('%f', '%s'),
                array('%d', '%s')
            );
        }
        
        // Clear cache
        $this->clear_rate_cache($download_id, $country);
        
        return false !== $result;
    }

    /**
     * Delete a tax rate for a specific product and country.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @param    string    $country        The country code.
     * @return   bool                      True on success, false on failure.
     */
    public function delete_tax_rate($download_id, $country) {
        global $wpdb;
        
        // Sanitize inputs
        $download_id = absint($download_id);
        $country = sanitize_text_field($country);
        
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'download_id' => $download_id,
                'country' => $country
            ),
            array('%d', '%s')
        );
        
        // Clear cache
        $this->clear_rate_cache($download_id, $country);
        
        return false !== $result;
    }

    /**
     * Delete all tax rates for a specific product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @return   bool                      True on success, false on failure.
     */
    public function delete_product_tax_rates($download_id) {
        global $wpdb;
        
        // Sanitize input
        $download_id = absint($download_id);
        
        $result = $wpdb->delete(
            $this->table_name,
            array('download_id' => $download_id),
            array('%d')
        );
        
        // Clear cache
        $this->clear_product_rate_cache($download_id);
        
        return false !== $result;
    }

    /**
     * Get all tax rates for all products.
     *
     * @since    2.0.0
     * @return   array    Array of tax rates.
     */
    public function get_all_tax_rates() {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'edd_custom_vat_all_rates';
        $cached_rates = wp_cache_get($cache_key, 'edd_custom_vat');
        
        if (false !== $cached_rates) {
            return $cached_rates;
        }
        
        $sql = "SELECT download_id, country, tax_rate FROM {$this->table_name}";
        $results = $wpdb->get_results($sql);
        
        $rates = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                if (!isset($rates[$row->download_id])) {
                    $rates[$row->download_id] = array();
                }
                $rates[$row->download_id][$row->country] = (float) $row->tax_rate;
            }
        }
        
        // Cache the result
        wp_cache_set($cache_key, $rates, 'edd_custom_vat', 3600);
        
        return $rates;
    }

    /**
     * Clear cache for a specific product and country tax rate.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     * @param    string    $country        The country code.
     */
    private function clear_rate_cache($download_id, $country) {
        // Clear specific rate cache
        $cache_key = 'edd_custom_vat_rate_' . $download_id . '_' . $country;
        wp_cache_delete($cache_key, 'edd_custom_vat');
        
        // Clear product rates cache
        $this->clear_product_rate_cache($download_id);
        
        // Clear all rates cache
        wp_cache_delete('edd_custom_vat_all_rates', 'edd_custom_vat');
    }

    /**
     * Clear cache for all tax rates of a specific product.
     *
     * @since    2.0.0
     * @param    int       $download_id    The download ID.
     */
    private function clear_product_rate_cache($download_id) {
        $cache_key = 'edd_custom_vat_rates_' . $download_id;
        wp_cache_delete($cache_key, 'edd_custom_vat');
    }

    /**
     * Export all tax rates to a CSV file.
     *
     * @since    2.0.0
     * @return   string    CSV content.
     */
    public function export_tax_rates() {
        global $wpdb;
        
        $sql = "SELECT d.post_title, tr.download_id, tr.country, tr.tax_rate 
                FROM {$this->table_name} tr
                JOIN {$wpdb->posts} d ON tr.download_id = d.ID
                ORDER BY d.post_title, tr.country";
        
        $results = $wpdb->get_results($sql);
        
        $csv = "Product Name,Product ID,Country,Tax Rate\n";
        
        if (!empty($results)) {
            foreach ($results as $row) {
                $csv .= '"' . str_replace('"', '""', $row->post_title) . '",';
                $csv .= $row->download_id . ',';
                $csv .= $row->country . ',';
                $csv .= $row->tax_rate . "\n";
            }
        }
        
        return $csv;
    }

    /**
     * Import tax rates from CSV data.
     *
     * @since    2.0.0
     * @param    string    $csv_data    CSV data.
     * @return   array                  Import results.
     */
    public function import_tax_rates($csv_data) {
        $lines = explode("\n", $csv_data);
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        // Skip header row
        array_shift($lines);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Parse CSV line
            $data = str_getcsv($line);
            
            if (count($data) < 3) {
                $results['failed']++;
                $results['errors'][] = sprintf(__('Invalid CSV format: %s', 'edd-custom-vat'), $line);
                continue;
            }
            
            // Extract data (format: Product Name, Product ID, Country, Tax Rate)
            $download_id = isset($data[1]) ? absint($data[1]) : 0;
            $country = isset($data[2]) ? sanitize_text_field($data[2]) : '';
            $tax_rate = isset($data[3]) ? (float) $data[3] : 0;
            
            // Validate data
            if (empty($download_id) || empty($country)) {
                $results['failed']++;
                $results['errors'][] = sprintf(__('Invalid data: %s', 'edd-custom-vat'), $line);
                continue;
            }
            
            // Check if product exists
            $post = get_post($download_id);
            if (!$post || 'download' !== $post->post_type) {
                $results['failed']++;
                $results['errors'][] = sprintf(__('Product ID %d does not exist', 'edd-custom-vat'), $download_id);
                continue;
            }
            
            // Update tax rate
            $success = $this->update_tax_rate($download_id, $country, $tax_rate);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf(__('Failed to import tax rate for product ID %d and country %s', 'edd-custom-vat'), $download_id, $country);
            }
        }
        
        return $results;
    }

    /**
     * Delete all tax rates from the database.
     *
     * @since    2.0.0
     * @return   bool    True on success, false on failure.
     */
    public function delete_all_tax_rates() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        // Clear all caches
        wp_cache_delete('edd_custom_vat_all_rates', 'edd_custom_vat');
        
        return false !== $result;
    }
}
