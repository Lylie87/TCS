<?php
/**
 * Customers management page
 *
 * @since      2.0.0
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('read')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

$db = new WP_Staff_Diary_Database();
$customers = $db->get_all_customers();
?>

<div class="wrap wp-staff-diary-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span> Customers
    </h1>
    <button type="button" class="page-title-action" id="add-customer-btn">Add New Customer</button>
    <hr class="wp-header-end">

    <!-- Search Bar -->
    <div class="customer-search-bar" style="margin: 20px 0;">
        <input type="text" id="customer-search" placeholder="Search customers by name, phone, or email..." style="width: 400px; padding: 8px;">
    </div>

    <!-- Customers Table -->
    <table class="wp-list-table widefat fixed striped customers-table">
        <thead>
            <tr>
                <th style="width: 25%;">Customer Name</th>
                <th style="width: 25%;">Contact Details</th>
                <th style="width: 30%;">Address</th>
                <th style="width: 10%; text-align: center;">Jobs</th>
                <th style="width: 10%; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody id="customers-table-body">
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <p style="color: #666; font-size: 16px;">No customers found. Click "Add New Customer" to get started.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <?php $job_count = $db->get_customer_jobs_count($customer->id); ?>
                    <tr data-customer-id="<?php echo esc_attr($customer->id); ?>">
                        <td>
                            <strong><?php echo esc_html($customer->customer_name); ?></strong>
                        </td>
                        <td>
                            <?php if ($customer->customer_phone): ?>
                                <div><span class="dashicons dashicons-phone"></span> <?php echo esc_html($customer->customer_phone); ?></div>
                            <?php endif; ?>
                            <?php if ($customer->customer_email): ?>
                                <div><span class="dashicons dashicons-email"></span> <?php echo esc_html($customer->customer_email); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $customer->customer_address ? nl2br(esc_html($customer->customer_address)) : '<span style="color: #999;">No address</span>'; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="customer-job-count"><?php echo $job_count; ?></span>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="button button-small edit-customer-btn" data-customer-id="<?php echo esc_attr($customer->id); ?>">
                                Edit
                            </button>
                            <?php if ($job_count == 0): ?>
                                <button type="button" class="button button-small button-link-delete delete-customer-btn" data-customer-id="<?php echo esc_attr($customer->id); ?>">
                                    Delete
                                </button>
                            <?php else: ?>
                                <span style="color: #999; font-size: 11px;">Has jobs</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Customer Modal -->
<div id="customer-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 600px;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2 id="customer-modal-title">Add New Customer</h2>

        <form id="customer-form">
            <input type="hidden" id="customer-id" name="customer_id" value="">

            <div class="form-row">
                <label for="customer-name">Customer Name <span style="color: red;">*</span></label>
                <input type="text" id="customer-name" name="customer_name" required style="width: 100%;">
            </div>

            <div class="form-row">
                <label for="customer-phone">Phone</label>
                <input type="tel" id="customer-phone" name="customer_phone" style="width: 100%;">
            </div>

            <div class="form-row">
                <label for="customer-email">Email</label>
                <input type="email" id="customer-email" name="customer_email" style="width: 100%;">
            </div>

            <div class="form-row">
                <label for="customer-address">Address</label>
                <textarea id="customer-address" name="customer_address" rows="3" style="width: 100%;"></textarea>
            </div>

            <div class="form-row">
                <label for="customer-notes">Notes</label>
                <textarea id="customer-notes" name="customer_notes" rows="3" style="width: 100%;"></textarea>
            </div>

            <div class="form-row" style="margin-top: 20px;">
                <button type="submit" class="button button-primary" id="save-customer-btn">Save Customer</button>
                <button type="button" class="button" id="cancel-customer-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.wp-staff-diary-wrap {
    margin: 20px 20px 0 0;
}

.wp-staff-diary-wrap h1 {
    margin-bottom: 10px;
}

.wp-staff-diary-wrap h1 .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    vertical-align: middle;
    margin-right: 5px;
}

.customer-search-bar input {
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.customers-table td {
    vertical-align: top;
    padding: 12px;
}

.customers-table .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 5px;
    vertical-align: middle;
}

.customer-job-count {
    display: inline-block;
    background: #2271b1;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 12px;
}

/* Modal Styles */
.wp-staff-diary-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.wp-staff-diary-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 30px;
    border: 1px solid #888;
    border-radius: 8px;
    max-width: 800px;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.wp-staff-diary-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}

.wp-staff-diary-modal-close:hover,
.wp-staff-diary-modal-close:focus {
    color: #000;
}

.form-row {
    margin-bottom: 15px;
}

