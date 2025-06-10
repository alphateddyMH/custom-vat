# EDD Custom VAT per Country

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/edd-custom-vat.svg)](https://wordpress.org/plugins/edd-custom-vat/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/edd-custom-vat.svg)](https://wordpress.org/plugins/edd-custom-vat/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/edd-custom-vat.svg)](https://wordpress.org/plugins/edd-custom-vat/)

EDD Custom VAT per Country enables product-specific and country-specific VAT rates for Easy Digital Downloads. This plugin allows you to set different VAT rates for each product and country, making it perfect for businesses that sell digital products with varying tax rates across different regions.

## Description

EDD Custom VAT per Country gives you complete control over your VAT rates on a per-product and per-country basis. This is particularly useful for businesses selling digital products in the EU, where different VAT rates may apply to different types of products.

For example, in Germany, e-books are taxed at 7% while standard digital products are taxed at 19%. With this plugin, you can easily set these different rates for each product.

### Key Features

- **Product-Specific VAT Rates**: Set different VAT rates for each product and country
- **Intuitive UI**: Easy-to-use interface for managing VAT rates directly in the product editor
- **Bundle Support**: Proper handling of bundles with products that have different tax rates
- **Recurring Payments**: Full support for EDD Recurring Payments with consistent tax rates
- **GoBD Compliance**: Detailed tax breakdowns in invoices for GoBD compliance
- **Import/Export**: Export and import your VAT rate configurations
- **WPML Compatible**: Works seamlessly with multilingual stores
- **Gateway Integration**: Correctly passes tax information to payment gateways like PayPal and Stripe

### How It Works

1. Enable the countries you want to use custom VAT rates for
2. Edit a product and set specific VAT rates for each country
3. When a customer purchases the product, the correct VAT rate is applied based on their country

If no custom rate is defined for a product/country combination, the default EDD tax rate will be used.

### Compatible With

- EDD Recurring Payments
- EDD Invoices
- EDD PDF Invoices
- EDD Software Licensing
- EDD Bundles
- WPML

## Installation

1. Upload the `edd-custom-vat` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Downloads → Custom VAT to configure the plugin

## Frequently Asked Questions

### Can I set different tax rates for the same country?

Yes, you can set different tax rates for different products in the same country. For example, you can set 7% for e-books and 19% for software in Germany.

### What happens if I don't set a custom tax rate?

If you don't set a custom tax rate for a product/country combination, the default EDD tax rate will be used.

### How does the plugin handle bundles?

For bundles, the plugin can display tax rates in three different ways:
- Detailed: Each item with its own rate
- Summarized: Grouped by tax rate
- Simple: Using the bundle's rate for all items

### Is this plugin compatible with WPML?

Yes, the plugin is compatible with WPML. You can enable WPML synchronization in the Advanced settings to keep tax rates consistent across translations.

### How does it work with recurring payments?

The plugin ensures that the same tax rate is applied to both the initial payment and all recurring payments, even if the tax rates change after the subscription is created.

## Screenshots

1. Product-specific VAT rates in the product editor
2. Settings page with country selection
3. Tax breakdown in the checkout
4. Detailed tax information in invoices
5. Import/Export functionality

## Changelog

### 2.0.0
* Complete rewrite with improved architecture
* Added support for bundles with different tax rates
* Added support for recurring payments
* Added import/export functionality
* Added detailed documentation
* Improved compatibility with other plugins
* Added GoBD-compliant invoice display

### 1.0.0
* Initial release

## Support

For support, please visit [our support page](https://itmedialaw.com/support/) or email us at support@itmedialaw.com.

## License

This plugin is licensed under the GPL v2 or later.

