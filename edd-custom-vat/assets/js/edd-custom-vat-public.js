/**
 * Public scripts for EDD Custom VAT per Country
 *
 * @package    EDD_Custom_VAT
 * @subpackage EDD_Custom_VAT/assets/js
 * @copyright  Copyright (c) 2023, Marian Härtel
 * @license    GPL-2.0+
 * @since      2.0.0
 */

(function($) {
    'use strict';

    /**
     * EDD Custom VAT Public Functions
     */
    var EDD_Custom_VAT_Public = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Listen for country changes in checkout
            $(document.body).on('change', '#billing_country', this.countryChanged);
            
            // Listen for EDD cart updates
            $(document.body).on('edd_cart_item_added', this.cartUpdated);
            $(document.body).on('edd_cart_item_removed', this.cartUpdated);
            $(document.body).on('edd_quantity_updated', this.cartUpdated);
        },

        countryChanged: function() {
            var country = $(this).val();
            
            // Show loading spinner
            $('#edd-custom-vat-loading').show();
            
            // Send AJAX request
            $.ajax({
                url: eddCustomVAT.ajaxurl,
                type: 'POST',
                data: {
                    action: 'edd_custom_vat_update_country',
                    country: country,
                    nonce: eddCustomVAT.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to update tax calculations
                        window.location.reload();
                    } else {
                        console.error('Error updating country:', response.data);
                    }
                },
                error: function() {
                    console.error('AJAX error when updating country');
                },
                complete: function() {
                    // Hide loading spinner
                    $('#edd-custom-vat-loading').hide();
                }
            });
        },

        cartUpdated: function() {
            // When the cart is updated, we need to ensure tax calculations are correct
            // This is handled by EDD, but we might need to update our tax breakdown display
            
            // If we're on the checkout page, reload after a short delay
            if ($('#edd_checkout_form_wrap').length) {
                setTimeout(function() {
                    // Instead of reloading, we could use AJAX to update just the tax breakdown
                    // But for simplicity and to ensure everything is correct, we reload
                    window.location.reload();
                }, 500);
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize only on EDD pages
        if ($('.edd_checkout_cart').length || $('.edd_download_purchase_form').length) {
            EDD_Custom_VAT_Public.init();
            
            // Add loading spinner if not already present
            if ($('#edd-custom-vat-loading').length === 0) {
                $('body').append('<div id="edd-custom-vat-loading" style="display:none;"><div><i class="fas fa-spinner fa-spin"></i><p>' + eddCustomVAT.i18n.loading + '</p></div></div>');
            }
        }
    });

})(jQuery);
