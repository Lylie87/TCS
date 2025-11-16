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

// Check user capabilities
if (!current_user_can('edit_posts')) {
    wp_die(__('Sorry, you are not allowed to access this page.'));
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

// Get entries for the current week - filter by FITTING DATE for calendar view
// Exclude quotes (status = 'quotation') from calendar
global $wpdb;
$table_diary = $wpdb->prefix . 'staff_diary_entries';
$entries = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_diary
     WHERE user_id = %d
     AND is_cancelled = 0
     AND fitting_date_unknown = 0
     AND status != 'quotation'
     AND (
         (fitting_date BETWEEN %s AND %s)
         OR (fitting_date IS NULL AND job_date BETWEEN %s AND %s)
     )
     ORDER BY fitting_date DESC, job_date DESC, created_at DESC",
    $current_user->ID,
    $start_date,
    $end_date_str,
    $start_date,
    $end_date_str
));

// Get ALL jobs with unknown fitting dates (not limited to current week)
// Exclude quotes from this section as well
$unknown_fitting_date_entries = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_diary
     WHERE user_id = %d
     AND fitting_date_unknown = 1
     AND is_cancelled = 0
     AND status != 'quotation'
     ORDER BY job_date DESC",
    $current_user->ID
));

// Get outstanding quotes (entries with quote_date that haven't been accepted yet)
$outstanding_quotes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_diary
     WHERE user_id = %d
     AND quote_date IS NOT NULL
     AND accepted_date IS NULL
     AND is_cancelled = 0
     ORDER BY quote_date DESC",
    $current_user->ID
));

// Organize scheduled entries by date (exclude jobs with unknown fitting dates)
$entries_by_date = array();

foreach ($entries as $entry) {
    // Skip jobs with unknown fitting dates (they're shown in the separate section)
    if (isset($entry->fitting_date_unknown) && $entry->fitting_date_unknown == 1) {
        continue;
    }

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

    <!-- Jobs with Unknown Fitting Dates -->
    <?php if (!empty($unknown_fitting_date_entries)): ?>
        <div class="unknown-fitting-dates-section" style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 6px; padding: 20px; margin: 20px 0;">
            <h2 style="margin: 0 0 15px 0; color: #856404; font-size: 18px;">
                <span class="dashicons dashicons-clock" style="font-size: 20px; vertical-align: middle;"></span>
                Pending Stock - Fitting Date Unknown (<?php echo count($unknown_fitting_date_entries); ?>)
            </h2>
            <p style="margin: 0 0 15px 0; color: #856404;">
                These jobs are awaiting stock and do not have a confirmed fitting date yet.
            </p>

            <table class="wp-list-table widefat fixed striped" style="background: white;">
                <thead>
                    <tr>
                        <th style="width: 12%;">Order #</th>
                        <th style="width: 12%;">Order Date</th>
                        <th style="width: 20%;">Customer</th>
                        <th style="width: 15%;">Fitter</th>
                        <th style="width: 20%;">Product</th>
                        <th style="width: 10%; text-align: right;">Total</th>
                        <th style="width: 11%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unknown_fitting_date_entries as $entry): ?>
                        <?php
                        $customer_id = isset($entry->customer_id) ? $entry->customer_id : null;
                        $customer = $customer_id ? $db->get_customer($customer_id) : null;

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

                        $order_number = isset($entry->order_number) ? $entry->order_number : 'Job #' . $entry->id;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($order_number); ?></strong></td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($entry->job_date))); ?></td>
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
                            <td>
                                <button class="button button-small view-entry" data-id="<?php echo esc_attr($entry->id); ?>">View</button>
                                <button class="button button-small edit-entry" data-id="<?php echo esc_attr($entry->id); ?>">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

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

    <!-- Outstanding Quotes Section -->
    <?php if (!empty($outstanding_quotes)): ?>
        <div class="outstanding-quotes-section" style="background: #e3f2fd; border: 2px solid #2196f3; border-radius: 6px; padding: 20px; margin: 20px 0;">
            <h2 style="margin: 0 0 15px 0; color: #1565c0; font-size: 18px;">
                <span class="dashicons dashicons-portfolio" style="font-size: 20px; vertical-align: middle;"></span>
                Outstanding Quotes (<?php echo count($outstanding_quotes); ?>)
            </h2>
            <p style="margin: 0 0 15px 0; color: #1565c0;">
                These quotes are awaiting customer acceptance.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
            <?php foreach ($outstanding_quotes as $quote):
                // Get customer details
                $customer = $quote->customer_id ? $db->get_customer($quote->customer_id) : null;

                // Calculate quote total
                $quote_total = $db->calculate_job_total($quote->id);

                // Calculate days since quote
                $quote_date_obj = new DateTime($quote->quote_date);
                $now = new DateTime();
                $days_ago = $now->diff($quote_date_obj)->days;

                // Determine urgency color
                $urgency_color = '#4caf50'; // Green (recent)
                if ($days_ago > 14) {
                    $urgency_color = '#f44336'; // Red (old)
                } elseif ($days_ago > 7) {
                    $urgency_color = '#ff9800'; // Orange (medium)
                }
            ?>
                <div class="quote-card" style="background: white; border-left: 4px solid <?php echo $urgency_color; ?>; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer;" onclick="viewEntryDetails(<?php echo $quote->id; ?>)">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 14px; color: #2271b1;"><?php echo esc_html($quote->order_number); ?></strong>
                            <?php if ($customer): ?>
                                <div style="font-size: 12px; color: #666; margin-top: 3px;">
                                    <?php echo esc_html($customer->customer_name); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right; font-size: 16px; font-weight: bold; color: #2271b1;">
                            £<?php echo number_format($quote_total, 2); ?>
                        </div>
                    </div>

                    <div style="font-size: 11px; color: #666; margin-bottom: 8px;">
                        <strong>Quote Date:</strong> <?php echo date('d/m/Y', strtotime($quote->quote_date)); ?>
                        <span style="color: <?php echo $urgency_color; ?>; font-weight: bold; margin-left: 8px;">
                            (<?php echo $days_ago; ?> day<?php echo $days_ago != 1 ? 's' : ''; ?> ago)
                        </span>
                    </div>

                    <?php if ($quote->product_description): ?>
                        <div style="font-size: 12px; color: #444; margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo esc_html($quote->product_description); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($quote->discount_type) && !empty($quote->discount_value)): ?>
                        <div style="background: #fff3cd; padding: 5px 8px; border-radius: 3px; font-size: 11px; color: #856404; margin-top: 8px;">
                            <strong>Discount Sent:</strong>
                            <?php
                            if ($quote->discount_type === 'percentage') {
                                echo number_format($quote->discount_value, 2) . '%';
                            } else {
                                echo '£' . number_format($quote->discount_value, 2);
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Reuse modals from my-diary.php -->
<!-- Add/Edit Entry Modal -->
<div id="entry-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2 id="modal-title">Add New Job</h2>

        <?php include WP_STAFF_DIARY_PATH . 'admin/views/partials/job-form.php'; ?>
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
                <input type="text" id="quick-postcode">
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
