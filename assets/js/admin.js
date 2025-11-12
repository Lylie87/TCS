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
                job_date: $('#job-date').val(),
                job_time: $('#job-time').val(),
                fitting_date: $('#fitting-date').val(),
                fitting_time_period: $('#fitting-time-period').val(),
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
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        $('#save-entry-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Job');
                    }
                },
                error: function() {
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
                        <td>${payment.payment_type} (${payment.payment_method}) - ${payment.recorded_at_formatted}</td>
                        <td class="amount">-£${parseFloat(payment.amount).toFixed(2)}</td>
                    </tr>`;
                });
            }

            // Balance
            const balanceClass = entry.balance > 0 ? 'balance-due' : 'balance-paid';
            html += `<tr class="${balanceClass}"><td><strong>Balance Due:</strong></td><td class="amount"><strong>£${parseFloat(entry.balance).toFixed(2)}</strong></td></tr>`;
            html += '</table>';
            html += '</div>';

            // Notes
            if (entry.notes) {
                html += '<div class="detail-section">';
                html += '<h3>Additional Notes</h3>';
                html += `<div class="notes-content">${entry.notes.replace(/\n/g, '<br>')}</div>`;
                html += '</div>';
            }

            // Actions
            html += '<div class="detail-section detail-actions">';
            if (!entry.is_cancelled) {
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
