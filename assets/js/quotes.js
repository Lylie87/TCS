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
    let currentAvailability = []; // Store availability data for conflict checking

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

        // Billing address toggle
        $('#quote-billing-address-different').on('change', function() {
            if ($(this).is(':checked')) {
                $('#quote-billing-address-section').slideDown();
            } else {
                $('#quote-billing-address-section').slideUp();
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

        // Addresses - fitting address is primary for quotes
        const billingIsDifferent = $('#quote-billing-address-different').is(':checked');

        formData.append('fitting_address_line_1', $('#quote-fitting-address-line-1').val());
        formData.append('fitting_address_line_2', $('#quote-fitting-address-line-2').val());
        formData.append('fitting_address_line_3', $('#quote-fitting-address-line-3').val());
        formData.append('fitting_postcode', $('#quote-fitting-postcode').val());

        // If billing address is different, use separate billing fields
        // Otherwise, copy fitting address to billing fields
        if (billingIsDifferent) {
            formData.append('billing_address_line_1', $('#quote-billing-address-line-1').val());
            formData.append('billing_address_line_2', $('#quote-billing-address-line-2').val());
            formData.append('billing_address_line_3', $('#quote-billing-address-line-3').val());
            formData.append('billing_postcode', $('#quote-billing-postcode').val());
        } else {
            // Billing same as fitting
            formData.append('billing_address_line_1', $('#quote-fitting-address-line-1').val());
            formData.append('billing_address_line_2', $('#quote-fitting-address-line-2').val());
            formData.append('billing_address_line_3', $('#quote-fitting-address-line-3').val());
            formData.append('billing_postcode', $('#quote-fitting-postcode').val());
        }

        // For quotes, fitting_address_different is always 0 (we don't use that paradigm)
        formData.append('fitting_address_different', 0);

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
        $('#fitter-availability-display').hide();
        $('#availability-calendar').empty();
        $('#convert-to-job-modal').fadeIn(200);

        // Add fitter selection handler to load availability
        $('#convert-fitter').off('change').on('change', function() {
            const fitterId = $(this).val();
            if (fitterId) {
                loadFitterAvailability(fitterId);
            } else {
                $('#fitter-availability-display').hide();
            }
        });
    }

    /**
     * Load fitter availability
     */
    function loadFitterAvailability(fitterId) {
        $('#fitter-availability-display').show();
        $('#availability-loading').show();
        $('#availability-calendar').empty();

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_fitter_availability',
                nonce: wpStaffDiary.nonce,
                fitter_id: fitterId,
                start_date: new Date().toISOString().split('T')[0],
                days: 14
            },
            success: function(response) {
                $('#availability-loading').hide();
                if (response.success) {
                    currentAvailability = response.data.availability;
                    displayFitterAvailability(response.data.availability);
                } else {
                    $('#availability-calendar').html('<p style="color: #d63638;">Error loading availability</p>');
                }
            },
            error: function() {
                $('#availability-loading').hide();
                $('#availability-calendar').html('<p style="color: #d63638;">Failed to load availability</p>');
            }
        });
    }

    /**
     * Display fitter availability calendar
     */
    function displayFitterAvailability(availability) {
        const $calendar = $('#availability-calendar');
        $calendar.empty();

        if (!availability || availability.length === 0) {
            $calendar.html('<p>No availability data found.</p>');
            return;
        }

        availability.forEach(function(day) {
            let statusClass = 'available';
            let statusColor = '#4caf50';
            let statusText = 'Available';

            if (day.all_day_booked) {
                statusClass = 'fully-booked';
                statusColor = '#f44336';
                statusText = 'Fully Booked';
            } else if (!day.am_available || !day.pm_available) {
                statusClass = 'partially-booked';
                statusColor = '#ff9800';
                if (!day.am_available && day.pm_available) {
                    statusText = 'PM Available';
                } else if (day.am_available && !day.pm_available) {
                    statusText = 'AM Available';
                }
            }

            const dateObj = new Date(day.date + 'T00:00:00');
            const formattedDate = dateObj.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short'
            });

            let jobsHtml = '';
            if (day.jobs && day.jobs.length > 0) {
                jobsHtml = '<div style="margin-top: 5px; font-size: 10px; color: #666;">';
                day.jobs.forEach(function(job) {
                    const period = job.time_period ? ' (' + job.time_period.toUpperCase() + ')' : '';
                    jobsHtml += '<div>' + job.order_number + period + '</div>';
                });
                jobsHtml += '</div>';
            }

            const $dayCard = $('<div class="availability-day-card" style="' +
                'padding: 10px;' +
                'border: 2px solid ' + statusColor + ';' +
                'border-radius: 4px;' +
                'background: white;' +
                'cursor: pointer;' +
                'transition: all 0.2s;' +
                '" data-date="' + day.date + '" data-available="' + (statusClass === 'available' ? '1' : '0') + '"></div>');

            $dayCard.html(
                '<div style="font-weight: bold; margin-bottom: 3px;">' + day.day_name.substring(0, 3) + '</div>' +
                '<div style="font-size: 14px; margin-bottom: 3px;">' + formattedDate + '</div>' +
                '<div style="font-size: 11px; color: ' + statusColor + '; font-weight: 600;">' + statusText + '</div>' +
                jobsHtml
            );

            // Add click handler to select date
            $dayCard.on('click', function() {
                const date = $(this).data('date');
                $('#convert-fitting-date').val(date);

                // Highlight selected
                $('.availability-day-card').css('box-shadow', 'none');
                $(this).css('box-shadow', '0 0 0 3px #2271b1');

                // Pre-select time period if partially booked
                if (!day.am_available && day.pm_available) {
                    $('#convert-fitting-time-period').val('pm');
                } else if (day.am_available && !day.pm_available) {
                    $('#convert-fitting-time-period').val('am');
                }
            });

            // Hover effect
            $dayCard.on('mouseenter', function() {
                if ($(this).data('available') === '1' || statusClass === 'partially-booked') {
                    $(this).css('transform', 'translateY(-2px)');
                    $(this).css('box-shadow', '0 4px 8px rgba(0,0,0,0.1)');
                }
            }).on('mouseleave', function() {
                $(this).css('transform', 'translateY(0)');
                if ($(this).css('box-shadow').indexOf('rgb(34, 113, 177)') === -1) {
                    $(this).css('box-shadow', 'none');
                }
            });

            $calendar.append($dayCard);
        });
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

        // Check for scheduling conflicts
        if (fittingDate && fittingTimePeriod) {
            const selectedCard = $('.availability-day-card[data-date="' + fittingDate + '"]');
            if (selectedCard.length > 0) {
                const dayData = selectedCard.data();

                // Find the corresponding day in availability data
                const availabilityDay = currentAvailability.find(function(day) {
                    return day.date === fittingDate;
                });

                if (availabilityDay) {
                    const timePeriod = fittingTimePeriod.toLowerCase();
                    let conflict = false;
                    let conflictMessage = '';

                    if (availabilityDay.all_day_booked) {
                        conflict = true;
                        conflictMessage = 'This fitter is fully booked on this date.';
                    } else if (timePeriod === 'am' && !availabilityDay.am_available) {
                        conflict = true;
                        conflictMessage = 'This fitter already has a morning job on this date.';
                    } else if (timePeriod === 'pm' && !availabilityDay.pm_available) {
                        conflict = true;
                        conflictMessage = 'This fitter already has an afternoon job on this date.';
                    } else if (timePeriod === 'all-day' && (!availabilityDay.am_available || !availabilityDay.pm_available)) {
                        conflict = true;
                        conflictMessage = 'This fitter is partially booked on this date. An all-day job would conflict.';
                    }

                    if (conflict) {
                        if (!confirm(conflictMessage + '\n\nDo you want to proceed anyway? This may result in a double-booking.')) {
                            return;
                        }
                    }
                }
            }
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
        $('#quote-billing-address-section').hide();
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

        // Addresses - fitting is primary for quotes
        $('#quote-fitting-address-line-1').val(quote.fitting_address_line_1 || '');
        $('#quote-fitting-address-line-2').val(quote.fitting_address_line_2 || '');
        $('#quote-fitting-address-line-3').val(quote.fitting_address_line_3 || '');
        $('#quote-fitting-postcode').val(quote.fitting_postcode || '');

        // Check if billing address is different from fitting address
        const billingDifferent = (
            quote.billing_address_line_1 !== quote.fitting_address_line_1 ||
            quote.billing_address_line_2 !== quote.fitting_address_line_2 ||
            quote.billing_address_line_3 !== quote.fitting_address_line_3 ||
            quote.billing_postcode !== quote.fitting_postcode
        );

        if (billingDifferent) {
            $('#quote-billing-address-different').prop('checked', true);
            $('#quote-billing-address-section').show();
            $('#quote-billing-address-line-1').val(quote.billing_address_line_1 || '');
            $('#quote-billing-address-line-2').val(quote.billing_address_line_2 || '');
            $('#quote-billing-address-line-3').val(quote.billing_address_line_3 || '');
            $('#quote-billing-postcode').val(quote.billing_postcode || '');
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

        // Header with PDF button
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
        html += '<h2 style="margin: 0;">Quote #' + quote.order_number + '</h2>';
        html += '<button type="button" class="button button-primary" id="generate-quote-pdf-btn" data-quote-id="' + quote.id + '">';
        html += '<span class="dashicons dashicons-pdf"></span> Generate PDF</button>';
        html += '</div>';

        html += '<div class="quote-status">Status: <span class="status-badge status-quotation">Quotation</span></div>';
        html += '<p style="color: #666; font-size: 13px;">Created: ' + new Date(quote.created_at).toLocaleDateString('en-GB') + '</p>';

        if (quote.customer) {
            html += '<h3>Customer Details</h3>';
            html += '<p><strong>' + quote.customer.customer_name + '</strong><br>';
            if (quote.customer.customer_phone) html += 'Phone: ' + quote.customer.customer_phone + '<br>';
            if (quote.customer.customer_email) html += 'Email: ' + quote.customer.customer_email + '</p>';
        }

        // Fitting Address
        if (quote.fitting_address_line_1) {
            html += '<h3>Fitting Address</h3>';
            html += '<p>';
            if (quote.fitting_address_line_1) html += quote.fitting_address_line_1 + '<br>';
            if (quote.fitting_address_line_2) html += quote.fitting_address_line_2 + '<br>';
            if (quote.fitting_address_line_3) html += quote.fitting_address_line_3 + '<br>';
            if (quote.fitting_postcode) html += quote.fitting_postcode;
            html += '</p>';
        }

        // Billing Address (if different)
        if (quote.billing_address_different == 1 && quote.billing_address_line_1) {
            html += '<h3>Billing Address</h3>';
            html += '<p>';
            if (quote.billing_address_line_1) html += quote.billing_address_line_1 + '<br>';
            if (quote.billing_address_line_2) html += quote.billing_address_line_2 + '<br>';
            if (quote.billing_address_line_3) html += quote.billing_address_line_3 + '<br>';
            if (quote.billing_postcode) html += quote.billing_postcode;
            html += '</p>';
        }

        html += '<h3>Product Details</h3>';
        html += '<p>' + (quote.product_description || 'No description') + '</p>';
        html += '<p>Quantity: ' + (quote.sq_mtr_qty || '0') + ' m² @ £' + (quote.price_per_sq_mtr || '0') + '/m²</p>';
        if (quote.fitting_cost && parseFloat(quote.fitting_cost) > 0) {
            html += '<p>Fitting Cost: £' + parseFloat(quote.fitting_cost).toFixed(2) + '</p>';
        }

        // Accessories
        if (quote.accessories && quote.accessories.length > 0) {
            html += '<h3>Accessories</h3>';
            html += '<ul>';
            quote.accessories.forEach(function(acc) {
                html += '<li>' + acc.accessory_name + ' - Qty: ' + acc.quantity + ' @ £' + parseFloat(acc.price_per_unit).toFixed(2) + ' = £' + parseFloat(acc.total_price).toFixed(2) + '</li>';
            });
            html += '</ul>';
        }

        // Financial Summary
        html += '<h3>Quote Summary</h3>';
        html += '<table style="width: 100%; border-collapse: collapse;">';
        html += '<tr><td>Subtotal:</td><td style="text-align: right;">£' + (quote.subtotal || quote.total || '0').toFixed(2) + '</td></tr>';

        if (vatEnabled == 1) {
            const vat = quote.vat || (quote.total * (vatRate / (100 + parseFloat(vatRate))));
            html += '<tr><td>VAT (' + vatRate + '%):</td><td style="text-align: right;">£' + parseFloat(vat).toFixed(2) + '</td></tr>';
        }

        html += '<tr style="font-weight: bold; font-size: 16px; border-top: 2px solid #ddd;"><td>Total:</td><td style="text-align: right;">£' + parseFloat(quote.total || '0').toFixed(2) + '</td></tr>';
        html += '</table>';

        // Notes
        if (quote.notes) {
            html += '<h3>Additional Notes</h3>';
            html += '<p>' + quote.notes.replace(/\n/g, '<br>') + '</p>';
        }

        // Photos
        if (quote.images && quote.images.length > 0) {
            html += '<h3>Photos</h3>';
            html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">';
            quote.images.forEach(function(img) {
                html += '<img src="' + img.image_url + '" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px;" />';
            });
            html += '</div>';
        }

        html += '</div>';

        $('#quote-details-content').html(html);

        // Attach PDF generation handler
        $('#generate-quote-pdf-btn').off('click').on('click', function() {
            generateQuotePDF($(this).data('quote-id'));
        });
    }

    /**
     * Generate Quote PDF
     */
    function generateQuotePDF(quoteId) {
        const $btn = $('#generate-quote-pdf-btn');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> Generating PDF...');

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_quote_pdf',
                nonce: wpStaffDiary.nonce,
                quote_id: quoteId
            },
            success: function(response) {
                if (response.success) {
                    // Open PDF in new tab
                    window.open(response.data.url, '_blank');

                    // Show success message
                    const successMsg = $('<div class="notice notice-success" style="margin: 10px 0; padding: 10px;"><p>PDF generated successfully!</p></div>');
                    $('#quote-details-content').prepend(successMsg);
                    setTimeout(function() {
                        successMsg.fadeOut(300, function() { $(this).remove(); });
                    }, 3000);
                } else {
                    alert('Error generating PDF: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to generate PDF. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
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
