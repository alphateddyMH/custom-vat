<?php
/**
 * Define the internationalization functionality.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/includes
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    2.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'edd-custom-vat',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
