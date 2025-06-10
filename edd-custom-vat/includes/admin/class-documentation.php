<?php
/**
 * The documentation functionality of the plugin.
 *
 * @since      2.0.0
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The documentation functionality of the plugin.
 *
 * Provides documentation and help for the plugin.
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/admin
 * @author     Marian Härtel <info@itmedialaw.com>
 */
class EDD_Custom_VAT_Documentation {

    /**
     * Add documentation page.
     *
     * @since    2.0.0
     */
    public function add_documentation_page() {
        add_submenu_page(
            'edit.php?post_type=download',
            __('Custom VAT Documentation', 'edd-custom-vat'),
            __('Custom VAT Docs', 'edd-custom-vat'),
            'manage_shop_settings',
            'edd-custom-vat-docs',
            array($this, 'render_documentation_page')
        );
    }

    /**
     * Render the documentation page.
     *
     * @since    2.0.0
     */
    public function render_documentation_page() {
        ?>
        <div class="wrap edd-custom-vat-documentation">
            <h1><?php _e('EDD Custom VAT per Country Documentation', 'edd-custom-vat'); ?></h1>
            
            <div class="edd-custom-vat-doc-wrapper">
                <div class="edd-custom-vat-doc-sidebar">
                    <div class="edd-custom-vat-doc-nav">
                        <h3><?php _e('Contents', 'edd-custom-vat'); ?></h3>
                        <ul>
                            <li><a href="#overview"><?php _e('Overview', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#getting-started"><?php _e('Getting Started', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#product-tax-rates"><?php _e('Setting Product Tax Rates', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#bundles"><?php _e('Working with Bundles', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#recurring"><?php _e('Recurring Payments', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#invoices"><?php _e('Invoices & Receipts', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#import-export"><?php _e('Import & Export', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#troubleshooting"><?php _e('Troubleshooting', 'edd-custom-vat'); ?></a></li>
                            <li><a href="#faq"><?php _e('FAQ', 'edd-custom-vat'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="edd-custom-vat-doc-support">
                        <h3><?php _e('Need Help?', 'edd-custom-vat'); ?></h3>
                        <p><?php _e('If you need assistance with this plugin, please contact us:', 'edd-custom-vat'); ?></p>
                        <ul>
                            <li><a href="https://itmedialaw.com/support/" target="_blank"><?php _e('Support Center', 'edd-custom-vat'); ?></a></li>
                            <li><a href="mailto:support@itmedialaw.com"><?php _e('Email Support', 'edd-custom-vat'); ?></a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="edd-custom-vat-doc-content">
                    <section id="overview" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Overview', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('EDD Custom VAT per Country allows you to set different VAT rates for each product and country. This is particularly useful for digital goods in the EU, where different VAT rates may apply to different types of products.', 'edd-custom-vat'); ?></p>
                        <p><?php _e('For example, in Germany, e-books are taxed at 7% while standard digital products are taxed at 19%. With this plugin, you can set these different rates for each product.', 'edd-custom-vat'); ?></p>
                        
                        <h3><?php _e('Key Features', 'edd-custom-vat'); ?></h3>
                        <ul>
                            <li><?php _e('Set custom VAT rates per product and country', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Support for bundles with mixed tax rates', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Compatible with EDD Recurring Payments', 'edd-custom-vat'); ?></li>
                            <li><?php _e('GoBD-compliant invoice display', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Import/Export functionality', 'edd-custom-vat'); ?></li>
                            <li><?php _e('WPML compatibility', 'edd-custom-vat'); ?></li>
                        </ul>
                    </section>
                    
                    <section id="getting-started" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Getting Started', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('To get started with EDD Custom VAT per Country, follow these steps:', 'edd-custom-vat'); ?></p>
                        
                        <ol>
                            <li>
                                <strong><?php _e('Enable the plugin', 'edd-custom-vat'); ?></strong>
                                <p><?php _e('Go to Downloads → Custom VAT and make sure the plugin is enabled.', 'edd-custom-vat'); ?></p>
                            </li>
                            <li>
                                <strong><?php _e('Configure countries', 'edd-custom-vat'); ?></strong>
                                <p><?php _e('Go to the "Countries" tab and select the countries for which you want to enable custom VAT rates.', 'edd-custom-vat'); ?></p>
                            </li>
                            <li>
                                <strong><?php _e('Set product-specific tax rates', 'edd-custom-vat'); ?></strong>
                                <p><?php _e('Edit a download product and find the "Custom VAT Rates" section in the Download Details.', 'edd-custom-vat'); ?></p>
                            </li>
                        </ol>
                        
                        <div class="edd-custom-vat-doc-note">
                            <p><strong><?php _e('Note:', 'edd-custom-vat'); ?></strong> <?php _e('For countries or products without custom rates, the default EDD tax rate will be used.', 'edd-custom-vat'); ?></p>
                        </div>
                    </section>
                    
                    <section id="product-tax-rates" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Setting Product Tax Rates', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('To set custom VAT rates for a specific product:', 'edd-custom-vat'); ?></p>
                        
                        <ol>
                            <li><?php _e('Edit the download product', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Scroll down to the "Download Details" section', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Find the "Custom VAT Rates" section', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Select a country from the dropdown', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Enter the tax rate percentage', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Click "Add Rate"', 'edd-custom-vat'); ?></li>
                        </ol>
                        
                        <p><?php _e('You can add as many country-specific rates as needed. To remove a rate, click the "Remove" button next to it.', 'edd-custom-vat'); ?></p>
                        
                        <div class="edd-custom-vat-doc-screenshot">
                            <img src="<?php echo EDD_CUSTOM_VAT_PLUGIN_URL; ?>assets/images/product-tax-rates.png" alt="<?php esc_attr_e('Product Tax Rates Screenshot', 'edd-custom-vat'); ?>" />
                        </div>
                    </section>
                    
                    <section id="bundles" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Working with Bundles', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('When working with EDD bundles that contain products with different tax rates, the plugin offers three display modes:', 'edd-custom-vat'); ?></p>
                        
                        <ul>
                            <li><strong><?php _e('Detailed:', 'edd-custom-vat'); ?></strong> <?php _e('Shows each bundle item with its own tax rate', 'edd-custom-vat'); ?></li>
                            <li><strong><?php _e('Summarized:', 'edd-custom-vat'); ?></strong> <?php _e('Groups items by tax rate', 'edd-custom-vat'); ?></li>
                            <li><strong><?php _e('Simple:', 'edd-custom-vat'); ?></strong> <?php _e('Uses the bundle\'s tax rate for all items', 'edd-custom-vat'); ?></li>
                        </ul>
                        
                        <p><?php _e('You can configure this in the Advanced settings tab.', 'edd-custom-vat'); ?></p>
                        
                        <div class="edd-custom-vat-doc-note">
                            <p><strong><?php _e('Note for GoBD compliance:', 'edd-custom-vat'); ?></strong> <?php _e('For GoBD-compliant invoices in Germany, it\'s recommended to use the "Detailed" or "Summarized" mode.', 'edd-custom-vat'); ?></p>
                        </div>
                    </section>
                    
                    <section id="recurring" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Recurring Payments', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('The plugin fully supports EDD Recurring Payments. When a customer subscribes to a recurring product:', 'edd-custom-vat'); ?></p>
                        
                        <ul>
                            <li><?php _e('The custom tax rate is applied to the initial payment', 'edd-custom-vat'); ?></li>
                            <li><?php _e('The same tax rate is stored and applied to all renewal payments', 'edd-custom-vat'); ?></li>
                            <li><?php _e('If the tax rate changes after subscription creation, the original rate is still used for renewals', 'edd-custom-vat'); ?></li>
                        </ul>
                        
                        <p><?php _e('This ensures consistent tax application throughout the subscription lifecycle.', 'edd-custom-vat'); ?></p>
                    </section>
                    
                    <section id="invoices" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Invoices & Receipts', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('The plugin enhances EDD\'s invoices and receipts to properly display custom tax rates:', 'edd-custom-vat'); ?></p>
                        
                        <ul>
                            <li><?php _e('Receipts show a breakdown of tax rates', 'edd-custom-vat'); ?></li>
                            <li><?php _e('PDF invoices include detailed tax information', 'edd-custom-vat'); ?></li>
                            <li><?php _e('For bundles, tax rates are displayed according to your chosen display mode', 'edd-custom-vat'); ?></li>
                        </ul>
                        
                        <p><?php _e('The plugin is compatible with:', 'edd-custom-vat'); ?></p>
                        <ul>
                            <li><?php _e('EDD Invoices', 'edd-custom-vat'); ?></li>
                            <li><?php _e('EDD PDF Invoices', 'edd-custom-vat'); ?></li>
                        </ul>
                    </section>
                    
                    <section id="import-export" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Import & Export', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('The plugin allows you to import and export tax rates:', 'edd-custom-vat'); ?></p>
                        
                        <h3><?php _e('Exporting Tax Rates', 'edd-custom-vat'); ?></h3>
                        <ol>
                            <li><?php _e('Go to Downloads → Custom VAT → Import/Export', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Click "Export to CSV"', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Save the CSV file', 'edd-custom-vat'); ?></li>
                        </ol>
                        
                        <h3><?php _e('Importing Tax Rates', 'edd-custom-vat'); ?></h3>
                        <ol>
                            <li><?php _e('Go to Downloads → Custom VAT → Import/Export', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Click "Choose File" and select your CSV file', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Click "Import from CSV"', 'edd-custom-vat'); ?></li>
                        </ol>
                        
                        <p><?php _e('The CSV format should be:', 'edd-custom-vat'); ?></p>
                        <pre>Product Name,Product ID,Country,Tax Rate
"My Product",123,DE,19
"Another Product",456,FR,20</pre>
                    </section>
                    
                    <section id="troubleshooting" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Troubleshooting', 'edd-custom-vat'); ?></h2>
                        <p><?php _e('If you encounter issues with the plugin, try these troubleshooting steps:', 'edd-custom-vat'); ?></p>
                        
                        <h3><?php _e('Tax rates are not being applied', 'edd-custom-vat'); ?></h3>
                        <ol>
                            <li><?php _e('Make sure the plugin is enabled in the General settings', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Check that the customer\'s country is enabled in the Countries settings', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Verify that you\'ve set a custom tax rate for the specific product and country', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Clear the tax rate cache in the Advanced settings', 'edd-custom-vat'); ?></li>
                        </ol>
                        
                        <h3><?php _e('Performance issues', 'edd-custom-vat'); ?></h3>
                        <ol>
                            <li><?php _e('Make sure your server meets the minimum requirements', 'edd-custom-vat'); ?></li>
                            <li><?php _e('Adjust the cache duration in the Advanced settings', 'edd-custom-vat'); ?></li>
                            <li><?php _e('If using object caching, make sure it\'s configured correctly', 'edd-custom-vat'); ?></li>
                        </ol>
                        
                        <h3><?php _e('Debug Mode', 'edd-custom-vat'); ?></h3>
                        <p><?php _e('For advanced troubleshooting, you can enable Debug Mode in the General settings. This will log additional information to help identify issues.', 'edd-custom-vat'); ?></p>
                    </section>
                    
                    <section id="faq" class="edd-custom-vat-doc-section">
                        <h2><?php _e('Frequently Asked Questions', 'edd-custom-vat'); ?></h2>
                        
                        <div class="edd-custom-vat-faq-item">
                            <h3><?php _e('Can I set different tax rates for the same country?', 'edd-custom-vat'); ?></h3>
                            <p><?php _e('Yes, you can set different tax rates for different products in the same country. For example, you can set 7% for e-books and 19% for software in Germany.', 'edd-custom-vat'); ?></p>
                        </div>
                        
                        <div class="edd-custom-vat-faq-item">
                            <h3><?php _e('What happens if I don\'t set a custom tax rate?', 'edd-custom-vat'); ?></h3>
                            <p><?php _e('If you don\'t set a custom tax rate for a product/country combination, the default EDD tax rate will be used.', 'edd-custom-vat'); ?></p>
                        </div>
                        
                        <div class="edd-custom-vat-faq-item">
                            <h3><?php _e('Is this plugin compatible with WPML?', 'edd-custom-vat'); ?></h3>
                            <p><?php _e('Yes, the plugin is compatible with WPML. You can enable WPML synchronization in the Advanced settings to keep tax rates consistent across translations.', 'edd-custom-vat'); ?></p>
                        </div>
                        
                        <div class="edd-custom-vat-faq-item">
                            <h3><?php _e('How does the plugin handle bundles?', 'edd-custom-vat'); ?></h3>
                            <p><?php _e('For bundles, the plugin can display tax rates in three different ways: detailed (each item with its own rate), summarized (grouped by rate), or simple (using the bundle\'s rate for all items).', 'edd-custom-vat'); ?></p>
                        </div>
                        
                        <div class="edd-custom-vat-faq-item">
                            <h3><?php _e('What happens if I delete the plugin?', 'edd-custom-vat'); ?></h3>
                            <p><?php _e('If you have the "Delete Data on Uninstall" option enabled, all custom tax rates will be deleted when you uninstall the plugin. Otherwise, the data will remain in the database.', 'edd-custom-vat'); ?></p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <?php
    }
}
