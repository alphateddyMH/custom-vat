<?php
/**
 * The product UI functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The product UI functionality of the plugin.
 *
 * Handles the UI for setting tax rates on products.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Product_UI {

    /**
     * Database instance.
     *
     * @since    2.0.0
     * @access   private
     * @var      EDD_Custom_VAT_Database    $db    Database instance.
     */
    private $db;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->db = new EDD_Custom_VAT_Database();
    }

    /**
     * Add tax rate fields to the product editor.
     *
     * @since    2.0.0
     * @param    int    $post_id    The post ID.
     */
    public function add_tax_rate_fields($post_id) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Get enabled countries
        $enabled_countries = EDD_Custom_VAT_Settings::get_enabled_countries();
        if (empty($enabled_countries)) {
            return;
        }

        // Get all countries
        $all_countries = $this->get_countries();
        
        // Get existing tax rates for this product
        $tax_rates = $this->db->get_product_tax_rates($post_id);
        
        // Start output
        ?>
        <div id="edd-custom-vat-rates" class="edd-custom-vat-product-section">
            <div class="edd-custom-vat-header">
                <h3><i class="fas fa-percentage"></i> <?php _e('Custom VAT Rates', 'edd-custom-vat'); ?></h3>
                <p class="description"><?php _e('Set custom VAT rates for specific countries. If no rate is set for a country, the default EDD tax rate will be used.', 'edd-custom-vat'); ?></p>
            </div>
            
            <div class="edd-custom-vat-rates-wrapper">
                <table class="edd-custom-vat-rates-table widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?php _e('Country', 'edd-custom-vat'); ?></th>
                            <th><?php _e('Tax Rate (%)', 'edd-custom-vat'); ?></th>
                            <th><?php _e('Actions', 'edd-custom-vat'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="edd-custom-vat-rates-rows">
                        <?php
                        if (!empty($tax_rates)) {
                            foreach ($tax_rates as $country => $rate) {
                                $country_name = isset($all_countries[$country]) ? $all_countries[$country] : $country;
                                $this->render_tax_rate_row($country, $country_name, $rate);
                            }
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <div class="edd-custom-vat-add-rate">
                                    <select id="edd-custom-vat-country">
                                        <option value=""><?php _e('-- Select Country --', 'edd-custom-vat'); ?></option>
                                        <?php
                                        foreach ($enabled_countries as $country_code) {
                                            if (isset($all_countries[$country_code]) && !isset($tax_rates[$country_code])) {
                                                echo '<option value="' . esc_attr($country_code) . '">' . esc_html($all_countries[$country_code]) . ' (' . esc_html($country_code) . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <input type="number" id="edd-custom-vat-rate" placeholder="<?php esc_attr_e('Tax Rate (%)', 'edd-custom-vat'); ?>" step="0.01" min="0" max="100" />
                                    <button type="button" id="edd-custom-vat-add-rate" class="button button-secondary">
                                        <i class="fas fa-plus"></i> <?php _e('Add Rate', 'edd-custom-vat'); ?>
                                    </button>
                                    <span class="spinner"></span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                <input type="hidden" name="edd_custom_vat_rates" id="edd-custom-vat-rates-data" value="<?php echo esc_attr(json_encode($tax_rates)); ?>" />
                <input type="hidden" name="edd_custom_vat_nonce" value="<?php echo wp_create_nonce('edd_custom_vat_save_rates'); ?>" />
            </div>
            
            <div class="edd-custom-vat-info">
                <p><i class="fas fa-info-circle"></i> <?php _e('Tax rates are applied based on the customer\'s billing country. For EU countries, make sure to set appropriate rates for digital goods.', 'edd-custom-vat'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render a tax rate row.
     *
     * @since    2.0.0
     * @param    string    $country_code    The country code.
     * @param    string    $country_name    The country name.
     * @param    float     $rate            The tax rate.
     */
    private function render_tax_rate_row($country_code, $country_name, $rate) {
        ?>
        <tr class="edd-custom-vat-rate-row" data-country="<?php echo esc_attr($country_code); ?>">
            <td class="edd-custom-vat-country">
                <?php echo esc_html($country_name); ?> (<?php echo esc_html($country_code); ?>)
            </td>
            <td class="edd-custom-vat-rate">
                <input type="number" name="edd_custom_vat_rate[<?php echo esc_attr($country_code); ?>]" value="<?php echo esc_attr($rate); ?>" step="0.01" min="0" max="100" />
            </td>
            <td class="edd-custom-vat-actions">
                <button type="button" class="button button-small edd-custom-vat-remove-rate" data-country="<?php echo esc_attr($country_code); ?>">
                    <i class="fas fa-trash-alt"></i> <?php _e('Remove', 'edd-custom-vat'); ?>
                </button>
            </td>
        </tr>
        <?php
    }

    /**
     * Save tax rate fields.
     *
     * @since    2.0.0
     * @param    int    $post_id    The post ID.
     * @param    array  $post_data  The post data.
     */
    public function save_tax_rate_fields($post_id, $post_data) {
        // Check if plugin is enabled
        if (!EDD_Custom_VAT_Settings::is_enabled()) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['edd_custom_vat_nonce']) || !wp_verify_nonce($_POST['edd_custom_vat_nonce'], 'edd_custom_vat_save_rates')) {
            return;
        }

        // Check if we have tax rates data
        if (!isset($_POST['edd_custom_vat_rates'])) {
            return;
        }

        // Get and decode tax rates data
        $tax_rates = json_decode(stripslashes($_POST['edd_custom_vat_rates']), true);
        
        if (!is_array($tax_rates)) {
            $tax_rates = array();
        }

        // Get existing tax rates
        $existing_rates = $this->db->get_product_tax_rates($post_id);
        
        // Process tax rates
        foreach ($tax_rates as $country => $rate) {
            $this->db->update_tax_rate($post_id, $country, $rate);
            
            // Remove from existing rates
            if (isset($existing_rates[$country])) {
                unset($existing_rates[$country]);
            }
        }
        
        // Delete any remaining existing rates (they were removed)
        foreach ($existing_rates as $country => $rate) {
            $this->db->delete_tax_rate($post_id, $country);
        }
        
        // Clear cache
        $this->clear_cache($post_id);
    }

    /**
     * AJAX handler for adding a tax rate.
     *
     * @since    2.0.0
     */
    public function ajax_add_tax_rate() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edd_custom_vat_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check required fields
        if (!isset($_POST['download_id']) || !isset($_POST['country']) || !isset($_POST['rate'])) {
            wp_send_json_error(__('Missing required fields.', 'edd-custom-vat'));
        }

        // Sanitize inputs
        $download_id = absint($_POST['download_id']);
        $country = sanitize_text_field($_POST['country']);
        $rate = (float) $_POST['rate'];

        // Validate download ID
        $download = get_post($download_id);
        if (!$download || 'download' !== $download->post_type) {
            wp_send_json_error(__('Invalid download ID.', 'edd-custom-vat'));
        }

        // Validate country
        $enabled_countries = EDD_Custom_VAT_Settings::get_enabled_countries();
        if (!in_array($country, $enabled_countries)) {
            wp_send_json_error(__('Invalid country.', 'edd-custom-vat'));
        }

        // Validate rate
        if ($rate < 0 || $rate > 100) {
            wp_send_json_error(__('Tax rate must be between 0 and 100.', 'edd-custom-vat'));
        }

        // Save tax rate
        $result = $this->db->update_tax_rate($download_id, $country, $rate);

        if ($result) {
            // Get country name
            $countries = $this->get_countries();
            $country_name = isset($countries[$country]) ? $countries[$country] : $country;
            
            // Clear cache
            $this->clear_cache($download_id);
            
            // Return success with row HTML
            ob_start();
            $this->render_tax_rate_row($country, $country_name, $rate);
            $row_html = ob_get_clean();
            
            wp_send_json_success(array(
                'message' => __('Tax rate added successfully.', 'edd-custom-vat'),
                'row_html' => $row_html,
                'country' => $country
            ));
        } else {
            wp_send_json_error(__('Failed to add tax rate.', 'edd-custom-vat'));
        }
    }

    /**
     * AJAX handler for removing a tax rate.
     *
     * @since    2.0.0
     */
    public function ajax_remove_tax_rate() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edd_custom_vat_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check required fields
        if (!isset($_POST['download_id']) || !isset($_POST['country'])) {
            wp_send_json_error(__('Missing required fields.', 'edd-custom-vat'));
        }

        // Sanitize inputs
        $download_id = absint($_POST['download_id']);
        $country = sanitize_text_field($_POST['country']);

        // Validate download ID
        $download = get_post($download_id);
        if (!$download || 'download' !== $download->post_type) {
            wp_send_json_error(__('Invalid download ID.', 'edd-custom-vat'));
        }

        // Delete tax rate
        $result = $this->db->delete_tax_rate($download_id, $country);

        if ($result) {
            // Clear cache
            $this->clear_cache($download_id);
            
            wp_send_json_success(array(
                'message' => __('Tax rate removed successfully.', 'edd-custom-vat'),
                'country' => $country
            ));
        } else {
            wp_send_json_error(__('Failed to remove tax rate.', 'edd-custom-vat'));
        }
    }

    /**
     * AJAX handler for getting country rates.
     *
     * @since    2.0.0
     */
    public function ajax_get_country_rates() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edd_custom_vat_nonce')) {
            wp_send_json_error(__('Security check failed.', 'edd-custom-vat'));
        }

        // Check required fields
        if (!isset($_POST['download_id'])) {
            wp_send_json_error(__('Missing download ID.', 'edd-custom-vat'));
        }

        // Sanitize input
        $download_id = absint($_POST['download_id']);

        // Validate download ID
        $download = get_post($download_id);
        if (!$download || 'download' !== $download->post_type) {
            wp_send_json_error(__('Invalid download ID.', 'edd-custom-vat'));
        }

        // Get tax rates
        $tax_rates = $this->db->get_product_tax_rates($download_id);

        wp_send_json_success(array(
            'tax_rates' => $tax_rates
        ));
    }

    /**
     * Get all countries.
     *
     * @since    2.0.0
     * @return   array    Array of countries.
     */
    private function get_countries() {
        if (function_exists('edd_get_country_list')) {
            return edd_get_country_list();
        }
        
        // Fallback if EDD function is not available
        return array(
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            // Add more countries as needed
        );
    }

    /**
     * Clear cache for a product.
     *
     * @since    2.0.0
     * @param    int    $download_id    The download ID.
     */
    private function clear_cache($download_id) {
        $cache = new EDD_Custom_VAT_Cache();
        $cache->invalidate_product_cache($download_id);
    }
}