.form-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.form-row input,
.form-row textarea {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-row input:focus,
.form-row textarea:focus {
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add Customer Button
    $('#add-customer-btn').on('click', function() {
        $('#customer-modal-title').text('Add New Customer');
        $('#customer-form')[0].reset();
        $('#customer-id').val('');
        $('#customer-modal').fadeIn();
    });

    // Edit Customer Button
    $(document).on('click', '.edit-customer-btn', function() {
        const customerId = $(this).data('customer-id');

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_customer',
                nonce: wpStaffDiary.nonce,
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    const customer = response.data.customer;
                    $('#customer-modal-title').text('Edit Customer');
                    $('#customer-id').val(customer.id);
                    $('#customer-name').val(customer.customer_name);
                    $('#customer-phone').val(customer.customer_phone || '');
                    $('#customer-email').val(customer.customer_email || '');
                    $('#customer-address').val(customer.customer_address || '');
                    $('#customer-notes').val(customer.notes || '');
                    $('#customer-modal').fadeIn();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error loading customer data');
            }
        });
    });

    // Delete Customer Button
    $(document).on('click', '.delete-customer-btn', function() {
        if (!confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
            return;
        }

        const customerId = $(this).data('customer-id');
        const $row = $(this).closest('tr');

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_customer',
                nonce: wpStaffDiary.nonce,
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($('#customers-table-body tr').length === 0) {
                            $('#customers-table-body').html('<tr><td colspan="5" style="text-align: center; padding: 40px;"><p style="color: #666; font-size: 16px;">No customers found. Click "Add New Customer" to get started.</p></td></tr>');
                        }
                    });
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error deleting customer');
            }
        });
    });

    // Save Customer Form
    $('#customer-form').on('submit', function(e) {
        e.preventDefault();

        const customerId = $('#customer-id').val();
        const isEdit = customerId !== '';
        const action = isEdit ? 'update_customer' : 'add_customer';

        const data = {
            action: action,
            nonce: wpStaffDiary.nonce,
            customer_name: $('#customer-name').val(),
            customer_phone: $('#customer-phone').val(),
            customer_email: $('#customer-email').val(),
            customer_address: $('#customer-address').val(),
            notes: $('#customer-notes').val()
        };

        if (isEdit) {
            data.customer_id = customerId;
        }

        $('#save-customer-btn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: wpStaffDiary.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#customer-modal').fadeOut();
                    location.reload(); // Reload to show updated list
                } else {
                    alert('Error: ' + response.data.message);
                }
                $('#save-customer-btn').prop('disabled', false).text('Save Customer');
            },
            error: function() {
                alert('Error saving customer');
                $('#save-customer-btn').prop('disabled', false).text('Save Customer');
            }
        });
    });

    // Cancel Button
    $('#cancel-customer-btn').on('click', function() {
        $('#customer-modal').fadeOut();
    });

    // Close Modal
    $('.wp-staff-diary-modal-close').on('click', function() {
        $('#customer-modal').fadeOut();
    });

    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('wp-staff-diary-modal')) {
            $('.wp-staff-diary-modal').fadeOut();
        }
    });

    // Search Customers
    let searchTimeout;
    $('#customer-search').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();

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
                    if (response.success) {
                        const customers = response.data.customers;
                        let html = '';

                        if (customers.length === 0) {
                            html = '<tr><td colspan="5" style="text-align: center; padding: 40px;"><p style="color: #666; font-size: 16px;">No customers found.</p></td></tr>';
                        } else {
                            customers.forEach(function(customer) {
                                // Get job count via another AJAX call (could be optimized)
                                html += buildCustomerRow(customer);
                            });
                        }

                        $('#customers-table-body').html(html);
                    }
                },
                error: function() {
                    alert('Error searching customers');
                }
            });
        }, 300);
    });

    // Helper function to build customer row
    function buildCustomerRow(customer) {
        let phone = customer.customer_phone ? '<div><span class="dashicons dashicons-phone"></span> ' + customer.customer_phone + '</div>' : '';
        let email = customer.customer_email ? '<div><span class="dashicons dashicons-email"></span> ' + customer.customer_email + '</div>' : '';
        let address = customer.customer_address ? customer.customer_address.replace(/\n/g, '<br>') : '<span style="color: #999;">No address</span>';

        return '<tr data-customer-id="' + customer.id + '">' +
            '<td><strong>' + customer.customer_name + '</strong></td>' +
            '<td>' + phone + email + '</td>' +
            '<td>' + address + '</td>' +
            '<td style="text-align: center;"><span class="customer-job-count">0</span></td>' +
            '<td style="text-align: center;">' +
                '<button type="button" class="button button-small edit-customer-btn" data-customer-id="' + customer.id + '">Edit</button> ' +
                '<button type="button" class="button button-small button-link-delete delete-customer-btn" data-customer-id="' + customer.id + '">Delete</button>' +
            '</td>' +
            '</tr>';
    }
});
</script>
