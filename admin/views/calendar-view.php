<?php
/**
 * Calendar View - Weekly Job Planner
 *
 * @since      2.0.0
 * @package    WP_Staff_Diary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$db = new WP_Staff_Diary_Database();

// Get current week or selected week
$week_offset = isset($_GET['week']) ? intval($_GET['week']) : 0;
$today = new DateTime();
$today->modify("$week_offset week");
$week_start = clone $today;
$week_start->modify('monday this week');

// Get all entries for the current week
$start_date = $week_start->format('Y-m-d');
$end_date = clone $week_start;
$end_date->modify('+6 days');
$end_date_str = $end_date->format('Y-m-d');

$entries = $db->get_user_entries($current_user->ID, $start_date, $end_date_str);

// Organize entries by fitting date (or job date if no fitting date)
$entries_by_date = array();
foreach ($entries as $entry) {
    // Use fitting_date if set, otherwise fall back to job_date
    $date_key = !empty($entry->fitting_date) ? $entry->fitting_date : $entry->job_date;
    if (!isset($entries_by_date[$date_key])) {
        $entries_by_date[$date_key] = array();
    }
    $entries_by_date[$date_key][] = $entry;
}

// Sort entries by time within each day
foreach ($entries_by_date as $date => $day_entries) {
    usort($day_entries, function($a, $b) {
        if ($a->job_time === null) return 1;
        if ($b->job_time === null) return -1;
        return strcmp($a->job_time, $b->job_time);
    });
    $entries_by_date[$date] = $day_entries;
}

// Get statuses and VAT settings
$statuses = get_option('wp_staff_diary_statuses', array(
    'pending' => 'Pending',
    'in-progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
));
$accessories = $db->get_all_accessories(true);
$fitters = get_option('wp_staff_diary_fitters', array());
$job_time_type = get_option('wp_staff_diary_job_time_type', 'none');
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');
?>

<div class="wrap wp-staff-diary-wrap">
    <h1>
        <span class="dashicons dashicons-calendar"></span> <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <div class="calendar-header">
        <div class="calendar-navigation">
            <a href="?page=wp-staff-diary&week=<?php echo ($week_offset - 1); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span> Previous Week
            </a>
            <span class="current-week">
                <strong>Week of <?php echo $week_start->format('d M Y'); ?></strong>
            </span>
            <a href="?page=wp-staff-diary&week=<?php echo ($week_offset + 1); ?>" class="button">
                Next Week <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
            <?php if ($week_offset != 0): ?>
                <a href="?page=wp-staff-diary" class="button">Today</a>
            <?php endif; ?>
        </div>

        <div class="calendar-actions">
            <a href="?page=wp-staff-diary&view=list" class="button">
                <span class="dashicons dashicons-list-view"></span> List View
            </a>
            <button type="button" class="button button-primary" id="add-new-entry">
                <span class="dashicons dashicons-plus-alt"></span> Add New Job
            </button>
        </div>
    </div>

    <div class="calendar-container">
        <div class="calendar-weekdays">
            <div class="weekday-name">Monday</div>
            <div class="weekday-name">Tuesday</div>
            <div class="weekday-name">Wednesday</div>
            <div class="weekday-name">Thursday</div>
            <div class="weekday-name">Friday</div>
            <div class="weekday-name">Saturday</div>
            <div class="weekday-name">Sunday</div>
        </div>

        <div class="calendar-grid">
        <?php
        for ($i = 0; $i < 7; $i++) {
            $current_day = clone $week_start;
            $current_day->modify("+$i day");
            $date_key = $current_day->format('Y-m-d');
            $is_today = $current_day->format('Y-m-d') === date('Y-m-d');
            $day_entries = isset($entries_by_date[$date_key]) ? $entries_by_date[$date_key] : array();
        ?>
            <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>">
                <div class="day-header">
                    <div class="day-date-number"><?php echo $current_day->format('d'); ?></div>
                    <?php if (count($day_entries) > 0): ?>
                        <div class="day-job-count"><?php echo count($day_entries); ?> job<?php echo count($day_entries) != 1 ? 's' : ''; ?></div>
                    <?php endif; ?>
                </div>
                <div class="day-entries">
                    <?php if (empty($day_entries)): ?>
                        <div class="no-jobs">
                            <span style="color: #999; font-size: 12px;">No jobs</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($day_entries as $entry): ?>
                            <?php
                            $customer_id = isset($entry->customer_id) ? $entry->customer_id : null;
                            $customer = $customer_id ? $db->get_customer($customer_id) : null;

                            // Get fitter info
                            $fitter_id = isset($entry->fitter_id) ? $entry->fitter_id : null;
                            $fitter = null;
                            $fitter_color = '#ddd';
                            if ($fitter_id !== null && isset($fitters[$fitter_id])) {
                                $fitter = $fitters[$fitter_id];
                                $fitter_color = $fitter['color'];
                            }

                            $is_cancelled = isset($entry->is_cancelled) ? $entry->is_cancelled : 0;
                            $status_class = $is_cancelled ? 'cancelled' : $entry->status;
                            $order_number = isset($entry->order_number) ? $entry->order_number : 'Job #' . $entry->id;
                            $product_desc = isset($entry->product_description) ? $entry->product_description : '';
                            ?>
                            <div class="calendar-entry status-<?php echo esc_attr($status_class); ?>"
                                 data-entry-id="<?php echo esc_attr($entry->id); ?>"
                                 style="border-left: 4px solid <?php echo esc_attr($fitter_color); ?>;<?php echo $is_cancelled ? ' opacity: 0.6;' : ''; ?>">
                                <div class="entry-order">
                                    <strong><?php echo esc_html($order_number); ?></strong>
                                </div>
                                <div class="entry-time">
                                    <?php echo $entry->job_time ? esc_html(date('H:i', strtotime($entry->job_time))) : '<span style="color: #999;">No time</span>'; ?>
                                </div>
                                <div class="entry-customer">
                                    <?php if ($customer): ?>
                                        <?php echo esc_html($customer->customer_name); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No customer</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($fitter): ?>
                                    <div class="entry-fitter" style="font-size: 11px; color: #666; margin-top: 2px;">
                                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo esc_attr($fitter_color); ?>; margin-right: 4px;"></span>
                                        <?php echo esc_html($fitter['name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="entry-product">
                                    <?php echo $product_desc ? esc_html(wp_trim_words($product_desc, 5)) : '<span style="color: #999;">—</span>'; ?>
                                </div>
                                <div class="entry-status-badge">
                                    <span class="status-badge-mini status-<?php echo esc_attr($status_class); ?>"></span>
                                </div>
                                <div class="entry-actions">
                                    <button class="button-link view-entry" data-id="<?php echo esc_attr($entry->id); ?>">
                                        View
                                    </button>
                                    <?php if (!$entry->is_cancelled): ?>
                                        <button class="button-link edit-entry" data-id="<?php echo esc_attr($entry->id); ?>">
                                            Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
        </div>
    </div>
</div>

<!-- Reuse modals from my-diary.php -->
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
                        <input type="text" id="customer-search" placeholder="Type to search customers..." autocomplete="off">
                        <input type="hidden" id="customer-id" name="customer_id" value="">
                        <div id="customer-search-results" class="search-results"></div>
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

<!-- Quick Add Customer Modal -->
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
                <label for="quick-customer-address">Address</label>
                <textarea id="quick-customer-address" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="button button-primary">Add Customer</button>
                <button type="button" class="button" id="cancel-quick-customer">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var weekOffset = <?php echo $week_offset; ?>;
    var vatEnabled = <?php echo $vat_enabled; ?>;
    var vatRate = <?php echo $vat_rate; ?>;
</script>
