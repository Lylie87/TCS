/**
 * WP Staff Diary - Admin JavaScript
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Current entry ID being edited
        let currentEntryId = 0;

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
            if (confirm('Are you sure you want to delete this entry?')) {
                deleteEntry(entryId);
            }
        });

        // Close modal
        $('.entry-modal-close, #cancel-entry').on('click', function() {
            closeModal();
        });

        // Click outside modal to close
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('entry-modal')) {
                closeModal();
            }
        });

        // Submit entry form
        $('#diary-entry-form').on('submit', function(e) {
            e.preventDefault();
            saveEntry();
        });

        // Upload image button
        $('#upload-image-btn').on('click', function() {
            $('#image-upload-input').click();
        });

        // Handle image upload
        $('#image-upload-input').on('change', function() {
            if (currentEntryId === 0) {
                alert('Please save the entry first before uploading images.');
                return;
            }

            const file = this.files[0];
            if (file) {
                uploadImage(file);
            }
        });

        // Remove image
        $(document).on('click', '.remove-image', function() {
            if (confirm('Are you sure you want to remove this image?')) {
                const imageId = $(this).data('image-id');
                removeImage(imageId);
            }
        });

        /**
         * Open modal for new entry
         */
        function openEntryModal(entryData = null) {
            currentEntryId = 0;
            $('#modal-title').text('Add New Job Entry');
            $('#diary-entry-form')[0].reset();
            $('#entry-id').val('');
            $('#image-gallery').html('');
            $('#cancel-entry').text('Cancel'); // Reset to Cancel

            if (entryData) {
                // Populate form with data for editing
                currentEntryId = entryData.id;
                $('#modal-title').text('Edit Job Entry');
                $('#entry-id').val(entryData.id);
                $('#job-date').val(entryData.job_date);
                $('#job-time').val(entryData.job_time);
                $('#client-name').val(entryData.client_name);
                $('#client-address').val(entryData.client_address);
                $('#client-phone').val(entryData.client_phone);
                $('#job-description').val(entryData.job_description);
                $('#plans').val(entryData.plans);
                $('#notes').val(entryData.notes);
                $('#status').val(entryData.status);

                // Enable upload button and set Close button
                $('#upload-image-btn').prop('disabled', false).removeClass('disabled');
                $('#cancel-entry').text('Close');

                // Load images if any
                loadEntryImages(entryData.id);
            } else {
                // Disable upload button for new entries
                $('#upload-image-btn').prop('disabled', true).addClass('disabled');
            }

            $('#entry-modal').fadeIn();
        }

        /**
         * Close modal
         */
        function closeModal() {
            $('.entry-modal').fadeOut();

            // If an entry was saved (currentEntryId is set), reload the page
            if (currentEntryId > 0) {
                location.reload();
            }

            currentEntryId = 0;
        }

        /**
         * Save entry via AJAX
         */
        function saveEntry() {
            const formData = {
                action: 'save_diary_entry',
                nonce: wpStaffDiary.nonce,
                entry_id: $('#entry-id').val(),
                job_date: $('#job-date').val(),
                job_time: $('#job-time').val(),
                client_name: $('#client-name').val(),
                client_address: $('#client-address').val(),
                client_phone: $('#client-phone').val(),
                job_description: $('#job-description').val(),
                plans: $('#plans').val(),
                notes: $('#notes').val(),
                status: $('#status').val()
            };

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        currentEntryId = response.data.entry_id;
                        $('#entry-id').val(response.data.entry_id);
                        $('#modal-title').text('Edit Job Entry');

                        // Enable image upload button
                        $('#upload-image-btn').prop('disabled', false).removeClass('disabled');

                        // Change Cancel button to Close
                        $('#cancel-entry').text('Close');

                        // Show success message
                        alert(response.data.message);

                        // Don't reload - keep modal open for image uploads
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while saving the entry.');
                }
            });
        }

        /**
         * Load entry for editing
         */
        function loadEntryForEdit(entryId) {
            // In a real implementation, this would fetch entry data via AJAX
            // For now, we'll use a simplified approach
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
                        openEntryModal(response.data);
                    }
                }
            });
        }

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
                    }
                }
            });
        }

        /**
         * Display entry details in modal
         */
        function displayEntryDetails(entry) {
            let html = `
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Date:</div>
                    <div class="entry-detail-value">${entry.job_date_formatted || entry.job_date}</div>
                </div>`;

            // Add time if available
            if (entry.job_time) {
                html += `
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Time:</div>
                    <div class="entry-detail-value">${entry.job_time_formatted || entry.job_time}</div>
                </div>`;
            }

            html += `
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Client Name:</div>
                    <div class="entry-detail-value">${entry.client_name || 'N/A'}</div>
                </div>
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Address:</div>
                    <div class="entry-detail-value">${entry.client_address || 'N/A'}</div>
                </div>
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Phone:</div>
                    <div class="entry-detail-value">${entry.client_phone || 'N/A'}</div>
                </div>
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Job Description:</div>
                    <div class="entry-detail-value">${entry.job_description || 'N/A'}</div>
                </div>
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Plans:</div>
                    <div class="entry-detail-value">${entry.plans || 'N/A'}</div>
                </div>
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Notes:</div>
                    <div class="entry-detail-value">${entry.notes || 'N/A'}</div>
                </div>
                <div class="entry-detail-row">
                    <div class="entry-detail-label">Status:</div>
                    <div class="entry-detail-value"><span class="status-badge status-${entry.status}">${entry.status}</span></div>
                </div>
            `;

            if (entry.images && entry.images.length > 0) {
                html += '<div class="entry-detail-row"><div class="entry-detail-label">Images:</div><div class="entry-images">';
                entry.images.forEach(function(image) {
                    html += `<img src="${image.image_url}" alt="Job image">`;
                });
                html += '</div></div>';
            }

            // Add payments section
            html += '<div class="entry-detail-row payments-section">';
            html += '<div class="entry-detail-label">Payments:</div>';
            html += '<div class="payments-container">';

            if (entry.payments && entry.payments.length > 0) {
                html += '<div class="payments-list" id="payments-list">';
                entry.payments.forEach(function(payment) {
                    html += `
                        <div class="payment-item" data-payment-id="${payment.id}">
                            <div class="payment-info">
                                <strong>£${parseFloat(payment.amount).toFixed(2)}</strong>
                                <span class="payment-type">${payment.payment_type || 'Payment'}</span>
                                <span class="payment-method">(${payment.payment_method})</span>
                            </div>
                            <div class="payment-meta">
                                Recorded by <strong>${payment.recorded_by_name}</strong> on ${payment.recorded_at_formatted}
                            </div>
                            ${payment.notes ? `<div class="payment-notes">${payment.notes}</div>` : ''}
                            <button class="button-link delete-payment" data-payment-id="${payment.id}">Delete</button>
                        </div>
                    `;
                });
                html += '</div>';
                html += `<div class="payments-total">Total Paid: <strong>£${parseFloat(entry.total_payments).toFixed(2)}</strong></div>`;
            } else {
                html += '<p id="no-payments-msg">No payments recorded yet.</p>';
            }

            // Add payment form
            html += `
                <div class="add-payment-form">
                    <h4>Record Payment</h4>
                    <div class="payment-form-row">
                        <label>Amount (£):</label>
                        <input type="number" id="payment-amount" step="0.01" min="0" placeholder="500.00">
                    </div>
                    <div class="payment-form-row">
                        <label>Payment Type:</label>
                        <select id="payment-type">
                            <option value="Deposit">Deposit</option>
                            <option value="Part Payment">Part Payment</option>
                            <option value="Final Payment">Final Payment</option>
                            <option value="Full Payment">Full Payment</option>
                        </select>
                    </div>
                    <div class="payment-form-row">
                        <label>Payment Method:</label>
                        <select id="payment-method">
                            ${generatePaymentMethodOptions()}
                        </select>
                    </div>
                    <div class="payment-form-row">
                        <label>Notes (optional):</label>
                        <textarea id="payment-notes" rows="2"></textarea>
                    </div>
                    <button type="button" class="button button-primary" id="add-payment-btn" data-entry-id="${entry.id}">
                        Record Payment
                    </button>
                </div>
            `;

            html += '</div></div>';

            $('#entry-details-content').html(html);
            $('#view-entry-modal').fadeIn();
        }

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

        /**
         * Upload image
         */
        function uploadImage(file) {
            const formData = new FormData();
            formData.append('action', 'upload_job_image');
            formData.append('nonce', wpStaffDiary.nonce);
            formData.append('entry_id', currentEntryId);
            formData.append('image', file);

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        loadEntryImages(currentEntryId);
                    } else {
                        alert('Error uploading image: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while uploading the image.');
                }
            });
        }

        /**
         * Load images for an entry
         */
        function loadEntryImages(entryId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_diary_entry',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId
                },
                success: function(response) {
                    if (response.success && response.data.images) {
                        displayImages(response.data.images);
                    }
                }
            });
        }

        /**
         * Display images in gallery
         */
        function displayImages(images) {
            let html = '';
            images.forEach(function(image) {
                html += `
                    <div class="gallery-item" data-image-id="${image.id}">
                        <img src="${image.image_url}" alt="${image.image_caption || 'Job image'}">
                        <button type="button" class="remove-image" data-image-id="${image.id}">&times;</button>
                    </div>
                `;
            });
            $('#image-gallery').html(html);
        }

        /**
         * Remove image
         */
        function removeImage(imageId) {
            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_diary_image',
                    nonce: wpStaffDiary.nonce,
                    image_id: imageId
                },
                success: function(response) {
                    if (response.success) {
                        $(`.gallery-item[data-image-id="${imageId}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error removing image: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while removing the image.');
                }
            });
        }

        /**
         * Add payment
         */
        $(document).on('click', '#add-payment-btn', function() {
            const entryId = $(this).data('entry-id');
            const amount = $('#payment-amount').val();
            const paymentMethod = $('#payment-method').val();
            const paymentType = $('#payment-type').val();
            const notes = $('#payment-notes').val();

            if (!amount || parseFloat(amount) <= 0) {
                alert('Please enter a valid amount.');
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_payment',
                    nonce: wpStaffDiary.nonce,
                    entry_id: entryId,
                    amount: amount,
                    payment_method: paymentMethod,
                    payment_type: paymentType,
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Reload the entry to show updated payments
                        viewEntryDetails(entryId);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while recording the payment.');
                }
            });
        });

        /**
         * Delete payment
         */
        $(document).on('click', '.delete-payment', function() {
            if (!confirm('Are you sure you want to delete this payment record?')) {
                return;
            }

            const paymentId = $(this).data('payment-id');
            const entryId = $('#add-payment-btn').data('entry-id');

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_payment',
                    nonce: wpStaffDiary.nonce,
                    payment_id: paymentId
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the entry to show updated payments
                        viewEntryDetails(entryId);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the payment.');
                }
            });
        });

        /**
         * Generate payment method options
         */
        function generatePaymentMethodOptions() {
            if (!wpStaffDiary.paymentMethods) {
                return '<option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Card Payment">Card Payment</option>';
            }

            let options = '';
            for (const [key, label] of Object.entries(wpStaffDiary.paymentMethods)) {
                options += `<option value="${key}">${label}</option>`;
            }
            return options;
        }

        /**
         * Generate status options
         */
        function generateStatusOptions(selectedStatus) {
            if (!wpStaffDiary.statuses) {
                return '<option value="pending">Pending</option><option value="in-progress">In Progress</option><option value="completed">Completed</option>';
            }

            let options = '';
            for (const [key, label] of Object.entries(wpStaffDiary.statuses)) {
                const selected = key === selectedStatus ? 'selected' : '';
                options += `<option value="${key}" ${selected}>${label}</option>`;
            }
            return options;
        }

        /**
         * Settings Page: Add Status
         */
        $('#add-status-btn').on('click', function() {
            const statusLabel = $('#new-status-label').val().trim();

            if (!statusLabel) {
                alert('Please enter a status name.');
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_status',
                    nonce: wpStaffDiary.nonce,
                    status_label: statusLabel
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
                    alert('An error occurred while adding the status.');
                }
            });
        });

        /**
         * Settings Page: Delete Status
         */
        $(document).on('click', '.delete-status', function() {
            const statusKey = $(this).data('status-key');

            if (!confirm('Are you sure you want to delete this status?')) {
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_status',
                    nonce: wpStaffDiary.nonce,
                    status_key: statusKey
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
                    alert('An error occurred while deleting the status.');
                }
            });
        });

        /**
         * Settings Page: Add Payment Method
         */
        $('#add-payment-method-btn').on('click', function() {
            const methodLabel = $('#new-payment-method-label').val().trim();

            if (!methodLabel) {
                alert('Please enter a payment method name.');
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'add_payment_method',
                    nonce: wpStaffDiary.nonce,
                    method_label: methodLabel
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
                    alert('An error occurred while adding the payment method.');
                }
            });
        });

        /**
         * Settings Page: Delete Payment Method
         */
        $(document).on('click', '.delete-payment-method', function() {
            const methodKey = $(this).data('method-key');

            if (!confirm('Are you sure you want to delete this payment method?')) {
                return;
            }

            $.ajax({
                url: wpStaffDiary.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_payment_method',
                    nonce: wpStaffDiary.nonce,
                    method_key: methodKey
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
                    alert('An error occurred while deleting the payment method.');
                }
            });
        });

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
