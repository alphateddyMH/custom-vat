<?php
/**
 * The general settings functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The general settings functionality of the plugin.
 *
 * Handles the general settings tab.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Settings_General {

    /**
     * Render the general settings section.
     *
     * @since    2.0.0
     */
    public function render() {
        $settings = EDD_Custom_VAT_Settings::get_general_settings();
        ?>
        <div class="edd-custom-vat-settings-section">
            <h2><?php _e('General Settings', 'edd-custom-vat'); ?></h2>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="edd_custom_vat_general[enabled]"><?php _e('Enable Custom VAT', 'edd-custom-vat'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="edd_custom_vat_general[enabled]" name="edd_custom_vat_general[enabled]" value="1" <?php checked(1, $settings['enabled']); ?> />
                            <span class="description"><?php _e('Enable or disable the custom VAT functionality.', 'edd-custom-vat'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edd_custom_vat_general[delete_data]"><?php _e('Delete Data on Uninstall', 'edd-custom-vat'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="edd_custom_vat_general[delete_data]" name="edd_custom_vat_general[delete_data]" value="1" <?php checked(1, $settings['delete_data']); ?> />
                            <span class="description"><?php _e('Delete all plugin data when uninstalling the plugin.', 'edd-custom-vat'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edd_custom_vat_general[debug_mode]"><?php _e('Debug Mode', 'edd-custom-vat'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="edd_custom_vat_general[debug_mode]" name="edd_custom_vat_general[debug_mode]" value="1" <?php checked(1, $settings['debug_mode']); ?> />
                            <span class="description"><?php _e('Enable debug mode for troubleshooting.', 'edd-custom-vat'); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="edd-custom-vat-info-box">
                <h3><i class="fas fa-info-circle"></i> <?php _e('How It Works', 'edd-custom-vat'); ?></h3>
                <p><?php _e('EDD Custom VAT per Country allows you to set different VAT rates for each product and country. When no custom rate is defined for a product/country combination, the default EDD tax rate will be used.', 'edd-custom-vat'); ?></p>
                <p><?php _e('To set custom VAT rates for a product:', 'edd-custom-vat'); ?></p>
                <ol>
                    <li><?php _e('Edit a download product', 'edd-custom-vat'); ?></li>
                    <li><?php _e('Find the "Custom VAT Rates" section in the Download Details', 'edd-custom-vat'); ?></li>
                    <li><?php _e('Add tax rates for specific countries', 'edd-custom-vat'); ?></li>
                </ol>
                <p><?php _e('For more information, please see the documentation.', 'edd-custom-vat'); ?></p>
            </div>
        </div>
        <?php
    }
}
