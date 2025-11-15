<?php
/**
 * My Jobs Page - List View
 *
 * @since      2.0.0
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('edit_posts')) {
    wp_die(__('Sorry, you are not allowed to access this page.'));
}

$current_user = wp_get_current_user();
$db = new WP_Staff_Diary_Database();

// Get current month or selected date range
$current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
$start_date = $current_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$entries = $db->get_user_entries($current_user->ID, $start_date, $end_date);

// Get statuses for dropdown
$statuses = get_option('wp_staff_diary_statuses', array(
    'pending' => 'Pending',
    'in-progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
));

// Get accessories for selection
$accessories = $db->get_all_accessories(true); // Active only

// Get fitters
$fitters = get_option('wp_staff_diary_fitters', array());

// Get time selection settings
$job_time_type = get_option('wp_staff_diary_job_time_type', 'none');

// Get VAT settings
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');
?>

<div class="wrap wp-staff-diary-wrap">
    <h1>
        <span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <div class="diary-header">
        <div class="date-selector">
            <label for="month-select">View Month:</label>
            <input type="month" id="month-select" value="<?php echo esc_attr($current_month); ?>">
        </div>

        <div class="view-actions">
            <a href="?page=wp-staff-diary" class="button">
                <span class="dashicons dashicons-calendar"></span> Calendar View
            </a>
            <button type="button" class="button button-primary" id="add-new-entry">
                <span class="dashicons dashicons-plus-alt"></span> Add New Job
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="diary-filters" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 3px;">
        <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
            <div>
                <label for="sort-date" style="margin-right: 5px; font-weight: 600;">Sort by Date:</label>
                <select id="sort-date" style="min-width: 150px;">
                    <option value="desc">Latest First</option>
                    <option value="asc">Earliest First</option>
                </select>
            </div>

            <div>
                <label for="filter-customer" style="margin-right: 5px; font-weight: 600;">Customer:</label>
                <input type="text" id="filter-customer" placeholder="Filter by customer name..." style="min-width: 200px;">
            </div>

            <div>
                <label for="filter-fitter" style="margin-right: 5px; font-weight: 600;">Fitter:</label>
                <select id="filter-fitter" style="min-width: 150px;">
                    <option value="">All Fitters</option>
                    <option value="unassigned">Unassigned</option>
                    <?php foreach ($fitters as $index => $fitter): ?>
                        <option value="<?php echo esc_attr($index); ?>"><?php echo esc_html($fitter['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filter-balance" style="margin-right: 5px; font-weight: 600;">Balance:</label>
                <select id="filter-balance" style="min-width: 150px;">
                    <option value="">All Jobs</option>
                    <option value="outstanding">Outstanding Balance</option>
                    <option value="paid">Fully Paid</option>
                    <option value="overpaid">Overpaid</option>
                </select>
            </div>

            <button type="button" id="clear-filters" class="button" style="margin-left: auto;">
                Clear Filters
            </button>
        </div>
    </div>

    <div class="diary-entries">
        <table class="wp-list-table widefat fixed striped jobs-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Order #</th>
                    <th style="width: 10%;">Job Date</th>
                    <th style="width: 18%;">Customer</th>
                    <th style="width: 12%;">Fitter</th>
                    <th style="width: 13%;">Product</th>
                    <th style="width: 9%; text-align: right;">Total</th>
                    <th style="width: 9%; text-align: right;">Balance</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 9%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <p style="color: #666; font-size: 16px;">No jobs found for this month. Click "Add New Job" to get started.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $customer_id = isset($entry->customer_id) ? $entry->customer_id : null;
                        $customer = $customer_id ? $db->get_customer($customer_id) : null;

                        // Get fitter info
                        $fitter_id = isset($entry->fitter_id) ? $entry->fitter_id : null;
                        $fitter = null;
                        if ($fitter_id !== null && isset($fitters[$fitter_id])) {
                            $fitter = $fitters[$fitter_id];
                        }

                        $subtotal = $db->calculate_job_subtotal($entry->id);
                        $total = $subtotal;
                        if ($vat_enabled == '1') {
                            $total = $subtotal * (1 + ($vat_rate / 100));
                        }
                        $payments = $db->get_entry_total_payments($entry->id);
                        $balance = $total - $payments;

                        $is_cancelled = isset($entry->is_cancelled) ? $entry->is_cancelled : 0;
                        $status_class = $is_cancelled ? 'cancelled' : $entry->status;
                        $order_number = isset($entry->order_number) ? $entry->order_number : 'Job #' . $entry->id;

                        // Prepare data attributes for filtering
                        $customer_name = $customer ? $customer->customer_name : '';
                        $fitter_attr = ($fitter_id !== null) ? $fitter_id : 'unassigned';
                        $balance_status = ($balance > 0) ? 'outstanding' : (($balance < 0) ? 'overpaid' : 'paid');
                        ?>
                        <tr data-entry-id="<?php echo esc_attr($entry->id); ?>"
                            data-job-date="<?php echo esc_attr($entry->job_date); ?>"
                            data-customer-name="<?php echo esc_attr(strtolower($customer_name)); ?>"
                            data-fitter-id="<?php echo esc_attr($fitter_attr); ?>"
                            data-balance="<?php echo esc_attr($balance_status); ?>"
                            <?php echo $is_cancelled ? 'style="opacity: 0.6;"' : ''; ?>>
                            <td><strong><?php echo esc_html($order_number); ?></strong></td>
                            <td>
                                <?php echo esc_html(date('d/m/Y', strtotime($entry->job_date))); ?>
                                <?php if ($entry->job_time): ?>
                                    <br><small><?php echo esc_html(date('H:i', strtotime($entry->job_time))); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($customer): ?>
                                    <strong><?php echo esc_html($customer->customer_name); ?></strong>
                                    <?php if ($customer->customer_phone): ?>
                                        <br><small><?php echo esc_html($customer->customer_phone); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">No customer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($fitter): ?>
                                    <span class="fitter-badge" style="display: inline-block; padding: 3px 8px; border-radius: 3px; background-color: <?php echo esc_attr($fitter['color']); ?>; color: white; font-size: 11px; font-weight: 600;">
                                        <?php echo esc_html($fitter['name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $entry->product_description ? esc_html(wp_trim_words($entry->product_description, 5)) : '<span style="color: #999;">—</span>'; ?>
                            </td>
                            <td style="text-align: right;">
                                <strong>£<?php echo number_format($total, 2); ?></strong>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($balance > 0): ?>
                                    <span style="color: #d63638; font-weight: bold;">£<?php echo number_format($balance, 2); ?></span>
                                <?php elseif ($balance < 0): ?>
                                    <span style="color: #00a32a; font-weight: bold;">-£<?php echo number_format(abs($balance), 2); ?></span>
                                <?php else: ?>
                                    <span style="color: #00a32a; font-weight: bold;">PAID</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($statuses[$status_class] ?? ucfirst($status_class)); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small view-entry" data-id="<?php echo esc_attr($entry->id); ?>">View</button>
                                <?php if (!$entry->is_cancelled): ?>
                                    <button class="button button-small edit-entry" data-id="<?php echo esc_attr($entry->id); ?>">Edit</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Entry Modal -->
<div id="entry-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2 id="modal-title">Add New Job</h2>

        <form id="diary-entry-form">
            <input type="hidden" id="entry-id" name="entry_id" value="">

            <div class="form-sections">
                <!-- Order Info Section -->
                <div class="form-section">
                    <h3>Order Information</h3>
                    <div class="form-grid">
                        <div class="form-field" id="order-number-display" style="display: none;">
                            <label>Order Number</label>
                            <div><strong id="order-number-value" style="font-size: 18px; color: #2271b1;"></strong></div>
                        </div>
                        <div class="form-field">
                            <label for="status">Status <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description" style="color: #d63638; margin-top: 5px;">
                                <strong>Note:</strong> Setting status to "Cancelled" will remove this job from your diary.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Customer Section -->
                <div class="form-section">
                    <h3>Customer Details</h3>
                    <div class="form-field">
                        <label for="customer-search">Search Customer</label>
                        <div style="position: relative;">
                            <input type="text" id="customer-search" placeholder="Type to search customers..." autocomplete="off" style="width: 100%;">
                            <input type="hidden" id="customer-id" name="customer_id" value="">
                            <div id="customer-search-results" class="search-results"></div>
                        </div>
                        <div id="selected-customer-display" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                            <strong>Selected Customer:</strong> <span id="selected-customer-name"></span>
                            <button type="button" class="button button-small" id="clear-customer-btn" style="margin-left: 10px;">Change</button>
                        </div>
                        <button type="button" class="button button-small" id="add-new-customer-inline" style="margin-top: 5px;">+ Add New Customer</button>
                    </div>
                </div>

                <!-- Fitter Selection -->
                <div class="form-section">
                    <h3>Assign Fitter</h3>
                    <div class="form-field">
                        <label for="fitter-id">Fitter</label>
                        <select id="fitter-id" name="fitter_id">
                            <option value="">None / Unassigned</option>
                            <?php foreach ($fitters as $index => $fitter): ?>
                                <option value="<?php echo esc_attr($index); ?>">
                                    <?php echo esc_html($fitter['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($fitters)): ?>
                            <p class="description">No fitters available. <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-settings#fitters'); ?>">Add fitters in settings</a></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Address Details Section -->
                <div class="form-section">
                    <h3>Address Details</h3>
                    <div class="form-subsection">
                        <h4>Billing Address</h4>
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="billing-address-line-1">Address Line 1</label>
                                <input type="text" id="billing-address-line-1" name="billing_address_line_1">
                            </div>
                            <div class="form-field">
                                <label for="billing-address-line-2">Address Line 2</label>
                                <input type="text" id="billing-address-line-2" name="billing_address_line_2">
                            </div>
                            <div class="form-field">
                                <label for="billing-address-line-3">Address Line 3</label>
                                <input type="text" id="billing-address-line-3" name="billing_address_line_3">
                            </div>
                            <div class="form-field">
                                <label for="billing-postcode">Postcode</label>
                                <input type="text" id="billing-postcode" name="billing_postcode">
                            </div>
                        </div>
                    </div>
                    <div class="form-field" style="margin: 15px 0;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="fitting-address-different" name="fitting_address_different" value="1">
                            <span>Fitting address is different from billing address</span>
                        </label>
                    </div>
                    <div class="form-subsection" id="fitting-address-section" style="display: none;">
                        <h4>Fitting Address</h4>
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="fitting-address-line-1">Address Line 1</label>
                                <input type="text" id="fitting-address-line-1" name="fitting_address_line_1">
                            </div>
                            <div class="form-field">
                                <label for="fitting-address-line-2">Address Line 2</label>
                                <input type="text" id="fitting-address-line-2" name="fitting_address_line_2">
                            </div>
                            <div class="form-field">
                                <label for="fitting-address-line-3">Address Line 3</label>
                                <input type="text" id="fitting-address-line-3" name="fitting_address_line_3">
                            </div>
                            <div class="form-field">
                                <label for="fitting-postcode">Postcode</label>
                                <input type="text" id="fitting-postcode" name="fitting_postcode">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Details Section -->
                <div class="form-section">
                    <h3>Job Details</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="job-date">Job Date <span class="required">*</span></label>
                            <input type="date" id="job-date" name="job_date" required>
                        </div>
                        <?php if ($job_time_type === 'time'): ?>
                        <div class="form-field">
                            <label for="job-time">Job Time</label>
                            <input type="time" id="job-time" name="job_time">
                        </div>
                        <?php endif; ?>
                        <div class="form-field">
                            <label for="fitting-date">Fitting Date</label>
                            <input type="date" id="fitting-date" name="fitting_date">
                        </div>
                        <?php if ($job_time_type === 'ampm'): ?>
                        <div class="form-field">
                            <label for="fitting-time-period">Fitting Time</label>
                            <select id="fitting-time-period" name="fitting_time_period">
                                <option value="">Select...</option>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="form-field">
                            <label for="area">Area</label>
                            <input type="text" id="area" name="area">
                        </div>
                        <div class="form-field">
                            <label for="size">Size</label>
                            <input type="text" id="size" name="size">
                        </div>
                    </div>
                </div>

                <!-- Product Section -->
                <div class="form-section">
                    <h3>Product Details</h3>

                    <!-- Product Source Toggle -->
                    <div class="form-field" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Product Source</label>
                        <div style="display: flex; gap: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="product_source" id="product-source-manual" value="manual" checked>
                                <span>Manual Entry</span>
                            </label>
                            <?php if (class_exists('WooCommerce')): ?>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="product_source" id="product-source-woocommerce" value="woocommerce">
                                <span>WooCommerce Product</span>
                            </label>
                            <?php else: ?>
                            <p class="description" style="color: #d63638; margin: 0;">
                                <strong>WooCommerce not detected.</strong> Install WooCommerce to select products from your store.
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- WooCommerce Product Search (hidden by default) -->
                    <?php if (class_exists('WooCommerce')): ?>
                    <div id="woocommerce-product-selector" style="display: none; margin-bottom: 20px;">
                        <div class="form-field">
                            <label for="woocommerce-product-search">Search WooCommerce Products</label>
                            <div style="position: relative;">
                                <input type="text" id="woocommerce-product-search" placeholder="Type to search products..." autocomplete="off" style="width: 100%;">
                                <input type="hidden" id="woocommerce-product-id" name="woocommerce_product_id" value="">
                                <div id="woocommerce-product-results" class="search-results"></div>
                            </div>
                            <div id="selected-wc-product-display" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                                <strong>Selected Product:</strong> <span id="selected-wc-product-name"></span>
                                <button type="button" class="button button-small" id="clear-wc-product-btn" style="margin-left: 10px;">Change</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Product Details Fields -->
                    <div id="product-details-fields">
                        <div class="form-field">
                            <label for="product-description">Product Description</label>
                            <textarea id="product-description" name="product_description" rows="3"></textarea>
                        </div>
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="sq-mtr-qty">Sq.Mtr / Quantity</label>
                                <input type="number" id="sq-mtr-qty" name="sq_mtr_qty" step="0.01" min="0">
                            </div>
                            <div class="form-field">
                                <label for="price-per-sq-mtr">Price per Sq.Mtr (£)</label>
                                <input type="number" id="price-per-sq-mtr" name="price_per_sq_mtr" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="calculation-display">
                        <strong>Product Total:</strong> £<span id="product-total-display">0.00</span>
                    </div>
                </div>

                <!-- Accessories Section -->
                <div class="form-section">
                    <h3>Accessories</h3>
                    <div id="accessories-list">
                        <?php foreach ($accessories as $accessory): ?>
                            <div class="accessory-item">
                                <label>
                                    <input type="checkbox" class="accessory-checkbox"
                                           data-accessory-id="<?php echo esc_attr($accessory->id); ?>"
                                           data-accessory-name="<?php echo esc_attr($accessory->accessory_name); ?>"
                                           data-price="<?php echo esc_attr($accessory->price); ?>">
                                    <?php echo esc_html($accessory->accessory_name); ?>
                                    (£<?php echo number_format($accessory->price, 2); ?>)
                                </label>
                                <input type="number" class="accessory-quantity"
                                       data-accessory-id="<?php echo esc_attr($accessory->id); ?>"
                                       min="1" value="1" step="0.01"
                                       style="width: 80px; margin-left: 10px;"
                                       disabled>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="calculation-display">
                        <strong>Accessories Total:</strong> £<span id="accessories-total-display">0.00</span>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="form-section financial-summary">
                    <h3>Financial Summary</h3>
                    <table class="calculation-table">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="amount">£<span id="subtotal-display">0.00</span></td>
                        </tr>
                        <?php if ($vat_enabled == '1'): ?>
                        <tr>
                            <td>VAT (<?php echo $vat_rate; ?>%):</td>
                            <td class="amount">£<span id="vat-display">0.00</span></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td><strong>Total:</strong></td>
                            <td class="amount"><strong>£<span id="total-display">0.00</span></strong></td>
                        </tr>
                    </table>
                </div>

                <!-- Photos Section -->
                <div class="form-section" id="photos-section" style="display: none;">
                    <h3>Photos</h3>
                    <div id="job-photos-container">
                        <p class="description">No photos uploaded yet.</p>
                    </div>
                    <button type="button" class="button" id="upload-photo-form-btn">
                        <span class="dashicons dashicons-camera"></span> Upload Photo
                    </button>
                    <input type="file" id="photo-upload-input-form" accept="image/*" style="display: none;">
                </div>

                <!-- Payment Recording Section -->
                <div class="form-section" id="payment-section" style="display: none;">
                    <h3>Payments</h3>

                    <!-- Payment History -->
                    <div id="payment-history-container" style="margin-bottom: 15px;">
                        <p class="description">No payments recorded yet.</p>
                    </div>

                    <!-- Record New Payment Form -->
                    <div id="payment-form-container" class="payment-form" style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px;">Record New Payment</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px;"><strong>Amount (£):</strong></label>
                                <input type="number" id="payment-amount-form" step="0.01" min="0.01" style="width: 100%;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px;"><strong>Payment Method:</strong></label>
                                <select id="payment-method-form" style="width: 100%;">
                                    <?php
                                    $payment_methods = get_option('wp_staff_diary_payment_methods', array());
                                    foreach ($payment_methods as $key => $label):
                                    ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;"><strong>Payment Type:</strong></label>
                            <select id="payment-type-form" style="width: 100%;">
                                <option value="deposit">Deposit</option>
                                <option value="partial">Partial Payment</option>
                                <option value="final">Final Payment</option>
                                <option value="full">Full Payment</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;"><strong>Notes:</strong></label>
                            <textarea id="payment-notes-form" rows="2" style="width: 100%;"></textarea>
                        </div>
                        <button type="button" class="button button-primary" id="record-payment-form-btn">
                            <span class="dashicons dashicons-yes"></span> Record Payment
                        </button>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-section">
                    <h3>Additional Notes</h3>
                    <div class="form-field">
                        <textarea id="notes" name="notes" rows="4" placeholder="Add any additional notes or special instructions..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary button-large" id="save-entry-btn">
                    <span class="dashicons dashicons-yes"></span> Save Job
                </button>
                <button type="button" class="button button-large" id="cancel-entry-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Entry Modal -->
<div id="view-entry-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 1000px; max-height: 90vh; overflow-y: auto;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <div id="entry-details-content"></div>
    </div>
</div>

<!-- Add New Customer Inline Modal -->
<div id="quick-add-customer-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 500px;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2>Add New Customer</h2>
        <form id="quick-add-customer-form">
            <div class="form-field">
                <label for="quick-customer-name">Customer Name <span class="required">*</span></label>
                <input type="text" id="quick-customer-name" required>
            </div>
            <div class="form-field">
                <label for="quick-customer-phone">Phone</label>
                <input type="tel" id="quick-customer-phone">
            </div>
            <div class="form-field">
                <label for="quick-customer-email">Email</label>
                <input type="email" id="quick-customer-email">
            </div>
            <div class="form-field">
                <label for="quick-address-line-1">Address Line 1</label>
                <input type="text" id="quick-address-line-1">
            </div>
            <div class="form-field">
                <label for="quick-address-line-2">Address Line 2</label>
                <input type="text" id="quick-address-line-2">
            </div>
            <div class="form-field">
                <label for="quick-address-line-3">Address Line 3</label>
                <input type="text" id="quick-address-line-3">
            </div>
            <div class="form-field">
                <label for="quick-postcode">Postcode</label>
                <input type="text" id="quick-postcode" style="max-width: 150px;">
            </div>
            <div class="modal-footer">
                <button type="submit" class="button button-primary">Add Customer</button>
                <button type="button" class="button" id="cancel-quick-customer">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var currentMonth = '<?php echo esc_js($current_month); ?>';
    var vatEnabled = <?php echo $vat_enabled; ?>;
    var vatRate = <?php echo $vat_rate; ?>;

    jQuery(document).ready(function($) {
        // List View Filters
        function applyFilters() {
            const sortDate = $('#sort-date').val();
            const filterCustomer = $('#filter-customer').val().toLowerCase();
            const filterFitter = $('#filter-fitter').val();
            const filterBalance = $('#filter-balance').val();

            let $rows = $('.jobs-table tbody tr').not(':has(td[colspan])');
            let visibleRows = [];

            // First, filter rows
            $rows.each(function() {
                const $row = $(this);
                const customerName = $row.data('customer-name') || '';
                const fitterId = String($row.data('fitter-id'));
                const balanceStatus = $row.data('balance');
                let visible = true;

                // Filter by customer
                if (filterCustomer && !customerName.includes(filterCustomer)) {
                    visible = false;
                }

                // Filter by fitter
                if (filterFitter && fitterId !== filterFitter) {
                    visible = false;
                }

                // Filter by balance
                if (filterBalance && balanceStatus !== filterBalance) {
                    visible = false;
                }

                if (visible) {
                    visibleRows.push($row);
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Then, sort visible rows
            if (visibleRows.length > 0) {
                visibleRows.sort(function(a, b) {
                    const dateA = new Date($(a).data('job-date'));
                    const dateB = new Date($(b).data('job-date'));

                    if (sortDate === 'asc') {
                        return dateA - dateB;
                    } else {
                        return dateB - dateA;
                    }
                });

                // Reorder rows in DOM
                const $tbody = $('.jobs-table tbody');
                visibleRows.forEach(function($row) {
                    $tbody.append($row);
                });
            }

            // Show/hide "no results" message
            if (visibleRows.length === 0) {
                if ($('.jobs-table tbody .no-results-row').length === 0) {
                    $('.jobs-table tbody').append(
                        '<tr class="no-results-row"><td colspan="9" style="text-align: center; padding: 40px;">' +
                        '<p style="color: #666; font-size: 16px;">No jobs match the selected filters.</p></td></tr>'
                    );
                }
            } else {
                $('.jobs-table tbody .no-results-row').remove();
            }
        }

        // Filter event handlers
        $('#sort-date').on('change', applyFilters);
        $('#filter-customer').on('input', function() {
            clearTimeout(window.filterCustomerTimeout);
            window.filterCustomerTimeout = setTimeout(applyFilters, 300);
        });
        $('#filter-fitter').on('change', applyFilters);
        $('#filter-balance').on('change', applyFilters);

        // Clear filters
        $('#clear-filters').on('click', function() {
            $('#sort-date').val('desc');
            $('#filter-customer').val('');
            $('#filter-fitter').val('');
            $('#filter-balance').val('');
            applyFilters();
        });

        // Apply initial sort on page load
        applyFilters();
    });
</script>
