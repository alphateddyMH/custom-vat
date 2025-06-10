<?php
/**
 * The advanced settings functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The advanced settings functionality of the plugin.
 *
 * Handles the advanced settings tab.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Settings_Advanced {

    /**
     * Render the advanced settings section.
     *
     * @since    2.0.0
     */
    public function render() {
        $settings = EDD_Custom_VAT_Settings::get_advanced_settings();
        ?>
        <div class="edd-custom-vat-settings-section">
            <h2><?php _e('Advanced Settings', 'edd-custom-vat'); ?></h2>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="edd_custom_vat_advanced[cache_duration]"><?php _e('Cache Duration', 'edd-custom-vat'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="edd_custom_vat_advanced[cache_duration]" name="edd_custom_vat_advanced[cache_duration]" value="<?php echo esc_attr($settings['cache_duration']); ?>" min="0" step="1" />
                            <span class="description"><?php _e('Duration in seconds to cache tax rates. Set to 0 to disable caching.', 'edd-custom-vat'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edd_custom_vat_advanced[bundle_display]"><?php _e('Bundle Display Mode', 'edd-custom-vat'); ?></label>
                        </th>
                        <td>
                            <select id="edd_custom_vat_advanced[bundle_display]" name="edd_custom_vat_advanced[bundle_display]">
                                <option value="detailed" <?php selected('detailed', $settings['bundle_display']); ?>><?php _e('Detailed (Show each item with its tax rate)', 'edd-custom-vat'); ?></option>
                                <option value="summarized" <?php selected('summarized', $settings['bundle_display']); ?>><?php _e('Summarized (Group by tax rate)', 'edd-custom-vat'); ?></option>
                                <option value="simple" <?php selected('simple', $settings['bundle_display']); ?>><?php _e('Simple (Use bundle tax rate for all items)', 'edd-custom-vat'); ?></option>
                            </select>
                            <span class="description"><?php _e('How to display tax rates for bundle products in receipts and invoices.', 'edd-custom-vat'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php _e('Performance Settings', 'edd-custom-vat'); ?></h3>
            
            <div class="edd-custom-vat-performance-tools">
                <button type="button" class="button" id="edd-custom-vat-clear-cache"><?php _e('Clear Tax Rate Cache', 'edd-custom-vat'); ?></button>
                <span class="spinner"></span>
                <div class="edd-custom-vat-cache-message"></div>
            </div>
            
            <h3><?php _e('Compatibility Settings', 'edd-custom-vat'); ?></h3>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="edd_custom_vat_advanced[wpml_sync]"><?php _e('WPML Synchronization', 'edd-custom-vat'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="edd_custom_vat_advanced[wpml_sync]" name="edd_custom_vat_advanced[wpml_sync]" value="1" <?php checked(isset($settings['wpml_sync']) ? $settings['wpml_sync'] : 0); ?> />
                            <span class="description"><?php _e('Synchronize tax rates across WPML translations of products.', 'edd-custom-vat'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="edd-custom-vat-info-box edd-custom-vat-warning">
                <h3><i class="fas fa-exclamation-triangle"></i> <?php _e('Advanced Settings Warning', 'edd-custom-vat'); ?></h3>
                <p><?php _e('These settings are for advanced users. Incorrect configuration may affect your store\'s performance or tax calculations. If you\'re unsure about these settings, please leave them at their default values.', 'edd-custom-vat'); ?></p>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Clear cache button functionality
                $('#edd-custom-vat-clear-cache').on('click', function() {
                    var $button = $(this);
                    var $spinner = $button.next('.spinner');
                    var $message = $('.edd-custom-vat-cache-message');
                    
                    $button.prop('disabled', true);
                    $spinner.css('visibility', 'visible');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'edd_custom_vat_clear_cache',
                            nonce: eddCustomVAT.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $message.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                            } else {
                                $message.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                            }
                        },
                        error: function() {
                            $message.html('<div class="notice notice-error inline"><p><?php echo esc_js(__('An error occurred while clearing the cache.', 'edd-custom-vat')); ?></p></div>');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                            $spinner.css('visibility', 'hidden');
                            
                            // Hide message after 5 seconds
                            setTimeout(function() {
                                $message.html('');
                            }, 5000);
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
