<?php
/**
 * The countries settings functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The countries settings functionality of the plugin.
 *
 * Handles the countries settings tab.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Settings_Countries {

    /**
     * Render the countries settings section.
     *
     * @since    2.0.0
     */
    public function render() {
        $settings = EDD_Custom_VAT_Settings::get_countries_settings();
        $countries = $this->get_edd_countries();
        ?>
        <div class="edd-custom-vat-settings-section">
            <h2><?php _e('Countries Settings', 'edd-custom-vat'); ?></h2>
            
            <p><?php _e('Select the countries for which you want to enable custom VAT rates. Only selected countries will be available when setting product-specific tax rates.', 'edd-custom-vat'); ?></p>
            
            <div class="edd-custom-vat-countries-wrapper">
                <div class="edd-custom-vat-countries-search">
                    <input type="text" id="edd-custom-vat-country-search" placeholder="<?php esc_attr_e('Search countries...', 'edd-custom-vat'); ?>" />
                </div>
                
                <div class="edd-custom-vat-countries-list">
                    <?php foreach ($countries as $code => $name) : ?>
                        <div class="edd-custom-vat-country-item">
                            <label>
                                <input type="checkbox" 
                                       name="edd_custom_vat_countries[enabled_countries][]" 
                                       value="<?php echo esc_attr($code); ?>" 
                                       <?php checked(in_array($code, $settings['enabled_countries'])); ?> />
                                <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="edd-custom-vat-countries-actions">
                    <button type="button" class="button" id="edd-custom-vat-select-all-countries"><?php _e('Select All', 'edd-custom-vat'); ?></button>
                    <button type="button" class="button" id="edd-custom-vat-deselect-all-countries"><?php _e('Deselect All', 'edd-custom-vat'); ?></button>
                    <button type="button" class="button" id="edd-custom-vat-select-eu-countries"><?php _e('Select EU Countries', 'edd-custom-vat'); ?></button>
                </div>
            </div>
            
            <div class="edd-custom-vat-info-box">
                <h3><i class="fas fa-info-circle"></i> <?php _e('Country Selection', 'edd-custom-vat'); ?></h3>
                <p><?php _e('Only countries that are selected here will be available when setting custom VAT rates for products. For all other countries, the default EDD tax rate will be used.', 'edd-custom-vat'); ?></p>
                <p><?php _e('For EU countries, make sure to select all relevant countries if you need to comply with EU VAT regulations.', 'edd-custom-vat'); ?></p>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Country search functionality
                $('#edd-custom-vat-country-search').on('keyup', function() {
                    var value = $(this).val().toLowerCase();
                    $('.edd-custom-vat-country-item').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });
                
                // Select all countries
                $('#edd-custom-vat-select-all-countries').on('click', function() {
                    $('.edd-custom-vat-country-item input[type="checkbox"]').prop('checked', true);
                });
                
                // Deselect all countries
                $('#edd-custom-vat-deselect-all-countries').on('click', function() {
                    $('.edd-custom-vat-country-item input[type="checkbox"]').prop('checked', false);
                });
                
                // Select EU countries
                $('#edd-custom-vat-select-eu-countries').on('click', function() {
                    var euCountries = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];
                    
                    $('.edd-custom-vat-country-item input[type="checkbox"]').each(function() {
                        var countryCode = $(this).val();
                        $(this).prop('checked', euCountries.indexOf(countryCode) !== -1);
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Get all countries from EDD.
     *
     * @since    2.0.0
     * @return   array    Array of countries.
     */
    private function get_edd_countries() {
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
}
