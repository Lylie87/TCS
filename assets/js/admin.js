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

        // Click outside modal to close
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('wp-staff-diary-modal')) {
                $('.wp-staff-diary-modal').fadeOut();
            }
        });

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
                        populateEntryForm(response.data);
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
            $('#area').val(entry.area);
            $('#size').val(entry.size);

            // Fitter
            if (entry.fitter_id !== undefined && entry.fitter_id !== null) {
                $('#fitter-id').val(entry.fitter_id);
            } else {
                $('#fitter-id').val('');
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

            // Photos section - show when editing existing entry
            if (entry.id && entry.id > 0) {
                $('#photos-section').show();

                // Display existing photos
                if (entry.images && entry.images.length > 0) {
                    let photosHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px;">';
                    entry.images.forEach(function(image) {
                        photosHtml += `<div style="position: relative;">
                            <img src="${image.image_url}" alt="Job photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open('${image.image_url}', '_blank')">
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

            const formData = {
                action: 'save_diary_entry',
                nonce: wpStaffDiary.nonce,
                entry_id: $('#entry-id').val(),
                customer_id: $('#customer-id').val(),
                fitter_id: $('#fitter-id').val(),
                job_date: $('#job-date').val(),
                job_time: $('#job-time').val(),
                fitting_date: $('#fitting-date').val(),
                fitting_time_period: $('#fitting-time-period').val(),
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
                notes: $('#notes').val(),
                status: $('#status').val(),
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
                html += `<div class="search-result-item" data-customer-id="${customer.id}">
                    <strong>${customer.customer_name}</strong><br>
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
        $('#sq-mtr-qty, #price-per-sq-mtr').on('input', function() {
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

            // Subtotal
            const subtotal = productTotal + accessoriesTotal;
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
                    if (response.success) {
                        displayEntryDetails(response.data);
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
            const balanceClass = entry.balance > 0 ? 'balance-due' : 'balance-paid';
            html += `<tr class="${balanceClass}"><td><strong>Balance Due:</strong></td><td class="amount"><strong>£${parseFloat(entry.balance).toFixed(2)}</strong></td></tr>`;
            html += '</table>';
            html += '</div>';

            // Photos Section
            html += '<div class="detail-section">';
            html += '<h3>Photos</h3>';
            if (entry.images && entry.images.length > 0) {
                html += '<div class="job-images-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">';
                entry.images.forEach(function(image) {
                    html += `<div class="job-image-item" style="position: relative;">
                        <img src="${image.image_url}" alt="Job photo" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open('${image.image_url}', '_blank')">
                        ${image.image_caption ? `<p style="font-size: 12px; color: #666; margin-top: 5px;">${image.image_caption}</p>` : ''}
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

        // Photo upload button click
        $(document).on('click', '#upload-photo-btn', function() {
            const entryId = $(this).data('entry-id');
            $(`#photo-upload-input-${entryId}`).click();
        });

        // Photo file selected in view modal (not edit form)
        $(document).on('change', '[id^="photo-upload-input-"]:not(#photo-upload-input-form)', function() {
            const entryId = $(this).attr('id').replace('photo-upload-input-', '');
            const file = this.files[0];

            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_job_image');
            formData.append('nonce', wpStaffDiary.nonce);
            formData.append('entry_id', entryId);
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
                        viewEntryDetails(entryId); // Reload the view
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to upload photo'));
                    }
                },
                error: function() {
                    alert('An error occurred while uploading the photo.');
                }
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

            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_job_image');
            formData.append('nonce', wpStaffDiary.nonce);
            formData.append('entry_id', entryId);
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
                        // Reload entry to show new photo
                        loadEntryForEdit(entryId);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to upload photo'));
                    }
                },
                error: function() {
                    alert('An error occurred while uploading the photo.');
                }
            });

            // Clear the file input
            $(this).val('');
        });

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
