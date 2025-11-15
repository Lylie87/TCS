/**
 * Quotes Management JavaScript
 * Handles all quote-related operations including add, edit, view, convert to job
 *
 * @since 2.4.0
 * @package WP_Staff_Diary
 */

(function($) {
    'use strict';

    // Global variables
    let currentQuoteId = null;
    let selectedCustomerId = null;
    let selectedWCProductId = null;

    /**
     * Initialize quotes page functionality
     */
    function initQuotes() {
        // Modal operations
        initModals();

        // Button click handlers
        $('#add-new-quote').on('click', openAddQuoteModal);
        $('.edit-quote').on('click', function() {
            editQuote($(this).data('id'));
        });
        $('.view-quote').on('click', function() {
            viewQuote($(this).data('id'));
        });
        $('.convert-to-job').on('click', function() {
            openConvertToJobModal($(this).data('id'));
        });

        // Form submissions
        $('#quote-entry-form').on('submit', saveQuote);
        $('#convert-to-job-form').on('submit', convertQuoteToJob);

        // Customer operations
        initCustomerSearch();
        $('#quote-add-new-customer-inline').on('click', openAddCustomerModal);
        $('#quick-add-customer-form').on('submit', addQuickCustomer);

        // Photo upload
        $('#quote-upload-photo-btn').on('click', function() {
            $('#quote-photo-upload-input').click();
        });
        $('#quote-photo-upload-input').on('change', handleQuotePhotoUpload);

        // Price calculations
        initPriceCalculations();

        // WooCommerce product search
        if ($('#quote-product-source-woocommerce').length) {
            initWooCommerceProductSearch();
        }

        // Fitting address toggle
        $('#quote-fitting-address-different').on('change', function() {
            if ($(this).is(':checked')) {
                $('#quote-fitting-address-section').slideDown();
            } else {
                $('#quote-fitting-address-section').slideUp();
            }
        });
    }

    /**
     * Initialize modal operations
     */
    function initModals() {
        // Close modals on X click
        $('.wp-staff-diary-modal-close').on('click', closeAllModals);

        // Close modals on background click
        $('.wp-staff-diary-modal').on('click', function(e) {
            if (e.target === this) {
                closeAllModals();
            }
        });

        // Cancel buttons
        $('#cancel-quote-btn').on('click', closeAllModals);
        $('.cancel-convert').on('click', closeAllModals);
        $('#cancel-quick-customer').on('click', closeAllModals);
    }

    /**
     * Open add quote modal
     */
    function openAddQuoteModal() {
        currentQuoteId = null;
        resetQuoteForm();
        $('#quote-modal-title').text('Add New Quote');
        $('#quote-number-display').hide();
        $('#quote-modal').fadeIn(200);
    }

    /**
     * Edit quote
     */
    function editQuote(quoteId) {
        currentQuoteId = quoteId;

        // Load quote data
        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_diary_entry',
                nonce: wpStaffDiary.nonce,
                entry_id: quoteId
            },
            success: function(response) {
                if (response.success) {
                    const quote = response.data.entry || response.data;
                    populateQuoteForm(quote);
                    $('#quote-modal-title').text('Edit Quote');
                    $('#quote-modal').fadeIn(200);
                } else {
                    alert('Error loading quote: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to load quote data.');
            }
        });
    }

    /**
     * View quote details
     */
    function viewQuote(quoteId) {
        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_diary_entry',
                nonce: wpStaffDiary.nonce,
                entry_id: quoteId
            },
            success: function(response) {
                if (response.success) {
                    const quote = response.data.entry || response.data;
                    displayQuoteDetails(quote);
                    $('#view-quote-modal').fadeIn(200);
                } else {
                    alert('Error loading quote: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to load quote data.');
            }
        });
    }

    /**
     * Save quote (create or update)
     */
    function saveQuote(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'save_diary_entry');
        formData.append('nonce', wpStaffDiary.nonce);
        formData.append('entry_id', currentQuoteId || 0);
        formData.append('status', 'quotation');
        formData.append('job_date', $('#quote-job-date').val());

        // Customer
        formData.append('customer_id', selectedCustomerId || '');

        // Addresses
        formData.append('billing_address_line_1', $('#quote-billing-address-line-1').val());
        formData.append('billing_address_line_2', $('#quote-billing-address-line-2').val());
        formData.append('billing_address_line_3', $('#quote-billing-address-line-3').val());
        formData.append('billing_postcode', $('#quote-billing-postcode').val());
        formData.append('fitting_address_different', $('#quote-fitting-address-different').is(':checked') ? 1 : 0);
        formData.append('fitting_address_line_1', $('#quote-fitting-address-line-1').val());
        formData.append('fitting_address_line_2', $('#quote-fitting-address-line-2').val());
        formData.append('fitting_address_line_3', $('#quote-fitting-address-line-3').val());
        formData.append('fitting_postcode', $('#quote-fitting-postcode').val());

        // Product details
        formData.append('product_description', $('#quote-product-description').val());
        formData.append('sq_mtr_qty', $('#quote-sq-mtr-qty').val());
        formData.append('price_per_sq_mtr', $('#quote-price-per-sq-mtr').val());
        formData.append('fitting_cost', $('#quote-fitting-cost').val());

        // Accessories
        const accessories = [];
        $('.quote-accessory-checkbox:checked').each(function() {
            const $checkbox = $(this);
            const $quantityInput = $('.quote-accessory-quantity[data-accessory-id="' + $checkbox.data('accessory-id') + '"]');
            accessories.push({
                accessory_id: $checkbox.data('accessory-id'),
                accessory_name: $checkbox.data('accessory-name'),
                quantity: $quantityInput.val(),
                price_per_unit: $checkbox.data('price')
            });
        });
        formData.append('accessories', JSON.stringify(accessories));

        // Notes
        formData.append('notes', $('#quote-notes').val());

        $('#save-quote-btn').prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> Saving...');

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                if (response.success) {
                    alert('Quote saved successfully!');
                    closeAllModals();
                    location.reload();
                } else {
                    alert('Error saving quote: ' + (response.data.message || 'Unknown error'));
                    $('#save-quote-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Quote');
                }
            },
            error: function() {
                alert('Failed to save quote.');
                $('#save-quote-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Quote');
            }
        });
    }

    /**
     * Open convert to job modal
     */
    function openConvertToJobModal(quoteId) {
        $('#convert-quote-id').val(quoteId);
        $('#convert-to-job-form')[0].reset();
        $('#convert-to-job-modal').fadeIn(200);
    }

    /**
     * Convert quote to job
     */
    function convertQuoteToJob(e) {
        e.preventDefault();

        const quoteId = $('#convert-quote-id').val();
        const fittingDate = $('#convert-fitting-date').val();
        const fittingTimePeriod = $('#convert-fitting-time-period').val();
        const fitterId = $('#convert-fitter').val();
        const fittingDateUnknown = $('#convert-fitting-date-unknown').is(':checked') ? 1 : 0;

        if (!fittingDateUnknown && !fittingDate) {
            alert('Please provide a fitting date or mark it as unknown.');
            return;
        }

        if (!fitterId) {
            alert('Please select a fitter.');
            return;
        }

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'convert_quote_to_job',
                nonce: wpStaffDiary.nonce,
                quote_id: quoteId,
                fitting_date: fittingDate,
                fitting_time_period: fittingTimePeriod,
                fitter_id: fitterId,
                fitting_date_unknown: fittingDateUnknown
            },
            success: function(response) {
                if (response.success) {
                    alert('Quote successfully converted to job!');
                    closeAllModals();
                    location.reload();
                } else {
                    alert('Error converting quote: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to convert quote to job.');
            }
        });
    }

    /**
     * Initialize customer search
     */
    function initCustomerSearch() {
        let searchTimeout;

        $('#quote-customer-search').on('input', function() {
            const searchTerm = $(this).val();

            clearTimeout(searchTimeout);

            if (searchTerm.length < 2) {
                $('#quote-customer-search-results').empty().hide();
                return;
            }

            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'search_customers',
                        nonce: wpStaffDiary.nonce,
                        search: searchTerm
                    },
                    success: function(response) {
                        if (response.success && response.data.customers.length > 0) {
                            displayCustomerSearchResults(response.data.customers);
                        } else {
                            $('#quote-customer-search-results').html('<div class="search-result-item">No customers found</div>').show();
                        }
                    }
                });
            }, 300);
        });

        $('#quote-clear-customer-btn').on('click', function() {
            selectedCustomerId = null;
            $('#quote-customer-id').val('');
            $('#quote-selected-customer-display').hide();
            $('#quote-customer-search').val('').show();
        });
    }

    /**
     * Display customer search results
     */
    function displayCustomerSearchResults(customers) {
        const $results = $('#quote-customer-search-results');
        $results.empty();

        customers.forEach(function(customer) {
            const $item = $('<div class="search-result-item"></div>');
            $item.html(
                '<strong>' + customer.customer_name + '</strong><br>' +
                '<small>' + (customer.customer_phone || '') + '</small>'
            );
            $item.on('click', function() {
                selectCustomer(customer);
            });
            $results.append($item);
        });

        $results.show();
    }

    /**
     * Select a customer
     */
    function selectCustomer(customer) {
        selectedCustomerId = customer.id;
        $('#quote-customer-id').val(customer.id);
        $('#quote-selected-customer-name').text(customer.customer_name);
        $('#quote-selected-customer-display').show();
        $('#quote-customer-search').hide();
        $('#quote-customer-search-results').empty().hide();
    }

    /**
     * Open add customer modal
     */
    function openAddCustomerModal() {
        $('#quick-add-customer-form')[0].reset();
        $('#quick-add-customer-modal').fadeIn(200);
    }

    /**
     * Add quick customer
     */
    function addQuickCustomer(e) {
        e.preventDefault();

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'add_customer',
                nonce: wpStaffDiary.nonce,
                customer_name: $('#quick-customer-name').val(),
                customer_phone: $('#quick-customer-phone').val(),
                customer_email: $('#quick-customer-email').val(),
                address_line_1: $('#quick-address-line-1').val(),
                address_line_2: $('#quick-address-line-2').val(),
                address_line_3: $('#quick-address-line-3').val(),
                postcode: $('#quick-postcode').val()
            },
            success: function(response) {
                if (response.success) {
                    selectCustomer(response.data.customer);
                    $('#quick-add-customer-modal').fadeOut(200);
                } else {
                    alert('Error adding customer: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to add customer.');
            }
        });
    }

    /**
     * Handle photo upload for quote
     */
    function handleQuotePhotoUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!currentQuoteId) {
            alert('Please save the quote first before uploading photos.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload_job_image');
        formData.append('nonce', wpStaffDiary.nonce);
        formData.append('diary_entry_id', currentQuoteId);
        formData.append('image', file);

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Photo uploaded successfully!');
                    // Reload photos
                    loadQuotePhotos(currentQuoteId);
                } else {
                    alert('Error uploading photo: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to upload photo.');
            }
        });
    }

    /**
     * Load quote photos
     */
    function loadQuotePhotos(quoteId) {
        // This would fetch and display photos for the current quote
        // Similar to job photos in main admin.js
    }

    /**
     * Initialize price calculations
     */
    function initPriceCalculations() {
        // Product price calculation
        $('#quote-sq-mtr-qty, #quote-price-per-sq-mtr, #quote-fitting-cost').on('input', calculateQuoteTotal);

        // Accessory calculations
        $('.quote-accessory-checkbox').on('change', function() {
            const $quantityInput = $('.quote-accessory-quantity[data-accessory-id="' + $(this).data('accessory-id') + '"]');
            if ($(this).is(':checked')) {
                $quantityInput.prop('disabled', false);
            } else {
                $quantityInput.prop('disabled', true);
            }
            calculateQuoteTotal();
        });

        $('.quote-accessory-quantity').on('input', calculateQuoteTotal);
    }

    /**
     * Calculate quote total
     */
    function calculateQuoteTotal() {
        // Product total
        const qty = parseFloat($('#quote-sq-mtr-qty').val()) || 0;
        const pricePerUnit = parseFloat($('#quote-price-per-sq-mtr').val()) || 0;
        const fittingCost = parseFloat($('#quote-fitting-cost').val()) || 0;
        const productTotal = (qty * pricePerUnit) + fittingCost;

        $('#quote-product-total-display').text(productTotal.toFixed(2));

        // Accessories total
        let accessoriesTotal = 0;
        $('.quote-accessory-checkbox:checked').each(function() {
            const price = parseFloat($(this).data('price')) || 0;
            const quantity = parseFloat($('.quote-accessory-quantity[data-accessory-id="' + $(this).data('accessory-id') + '"]').val()) || 1;
            accessoriesTotal += price * quantity;
        });

        $('#quote-accessories-total-display').text(accessoriesTotal.toFixed(2));

        // Subtotal
        const subtotal = productTotal + accessoriesTotal;
        $('#quote-subtotal-display').text(subtotal.toFixed(2));

        // VAT
        if (typeof vatEnabled !== 'undefined' && vatEnabled == 1) {
            const vatAmount = subtotal * (vatRate / 100);
            $('#quote-vat-display').text(vatAmount.toFixed(2));

            const total = subtotal + vatAmount;
            $('#quote-total-display').text(total.toFixed(2));
        } else {
            $('#quote-total-display').text(subtotal.toFixed(2));
        }
    }

    /**
     * Initialize WooCommerce product search
     */
    function initWooCommerceProductSearch() {
        let searchTimeout;

        // Toggle product source
        $('input[name="quote_product_source"]').on('change', function() {
            if ($(this).val() === 'woocommerce') {
                $('#quote-woocommerce-product-selector').slideDown();
            } else {
                $('#quote-woocommerce-product-selector').slideUp();
            }
        });

        // Product search
        $('#quote-woocommerce-product-search').on('input', function() {
            const searchTerm = $(this).val();

            clearTimeout(searchTimeout);

            if (searchTerm.length < 2) {
                $('#quote-woocommerce-product-results').empty().hide();
                return;
            }

            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'search_woocommerce_products',
                        nonce: wpStaffDiary.nonce,
                        search: searchTerm
                    },
                    success: function(response) {
                        if (response.success && response.data.products.length > 0) {
                            displayWCProductSearchResults(response.data.products);
                        } else {
                            $('#quote-woocommerce-product-results').html('<div class="search-result-item">No products found</div>').show();
                        }
                    }
                });
            }, 300);
        });

        $('#quote-clear-wc-product-btn').on('click', function() {
            selectedWCProductId = null;
            $('#quote-woocommerce-product-id').val('');
            $('#quote-selected-wc-product-display').hide();
            $('#quote-woocommerce-product-search').val('').show();
        });
    }

    /**
     * Display WooCommerce product search results
     */
    function displayWCProductSearchResults(products) {
        const $results = $('#quote-woocommerce-product-results');
        $results.empty();

        products.forEach(function(product) {
            const $item = $('<div class="search-result-item"></div>');
            $item.html(
                '<strong>' + product.name + '</strong><br>' +
                '<small>Price: £' + product.price + (product.sku ? ' | SKU: ' + product.sku : '') + '</small>'
            );
            $item.on('click', function() {
                selectWCProduct(product);
            });
            $results.append($item);
        });

        $results.show();
    }

    /**
     * Select WooCommerce product
     */
    function selectWCProduct(product) {
        selectedWCProductId = product.id;
        $('#quote-woocommerce-product-id').val(product.id);
        $('#quote-selected-wc-product-name').text(product.name);
        $('#quote-selected-wc-product-display').show();
        $('#quote-woocommerce-product-search').hide();
        $('#quote-woocommerce-product-results').empty().hide();

        // Populate product fields
        $('#quote-product-description').val(product.name + '\n' + (product.description || ''));
        $('#quote-price-per-sq-mtr').val(product.price);
    }

    /**
     * Reset quote form
     */
    function resetQuoteForm() {
        $('#quote-entry-form')[0].reset();
        selectedCustomerId = null;
        selectedWCProductId = null;
        $('#quote-entry-id').val('');
        $('#quote-selected-customer-display').hide();
        $('#quote-customer-search').show();
        $('#quote-fitting-address-section').hide();
        $('.quote-accessory-checkbox').prop('checked', false);
        $('.quote-accessory-quantity').prop('disabled', true).val(1);
        calculateQuoteTotal();
    }

    /**
     * Populate quote form with data
     */
    function populateQuoteForm(quote) {
        $('#quote-entry-id').val(quote.id);
        $('#quote-number-value').text(quote.order_number);
        $('#quote-number-display').show();

        // Customer
        if (quote.customer_id && quote.customer) {
            selectCustomer(quote.customer);
        }

        // Addresses
        $('#quote-billing-address-line-1').val(quote.billing_address_line_1 || '');
        $('#quote-billing-address-line-2').val(quote.billing_address_line_2 || '');
        $('#quote-billing-address-line-3').val(quote.billing_address_line_3 || '');
        $('#quote-billing-postcode').val(quote.billing_postcode || '');

        if (quote.fitting_address_different) {
            $('#quote-fitting-address-different').prop('checked', true);
            $('#quote-fitting-address-section').show();
            $('#quote-fitting-address-line-1').val(quote.fitting_address_line_1 || '');
            $('#quote-fitting-address-line-2').val(quote.fitting_address_line_2 || '');
            $('#quote-fitting-address-line-3').val(quote.fitting_address_line_3 || '');
            $('#quote-fitting-postcode').val(quote.fitting_postcode || '');
        }

        // Product
        $('#quote-product-description').val(quote.product_description || '');
        $('#quote-sq-mtr-qty').val(quote.sq_mtr_qty || '');
        $('#quote-price-per-sq-mtr').val(quote.price_per_sq_mtr || '');
        $('#quote-fitting-cost').val(quote.fitting_cost || '');

        // Accessories
        if (quote.accessories && quote.accessories.length > 0) {
            quote.accessories.forEach(function(accessory) {
                const $checkbox = $('.quote-accessory-checkbox[data-accessory-id="' + accessory.accessory_id + '"]');
                const $quantity = $('.quote-accessory-quantity[data-accessory-id="' + accessory.accessory_id + '"]');
                $checkbox.prop('checked', true);
                $quantity.prop('disabled', false).val(accessory.quantity);
            });
        }

        // Notes
        $('#quote-notes').val(quote.notes || '');

        // Recalculate totals
        calculateQuoteTotal();
    }

    /**
     * Display quote details in view modal
     */
    function displayQuoteDetails(quote) {
        // This would create a detailed view similar to job sheet
        // Showing all quote information in a readable format
        let html = '<div class="quote-details-view">';
        html += '<h2>Quote #' + quote.order_number + '</h2>';
        html += '<div class="quote-status">Status: <span class="status-badge status-quotation">Quotation</span></div>';

        if (quote.customer) {
            html += '<h3>Customer</h3>';
            html += '<p><strong>' + quote.customer.customer_name + '</strong><br>';
            if (quote.customer.customer_phone) html += 'Phone: ' + quote.customer.customer_phone + '<br>';
            if (quote.customer.customer_email) html += 'Email: ' + quote.customer.customer_email + '</p>';
        }

        html += '<h3>Product Details</h3>';
        html += '<p>' + (quote.product_description || 'No description') + '</p>';
        html += '<p>Quantity: ' + (quote.sq_mtr_qty || '0') + ' @ £' + (quote.price_per_sq_mtr || '0') + '/unit</p>';
        html += '<p>Fitting Cost: £' + (quote.fitting_cost || '0') + '</p>';

        html += '<h3>Total</h3>';
        html += '<p><strong>£' + (quote.total || '0').toFixed(2) + '</strong></p>';

        html += '</div>';

        $('#quote-details-content').html(html);
    }

    /**
     * Close all modals
     */
    function closeAllModals() {
        $('.wp-staff-diary-modal').fadeOut(200);
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Only initialize if we're on the quotes page
        if ($('#add-new-quote').length) {
            initQuotes();
        }
    });

})(jQuery);
