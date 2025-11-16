/**
 * WP Staff Diary - Admin JavaScript v2.0.0
 *
 * @since      2.0.0
 * @package    WP_Staff_Diary
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Current entry ID being edited
        let currentEntryId = 0;
        let selectedCustomerId = 0;
        let customerSearchTimeout = null;

        // ===========================================
        // UTILITY FUNCTIONS
        // ===========================================

        /**
         * Escape HTML to prevent XSS attacks
         */
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ===========================================
        // NAVIGATION & UI CONTROLS
        // ===========================================

        // Month selector change
        $('#month-select, #month-select-overview').on('change', function() {
            const month = $(this).val();
            window.location.href = updateQueryStringParameter(window.location.href, 'month', month);
        });

        // Staff filter change (overview page)
        $('#staff-filter').on('change', function() {
            const staffId = $(this).val();
            if (staffId === '') {
                $('.staff-section').show();
            } else {
                $('.staff-section').hide();
                $(`.staff-section[data-staff-id="${staffId}"]`).show();
            }
        });

        // Modal close handlers
        $('.wp-staff-diary-modal-close, #cancel-entry-btn, #cancel-customer-btn, #cancel-quick-customer').on('click', function() {
            $(this).closest('.wp-staff-diary-modal').fadeOut();
        });

        // Click outside modal to close - DISABLED to prevent accidental data loss
        // Users must explicitly click the X button or Cancel button to close modals
        // $(window).on('click', function(event) {
        //     if ($(event.target).hasClass('wp-staff-diary-modal')) {
        //         $('.wp-staff-diary-modal').fadeOut();
        //     }
        // });

        // ===========================================
        // ENTRY MODAL & FORM
        // ===========================================

        // Add new entry button
        $('#add-new-entry').on('click', function() {
            openEntryModal();
        });

        // Edit entry button
        $(document).on('click', '.edit-entry', function() {
            const entryId = $(this).data('id');
            loadEntryForEdit(entryId);
        });

        // View entry button
        $(document).on('click', '.view-entry', function() {
            const entryId = $(this).data('id');
            viewEntryDetails(entryId);
        });

        // Delete entry button
        $(document).on('click', '.delete-entry', function() {
            const entryId = $(this).data('id');
            if (confirm('Do you want to DELETE (permanently remove) or CANCEL this job?\n\nClick OK to DELETE permanently, or Cancel to go back and choose CANCEL instead.')) {
                if (confirm('Are you absolutely sure you want to DELETE this entry? This action cannot be undone!')) {
                    deleteEntry(entryId);
                }
            }
        });

        // Submit entry form
        $('#diary-entry-form').on('submit', function(e) {
            e.preventDefault();
            saveEntry();
        });

        /**
         * Open modal for new entry
         */
        function openEntryModal() {
            currentEntryId = 0;
            selectedCustomerId = 0;
            $('#modal-title').text('Add New Job');
            $('#diary-entry-form')[0].reset();
            $('#entry-id').val('');
            $('#customer-id').val('');
            $('#order-number-display').hide();
            $('#selected-customer-display').hide();
            $('#customer-search').val('').show();

            // Reset accessories
            $('.accessory-checkbox').prop('checked', false);
            $('.accessory-quantity').prop('disabled', true).val(1);

            // Reset calculations
            updateCalculations();

            $('#entry-modal').fadeIn();
        }

        /**
         * Load entry for editing
         */
        function loadEntryForEdit(entryId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    if (response.success) {
                        // Data is wrapped in response.data.entry by the modular jobs controller
                        const entry = response.data.entry || response.data;
                        populateEntryForm(entry);
                    } else {
                        alert('Error loading entry: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while loading the entry.');
                }
            });
        }

        /**
         * Populate form with entry data
         */
        function populateEntryForm(entry) {
            currentEntryId = entry.id;
            $('#modal-title').text('Edit Job - ' + entry.order_number);
            $('#entry-id').val(entry.id);

            // Show order number
            $('#order-number-value').text(entry.order_number);
            $('#order-number-display').show();

            // Customer
            if (entry.customer) {
                selectedCustomerId = entry.customer.id;
                $('#customer-id').val(entry.customer.id);
                $('#selected-customer-name').text(entry.customer.customer_name);
                $('#selected-customer-display').show();
                $('#customer-search').hide();
            }

            // Job details
            $('#job-date').val(entry.job_date);
            $('#job-time').val(entry.job_time);
            $('#fitting-date').val(entry.fitting_date);
            $('#fitting-time-period').val(entry.fitting_time_period);
            $('#fitting-date-unknown').prop('checked', entry.fitting_date_unknown == 1);
            $('#area').val(entry.area);
            $('#size').val(entry.size);

            // Fitter
            if (entry.fitter_id !== undefined && entry.fitter_id !== null) {
                $('#fitter').val(entry.fitter_id);
            } else {
                $('#fitter').val('');
            }

            // Addresses
            $('#billing-address-line-1').val(entry.billing_address_line_1 || '');
            $('#billing-address-line-2').val(entry.billing_address_line_2 || '');
            $('#billing-address-line-3').val(entry.billing_address_line_3 || '');
            $('#billing-postcode').val(entry.billing_postcode || '');

            // Fitting address
            if (entry.fitting_address_different == 1) {
                $('#fitting-address-different').prop('checked', true);
                $('#fitting-address-section').show();
                $('#fitting-address-line-1').val(entry.fitting_address_line_1 || '');
                $('#fitting-address-line-2').val(entry.fitting_address_line_2 || '');
                $('#fitting-address-line-3').val(entry.fitting_address_line_3 || '');
                $('#fitting-postcode').val(entry.fitting_postcode || '');
            } else {
                $('#fitting-address-different').prop('checked', false);
                $('#fitting-address-section').hide();
            }

            // Product
            $('#product-description').val(entry.product_description);
            $('#sq-mtr-qty').val(entry.sq_mtr_qty);
            $('#price-per-sq-mtr').val(entry.price_per_sq_mtr);
            $('#fitting-cost').val(entry.fitting_cost || 0);

            // Accessories
            $('.accessory-checkbox').prop('checked', false);
            $('.accessory-quantity').prop('disabled', true).val(1);

            if (entry.accessories && entry.accessories.length > 0) {
                entry.accessories.forEach(function(acc) {
                    const checkbox = $(`.accessory-checkbox[data-accessory-id="${acc.accessory_id}"]`);
                    const quantityInput = $(`.accessory-quantity[data-accessory-id="${acc.accessory_id}"]`);

                    checkbox.prop('checked', true);
                    quantityInput.prop('disabled', false).val(acc.quantity);
                });
            }

            // Notes and status
            $('#notes').val(entry.notes);
            $('#status').val(entry.status);
            $('#job-type').val(entry.job_type || 'residential');

            // Photos section - show when editing existing entry
            if (entry.id && entry.id > 0) {
                $('#photos-section').show();

                // Display existing photos
                if (entry.images && entry.images.length > 0) {
                    let photosHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px;">';
                    entry.images.forEach(function(image) {
                        const categoryColors = {
                            'before': '#3b82f6',
                            'during': '#f59e0b',
                            'after': '#10b981',
                            'general': '#6b7280'
                        };
                        const category = image.image_category || 'general';
                        const categoryColor = categoryColors[category] || categoryColors['general'];
                        const categoryLabel = category.charAt(0).toUpperCase() + category.slice(1);

                        photosHtml += `<div style="position: relative;">
                            <img src="${escapeHtml(image.image_url)}" alt="Job photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open('${escapeHtml(image.image_url)}', '_blank')">
                            <span style="position: absolute; top: 5px; right: 5px; background: ${categoryColor}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">${categoryLabel}</span>
                        </div>`;
                    });
                    photosHtml += '</div>';
                    $('#job-photos-container').html(photosHtml);
                } else {
                    $('#job-photos-container').html('<p class="description">No photos uploaded yet.</p>');
                }

                // Store entry ID for photo upload
                $('#upload-photo-form-btn').data('entry-id', entry.id);
                $('#photo-upload-input-form').data('entry-id', entry.id);
            } else {
                $('#photos-section').hide();
            }

            // Payment section - show when editing existing job
            console.log('Payment section check:', {
                'entry.id': entry.id,
                'entry.is_cancelled': entry.is_cancelled,
                'entry.balance': entry.balance,
                'entry.total': entry.total
            });
            if (entry.id && entry.id > 0 && entry.is_cancelled != 1) {
                $('#payment-section').show();
                console.log('Payment section SHOWN');

                // Display payment history
                if (entry.payments && entry.payments.length > 0) {
                    let historyHtml = '<div style="background: #f0f0f1; padding: 12px; border-radius: 4px;">';
                    historyHtml += '<h4 style="margin-top: 0;">Payment History</h4>';
                    historyHtml += '<table style="width: 100%; border-collapse: collapse;">';
                    entry.payments.forEach(function(payment) {
                        historyHtml += `<tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px 0;">
                                <strong>${payment.payment_type}</strong> (${payment.payment_method})<br>
                                <small style="color: #666;">Recorded by ${payment.recorded_by_name} on ${payment.recorded_at_formatted}</small>
                            </td>
                            <td style="padding: 8px 0; text-align: right;"><strong>£${parseFloat(payment.amount).toFixed(2)}</strong></td>
                        </tr>`;
                    });
                    historyHtml += '</table>';
                    historyHtml += '</div>';
                    $('#payment-history-container').html(historyHtml);
                } else {
                    $('#payment-history-container').html('<p class="description">No payments recorded yet.</p>');
                }

                // Show/hide payment form based on balance
                if (entry.balance > 0) {
                    $('#payment-form-container').show();
                    $('#payment-amount-form').val(entry.balance.toFixed(2));
                    $('#record-payment-form-btn').data('entry-id', entry.id);
                } else {
                    $('#payment-form-container').hide();
                }
            } else {
                $('#payment-section').hide();
            }

            // Update calculations
            updateCalculations();

            $('#entry-modal').fadeIn();
        }

        /**
         * Save entry
         */
        function saveEntry() {
            // Gather accessories data
            const accessories = [];
            $('.accessory-checkbox:checked').each(function() {
                const accessoryId = $(this).data('accessory-id');
                const quantityInput = $(`.accessory-quantity[data-accessory-id="${accessoryId}"]`);

                accessories.push({
                    accessory_id: accessoryId,
                    accessory_name: $(this).data('accessory-name'),
                    quantity: parseFloat(quantityInput.val()) || 1,
                    price_per_unit: parseFloat($(this).data('price'))
                });
            });

            console.log('=== SAVE ENTRY DEBUG ===');
            console.log('Fitter dropdown value:', $('#fitter').val());
            console.log('Fitter dropdown HTML:', $('#fitter')[0]);

            const formData = {
                action: 'save_diary_entry',
                nonce: wpStaffDiary.nonce,
                entry_id: $('#entry-id').val(),
                customer_id: $('#customer-id').val(),
                fitter_id: $('#fitter').val(),
                job_date: $('#job-date').val(),
                job_time: $('#job-time').val(),
                fitting_date: $('#fitting-date').val(),
                fitting_time_period: $('#fitting-time-period').val(),
                fitting_date_unknown: $('#fitting-date-unknown').is(':checked') ? 1 : 0,
                billing_address_line_1: $('#billing-address-line-1').val(),
                billing_address_line_2: $('#billing-address-line-2').val(),
                billing_address_line_3: $('#billing-address-line-3').val(),
                billing_postcode: $('#billing-postcode').val(),
                fitting_address_different: $('#fitting-address-different').is(':checked') ? 1 : 0,
                fitting_address_line_1: $('#fitting-address-line-1').val(),
                fitting_address_line_2: $('#fitting-address-line-2').val(),
                fitting_address_line_3: $('#fitting-address-line-3').val(),
                fitting_postcode: $('#fitting-postcode').val(),
                area: $('#area').val(),
                size: $('#size').val(),
                product_description: $('#product-description').val(),
                sq_mtr_qty: $('#sq-mtr-qty').val(),
                price_per_sq_mtr: $('#price-per-sq-mtr').val(),
                fitting_cost: $('#fitting-cost').val(),
                notes: $('#notes').val(),
                status: $('#status').val(),
                job_type: $('#job-type').val(),
                accessories: accessories
            };

            $('#save-entry-btn').prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Save response:', response);
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        console.error('Save error:', response);
                        $('#save-entry-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Job');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr, status, error);
                    alert('An error occurred while saving the entry.');
                    $('#save-entry-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Job');
                }
            });
        }

        // ===========================================
        // CUSTOMER MANAGEMENT
        // ===========================================

        // Customer search with debounce
        $('#customer-search').on('keyup', function() {
            const searchTerm = $(this).val();

            clearTimeout(customerSearchTimeout);

            if (searchTerm.length < 2) {
                $('#customer-search-results').html('').hide();
                return;
            }

            customerSearchTimeout = setTimeout(function() {
                searchCustomers(searchTerm);
            }, 300);
        });

        /**
         * Search customers
         */
        function searchCustomers(searchTerm) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'search_customers',
                    nonce: wpStaffDiary.nonce,
                    search: searchTerm
                },
                success: function(response) {
                    if (response.success) {
                        displayCustomerSearchResults(response.data.customers);
                    }
                },
                error: function() {
                    console.error('Error searching customers');
                }
            });
        }

        /**
         * Display customer search results
         */
        function displayCustomerSearchResults(customers) {
            if (customers.length === 0) {
                $('#customer-search-results').html('<div class="search-result-item">No customers found</div>').show();
                positionCustomerDropdown();
                return;
            }

            let html = '';
            customers.forEach(function(customer) {
                // Add WooCommerce badge if this is a WooCommerce customer
                const wcBadge = customer.is_woocommerce
                    ? '<span style="display: inline-block; padding: 2px 6px; margin-left: 8px; border-radius: 3px; background: #96588a; color: white; font-size: 10px; font-weight: 600;">WooCommerce</span>'
                    : '';

                html += `<div class="search-result-item" data-customer-id="${customer.id}">
                    <strong>${customer.customer_name}</strong>${wcBadge}<br>
                    ${customer.customer_phone ? customer.customer_phone : ''}
                    ${customer.customer_email ? ' | ' + customer.customer_email : ''}
                </div>`;
            });

            $('#customer-search-results').html(html).show();
            positionCustomerDropdown();
        }

        /**
         * Position the customer search dropdown relative to input field
         */
        function positionCustomerDropdown() {
            const $input = $('#customer-search');
            const $dropdown = $('#customer-search-results');

            if ($input.length && $dropdown.is(':visible')) {
                const inputOffset = $input.offset();
                const inputHeight = $input.outerHeight();
                const inputWidth = $input.outerWidth();

                $dropdown.css({
                    'top': (inputOffset.top + inputHeight) + 'px',
                    'left': inputOffset.left + 'px',
                    'width': inputWidth + 'px'
                });
            }
        }

        // Select customer from search results
        $(document).on('click', '.search-result-item', function() {
            const customerId = $(this).data('customer-id');
            if (customerId) {
                selectCustomer(customerId, $(this).find('strong').text());
            }
        });

        /**
         * Select customer
         */
        function selectCustomer(customerId, customerName) {
            selectedCustomerId = customerId;
            $('#customer-id').val(customerId);
            $('#selected-customer-name').text(customerName);
            $('#selected-customer-display').show();
            $('#customer-search').val('').hide();
            $('#customer-search-results').html('').hide();

            // Fetch customer details and auto-populate billing address
            $.ajax({
                url: wpStaffDiary.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_customer',
                    nonce: wpStaffDiary.nonce,
                    customer_id: customerId
                },
                success: function(response) {
                    if (response.success && response.data.customer) {
                        const customer = response.data.customer;

                        // Auto-populate billing address fields
                        $('#billing-address-line-1').val(customer.address_line_1 || '');
                        $('#billing-address-line-2').val(customer.address_line_2 || '');
                        $('#billing-address-line-3').val(customer.address_line_3 || '');
                        $('#billing-postcode').val(customer.postcode || '');
                    }
                }
            });
        }

        // Clear customer selection
        $('#clear-customer-btn').on('click', function() {
            selectedCustomerId = 0;
            $('#customer-id').val('');
            $('#selected-customer-display').hide();
            $('#customer-search').val('').show();
        });

        // Add new customer inline
        $('#add-new-customer-inline').on('click', function() {
            $('#quick-add-customer-modal').fadeIn();
        });

        // Quick add customer form
        $('#quick-add-customer-form').on('submit', function(e) {
            e.preventDefault();

            const customerData = {
                action: 'add_customer',
                nonce: wpStaffDiary.nonce,
                customer_name: $('#quick-customer-name').val(),
                customer_phone: $('#quick-customer-phone').val(),
                customer_email: $('#quick-customer-email').val(),
                address_line_1: $('#quick-address-line-1').val(),
                address_line_2: $('#quick-address-line-2').val(),
                address_line_3: $('#quick-address-line-3').val(),
                postcode: $('#quick-postcode').val(),
                notes: ''
            };

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: customerData,
                success: function(response) {
                    if (response.success) {
                        selectCustomer(response.data.customer.id, response.data.customer.customer_name);
                        $('#quick-add-customer-modal').fadeOut();
                        $('#quick-add-customer-form')[0].reset();
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while adding the customer.');
                }
            });
        });

        // ===========================================
        // WOOCOMMERCE PRODUCT INTEGRATION
        // ===========================================

        let wcProductSearchTimeout = null;
        let selectedWCProductId = 0;

        // Product source toggle
        $('input[name="product_source"]').on('change', function() {
            const source = $(this).val();

            if (source === 'woocommerce') {
                $('#woocommerce-product-selector').slideDown();
                $('#product-details-fields').find('input, textarea').prop('disabled', true);
            } else {
                $('#woocommerce-product-selector').slideUp();
                $('#selected-wc-product-display').hide();
                $('#woocommerce-product-search').val('');
                $('#woocommerce-product-results').html('').hide();
                $('#woocommerce-product-id').val('');
                selectedWCProductId = 0;
                $('#product-details-fields').find('input, textarea').prop('disabled', false);
            }
        });

        // WooCommerce product search with debounce
        $('#woocommerce-product-search').on('keyup', function() {
            const searchTerm = $(this).val();

            clearTimeout(wcProductSearchTimeout);

            if (searchTerm.length < 2) {
                $('#woocommerce-product-results').html('').hide();
                return;
            }

            wcProductSearchTimeout = setTimeout(function() {
                searchWCProducts(searchTerm);
            }, 300);
        });

        /**
         * Search WooCommerce products
         */
        function searchWCProducts(searchTerm) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'search_woocommerce_products',
                    nonce: wpStaffDiary.nonce,
                    search: searchTerm
                },
                success: function(response) {
                    if (response.success) {
                        displayWCProductResults(response.data.products);
                    }
                },
                error: function() {
                    console.error('Error searching WooCommerce products');
                }
            });
        }

        /**
         * Display WooCommerce product search results
         */
        function displayWCProductResults(products) {
            if (products.length === 0) {
                $('#woocommerce-product-results').html('<div class="search-result-item">No products found</div>').show();
                positionWCProductDropdown();
                return;
            }

            let html = '';
            products.forEach(function(product) {
                const price = product.price ? `£${parseFloat(product.price).toFixed(2)}` : 'Price not set';
                const sku = product.sku ? ` (SKU: ${product.sku})` : '';

                html += `<div class="search-result-item wc-product-result" data-product-id="${product.id}" data-product-name="${product.name}" data-product-price="${product.price}" data-product-description="${product.description}">
                    <strong>${product.name}</strong>${sku}<br>
                    <span style="color: #2271b1; font-weight: 600;">${price}</span>
                </div>`;
            });

            $('#woocommerce-product-results').html(html).show();
            positionWCProductDropdown();
        }

        /**
         * Position the WooCommerce product search dropdown
         * Note: Positioning is now handled by CSS (position: absolute)
         */
        function positionWCProductDropdown() {
            // No longer needed - CSS handles positioning with absolute positioning
            // Keeping function stub for backwards compatibility
        }

        // Select WooCommerce product from search results
        $(document).on('click', '.wc-product-result', function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('product-price');
            const productDescription = $(this).data('product-description');

            if (productId) {
                selectWCProduct(productId, productName, productPrice, productDescription);
            }
        });

        /**
         * Select WooCommerce product and auto-populate fields
         */
        function selectWCProduct(productId, productName, productPrice, productDescription) {
            selectedWCProductId = productId;
            $('#woocommerce-product-id').val(productId);
            $('#selected-wc-product-name').text(productName);
            $('#selected-wc-product-display').show();
            $('#woocommerce-product-search').val('').hide();
            $('#woocommerce-product-results').html('').hide();

            // Auto-populate product fields
            $('#product-description').val(productDescription || productName);
            $('#price-per-sq-mtr').val(productPrice || '');

            // Enable the fields temporarily to allow edits
            $('#product-details-fields').find('input, textarea').prop('disabled', false);

            // Trigger calculations
            updateCalculations();
        }

        // Clear WooCommerce product selection
        $('#clear-wc-product-btn').on('click', function() {
            selectedWCProductId = 0;
            $('#woocommerce-product-id').val('');
            $('#selected-wc-product-display').hide();
            $('#woocommerce-product-search').val('').show();

            // Clear product fields
            $('#product-description').val('');
            $('#price-per-sq-mtr').val('');
            $('#sq-mtr-qty').val('');

            updateCalculations();
        });

        // ===========================================
        // ADDRESS HANDLING
        // ===========================================

        // Fitting address checkbox toggle
        $(document).on('change', '#fitting-address-different', function() {
            if ($(this).is(':checked')) {
                $('#fitting-address-section').slideDown();
            } else {
                $('#fitting-address-section').slideUp();
                // Clear fitting address fields when unchecked
                $('#fitting-address-line-1').val('');
                $('#fitting-address-line-2').val('');
                $('#fitting-address-line-3').val('');
                $('#fitting-postcode').val('');
            }
        });

        // Fitting date unknown checkbox toggle
        $(document).on('change', '#fitting-date-unknown', function() {
            if ($(this).is(':checked')) {
                $('#fitting-date').prop('disabled', true).val('');
                $('#fitting-time-period').prop('disabled', true).val('');
            } else {
                $('#fitting-date').prop('disabled', false);
                $('#fitting-time-period').prop('disabled', false);
            }
        });

        // ===========================================
        // ACCESSORIES & CALCULATIONS
        // ===========================================

        // Accessory checkbox change
        $('.accessory-checkbox').on('change', function() {
            const accessoryId = $(this).data('accessory-id');
            const quantityInput = $(`.accessory-quantity[data-accessory-id="${accessoryId}"]`);

            if ($(this).is(':checked')) {
                quantityInput.prop('disabled', false);
            } else {
                quantityInput.prop('disabled', true).val(1);
            }

            updateCalculations();
        });

        // Quantity input change
        $('.accessory-quantity').on('input', function() {
            updateCalculations();
        });

        // Product fields change
        $('#sq-mtr-qty, #price-per-sq-mtr, #fitting-cost').on('input', function() {
            updateCalculations();
        });

        /**
         * Update all calculations
         */
        function updateCalculations() {
            // Product total
            const sqMtrQty = parseFloat($('#sq-mtr-qty').val()) || 0;
            const pricePerSqMtr = parseFloat($('#price-per-sq-mtr').val()) || 0;
            const productTotal = sqMtrQty * pricePerSqMtr;

            $('#product-total-display').text(productTotal.toFixed(2));

            // Accessories total
            let accessoriesTotal = 0;
            $('.accessory-checkbox:checked').each(function() {
                const accessoryId = $(this).data('accessory-id');
                const price = parseFloat($(this).data('price'));
                const quantity = parseFloat($(`.accessory-quantity[data-accessory-id="${accessoryId}"]`).val()) || 1;
                accessoriesTotal += price * quantity;
            });

            $('#accessories-total-display').text(accessoriesTotal.toFixed(2));

            // Fitting cost
            const fittingCost = parseFloat($('#fitting-cost').val()) || 0;

            // Subtotal
            const subtotal = productTotal + fittingCost + accessoriesTotal;
            $('#subtotal-display').text(subtotal.toFixed(2));

            // VAT
            if (typeof vatEnabled !== 'undefined' && vatEnabled == 1) {
                const vatAmount = subtotal * (vatRate / 100);
                $('#vat-display').text(vatAmount.toFixed(2));

                // Total
                const total = subtotal + vatAmount;
                $('#total-display').text(total.toFixed(2));
            } else {
                $('#total-display').text(subtotal.toFixed(2));
            }
        }

        // ===========================================
        // VIEW ENTRY DETAILS
        // ===========================================

        /**
         * View entry details
         */
        function viewEntryDetails(entryId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    console.log('=== GET DIARY ENTRY DEBUG ===');
                    console.log('Full response:', JSON.stringify(response, null, 2));
                    if (response.success) {
                        // Data is wrapped in response.data.entry by the modular jobs controller
                        const entry = response.data.entry || response.data;
                        console.log('Order number:', entry.order_number);
                        console.log('Customer:', entry.customer);
                        console.log('Status:', entry.status);
                        console.log('Subtotal:', entry.subtotal);
                        console.log('Total:', entry.total);
                        console.log('Fitter ID:', entry.fitter_id);
                        displayEntryDetails(entry);
                    } else {
                        alert('Error loading entry: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while loading the entry.');
                }
            });
        }

        /**
         * Display entry details in view modal
         */
        function displayEntryDetails(entry) {
            let html = '<div class="job-sheet-content">';

            // Header
            html += `<div class="job-sheet-header">
                <h2>Job Sheet</h2>
                <div class="order-number-large">Order #${entry.order_number}</div>
            </div>`;

            // Customer Section
            html += '<div class="detail-section">';
            html += '<h3>Customer Details</h3>';
            if (entry.customer) {
                html += `<div class="detail-grid">
                    <div class="detail-item"><strong>Name:</strong> ${entry.customer.customer_name}</div>
                    ${entry.customer.customer_phone ? `<div class="detail-item"><strong>Phone:</strong> ${entry.customer.customer_phone}</div>` : ''}
                    ${entry.customer.customer_email ? `<div class="detail-item"><strong>Email:</strong> ${entry.customer.customer_email}</div>` : ''}
                    ${entry.customer.customer_address ? `<div class="detail-item full-width"><strong>Address:</strong> ${entry.customer.customer_address.replace(/\n/g, '<br>')}</div>` : ''}
                </div>`;
            } else {
                html += '<p style="color: #999;">No customer assigned</p>';
            }
            html += '</div>';

            // Job Details Section
            html += '<div class="detail-section">';
            html += '<h3>Job Details</h3>';
            html += '<div class="detail-grid">';
            if (entry.job_date) {
                html += `<div class="detail-item"><strong>Job Date:</strong> ${entry.job_date_formatted}${entry.job_time_formatted ? ' at ' + entry.job_time_formatted : ''}</div>`;
            }
            if (entry.fitting_date) {
                html += `<div class="detail-item"><strong>Fitting Date:</strong> ${entry.fitting_date_formatted}${entry.fitting_time_period ? ' (' + entry.fitting_time_period + ')' : ''}</div>`;
            }
            if (entry.area) {
                html += `<div class="detail-item"><strong>Area:</strong> ${entry.area}</div>`;
            }
            if (entry.size) {
                html += `<div class="detail-item"><strong>Size:</strong> ${entry.size}</div>`;
            }
            html += `<div class="detail-item"><strong>Status:</strong> <span class="status-badge status-${entry.status}">${wpStaffDiary.statuses[entry.status] || entry.status}</span></div>`;
            html += '</div>';
            html += '</div>';

            // Product Section
            if (entry.product_description || entry.sq_mtr_qty) {
                html += '<div class="detail-section">';
                html += '<h3>Product & Services</h3>';
                html += '<table class="financial-table">';
                html += '<thead><tr><th>Description</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>';
                html += '<tbody>';

                if (entry.product_description) {
                    const productTotal = (entry.sq_mtr_qty || 0) * (entry.price_per_sq_mtr || 0);
                    html += `<tr>
                        <td>${entry.product_description.replace(/\n/g, '<br>')}</td>
                        <td>${entry.sq_mtr_qty || '-'}</td>
                        <td>£${entry.price_per_sq_mtr ? parseFloat(entry.price_per_sq_mtr).toFixed(2) : '-'}</td>
                        <td>£${productTotal.toFixed(2)}</td>
                    </tr>`;
                }

                // Fitting cost row
                if (entry.fitting_cost && parseFloat(entry.fitting_cost) > 0) {
                    html += `<tr>
                        <td>Fitting Cost</td>
                        <td>-</td>
                        <td>-</td>
                        <td>£${parseFloat(entry.fitting_cost).toFixed(2)}</td>
                    </tr>`;
                }

                if (entry.accessories && entry.accessories.length > 0) {
                    entry.accessories.forEach(function(acc) {
                        html += `<tr>
                            <td>${acc.accessory_name}</td>
                            <td>${parseFloat(acc.quantity).toFixed(2)}</td>
                            <td>£${parseFloat(acc.price_per_unit).toFixed(2)}</td>
                            <td>£${parseFloat(acc.total_price).toFixed(2)}</td>
                        </tr>`;
                    });
                }

                html += '</tbody></table>';
                html += '</div>';
            }

            // Financial Summary
            html += '<div class="detail-section">';
            html += '<h3>Financial Summary</h3>';
            html += '<table class="financial-summary-table">';
            html += `<tr><td><strong>Subtotal:</strong></td><td class="amount">£${parseFloat(entry.subtotal).toFixed(2)}</td></tr>`;
            if (entry.vat_amount > 0) {
                html += `<tr><td><strong>VAT (${entry.vat_rate}%):</strong></td><td class="amount">£${parseFloat(entry.vat_amount).toFixed(2)}</td></tr>`;
            }
            html += `<tr class="total-row"><td><strong>Total:</strong></td><td class="amount"><strong>£${parseFloat(entry.total).toFixed(2)}</strong></td></tr>`;

            // Payments
            if (entry.payments && entry.payments.length > 0) {
                entry.payments.forEach(function(payment) {
                    html += `<tr class="payment-row">
                        <td>${payment.payment_type} (${payment.payment_method}) - Recorded by ${payment.recorded_by_name} on ${payment.recorded_at_formatted}</td>
                        <td class="amount">-£${parseFloat(payment.amount).toFixed(2)}</td>
                    </tr>`;
                });
            }

            // Balance
            const balance = parseFloat(entry.balance);
            const balanceClass = balance > 0 ? 'balance-due' : 'balance-paid';
            const balanceLabel = balance > 0 ? 'Balance Due:' : 'PAID IN FULL';
            const balanceAmount = balance > 0 ? `£${balance.toFixed(2)}` : '£0.00';
            html += `<tr class="${balanceClass}"><td><strong>${balanceLabel}</strong></td><td class="amount"><strong>${balanceAmount}</strong></td></tr>`;
            html += '</table>';

            // Payment Progress Visualization
            if (entry.total > 0) {
                const totalPaid = Math.max(0, parseFloat(entry.total) - balance);
                let percentPaid = (totalPaid / entry.total) * 100;

                // Clamp percentage between 0 and 100 to handle edge cases
                percentPaid = Math.min(100, Math.max(0, percentPaid));

                // Check for invalid numbers
                if (isNaN(percentPaid)) {
                    percentPaid = 0;
                }

                // Group payments by type
                const paymentsByType = {
                    'deposit': 0,
                    'partial': 0,
                    'final': 0,
                    'full': 0
                };
                if (entry.payments && entry.payments.length > 0) {
                    entry.payments.forEach(function(payment) {
                        const type = payment.payment_type || 'partial';
                        const amount = parseFloat(payment.amount);
                        if (!isNaN(amount) && amount > 0) {
                            paymentsByType[type] = (paymentsByType[type] || 0) + amount;
                        }
                    });
                }

                html += '<div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px;">';
                html += '<h4 style="margin-top: 0; margin-bottom: 10px; color: #2c3e50;">Payment Progress</h4>';

                // Progress bar
                html += '<div style="position: relative; background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; margin-bottom: 15px;">';
                if (totalPaid > 0) {
                    html += `<div style="position: absolute; left: 0; top: 0; height: 100%; width: ${percentPaid}%; background: linear-gradient(90deg, #10b981 0%, #34d399 100%); transition: width 0.3s ease;"></div>`;
                }
                html += `<div style="position: absolute; width: 100%; text-align: center; line-height: 30px; font-weight: 600; color: ${totalPaid > 0 ? '#fff' : '#666'}; text-shadow: ${totalPaid > 0 ? '0 1px 2px rgba(0,0,0,0.3)' : 'none'};">${percentPaid.toFixed(1)}% Paid</div>`;
                html += '</div>';

                // Payment breakdown
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">';
                html += `<div><strong>Total Due:</strong> £${parseFloat(entry.total).toFixed(2)}</div>`;
                html += `<div><strong>Total Paid:</strong> <span style="color: #10b981;">£${totalPaid.toFixed(2)}</span></div>`;
                if (paymentsByType['deposit'] > 0) {
                    html += `<div><strong>Deposits:</strong> £${paymentsByType['deposit'].toFixed(2)}</div>`;
                }
                if (paymentsByType['partial'] > 0) {
                    html += `<div><strong>Partial Payments:</strong> £${paymentsByType['partial'].toFixed(2)}</div>`;
                }
                if (paymentsByType['final'] > 0) {
                    html += `<div><strong>Final Payments:</strong> £${paymentsByType['final'].toFixed(2)}</div>`;
                }
                if (paymentsByType['full'] > 0) {
                    html += `<div><strong>Full Payments:</strong> £${paymentsByType['full'].toFixed(2)}</div>`;
                }
                html += '</div>';
                html += '</div>';
            }

            html += '</div>';

            // Photos Section
            html += '<div class="detail-section">';
            html += '<h3>Photos</h3>';
            if (entry.images && entry.images.length > 0) {
                // Group images by category
                const beforeImages = entry.images.filter(img => img.image_category === 'before');
                const duringImages = entry.images.filter(img => img.image_category === 'during');
                const afterImages = entry.images.filter(img => img.image_category === 'after');
                const generalImages = entry.images.filter(img => !img.image_category || img.image_category === 'general');

                // Show before/after comparison button if we have both
                if (beforeImages.length > 0 && afterImages.length > 0) {
                    html += `<button type="button" class="button" id="view-comparison-btn" data-entry-id="${entry.id}" style="margin-bottom: 15px;">
                        <span class="dashicons dashicons-image-flip-horizontal"></span> View Before/After Comparison
                    </button>`;
                }

                html += '<div class="job-images-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">';
                entry.images.forEach(function(image) {
                    const categoryColors = {
                        'before': '#3b82f6',
                        'during': '#f59e0b',
                        'after': '#10b981',
                        'general': '#6b7280'
                    };
                    const category = image.image_category || 'general';
                    const categoryColor = categoryColors[category] || categoryColors['general'];
                    const categoryLabel = category.charAt(0).toUpperCase() + category.slice(1);

                    html += `<div class="job-image-item" style="position: relative;">
                        <img src="${escapeHtml(image.image_url)}" alt="Job photo" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open('${escapeHtml(image.image_url)}', '_blank')">
                        <span style="position: absolute; top: 5px; right: 5px; background: ${categoryColor}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">${categoryLabel}</span>
                        ${image.image_caption ? `<p style="font-size: 12px; color: #666; margin-top: 5px;">${escapeHtml(image.image_caption)}</p>` : ''}
                    </div>`;
                });
                html += '</div>';
            } else {
                html += '<p style="color: #999;">No photos uploaded yet.</p>';
            }
            if (entry.is_cancelled != 1) {
                html += `<button type="button" class="button" id="upload-photo-btn" data-entry-id="${entry.id}">
                    <span class="dashicons dashicons-camera"></span> Upload Photo
                </button>`;
                html += `<input type="file" id="photo-upload-input-${entry.id}" accept="image/*" style="display: none;">`;
            }
            html += '</div>';

            // Record Payment Section
            if (entry.is_cancelled != 1 && entry.balance > 0) {
                html += '<div class="detail-section">';
                html += '<h3>Record Payment</h3>';
                html += `<div class="payment-form" style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px;"><strong>Amount (£):</strong></label>
                            <input type="number" id="payment-amount-${entry.id}" step="0.01" min="0.01" max="${entry.balance}" value="${entry.balance.toFixed(2)}" style="width: 100%;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px;"><strong>Payment Method:</strong></label>
                            <select id="payment-method-${entry.id}" style="width: 100%;">
                                ${Object.entries(wpStaffDiary.paymentMethods || {'cash': 'Cash', 'bank-transfer': 'Bank Transfer', 'card-payment': 'Card Payment'}).map(([key, label]) => `<option value="${key}">${label}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 5px;"><strong>Payment Type:</strong></label>
                        <select id="payment-type-${entry.id}" style="width: 100%;">
                            <option value="deposit">Deposit</option>
                            <option value="partial">Partial Payment</option>
                            <option value="final">Final Payment</option>
                            <option value="full">Full Payment</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 5px;"><strong>Notes:</strong></label>
                        <textarea id="payment-notes-${entry.id}" rows="2" style="width: 100%;"></textarea>
                    </div>
                    <button type="button" class="button button-primary" id="record-payment-btn" data-entry-id="${entry.id}">
                        <span class="dashicons dashicons-yes"></span> Record Payment
                    </button>
                </div>`;
                html += '</div>';
            }

            // Notes
            if (entry.notes) {
                html += '<div class="detail-section">';
                html += '<h3>Additional Notes</h3>';
                html += `<div class="notes-content">${entry.notes.replace(/\n/g, '<br>')}</div>`;
                html += '</div>';
            }

            // Actions
            html += '<div class="detail-section detail-actions">';
            if (entry.is_cancelled != 1) {
                html += `<button type="button" class="button" onclick="window.open('${wpStaffDiary.ajaxUrl.replace('admin-ajax.php', '')}admin-post.php?action=wp_staff_diary_download_pdf&entry_id=${entry.id}&nonce=${wpStaffDiary.nonce}')">
                    <span class="dashicons dashicons-pdf"></span> Download PDF
                </button>`;
                html += `<button type="button" class="button edit-entry" data-id="${entry.id}">
                    <span class="dashicons dashicons-edit"></span> Edit Job
                </button>`;
            }
            html += '</div>';

            html += '</div>'; // Close job-sheet-content

            $('#entry-details-content').html(html);
            $('#view-entry-modal').fadeIn();
        }

        // ===========================================
        // PHOTOS - Upload
        // ===========================================

        // Helper function to show photo category selection modal
        function showPhotoCategoryModal(file, entryId, callback) {
            // Prevent duplicate modals
            if ($('#photo-category-modal').length > 0) {
                return;
            }

            const categoryHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">Photo Category</h3>
                    <p>Select the category for this photo:</p>
                    <select id="photo-category-select" style="width: 100%; padding: 8px; margin-bottom: 15px;">
                        <option value="before">Before</option>
                        <option value="during">During</option>
                        <option value="after">After</option>
                        <option value="general">General</option>
                    </select>
                    <p>Add a caption (optional):</p>
                    <input type="text" id="photo-caption-input" placeholder="Enter photo caption..." style="width: 100%; padding: 8px; margin-bottom: 15px;">
                    <div style="text-align: right;">
                        <button type="button" class="button" id="cancel-photo-upload" style="margin-right: 10px;">Cancel</button>
                        <button type="button" class="button button-primary" id="confirm-photo-upload">Upload Photo</button>
                    </div>
                </div>
            `;

            // Create temporary modal
            $('body').append(`
                <div id="photo-category-modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);">
                    <div style="background-color: #fff; margin: 10% auto; padding: 0; border: 1px solid #888; width: 400px; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        ${categoryHtml}
                    </div>
                </div>
            `);

            $('#photo-category-modal').fadeIn();

            // Handle cancel
            $('#cancel-photo-upload').on('click', function() {
                $('#photo-category-modal').remove();
                $(document).off('keydown.photoCategoryModal');
                callback(null);
            });

            // Handle confirm
            $('#confirm-photo-upload').on('click', function() {
                const category = $('#photo-category-select').val();
                const caption = $('#photo-caption-input').val();
                $('#photo-category-modal').remove();
                $(document).off('keydown.photoCategoryModal');
                callback({category: category, caption: caption});
            });

            // Handle escape key
            $(document).on('keydown.photoCategoryModal', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    $('#photo-category-modal').remove();
                    $(document).off('keydown.photoCategoryModal');
                    callback(null);
                }
            });
        }

        // Photo upload button click
        $(document).on('click', '#upload-photo-btn', function() {
            const entryId = $(this).data('entry-id');
            $(`#photo-upload-input-${entryId}`).click();
        });

        // Photo file selected in view modal (not edit form)
        $(document).on('change', '[id^="photo-upload-input-"]:not(#photo-upload-input-form)', function() {
            const entryId = $(this).attr('id').replace('photo-upload-input-', '');
            const file = this.files[0];
            const $input = $(this);

            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                $input.val('');
                return;
            }

            // Show category selection modal
            showPhotoCategoryModal(file, entryId, function(result) {
                if (!result) {
                    // User cancelled
                    $input.val('');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'upload_job_image');
                formData.append('nonce', wpStaffDiary.nonce);
                formData.append('diary_entry_id', entryId);
                formData.append('image', file);
                formData.append('category', result.category);
                if (result.caption) {
                    formData.append('caption', result.caption);
                }

                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Photo uploaded successfully!');
                            viewEntryDetails(entryId); // Reload the view
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to upload photo'));
                        }
                        $input.val('');
                    },
                    error: function(xhr, status, error) {
                        console.error('Upload error:', xhr.responseText);
                        alert('An error occurred while uploading the photo.');
                        $input.val('');
                    }
                });
            });
        });

        // ===========================================
        // PAYMENTS - Record Payment
        // ===========================================

        // Record payment button click
        $(document).on('click', '#record-payment-btn', function() {
            const entryId = $(this).data('entry-id');
            const amount = $(`#payment-amount-${entryId}`).val();
            const method = $(`#payment-method-${entryId}`).val();
            const type = $(`#payment-type-${entryId}`).val();
            const notes = $(`#payment-notes-${entryId}`).val();

            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter a valid payment amount');
                return;
            }

            if (confirm(`Record payment of £${parseFloat(amount).toFixed(2)}?`)) {
                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'add_payment',
                        nonce: wpStaffDiary.nonce,
                        entry_id: entryId,
                        amount: amount,
                        payment_method: method,
                        payment_type: type,
                        notes: notes
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Payment recorded successfully!');
                            viewEntryDetails(entryId); // Reload the view
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to record payment'));
                        }
                    },
                    error: function() {
                        alert('An error occurred while recording the payment.');
                    }
                });
            }
        });

        // ===========================================
        // PHOTOS & PAYMENTS - Edit Form
        // ===========================================

        // Photo upload button click in edit form
        $(document).on('click', '#upload-photo-form-btn', function() {
            $('#photo-upload-input-form').click();
        });

        // Photo file selected in edit form
        $(document).on('change', '#photo-upload-input-form', function() {
            const entryId = $(this).data('entry-id');
            const file = this.files[0];
            const $input = $(this);

            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                $input.val('');
                return;
            }

            // Show category selection modal
            showPhotoCategoryModal(file, entryId, function(result) {
                if (!result) {
                    // User cancelled
                    $input.val('');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'upload_job_image');
                formData.append('nonce', wpStaffDiary.nonce);
                formData.append('diary_entry_id', entryId);
                formData.append('image', file);
                formData.append('category', result.category);
                if (result.caption) {
                    formData.append('caption', result.caption);
                }

                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Photo uploaded successfully!');
                            // Reload entry to show new photo
                            loadEntryForEdit(entryId);
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to upload photo'));
                        }
                        $input.val('');
                    },
                    error: function() {
                        alert('An error occurred while uploading the photo.');
                        $input.val('');
                    }
                });
            });
        });

        // Before/After comparison button click
        $(document).on('click', '#view-comparison-btn', function() {
            const entryId = $(this).data('entry-id');

            // Fetch full entry data
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    if (response.success && response.data.entry) {
                        showBeforeAfterComparison(response.data.entry);
                    } else {
                        alert('Error loading photos');
                    }
                },
                error: function() {
                    alert('Failed to load photos');
                }
            });
        });

        // Show before/after comparison modal
        function showBeforeAfterComparison(entry) {
            // Prevent duplicate modals
            if ($('#comparison-modal').length > 0) {
                return;
            }

            // Validate entry has images
            if (!entry.images || entry.images.length === 0) {
                alert('No images found for this job');
                return;
            }

            const beforeImages = entry.images.filter(img => img.image_category === 'before');
            const afterImages = entry.images.filter(img => img.image_category === 'after');
            const duringImages = entry.images.filter(img => img.image_category === 'during');

            let comparisonHtml = `
                <div style="padding: 20px;">
                    <h2 style="margin-top: 0;">Before/After Comparison - ${escapeHtml(entry.order_number)}</h2>
                    <div style="margin-bottom: 20px;">
                        <p style="color: #666;">Job Date: ${new Date(entry.job_date).toLocaleDateString('en-GB')}</p>
                    </div>
            `;

            // Create side-by-side comparisons
            const maxPairs = Math.max(beforeImages.length, afterImages.length);
            for (let i = 0; i < maxPairs; i++) {
                comparisonHtml += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 20px;">';

                // Before column
                comparisonHtml += '<div>';
                comparisonHtml += '<h3 style="color: #3b82f6; margin-bottom: 10px;"><span class="dashicons dashicons-arrow-left-alt"></span> Before</h3>';
                if (beforeImages[i]) {
                    comparisonHtml += `
                        <img src="${escapeHtml(beforeImages[i].image_url)}" alt="Before" style="width: 100%; height: 300px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #3b82f6;" onclick="window.open('${escapeHtml(beforeImages[i].image_url)}', '_blank')">
                        ${beforeImages[i].image_caption ? `<p style="font-size: 13px; color: #666; margin-top: 8px;">${escapeHtml(beforeImages[i].image_caption)}</p>` : ''}
                    `;
                } else {
                    comparisonHtml += '<p style="color: #999; font-style: italic;">No before photo</p>';
                }
                comparisonHtml += '</div>';

                // After column
                comparisonHtml += '<div>';
                comparisonHtml += '<h3 style="color: #10b981; margin-bottom: 10px;"><span class="dashicons dashicons-arrow-right-alt"></span> After</h3>';
                if (afterImages[i]) {
                    comparisonHtml += `
                        <img src="${escapeHtml(afterImages[i].image_url)}" alt="After" style="width: 100%; height: 300px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #10b981;" onclick="window.open('${escapeHtml(afterImages[i].image_url)}', '_blank')">
                        ${afterImages[i].image_caption ? `<p style="font-size: 13px; color: #666; margin-top: 8px;">${escapeHtml(afterImages[i].image_caption)}</p>` : ''}
                    `;
                } else {
                    comparisonHtml += '<p style="color: #999; font-style: italic;">No after photo</p>';
                }
                comparisonHtml += '</div>';

                comparisonHtml += '</div>';
            }

            // Show during photos if any
            if (duringImages.length > 0) {
                comparisonHtml += '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">';
                comparisonHtml += '<h3 style="color: #f59e0b; margin-bottom: 15px;"><span class="dashicons dashicons-images-alt2"></span> During Progress</h3>';
                comparisonHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
                duringImages.forEach(function(image) {
                    comparisonHtml += `
                        <div>
                            <img src="${escapeHtml(image.image_url)}" alt="During" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #f59e0b;" onclick="window.open('${escapeHtml(image.image_url)}', '_blank')">
                            ${image.image_caption ? `<p style="font-size: 12px; color: #666; margin-top: 5px;">${escapeHtml(image.image_caption)}</p>` : ''}
                        </div>
                    `;
                });
                comparisonHtml += '</div></div>';
            }

            comparisonHtml += `
                    <div style="text-align: right; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <button type="button" class="button button-primary" id="close-comparison-modal">Close</button>
                    </div>
                </div>
            `;

            // Create comparison modal
            $('body').append(`
                <div id="comparison-modal" style="display: none; position: fixed; z-index: 999998; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8);">
                    <div style="background-color: #fff; margin: 2% auto; max-width: 1200px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
                        ${comparisonHtml}
                    </div>
                </div>
            `);

            $('#comparison-modal').fadeIn();

            // Cleanup function
            function closeComparisonModal() {
                $('#comparison-modal').remove();
                $(document).off('keydown.comparisonModal');
            }

            // Handle close button
            $('#close-comparison-modal').on('click', closeComparisonModal);

            // Close on background click
            $('#comparison-modal').on('click', function(e) {
                if (e.target.id === 'comparison-modal') {
                    closeComparisonModal();
                }
            });

            // Handle escape key
            $(document).on('keydown.comparisonModal', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    closeComparisonModal();
                }
            });
        }

        // Record payment button click in edit form
        $(document).on('click', '#record-payment-form-btn', function() {
            const entryId = $(this).data('entry-id');
            const amount = $('#payment-amount-form').val();
            const method = $('#payment-method-form').val();
            const type = $('#payment-type-form').val();
            const notes = $('#payment-notes-form').val();

            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter a valid payment amount');
                return;
            }

            if (confirm(`Record payment of £${parseFloat(amount).toFixed(2)}?`)) {
                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'add_payment',
                        nonce: wpStaffDiary.nonce,
                        entry_id: entryId,
                        amount: amount,
                        payment_method: method,
                        payment_type: type,
                        notes: notes
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Payment recorded successfully!');
                            // Reload entry to update balance
                            loadEntryForEdit(entryId);
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to record payment'));
                        }
                    },
                    error: function() {
                        alert('An error occurred while recording the payment.');
                    }
                });
            }
        });

        // ===========================================
        // DELETE ENTRY
        // ===========================================

        /**
         * Delete entry
         */
        function deleteEntry(entryId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the entry.');
                }
            });
        }

        // ===========================================
        // PAYMENT REMINDERS
        // ===========================================

        /**
         * Send payment reminder button click
         */
        $(document).on('click', '#send-payment-reminder-btn', function() {
            const entryId = $(this).data('entry-id');
            const customerEmail = $(this).data('customer-email');
            const customerName = $(this).data('customer-name');
            const balance = $(this).data('balance');

            if (!customerEmail) {
                alert('Customer has no email address on file');
                return;
            }

            if (parseFloat(balance) <= 0) {
                alert('This job has no outstanding balance');
                return;
            }

            showPaymentReminderModal(entryId, customerEmail, customerName, balance);
        });

        /**
         * Show payment reminder modal
         */
        function showPaymentReminderModal(entryId, customerEmail, customerName, balance) {
            let html = '<div id="payment-reminder-modal-content">';
            html += '<h2>Send Payment Reminder</h2>';
            html += '<p><strong>Customer:</strong> ' + customerName + '</p>';
            html += '<p><strong>Email:</strong> ' + customerEmail + '</p>';
            html += '<p><strong>Outstanding Balance:</strong> £' + parseFloat(balance).toFixed(2) + '</p>';
            html += '<form id="payment-reminder-form">';
            html += '<div class="form-field">';
            html += '<label for="payment-reminder-message">Custom Message (Optional)</label>';
            html += '<textarea id="payment-reminder-message" rows="6" placeholder="Leave blank to use default reminder template..."></textarea>';
            html += '<p class="description">A professional payment reminder will be sent to the customer.</p>';
            html += '</div>';
            html += '<div class="modal-footer">';
            html += '<button type="submit" class="button button-primary"><span class="dashicons dashicons-email"></span> Send Reminder</button>';
            html += '<button type="button" class="button close-payment-reminder-modal">Cancel</button>';
            html += '</div>';
            html += '</form>';
            html += '</div>';

            // Create modal if doesn't exist
            if ($('#payment-reminder-modal').length === 0) {
                $('body').append('<div id="payment-reminder-modal" class="wp-staff-diary-modal"><div class="wp-staff-diary-modal-content"><span class="wp-staff-diary-modal-close">&times;</span><div id="payment-reminder-modal-body"></div></div></div>');
            }

            $('#payment-reminder-modal-body').html(html);
            $('#payment-reminder-modal').fadeIn(200);

            // Form submit handler
            $('#payment-reminder-form').off('submit').on('submit', function(e) {
                e.preventDefault();
                sendPaymentReminder(entryId, $('#payment-reminder-message').val());
            });

            // Close modal handlers
            $('.close-payment-reminder-modal, #payment-reminder-modal .wp-staff-diary-modal-close').off('click').on('click', function() {
                $('#payment-reminder-modal').fadeOut(200);
            });

            $('#payment-reminder-modal').off('click').on('click', function(e) {
                if (e.target.id === 'payment-reminder-modal') {
                    $(this).fadeOut(200);
                }
            });
        }

        /**
         * Send payment reminder
         */
        function sendPaymentReminder(entryId, customMessage) {
            const $form = $('#payment-reminder-form');
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> Sending...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'send_payment_reminder',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId,
                    custom_message: customMessage
                },
                success: function(response) {
                    if (response.success) {
                        alert('Payment reminder sent successfully!');
                        $('#payment-reminder-modal').fadeOut(200);
                        // Reload entry to see updated reminder history
                        if (typeof loadEntryForEdit === 'function') {
                            loadEntryForEdit(entryId);
                        }
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to send reminder'));
                    }
                },
                error: function() {
                    alert('An error occurred while sending the reminder.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }

        // ===========================================
        // JOB TEMPLATES
        // ===========================================

        /**
         * Save current form as template button click
         */
        $(document).on('click', '#save-as-template-btn', function() {
            showSaveTemplateModal();
        });

        /**
         * Load from template button click
         */
        $(document).on('click', '#load-from-template-btn', function() {
            showTemplateSelectionModal();
        });

        /**
         * Show save template modal
         */
        function showSaveTemplateModal() {
            // Get current form values
            const formData = {
                product_description: $('#product-description').val() || '',
                sq_mtr_qty: $('#sq-mtr-qty').val() || '',
                price_per_sq_mtr: $('#price-per-sq-mtr').val() || '',
                fitting_cost: $('#fitting-cost').val() || '',
                accessories: []
            };

            // Get selected accessories
            $('.accessory-checkbox:checked').each(function() {
                const accessoryId = $(this).data('accessory-id');
                const quantity = $('.accessory-quantity[data-accessory-id="' + accessoryId + '"]').val();
                formData.accessories.push({
                    id: accessoryId,
                    name: $(this).data('accessory-name'),
                    price: $(this).data('price'),
                    quantity: quantity
                });
            });

            let html = '<div id="save-template-modal-content">';
            html += '<h2>Save as Template</h2>';
            html += '<form id="save-template-form">';
            html += '<div class="form-field">';
            html += '<label for="template-name">Template Name <span class="required">*</span></label>';
            html += '<input type="text" id="template-name" class="regular-text" required>';
            html += '</div>';
            html += '<div class="form-field">';
            html += '<label for="template-description">Description (Optional)</label>';
            html += '<textarea id="template-description" rows="3"></textarea>';
            html += '</div>';
            html += '<div class="modal-footer">';
            html += '<button type="submit" class="button button-primary">Save Template</button>';
            html += '<button type="button" class="button close-save-template-modal">Cancel</button>';
            html += '</div>';
            html += '</form>';
            html += '</div>';

            // Create modal if doesn't exist
            if ($('#save-template-modal').length === 0) {
                $('body').append('<div id="save-template-modal" class="wp-staff-diary-modal"><div class="wp-staff-diary-modal-content"><span class="wp-staff-diary-modal-close">&times;</span><div id="save-template-modal-body"></div></div></div>');
            }

            $('#save-template-modal-body').html(html);
            $('#save-template-modal').fadeIn(200);

            // Form submit handler
            $('#save-template-form').off('submit').on('submit', function(e) {
                e.preventDefault();
                saveJobTemplate(formData, $('#template-name').val(), $('#template-description').val());
            });

            // Close modal handlers
            $('.close-save-template-modal, #save-template-modal .wp-staff-diary-modal-close').off('click').on('click', function() {
                $('#save-template-modal').fadeOut(200);
            });

            $('#save-template-modal').off('click').on('click', function(e) {
                if (e.target.id === 'save-template-modal') {
                    $(this).fadeOut(200);
                }
            });
        }

        /**
         * Save job template
         */
        function saveJobTemplate(formData, templateName, templateDescription) {
            const $submitBtn = $('#save-template-form button[type="submit"]');
            const originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> Saving...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'save_job_template',
                    nonce: wpStaffDiary.nonce,
                    template_name: templateName,
                    template_description: templateDescription,
                    product_description: formData.product_description,
                    sq_mtr_qty: formData.sq_mtr_qty,
                    price_per_sq_mtr: formData.price_per_sq_mtr,
                    fitting_cost: formData.fitting_cost,
                    accessories: formData.accessories
                },
                success: function(response) {
                    if (response.success) {
                        alert('Template saved successfully!');
                        $('#save-template-modal').fadeOut(200);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to save template'));
                    }
                },
                error: function() {
                    alert('An error occurred while saving the template.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        }

        /**
         * Show template selection modal
         */
        function showTemplateSelectionModal() {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_job_templates',
                    nonce: wpStaffDiary.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayTemplateSelectionModal(response.data.templates);
                    } else {
                        alert('Error loading templates: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('An error occurred while loading templates.');
                }
            });
        }

        /**
         * Display template selection modal
         */
        function displayTemplateSelectionModal(templates) {
            let html = '<div id="template-selection-modal-content">';
            html += '<h2>Load from Template</h2>';

            if (templates.length === 0) {
                html += '<p>No templates available. Save your first template using "Save as Template" button.</p>';
                html += '<div class="modal-footer">';
                html += '<button type="button" class="button close-template-selection-modal">Close</button>';
                html += '</div>';
            } else {
                html += '<div class="template-list" style="max-height: 400px; overflow-y: auto;">';
                templates.forEach(function(template) {
                    html += '<div class="template-item" style="padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px; cursor: pointer;" data-template-id="' + template.id + '">';
                    html += '<h3 style="margin: 0 0 5px 0;">' + template.template_name + '</h3>';
                    if (template.template_description) {
                        html += '<p style="margin: 0 0 5px 0; color: #666;">' + template.template_description + '</p>';
                    }
                    if (template.product_description) {
                        html += '<p style="margin: 0; font-size: 12px; color: #999;">Product: ' + template.product_description.substring(0, 60) + (template.product_description.length > 60 ? '...' : '') + '</p>';
                    }
                    html += '<div style="margin-top: 10px;">';
                    html += '<button type="button" class="button button-primary button-small load-template-btn" data-template-id="' + template.id + '">Load</button>';
                    html += '<button type="button" class="button button-small delete-template-btn" data-template-id="' + template.id + '" style="margin-left: 5px;">Delete</button>';
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                html += '<div class="modal-footer" style="margin-top: 15px;">';
                html += '<button type="button" class="button close-template-selection-modal">Cancel</button>';
                html += '</div>';
            }

            html += '</div>';

            // Create modal if doesn't exist
            if ($('#template-selection-modal').length === 0) {
                $('body').append('<div id="template-selection-modal" class="wp-staff-diary-modal"><div class="wp-staff-diary-modal-content"><span class="wp-staff-diary-modal-close">&times;</span><div id="template-selection-modal-body"></div></div></div>');
            }

            $('#template-selection-modal-body').html(html);
            $('#template-selection-modal').fadeIn(200);

            // Load template button click
            $(document).on('click', '.load-template-btn', function() {
                const templateId = $(this).data('template-id');
                loadJobTemplate(templateId);
            });

            // Delete template button click
            $(document).on('click', '.delete-template-btn', function() {
                const templateId = $(this).data('template-id');
                if (confirm('Are you sure you want to delete this template?')) {
                    deleteJobTemplate(templateId);
                }
            });

            // Close modal handlers
            $('.close-template-selection-modal, #template-selection-modal .wp-staff-diary-modal-close').off('click').on('click', function() {
                $('#template-selection-modal').fadeOut(200);
            });

            $('#template-selection-modal').off('click').on('click', function(e) {
                if (e.target.id === 'template-selection-modal') {
                    $(this).fadeOut(200);
                }
            });
        }

        /**
         * Load job template
         */
        function loadJobTemplate(templateId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_job_template',
                    nonce: wpStaffDiary.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        const template = response.data.template;

                        // Fill form fields
                        $('#product-description').val(template.product_description || '');
                        $('#sq-mtr-qty').val(template.sq_mtr_qty || '');
                        $('#price-per-sq-mtr').val(template.price_per_sq_mtr || '');
                        $('#fitting-cost').val(template.fitting_cost || '0.00');

                        // Clear all accessory selections first
                        $('.accessory-checkbox').prop('checked', false);
                        $('.accessory-quantity').prop('disabled', true).val('1');

                        // Load accessories
                        if (template.accessories && template.accessories.length > 0) {
                            template.accessories.forEach(function(accessory) {
                                const checkbox = $('.accessory-checkbox[data-accessory-id="' + accessory.id + '"]');
                                const quantityInput = $('.accessory-quantity[data-accessory-id="' + accessory.id + '"]');

                                checkbox.prop('checked', true);
                                quantityInput.prop('disabled', false).val(accessory.quantity);
                            });
                        }

                        // Trigger calculation update
                        if (typeof updateCalculations === 'function') {
                            updateCalculations();
                        }

                        $('#template-selection-modal').fadeOut(200);
                        alert('Template loaded successfully!');
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to load template'));
                    }
                },
                error: function() {
                    alert('An error occurred while loading the template.');
                }
            });
        }

        /**
         * Delete job template
         */
        function deleteJobTemplate(templateId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_job_template',
                    nonce: wpStaffDiary.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Template deleted successfully!');
                        // Refresh template list
                        showTemplateSelectionModal();
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to delete template'));
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the template.');
                }
            });
        }

        // ===========================================
        // BULK ACTIONS
        // ===========================================

        /**
         * Select all checkboxes for bulk actions
         */
        $(document).on('change', '#select-all-jobs', function() {
            $('.bulk-select-job').prop('checked', $(this).is(':checked'));
            updateBulkActionButtons();
        });

        /**
         * Individual checkbox change
         */
        $(document).on('change', '.bulk-select-job', function() {
            updateBulkActionButtons();

            // Update select all checkbox
            const totalCheckboxes = $('.bulk-select-job').length;
            const checkedCheckboxes = $('.bulk-select-job:checked').length;
            $('#select-all-jobs').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        /**
         * Update bulk action buttons visibility and count
         */
        function updateBulkActionButtons() {
            const selectedCount = $('.bulk-select-job:checked').length;

            if (selectedCount > 0) {
                $('#bulk-actions-bar').show();
                $('#selected-count').text(selectedCount);
            } else {
                $('#bulk-actions-bar').hide();
            }
        }

        /**
         * Bulk update status
         */
        $(document).on('click', '#bulk-update-status-btn', function() {
            const selectedIds = getSelectedJobIds();

            if (selectedIds.length === 0) {
                alert('Please select jobs first');
                return;
            }

            const newStatus = prompt('Enter new status (pending, in-progress, completed, cancelled):');

            if (!newStatus) {
                return;
            }

            if (confirm(`Update status for ${selectedIds.length} job(s) to "${newStatus}"?`)) {
                performBulkAction('bulk_update_status', {entry_ids: selectedIds, new_status: newStatus}, this);
            }
        });

        /**
         * Bulk delete jobs
         */
        $(document).on('click', '#bulk-delete-btn', function() {
            const selectedIds = getSelectedJobIds();

            if (selectedIds.length === 0) {
                alert('Please select jobs first');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedIds.length} job(s)? This action cannot be undone.`)) {
                performBulkAction('bulk_delete_jobs', {entry_ids: selectedIds}, this);
            }
        });

        /**
         * Bulk export to CSV
         */
        $(document).on('click', '#bulk-export-btn', function() {
            const selectedIds = getSelectedJobIds();

            if (selectedIds.length === 0) {
                alert('Please select jobs first');
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bulk_export_jobs',
                    nonce: wpStaffDiary.nonce,
                    entry_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        // Convert to CSV and download
                        const csvContent = response.data.csv_data.map(row =>
                            row.map(cell => '"' + String(cell).replace(/"/g, '""') + '"').join(',')
                        ).join('\n');

                        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);

                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        alert('Export completed successfully!');
                    } else {
                        alert('Error: ' + (response.data.message || 'Export failed'));
                    }
                },
                error: function() {
                    alert('An error occurred during export');
                }
            });
        });

        /**
         * Get selected job IDs
         */
        function getSelectedJobIds() {
            const ids = [];
            $('.bulk-select-job:checked').each(function() {
                ids.push($(this).val());
            });
            return ids;
        }

        /**
         * Perform bulk action
         */
        function performBulkAction(action, data, btnElement) {
            const $btn = $(btnElement);
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: wpStaffDiary.nonce,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Reload to show updated jobs
                    } else {
                        alert('Error: ' + (response.data.message || 'Action failed'));
                    }
                },
                error: function() {
                    alert('An error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        }

        // ===========================================
        // UTILITY FUNCTIONS
        // ===========================================

        /**
         * Update query string parameter
         */
        function updateQueryStringParameter(uri, key, value) {
            const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            const separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            } else {
                return uri + separator + key + "=" + value;
            }
        }

    });

})(jQuery);
