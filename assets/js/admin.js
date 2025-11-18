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
        let currentDiscountType = null;
        let currentDiscountValue = 0;

        // ===========================================
        // PHOTO CATEGORY MODAL
        // ===========================================

        /**
         * Show photo category selection modal
         */
        function showPhotoCategory(file, entryId, callback) {
            // Prevent duplicate modals
            if ($('#photo-category-modal-admin').length > 0) {
                return;
            }

            const categoryHtml = `
                <div style="padding: 20px;">
                    <h3 style="margin-top: 0;">Photo Category</h3>
                    <p>Select the category for this photo:</p>
                    <select id="photo-category-select-admin" style="width: 100%; padding: 8px; margin-bottom: 15px;">
                        <option value="before">Before</option>
                        <option value="during">During</option>
                        <option value="after">After</option>
                        <option value="general">General</option>
                    </select>
                    <p>Add a caption (optional):</p>
                    <input type="text" id="photo-caption-input-admin" placeholder="Enter photo caption..." style="width: 100%; padding: 8px; margin-bottom: 15px;">
                    <div style="text-align: right;">
                        <button type="button" class="button" id="cancel-photo-upload-admin" style="margin-right: 10px;">Cancel</button>
                        <button type="button" class="button button-primary" id="confirm-photo-upload-admin">Upload Photo</button>
                    </div>
                </div>
            `;

            // Create temporary modal
            $('body').append(`
                <div id="photo-category-modal-admin" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);">
                    <div style="background-color: #fff; margin: 10% auto; padding: 0; border: 1px solid #888; width: 400px; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        ${categoryHtml}
                    </div>
                </div>
            `);

            $('#photo-category-modal-admin').fadeIn();

            // Handle cancel
            $('#cancel-photo-upload-admin').on('click', function() {
                $('#photo-category-modal-admin').remove();
                $(document).off('keydown.photoCategoryModalAdmin');
                callback(null);
            });

            // Handle confirm
            $('#confirm-photo-upload-admin').on('click', function() {
                const category = $('#photo-category-select-admin').val();
                const caption = $('#photo-caption-input-admin').val();
                $('#photo-category-modal-admin').remove();
                $(document).off('keydown.photoCategoryModalAdmin');
                callback({category: category, caption: caption});
            });

            // Handle escape key
            $(document).on('keydown.photoCategoryModalAdmin', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    $('#photo-category-modal-admin').remove();
                    $(document).off('keydown.photoCategoryModalAdmin');
                    callback(null);
                }
            });
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
        $(document).on('click', '#add-new-entry', function() {
            openEntryModal();
        });

        // Add new measure button
        $(document).on('click', '#add-new-measure', function() {
            openMeasureModal();
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

        // Cancel entry button
        $(document).on('click', '.cancel-entry', function() {
            const entryId = $(this).data('id');
            if (confirm('Are you sure you want to cancel this entry? It will be removed from the calendar but can be restored later.')) {
                cancelEntry(entryId);
            }
        });

        // Submit entry form
        $('#diary-entry-form').on('submit', function(e) {
            e.preventDefault();
            saveEntry();
        });

        // Submit measure form
        $('#measure-entry-form').on('submit', function(e) {
            e.preventDefault();
            saveMeasure();
        });

        /**
         * Open modal for new entry
         */
        function openEntryModal() {
            currentEntryId = 0;
            selectedCustomerId = 0;
            currentDiscountType = null;
            currentDiscountValue = 0;
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
         * Open measure modal for adding new measure
         */
        function openMeasureModal() {
            $('#measure-modal-title').text('Add New Measure');
            $('#measure-entry-form')[0].reset();
            $('#measure-entry-id').val('');
            $('#measure-customer-id').val('');
            $('#measure-number-display').hide();
            $('#measure-date').val(new Date().toISOString().split('T')[0]);
            $('#measure-job-date').val(new Date().toISOString().split('T')[0]);
            $('#measure-photos-container').html('<p class="description">No photos uploaded yet.</p>');

            // Reset customer selection
            $('#measure-customer-search-container').show();
            $('#measure-selected-customer-display').hide();
            $('#measure-manual-customer-entry').hide();
            $('#measure-customer-search').val('');

            $('#measure-modal').fadeIn();
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
            $('#quote-date').val(entry.quote_date);
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

            // Address fields - fitting is primary, billing is optional
            $('#fitting-address-line-1').val(entry.fitting_address_line_1 || '');
            $('#fitting-address-line-2').val(entry.fitting_address_line_2 || '');
            $('#fitting-address-line-3').val(entry.fitting_address_line_3 || '');
            $('#fitting-postcode').val(entry.fitting_postcode || '');

            // Billing address (if different)
            if (entry.fitting_address_different == 1) {
                $('#billing-address-different').prop('checked', true);
                $('#billing-address-section').show();
                $('#billing-address-line-1').val(entry.billing_address_line_1 || '');
                $('#billing-address-line-2').val(entry.billing_address_line_2 || '');
                $('#billing-address-line-3').val(entry.billing_address_line_3 || '');
                $('#billing-postcode').val(entry.billing_postcode || '');
            } else {
                $('#billing-address-different').prop('checked', false);
                $('#billing-address-section').hide();
            }

            // Product
            $('#product-description').val(entry.product_description);
            $('#sq-mtr-qty').val(entry.sq_mtr_qty);
            $('#price-per-sq-mtr').val(entry.price_per_sq_mtr);
            $('#fitting-cost').val(entry.fitting_cost || 0);

            // Discount information
            if (entry.discount_type && entry.discount_value > 0) {
                currentDiscountType = entry.discount_type;
                currentDiscountValue = parseFloat(entry.discount_value);
            } else {
                currentDiscountType = null;
                currentDiscountValue = 0;
            }

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
                        const categoryLabel = image.category ? ` (${image.category})` : '';
                        const captionLabel = image.image_caption ? `<div style="font-size: 11px; margin-top: 4px; color: #666;">${image.image_caption}</div>` : '';

                        photosHtml += `<div style="position: relative;">
                            <img src="${image.image_url}"
                                 alt="Job photo"
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                 onclick="window.open('${image.image_url}', '_blank')"
                                 title="Click to open full size">
                            <div style="font-size: 10px; margin-top: 2px; color: #999; font-weight: 600;">${categoryLabel}</div>
                            ${captionLabel}
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
         * Save entry (job)
         * @param {Function} callback - Optional callback function to run after successful save
         * @param {Boolean} keepOpen - If true, don't reload page/close modal after save
         */
        function saveEntry(callback, keepOpen) {
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
                fitter_id: $('#fitter').val(),
                job_date: $('#job-date').val(),
                quote_date: $('#quote-date').val(),
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
                        const entryId = response.data.entry_id;

                        if (!keepOpen) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            // Update the entry ID in the form
                            $('#entry-id').val(entryId);

                            // Re-enable save button
                            $('#save-entry-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Job');

                            // Call callback if provided
                            if (callback && typeof callback === 'function') {
                                callback(entryId);
                            }
                        }
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

        /**
         * Save measure entry
         * @param {Function} callback - Optional callback function to run after successful save
         * @param {Boolean} keepOpen - If true, don't reload page/close modal after save
         */
        function saveMeasure(callback, keepOpen) {
            // Validate customer selection
            const customerId = $('#measure-customer-id').val();
            if (!customerId) {
                alert('Please select or add a customer first.');
                return;
            }

            const formData = {
                action: 'save_diary_entry',
                nonce: wpStaffDiary.nonce,
                entry_id: $('#measure-entry-id').val(),
                customer_id: customerId,
                job_date: $('#measure-job-date').val(),
                fitting_date: $('#measure-date').val(),
                job_time: $('#measure-time').val(),
                fitting_address_line_1: $('#measure-address-line-1').val(),
                fitting_address_line_2: $('#measure-address-line-2').val(),
                fitting_address_line_3: $('#measure-address-line-3').val(),
                fitting_postcode: $('#measure-postcode').val(),
                notes: $('#measure-notes').val(),
                status: $('#measure-status').val() // 'measure'
            };

            console.log('Saving measure with data:', formData);

            $('#save-measure-btn').prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        if (keepOpen) {
                            // Show success message without alert
                            console.log('Measure saved successfully:', response.data.entry_id);
                            $('#save-measure-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Measure');

                            // Call callback if provided
                            if (callback && typeof callback === 'function') {
                                callback(response.data.entry_id);
                            }
                        } else {
                            alert(response.data.message);
                            location.reload();
                        }
                    } else {
                        alert('Error: ' + response.data.message);
                        $('#save-measure-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Measure');
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while saving the measure.');
                    $('#save-measure-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Save Measure');
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
                        const source = $('#quick-add-customer-modal').data('source');
                        if (source === 'measure') {
                            // Pre-fill measure form with customer details
                            const customer = response.data.customer;
                            const customerAddress = [
                                customer.address_line_1,
                                customer.address_line_2,
                                customer.address_line_3,
                                customer.postcode
                            ].filter(Boolean).join('\n');
                            selectMeasureCustomer(customer.id, customer.customer_name, customer.customer_phone || '', customerAddress);
                        } else {
                            selectCustomer(response.data.customer.id, response.data.customer.customer_name);
                        }
                        $('#quick-add-customer-modal').fadeOut();
                        $('#quick-add-customer-form')[0].reset();
                        $('#quick-add-customer-modal').removeData('source');
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
        // MEASURE CUSTOMER MANAGEMENT
        // ===========================================

        let measureCustomerSearchTimeout = null;
        let measureSelectedCustomerId = 0;

        // Measure customer search with debounce
        $('#measure-customer-search').on('keyup', function() {
            const searchTerm = $(this).val();

            clearTimeout(measureCustomerSearchTimeout);

            if (searchTerm.length < 2) {
                $('#measure-customer-search-results').html('').hide();
                return;
            }

            measureCustomerSearchTimeout = setTimeout(function() {
                searchMeasureCustomers(searchTerm);
            }, 300);
        });

        /**
         * Search customers for measure
         */
        function searchMeasureCustomers(searchTerm) {
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
                        displayMeasureCustomerSearchResults(response.data.customers);
                    }
                },
                error: function() {
                    console.error('Error searching customers for measure');
                }
            });
        }

        /**
         * Display customer search results for measure
         */
        function displayMeasureCustomerSearchResults(customers) {
            if (customers.length === 0) {
                $('#measure-customer-search-results').html('<div class="search-result-item">No customers found</div>').show();
                return;
            }

            let html = '';
            customers.forEach(function(customer) {
                const phone = customer.customer_phone ? ` - ${customer.customer_phone}` : '';
                html += `<div class="search-result-item measure-customer-result" data-customer-id="${customer.id}" data-customer-name="${customer.customer_name}" data-customer-phone="${customer.customer_phone || ''}" data-customer-address="${customer.customer_address || ''}">
                    <strong>${customer.customer_name}</strong>${phone}
                </div>`;
            });

            $('#measure-customer-search-results').html(html).show();
        }

        // Select customer from search results for measure
        $(document).on('click', '.measure-customer-result', function() {
            const customerId = $(this).data('customer-id');
            const customerName = $(this).data('customer-name');
            const customerPhone = $(this).data('customer-phone');
            const customerAddress = $(this).data('customer-address');

            selectMeasureCustomer(customerId, customerName, customerPhone, customerAddress);
        });

        /**
         * Select a customer for measure
         */
        function selectMeasureCustomer(customerId, customerName, customerPhone, customerAddress) {
            measureSelectedCustomerId = customerId;
            $('#measure-customer-id').val(customerId);
            $('#measure-selected-customer-name').text(customerName);
            $('#measure-customer-search-container').hide();
            $('#measure-selected-customer-display').show();
            $('#measure-customer-search-results').html('').hide();
            $('#measure-manual-customer-entry').hide();

            // Pre-fill address if available
            if (customerAddress) {
                // Parse address - customer_address is built from filtered array
                // so we need to fetch the actual customer data to get proper field mapping
                $.ajax({
                    url: wpStaffDiary.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_customer',
                        nonce: wpStaffDiary.nonce,
                        customer_id: customerId
                    },
                    success: function(response) {
                        if (response.success && response.data.customer) {
                            const customer = response.data.customer;
                            $('#measure-address-line-1').val(customer.address_line_1 || '');
                            $('#measure-address-line-2').val(customer.address_line_2 || '');
                            $('#measure-address-line-3').val(customer.address_line_3 || '');
                            $('#measure-postcode').val(customer.postcode || '');
                        }
                    }
                });
            }
        }

        // Clear customer selection for measure
        $('#measure-clear-customer-btn').on('click', function() {
            measureSelectedCustomerId = 0;
            $('#measure-customer-id').val('');
            $('#measure-selected-customer-display').hide();
            $('#measure-customer-search').val('').show();
            $('#measure-customer-search-container').show();
        });

        // Add new customer inline for measure - open the quick add modal
        $('#measure-add-new-customer-inline').on('click', function() {
            // Set a flag to know we're adding from measure form
            $('#quick-add-customer-modal').data('source', 'measure');
            $('#quick-add-customer-modal').fadeIn();
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

        // Billing address checkbox toggle
        $(document).on('change', '#billing-address-different', function() {
            if ($(this).is(':checked')) {
                $('#billing-address-section').slideDown();
            } else {
                $('#billing-address-section').slideUp();
                // Clear billing address fields when unchecked
                $('#billing-address-line-1').val('');
                $('#billing-address-line-2').val('');
                $('#billing-address-line-3').val('');
                $('#billing-postcode').val('');
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
            let total = subtotal;
            if (typeof vatEnabled !== 'undefined' && vatEnabled == 1) {
                const vatAmount = subtotal * (vatRate / 100);
                $('#vat-display').text(vatAmount.toFixed(2));
                total = subtotal + vatAmount;
            }

            // Apply discount if exists
            if (currentDiscountType && currentDiscountValue > 0) {
                let discountAmount = 0;
                if (currentDiscountType === 'percentage') {
                    discountAmount = (total * currentDiscountValue) / 100;
                } else {
                    discountAmount = Math.min(currentDiscountValue, total);
                }

                // Show discount rows
                $('#original-total-row').show();
                $('#discount-row').show();
                $('#original-total-display').text(total.toFixed(2));

                // Update discount label and amount
                const discountLabel = currentDiscountType === 'percentage'
                    ? currentDiscountValue.toFixed(2) + '%'
                    : '£' + currentDiscountValue.toFixed(2);
                $('#discount-label-display').text(discountLabel);
                $('#discount-amount-display').text(discountAmount.toFixed(2));

                // Calculate final total
                total = total - discountAmount;
                $('#total-label').text('Final Total:');
            } else {
                // Hide discount rows
                $('#original-total-row').hide();
                $('#discount-row').hide();
                $('#total-label').text('Total:');
            }

            $('#total-display').text(total.toFixed(2));
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
                        // Data is wrapped in response.data.entry by the modular jobs controller
                        const entry = response.data.entry || response.data;
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
            // Check if this is a measure - display simpler view
            if (entry.status === 'measure') {
                displayMeasureDetails(entry);
                return;
            }

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
            html += '</div>';

            // Photos Section
            html += '<div class="detail-section">';
            html += '<h3>Photos</h3>';
            if (entry.images && entry.images.length > 0) {
                html += '<div class="job-images-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">';
                entry.images.forEach(function(image) {
                    const categoryLabel = image.category ? `<div style="font-size: 11px; color: #999; font-weight: 600; margin-top: 4px;">(${image.category})</div>` : '';
                    html += `<div class="job-image-item" style="position: relative;">
                        <img src="${image.image_url}" alt="Job photo" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open('${image.image_url}', '_blank')" title="Click to open full size">
                        ${categoryLabel}
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

            // Internal Notes
            if (entry.notes) {
                html += '<div class="detail-section">';
                html += '<h3>Internal Notes</h3>';
                html += `<div class="notes-content">${entry.notes.replace(/\n/g, '<br>')}</div>`;
                html += '</div>';
            }

            // Comments Section
            html += generateCommentsSection(entry.id);

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

            // Load comments after modal is displayed
            loadComments(entry.id);
        }

        /**
         * Display measure details in view modal
         */
        function displayMeasureDetails(entry) {
            let html = '<div class="job-sheet-content">';

            // Header
            html += `<div class="job-sheet-header" style="background: #9b59b6;">
                <h2 style="color: white;">Measure Appointment</h2>
                <div class="order-number-large" style="color: white;">Measure #${entry.order_number}</div>
            </div>`;

            // Customer Section
            html += '<div class="detail-section">';
            html += '<h3>Customer Details</h3>';
            if (entry.customer) {
                html += `<div class="detail-grid">
                    <div class="detail-item"><strong>Name:</strong> ${entry.customer.customer_name}</div>
                    ${entry.customer.customer_phone ? `<div class="detail-item"><strong>Phone:</strong> ${entry.customer.customer_phone}</div>` : ''}
                </div>`;
            } else {
                html += '<p style="color: #999;">No customer information</p>';
            }
            html += '</div>';

            // Measure Address Section
            html += '<div class="detail-section">';
            html += '<h3>Measure Address</h3>';
            if (entry.fitting_address_line_1 || entry.fitting_address_line_2 || entry.fitting_address_line_3 || entry.fitting_postcode) {
                let address = [];
                if (entry.fitting_address_line_1) address.push(entry.fitting_address_line_1);
                if (entry.fitting_address_line_2) address.push(entry.fitting_address_line_2);
                if (entry.fitting_address_line_3) address.push(entry.fitting_address_line_3);
                if (entry.fitting_postcode) address.push(entry.fitting_postcode);
                html += `<div class="detail-item">${address.join('<br>')}</div>`;
            } else {
                html += '<p style="color: #999;">No address specified</p>';
            }
            html += '</div>';

            // Measure Schedule Section
            html += '<div class="detail-section">';
            html += '<h3>Schedule</h3>';
            html += '<div class="detail-grid">';
            if (entry.fitting_date) {
                html += `<div class="detail-item"><strong>Measure Date:</strong> ${entry.fitting_date_formatted}</div>`;
            }
            if (entry.job_time) {
                html += `<div class="detail-item"><strong>Measure Time:</strong> ${entry.job_time_formatted}</div>`;
            }
            html += '</div>';
            html += '</div>';

            // Photos Section
            html += '<div class="detail-section">';
            html += '<h3>Photos</h3>';
            if (entry.images && entry.images.length > 0) {
                html += '<div class="job-images-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">';
                entry.images.forEach(function(image) {
                    const categoryLabel = image.category ? `<div style="font-size: 11px; color: #999; font-weight: 600; margin-top: 4px;">(${image.category})</div>` : '';
                    html += `<div class="job-image-item" style="position: relative;">
                        <img src="${image.image_url}" alt="Measure photo" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open('${image.image_url}', '_blank')" title="Click to open full size">
                        ${categoryLabel}
                        ${image.image_caption ? `<p style="font-size: 12px; color: #666; margin-top: 5px;">${image.image_caption}</p>` : ''}
                    </div>`;
                });
                html += '</div>';
            } else {
                html += '<p style="color: #999;">No photos uploaded yet.</p>';
            }
            html += `<button type="button" class="button" id="upload-photo-btn" data-entry-id="${entry.id}">
                <span class="dashicons dashicons-camera"></span> Upload Photo
            </button>`;
            html += `<input type="file" id="photo-upload-input-${entry.id}" accept="image/*" style="display: none;">`;
            html += '</div>';

            // Internal Notes
            if (entry.notes) {
                html += '<div class="detail-section">';
                html += '<h3>Internal Notes</h3>';
                html += `<div class="notes-content">${entry.notes.replace(/\n/g, '<br>')}</div>`;
                html += '</div>';
            }

            // Comments Section
            html += generateCommentsSection(entry.id);

            // Actions - Convert buttons
            html += '<div class="detail-section detail-actions">';
            html += `<button type="button" class="button button-primary" id="convert-measure-to-quote-btn" data-measure-id="${entry.id}" style="background: #2271b1; border-color: #2271b1;">
                <span class="dashicons dashicons-clipboard"></span> Convert to Quote
            </button>`;
            html += `<button type="button" class="button button-primary" id="convert-measure-to-job-btn" data-measure-id="${entry.id}" style="background: #00a32a; border-color: #00a32a; margin-left: 10px;">
                <span class="dashicons dashicons-hammer"></span> Convert to Job
            </button>`;
            html += `<button type="button" class="button edit-entry" data-id="${entry.id}" style="margin-left: 10px;">
                <span class="dashicons dashicons-edit"></span> Edit Measure
            </button>`;
            html += `<button type="button" class="button cancel-entry" data-id="${entry.id}" style="margin-left: 10px; background: #d63638; color: white; border-color: #d63638;">
                <span class="dashicons dashicons-no"></span> Cancel Measure
            </button>`;
            html += '</div>';

            html += '</div>'; // Close job-sheet-content

            $('#entry-details-content').html(html);
            $('#view-entry-modal').fadeIn();

            // Load comments after modal is displayed
            loadComments(entry.id);
        }

        // ===========================================
        // PHOTOS - Upload
        // ===========================================

        // Photo upload button click
        $(document).on('click', '#upload-photo-btn', function() {
            const entryId = $(this).data('entry-id');
            console.log('Upload photo clicked, entry ID:', entryId);
            const inputElement = $(`#photo-upload-input-${entryId}`);
            console.log('Input element found:', inputElement.length);
            if (inputElement.length > 0) {
                inputElement.click();
            } else {
                console.error('Photo input element not found: #photo-upload-input-' + entryId);
            }
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
            showPhotoCategory(file, entryId, function(result) {
                if (!result) {
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
                    error: function() {
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
            const entryId = $('#entry-id').val();

            if (!entryId) {
                if (confirm('The job needs to be saved before you can upload photos. Would you like to save it now?')) {
                    // Save the job, then trigger photo upload
                    saveEntry(function(savedEntryId) {
                        // After successful save, set the entry ID and trigger photo upload
                        $('#entry-id').val(savedEntryId);
                        $('#photo-upload-input-form').data('entry-id', savedEntryId);
                        $('#photo-upload-input-form').click();
                    }, true); // true = don't close modal after save
                }
                return;
            }

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
            showPhotoCategory(file, entryId, function(result) {
                if (!result) {
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

            // Clear the file input
            $(this).val('');
        });

        // Measure photo upload button click
        $(document).on('click', '#upload-measure-photo-btn', function() {
            const entryId = $('#measure-entry-id').val();

            if (!entryId) {
                if (confirm('You need to save the measure first before uploading photos. Would you like to save now?')) {
                    // Save the measure, then trigger photo upload
                    saveMeasure(function(savedEntryId) {
                        // After successful save, set the entry ID and trigger photo upload
                        $('#measure-entry-id').val(savedEntryId);
                        $('#measure-photo-upload-input').click();
                    }, true); // true = don't close modal after save
                }
                return;
            }

            $('#measure-photo-upload-input').click();
        });

        // Measure photo file selected
        $(document).on('change', '#measure-photo-upload-input', function() {
            const entryId = $('#measure-entry-id').val();
            const file = this.files[0];
            const $input = $(this);

            if (!file) return;

            if (!entryId) {
                alert('Please save the measure first before uploading photos.');
                $input.val('');
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                $input.val('');
                return;
            }

            // Show category selection modal
            showPhotoCategory(file, entryId, function(result) {
                if (!result) {
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
                            // Reload photos in the form
                            loadMeasurePhotos(entryId);
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

            // Clear the file input
            $(this).val('');
        });

        /**
         * Load photos for measure form
         */
        function loadMeasurePhotos(entryId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_entry_photos',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    if (response.success && response.data.photos) {
                        let html = '';
                        if (response.data.photos.length > 0) {
                            response.data.photos.forEach(function(photo) {
                                const categoryLabel = photo.image_category ? ` (${photo.image_category})` : '';
                                const caption = photo.image_caption ? `<div style="font-size: 10px; color: #999; margin-top: 2px;">${photo.image_caption}</div>` : '';
                                html += `<div class="photo-item" style="display: inline-block; margin: 10px; position: relative;">
                                    <img src="${photo.image_url}" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" onclick="window.open('${photo.image_url}', '_blank')" title="Click to open full size">
                                    <div style="font-size: 11px; color: #666; font-weight: 600;">${categoryLabel}</div>
                                    ${caption}
                                </div>`;
                            });
                        } else {
                            html = '<p class="description">No photos uploaded yet.</p>';
                        }
                        $('#measure-photos-container').html(html);
                    } else {
                        $('#measure-photos-container').html('<p class="description">No photos uploaded yet.</p>');
                    }
                },
                error: function() {
                    console.error('Failed to load photos');
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
        // CANCEL ENTRY
        // ===========================================

        /**
         * Cancel entry
         */
        function cancelEntry(entryId) {
            console.log('Cancelling entry ID:', entryId);
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cancel_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    console.log('Cancel response:', response);
                    if (response.success) {
                        // Close all modals first
                        $('.wp-staff-diary-modal').fadeOut();
                        console.log('Modals closed, reloading page...');

                        // Use setTimeout to ensure modal close animation completes
                        setTimeout(function() {
                            console.log('Executing reload now');
                            window.location.reload();
                        }, 300);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cancel error:', status, error);
                    alert('An error occurred while cancelling the entry.');
                }
            });
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
        // ACCESSORIES MANAGEMENT (Settings Page)
        // ===========================================

        /**
         * Edit accessory - Toggle inline edit mode
         */
        $(document).on('click', '.edit-accessory', function() {
            const $row = $(this).closest('tr');
            const accessoryId = $(this).data('id');

            // Show edit inputs, hide display spans
            $row.find('.accessory-name-display, .accessory-price-display, .accessory-active-display').hide();
            $row.find('.accessory-name-edit, .accessory-price-edit, .accessory-active-edit').show();

            // Show save/cancel buttons, hide edit/delete buttons
            $row.find('.edit-accessory, .delete-accessory').hide();
            $row.find('.save-accessory, .cancel-accessory-edit').show();
        });

        /**
         * Cancel accessory edit - Revert to display mode
         */
        $(document).on('click', '.cancel-accessory-edit', function() {
            const $row = $(this).closest('tr');

            // Hide edit inputs, show display spans
            $row.find('.accessory-name-edit, .accessory-price-edit, .accessory-active-edit').hide();
            $row.find('.accessory-name-display, .accessory-price-display, .accessory-active-display').show();

            // Hide save/cancel buttons, show edit/delete buttons
            $row.find('.save-accessory, .cancel-accessory-edit').hide();
            $row.find('.edit-accessory, .delete-accessory').show();

            // Reset inputs to original values
            $row.find('.accessory-name-edit').val($row.find('.accessory-name-display').text());
            $row.find('.accessory-price-edit').val($row.find('.accessory-price-display').text().replace('£', '').replace(',', ''));
        });

        /**
         * Save accessory - Update via AJAX
         */
        $(document).on('click', '.save-accessory', function() {
            const $button = $(this);
            const $row = $button.closest('tr');
            const accessoryId = $button.data('id');

            const accessoryName = $row.find('.accessory-name-edit').val().trim();
            const price = parseFloat($row.find('.accessory-price-edit').val());
            const isActive = $row.find('.accessory-active-edit').is(':checked') ? 1 : 0;

            // Validation
            if (!accessoryName) {
                alert('Accessory name is required');
                return;
            }

            if (isNaN(price) || price < 0) {
                alert('Please enter a valid price');
                return;
            }

            // Disable button during save
            $button.prop('disabled', true).text('Saving...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_accessory',
                    nonce: wpStaffDiary.nonce,
                    accessory_id: accessoryId,
                    accessory_name: accessoryName,
                    price: price,
                    is_active: isActive
                },
                success: function(response) {
                    if (response.success) {
                        // Update display values
                        $row.find('.accessory-name-display').text(accessoryName);
                        $row.find('.accessory-price-display').text('£' + price.toFixed(2));
                        $row.find('.accessory-active-display').text(isActive ? 'Yes' : 'No');

                        // Switch back to display mode
                        $row.find('.accessory-name-edit, .accessory-price-edit, .accessory-active-edit').hide();
                        $row.find('.accessory-name-display, .accessory-price-display, .accessory-active-display').show();
                        $row.find('.save-accessory, .cancel-accessory-edit').hide();
                        $row.find('.edit-accessory, .delete-accessory').show();

                        // Show success message
                        alert('Accessory updated successfully');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating the accessory');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save');
                }
            });
        });

        /**
         * Delete accessory - Remove via AJAX
         */
        $(document).on('click', '.delete-accessory', function() {
            if (!confirm('Are you sure you want to delete this accessory? This action cannot be undone.')) {
                return;
            }

            const $button = $(this);
            const $row = $button.closest('tr');
            const accessoryId = $button.data('id');

            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_accessory',
                    nonce: wpStaffDiary.nonce,
                    accessory_id: accessoryId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();

                            // Check if table is empty
                            if ($('#accessories-table tbody tr').length === 0) {
                                $('#accessories-table tbody').html(
                                    '<tr><td colspan="4" style="text-align: center; color: #666;">No accessories added yet. Add your first accessory below.</td></tr>'
                                );
                            }
                        });
                        alert('Accessory deleted successfully');
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the accessory');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        });

        /**
         * Add new accessory - Create via AJAX
         */
        $(document).on('click', '#add-accessory-btn', function() {
            const $button = $(this);
            const accessoryName = $('#new-accessory-name').val().trim();
            const price = parseFloat($('#new-accessory-price').val());

            // Validation
            if (!accessoryName) {
                alert('Please enter an accessory name');
                return;
            }

            if (isNaN(price) || price < 0) {
                alert('Please enter a valid price');
                return;
            }

            $button.prop('disabled', true).text('Adding...');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_accessory',
                    nonce: wpStaffDiary.nonce,
                    accessory_name: accessoryName,
                    price: price
                },
                success: function(response) {
                    if (response.success) {
                        const accessoryId = response.data.accessory.id;

                        // Remove "no accessories" message if it exists
                        $('#accessories-table tbody tr td[colspan]').closest('tr').remove();

                        // Add new row to table
                        const newRow = `
                            <tr data-accessory-id="${accessoryId}">
                                <td>
                                    <span class="accessory-name-display">${accessoryName}</span>
                                    <input type="text" class="accessory-name-edit regular-text" value="${accessoryName}" style="display:none;">
                                </td>
                                <td>
                                    <span class="accessory-price-display">£${price.toFixed(2)}</span>
                                    <input type="number" class="accessory-price-edit small-text" value="${price}" step="0.01" min="0" style="display:none;">
                                </td>
                                <td>
                                    <span class="accessory-active-display">Yes</span>
                                    <input type="checkbox" class="accessory-active-edit" checked style="display:none;">
                                </td>
                                <td>
                                    <button type="button" class="button button-small edit-accessory" data-id="${accessoryId}">Edit</button>
                                    <button type="button" class="button button-small save-accessory" data-id="${accessoryId}" style="display:none;">Save</button>
                                    <button type="button" class="button button-small cancel-accessory-edit" style="display:none;">Cancel</button>
                                    <button type="button" class="button button-small button-link-delete delete-accessory" data-id="${accessoryId}">Delete</button>
                                </td>
                            </tr>
                        `;

                        $('#accessories-table tbody').append(newRow);

                        // Clear form
                        $('#new-accessory-name').val('');
                        $('#new-accessory-price').val('0.00');

                        alert('Accessory added successfully');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while adding the accessory');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Add Accessory');
                }
            });
        });

        // ===========================================
        // MEASURE CONVERSIONS
        // ===========================================

        /**
         * Convert measure to quote
         */
        $(document).on('click', '#convert-measure-to-quote-btn', function() {
            const measureId = $(this).data('measure-id');

            // Close view modal
            $('#view-entry-modal').fadeOut();

            // Fetch measure data and populate quote form
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: measureId
                },
                success: function(response) {
                    if (response.success) {
                        const measure = response.data.entry || response.data;

                        // Redirect to quotes page with pre-filled data
                        const params = new URLSearchParams({
                            action: 'new',
                            from_measure: measureId,
                            order_number: measure.order_number || '',
                            customer_id: measure.customer_id || '',
                            fitting_date: measure.fitting_date || '',
                            fitting_address_line_1: measure.fitting_address_line_1 || '',
                            fitting_address_line_2: measure.fitting_address_line_2 || '',
                            fitting_address_line_3: measure.fitting_address_line_3 || '',
                            fitting_postcode: measure.fitting_postcode || '',
                            notes: measure.notes || ''
                        });

                        window.location.href = 'admin.php?page=wp-staff-diary-quotes&' + params.toString();
                    } else {
                        alert('Error loading measure data');
                    }
                },
                error: function() {
                    alert('An error occurred while loading measure data');
                }
            });
        });

        /**
         * Convert measure to job
         */
        $(document).on('click', '#convert-measure-to-job-btn', function() {
            const measureId = $(this).data('measure-id');

            // Close view modal
            $('#view-entry-modal').fadeOut();

            // Fetch measure data and open job form
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: measureId
                },
                success: function(response) {
                    if (response.success) {
                        const measure = response.data.entry || response.data;

                        // Open job entry modal
                        $('#entry-modal-title').text('Convert Measure to Job');
                        $('#diary-entry-form')[0].reset();
                        $('#entry-id').val('');

                        // Preserve order number
                        if (measure.order_number) {
                            $('#order-number').val(measure.order_number);
                        }

                        // Pre-fill form with measure data
                        if (measure.customer_id) {
                            $('#customer-id').val(measure.customer_id);
                            if (measure.customer) {
                                $('#customer-search').hide();
                                $('#selected-customer-name').text(measure.customer.customer_name);
                                $('#selected-customer-display').show();
                            }
                        }

                        // Pre-fill address
                        if (measure.fitting_address_line_1) {
                            $('#fitting-address-line-1').val(measure.fitting_address_line_1);
                            $('#fitting-address-line-2').val(measure.fitting_address_line_2 || '');
                            $('#fitting-address-line-3').val(measure.fitting_address_line_3 || '');
                            $('#fitting-postcode').val(measure.fitting_postcode || '');
                        }

                        // Pre-fill date (from measure date to fitting date)
                        if (measure.fitting_date) {
                            $('#fitting-date').val(measure.fitting_date);
                        }

                        // Pre-fill notes
                        if (measure.notes) {
                            $('#notes').val(measure.notes);
                        }

                        // Set job date to today
                        $('#job-date').val(new Date().toISOString().split('T')[0]);

                        $('#entry-modal').fadeIn();
                    } else {
                        alert('Error loading measure data');
                    }
                },
                error: function() {
                    alert('An error occurred while loading measure data');
                }
            });
        });

        // ===========================================
        // COMMENTS SYSTEM
        // ===========================================

        /**
         * Generate comments section HTML
         */
        function generateCommentsSection(entryId) {
            let html = '<div class="detail-section comments-section">';
            html += '<h3><span class="dashicons dashicons-admin-comments"></span> Comments</h3>';
            html += `<div class="comments-list" id="comments-list-${entryId}">`;
            html += `<div class="loading-comments" style="text-align: center; padding: 20px; color: #666;">`;
            html += `<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> Loading comments...`;
            html += `</div>`;
            html += '</div>';

            // Add comment form
            html += '<div class="add-comment-form">';
            html += '<textarea placeholder="Add a comment..." id="new-comment-text-' + entryId + '"></textarea>';
            html += '<button type="button" class="button button-primary add-comment-btn" data-entry-id="' + entryId + '">';
            html += '<span class="dashicons dashicons-plus"></span> Add Comment';
            html += '</button>';
            html += '</div>';

            html += '</div>';
            return html;
        }

        /**
         * Load and display comments for an entry
         */
        function loadComments(entryId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_comments',
                    nonce: wpStaffDiary.nonce,
                    diary_entry_id: entryId
                },
                success: function(response) {
                    if (response.success) {
                        displayComments(entryId, response.data.comments);
                    } else {
                        $(`#comments-list-${entryId}`).html('<div class="no-comments-msg">Failed to load comments</div>');
                    }
                },
                error: function() {
                    $(`#comments-list-${entryId}`).html('<div class="no-comments-msg">Error loading comments</div>');
                }
            });
        }

        /**
         * Display comments in the list
         */
        function displayComments(entryId, comments) {
            const $commentsList = $(`#comments-list-${entryId}`);

            if (!comments || comments.length === 0) {
                $commentsList.html('<div class="no-comments-msg">No comments yet. Be the first to comment!</div>');
                return;
            }

            let html = '';
            comments.forEach(function(comment) {
                html += renderCommentItem(comment);
            });

            $commentsList.html(html);
        }

        /**
         * Render a single comment item
         */
        function renderCommentItem(comment) {
            const createdDate = new Date(comment.created_at);
            const updatedDate = new Date(comment.updated_at);
            const wasEdited = updatedDate > createdDate;

            let dateText = formatCommentDate(createdDate);
            if (wasEdited) {
                dateText += ' (edited)';
            }

            let html = `<div class="comment-item" data-comment-id="${comment.id}">`;
            html += '<div class="comment-header">';
            html += `<span class="comment-author">${escapeHtml(comment.user_name || 'Unknown User')}</span>`;
            html += `<span class="comment-date">${dateText}</span>`;
            html += '</div>';
            html += `<div class="comment-text">${escapeHtml(comment.comment_text)}</div>`;

            // Edit form (hidden by default)
            html += '<div class="comment-edit-form">';
            html += `<textarea class="comment-edit-textarea">${escapeHtml(comment.comment_text)}</textarea>`;
            html += '<div class="comment-actions">';
            html += `<button type="button" class="button comment-btn-save" data-comment-id="${comment.id}">Save</button>`;
            html += `<button type="button" class="button comment-btn-cancel">Cancel</button>`;
            html += '</div>';
            html += '</div>';

            // Action buttons
            html += '<div class="comment-actions">';
            html += `<button type="button" class="button comment-btn-edit" data-comment-id="${comment.id}">`;
            html += '<span class="dashicons dashicons-edit"></span> Edit';
            html += '</button>';
            html += `<button type="button" class="button comment-btn-delete" data-comment-id="${comment.id}">`;
            html += '<span class="dashicons dashicons-trash"></span> Delete';
            html += '</button>';
            html += '</div>';

            html += '</div>';
            return html;
        }

        /**
         * Format comment date
         */
        function formatCommentDate(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return diffMins + ' minute' + (diffMins > 1 ? 's' : '') + ' ago';
            if (diffHours < 24) return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
            if (diffDays < 7) return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';

            // Format as date
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add comment button click
        $(document).on('click', '.add-comment-btn', function() {
            const entryId = $(this).data('entry-id');
            const $textarea = $(`#new-comment-text-${entryId}`);
            const commentText = $textarea.val().trim();

            if (!commentText) {
                alert('Please enter a comment');
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_comment',
                    nonce: wpStaffDiary.nonce,
                    diary_entry_id: entryId,
                    comment_text: commentText
                },
                success: function(response) {
                    if (response.success) {
                        $textarea.val(''); // Clear textarea
                        loadComments(entryId); // Reload comments
                    } else {
                        alert('Failed to add comment: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error adding comment');
                }
            });
        });

        // Edit comment button click
        $(document).on('click', '.comment-btn-edit', function() {
            const $commentItem = $(this).closest('.comment-item');
            $commentItem.find('.comment-text').hide();
            $commentItem.find('.comment-actions').first().hide();
            $commentItem.find('.comment-edit-form').addClass('active');
        });

        // Cancel edit button click
        $(document).on('click', '.comment-btn-cancel', function() {
            const $commentItem = $(this).closest('.comment-item');
            $commentItem.find('.comment-text').show();
            $commentItem.find('.comment-actions').first().show();
            $commentItem.find('.comment-edit-form').removeClass('active');
        });

        // Save edited comment button click
        $(document).on('click', '.comment-btn-save', function() {
            const commentId = $(this).data('comment-id');
            const $commentItem = $(this).closest('.comment-item');
            const commentText = $commentItem.find('.comment-edit-textarea').val().trim();

            if (!commentText) {
                alert('Comment cannot be empty');
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_comment',
                    nonce: wpStaffDiary.nonce,
                    comment_id: commentId,
                    comment_text: commentText
                },
                success: function(response) {
                    if (response.success) {
                        // Update the comment display
                        $commentItem.find('.comment-text').text(commentText).show();
                        $commentItem.find('.comment-actions').first().show();
                        $commentItem.find('.comment-edit-form').removeClass('active');

                        // Update edited indicator
                        const dateText = $commentItem.find('.comment-date').text().replace(' (edited)', '') + ' (edited)';
                        $commentItem.find('.comment-date').text(dateText);
                    } else {
                        alert('Failed to update comment: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error updating comment');
                }
            });
        });

        // Delete comment button click
        $(document).on('click', '.comment-btn-delete', function() {
            if (!confirm('Are you sure you want to delete this comment?')) {
                return;
            }

            const commentId = $(this).data('comment-id');
            const $commentItem = $(this).closest('.comment-item');
            const entryId = $('.add-comment-btn').data('entry-id');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_comment',
                    nonce: wpStaffDiary.nonce,
                    comment_id: commentId
                },
                success: function(response) {
                    if (response.success) {
                        $commentItem.fadeOut(300, function() {
                            $(this).remove();
                            // Check if there are any comments left
                            if ($('.comment-item').length === 0) {
                                loadComments(entryId);
                            }
                        });
                    } else {
                        alert('Failed to delete comment: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error deleting comment');
                }
            });
        });

        // ===========================================
        // DISCOUNT OFFERS
        // ===========================================

        // ===========================================
        // CURRENCY SETTINGS (Settings Page)
        // ===========================================

        /**
         * Auto-update currency symbol when currency code changes
         */
        $('#currency_code').on('change', function() {
            const currencySymbols = {
                'GBP': '£',
                'USD': '$',
                'EUR': '€',
                'AUD': 'A$',
                'CAD': 'C$',
                'NZD': 'NZ$',
                'JPY': '¥',
                'CHF': 'CHF',
                'SEK': 'kr',
                'NOK': 'kr',
                'DKK': 'kr'
            };

            const selectedCode = $(this).val();
            if (currencySymbols[selectedCode]) {
                $('#currency_symbol').val(currencySymbols[selectedCode]);
            }
        });

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
