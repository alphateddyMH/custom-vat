/**
 * Admin scripts for EDD Custom VAT per Country
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
     * Product Tax Rates UI
     */
    var EDD_Custom_VAT_Product_UI = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initSortable();
        },

        bindEvents: function() {
            // Add tax rate
            $('#edd-custom-vat-add-rate').on('click', this.addTaxRate);
            
            // Remove tax rate
            $(document).on('click', '.edd-custom-vat-remove-rate', this.removeTaxRate);
            
            // Update tax rates data when rates change
            $(document).on('change', '.edd-custom-vat-rate input', this.updateTaxRatesData);
        },

        initTooltips: function() {
            $('.edd-custom-vat-help-tip').tooltip({
                content: function() {
                    return $(this).prop('title');
                },
                position: {
                    my: 'center top',
                    at: 'center bottom+10',
                    collision: 'flipfit'
                },
                hide: {
                    duration: 200
                },
                show: {
                    duration: 200
                }
            });
        },

        initSortable: function() {
            $('#edd-custom-vat-rates-rows').sortable({
                items: 'tr',
                cursor: 'move',
                axis: 'y',
                handle: '.edd-custom-vat-sort',
                scrollSensitivity: 40,
                helper: function(e, ui) {
                    ui.children().each(function() {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                start: function(event, ui) {
                    ui.item.css('background-color', '#f6f6f6');
                },
                stop: function(event, ui) {
                    ui.item.removeAttr('style');
                    EDD_Custom_VAT_Product_UI.updateTaxRatesData();
                }
            });
        },

        addTaxRate: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var $country = $('#edd-custom-vat-country');
            var $rate = $('#edd-custom-vat-rate');
            var country = $country.val();
            var rate = $rate.val();
            var download_id = $('#post_ID').val();
            
            // Validate inputs
            if (!country) {
                alert(eddCustomVAT.i18n.selectCountry);
                return;
            }
            
            if (!rate || isNaN(parseFloat(rate))) {
                alert(eddCustomVAT.i18n.enterValidRate);
                return;
            }
            
            // Show spinner
            $spinner.css('visibility', 'visible');
            $button.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: eddCustomVAT.ajaxurl,
                type: 'POST',
                data: {
                    action: 'edd_custom_vat_add_tax_rate',
                    nonce: eddCustomVAT.nonce,
                    download_id: download_id,
                    country: country,
                    rate: rate
                },
                success: function(response) {
                    if (response.success) {
                        // Add new row to table
                        $('#edd-custom-vat-rates-rows').append(response.data.row_html);
                        
                        // Remove country from dropdown
                        $country.find('option[value="' + response.data.country + '"]').remove();
                        
                        // Reset inputs
                        $country.val('');
                        $rate.val('');
                        
                        // Update tax rates data
                        EDD_Custom_VAT_Product_UI.updateTaxRatesData();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert(eddCustomVAT.i18n.error);
                },
                complete: function() {
                    $spinner.css('visibility', 'hidden');
                    $button.prop('disabled', false);
                }
            });
        },

        removeTaxRate: function(e) {
            e.preventDefault();
            
            if (!confirm(eddCustomVAT.i18n.confirmDelete)) {
                return;
            }
            
            var $button = $(this);
            var $row = $button.closest('tr');
            var country = $button.data('country');
            var download_id = $('#post_ID').val();
            
            // Show loading state
            $button.prop('disabled', true);
            $row.css('opacity', '0.5');
            
            // Send AJAX request
            $.ajax({
                url: eddCustomVAT.ajaxurl,
                type: 'POST',
                data: {
                    action: 'edd_custom_vat_remove_tax_rate',
                    nonce: eddCustomVAT.nonce,
                    download_id: download_id,
                    country: country
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update tax rates data
                            EDD_Custom_VAT_Product_UI.updateTaxRatesData();
                            
                            // Add country back to dropdown
                            var countryName = $row.find('.edd-custom-vat-country').text().trim();
                            var countryCode = response.data.country;
                            
                            // Extract country name and code from the cell text
                            var matches = countryName.match(/(.*) \((.*)\)/);
                            if (matches && matches.length === 3) {
                                countryName = matches[1];
                                countryCode = matches[2];
                            }
                            
                            $('#edd-custom-vat-country').append(
                                $('<option></option>')
                                    .val(countryCode)
                                    .text(countryName + ' (' + countryCode + ')')
                            );
                        });
                    } else {
                        alert(response.data);
                        $button.prop('disabled', false);
                        $row.css('opacity', '1');
                    }
                },
                error: function() {
                    alert(eddCustomVAT.i18n.error);
                    $button.prop('disabled', false);
                    $row.css('opacity', '1');
                }
            });
        },

        updateTaxRatesData: function() {
            var taxRates = {};
            
            // Loop through all tax rate rows
            $('.edd-custom-vat-rate-row').each(function() {
                var $row = $(this);
                var country = $row.data('country');
                var rate = parseFloat($row.find('input').val());
                
                if (!isNaN(rate)) {
                    taxRates[country] = rate;
                }
            });
            
            // Update hidden input
            $('#edd-custom-vat-rates-data').val(JSON.stringify(taxRates));
        }
    };

    /**
     * Countries Settings UI
     */
    var EDD_Custom_VAT_Countries_UI = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Country search
            $('#edd-custom-vat-country-search').on('keyup', this.searchCountries);
            
            // Select/deselect all countries
            $('#edd-custom-vat-select-all-countries').on('click', this.selectAllCountries);
            $('#edd-custom-vat-deselect-all-countries').on('click', this.deselectAllCountries);
            $('#edd-custom-vat-select-eu-countries').on('click', this.selectEUCountries);
        },

        searchCountries: function() {
            var value = $(this).val().toLowerCase();
            $('.edd-custom-vat-country-item').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        },

        selectAllCountries: function() {
            $('.edd-custom-vat-country-item input[type="checkbox"]').prop('checked', true);
        },

        deselectAllCountries: function() {
            $('.edd-custom-vat-country-item input[type="checkbox"]').prop('checked', false);
        },

        selectEUCountries: function() {
            var euCountries = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];
            
            $('.edd-custom-vat-country-item input[type="checkbox"]').each(function() {
                var countryCode = $(this).val();
                $(this).prop('checked', euCountries.indexOf(countryCode) !== -1);
            });
        }
    };

    /**
     * Documentation UI
     */
    var EDD_Custom_VAT_Documentation_UI = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Smooth scroll for documentation links
            $('.edd-custom-vat-doc-nav a').on('click', this.smoothScroll);
        },

        smoothScroll: function(e) {
            e.preventDefault();
            
            var target = $(this).attr('href');
            var $target = $(target);
            
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 50
                }, 500);
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize only on relevant pages
        if ($('#edd-custom-vat-rates').length) {
            EDD_Custom_VAT_Product_UI.init();
        }
        
        if ($('.edd-custom-vat-countries-wrapper').length) {
            EDD_Custom_VAT_Countries_UI.init();
        }
        
        if ($('.edd-custom-vat-documentation').length) {
            EDD_Custom_VAT_Documentation_UI.init();
        }
        
        // Clear cache button
        $('#edd-custom-vat-clear-cache').on('click', function() {
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var $message = $('.edd-custom-vat-cache-message');
            
            $button.prop('disabled', true);
            $spinner.css('visibility', 'visible');
            
            $.ajax({
                url: eddCustomVAT.ajaxurl,
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
                    $message.html('<div class="notice notice-error inline"><p>' + eddCustomVAT.i18n.error + '</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    setTimeout(function() {
                        $message.html('');
                    }, 5000);
                }
            });
        });
    });

})(jQuery);
