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
// Administrators see all entries, regular users see only their own
global $wpdb;
$table_diary = $wpdb->prefix . 'staff_diary_entries';

if (current_user_can('manage_options')) {
    // Administrator - show ALL entries
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_diary
         WHERE is_cancelled = 0
         AND fitting_date_unknown = 0
         AND status != 'quotation'
         AND (
             (fitting_date BETWEEN %s AND %s)
             OR (fitting_date IS NULL AND job_date BETWEEN %s AND %s)
         )
         ORDER BY fitting_date DESC, job_date DESC, created_at DESC",
        $start_date,
        $end_date_str,
        $start_date,
        $end_date_str
    ));
} else {
    // Regular user - show only their entries
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
}

error_log('===== CALENDAR VIEW QUERY =====');
error_log('User ID: ' . $current_user->ID);
error_log('Date range: ' . $start_date . ' to ' . $end_date_str);
error_log('Entries found: ' . count($entries));
foreach ($entries as $entry) {
    error_log('Entry ID: ' . $entry->id . ', Order: ' . $entry->order_number . ', Status: ' . $entry->status . ', is_cancelled: ' . $entry->is_cancelled);
}
error_log('===== END CALENDAR VIEW QUERY =====');

// Get ALL jobs with unknown fitting dates (not limited to current week)
// Exclude quotes from this section as well
// Order by fitting_date if available, otherwise job_date (soonest first)
if (current_user_can('manage_options')) {
    // Administrator - show ALL entries with unknown fitting dates
    $unknown_fitting_date_entries = $wpdb->get_results(
        "SELECT * FROM $table_diary
         WHERE fitting_date_unknown = 1
         AND is_cancelled = 0
         AND status != 'quotation'
         ORDER BY
            CASE WHEN fitting_date IS NOT NULL THEN fitting_date ELSE job_date END ASC"
    );
} else {
    // Regular user - show only their entries with unknown fitting dates
    $unknown_fitting_date_entries = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_diary
         WHERE user_id = %d
         AND fitting_date_unknown = 1
         AND is_cancelled = 0
         AND status != 'quotation'
         ORDER BY
            CASE WHEN fitting_date IS NOT NULL THEN fitting_date ELSE job_date END ASC",
        $current_user->ID
    ));
}

// Get outstanding quotes (entries with quote_date that haven't been accepted yet)
if (current_user_can('manage_options')) {
    // Administrator - show ALL outstanding quotes
    $outstanding_quotes = $wpdb->get_results(
        "SELECT * FROM $table_diary
         WHERE quote_date IS NOT NULL
         AND accepted_date IS NULL
         AND is_cancelled = 0
         ORDER BY quote_date DESC"
    );
} else {
    // Regular user - show only their outstanding quotes
    $outstanding_quotes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_diary
         WHERE user_id = %d
         AND quote_date IS NOT NULL
         AND accepted_date IS NULL
         AND is_cancelled = 0
         ORDER BY quote_date DESC",
        $current_user->ID
    ));
}

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
// Order: Measures with specific times first (by time), then AM jobs, then PM jobs
foreach ($entries_by_date as $date => $day_entries) {
    usort($day_entries, function($a, $b) {
        // Both have specific times - sort by time
        if ($a->job_time !== null && $b->job_time !== null) {
            return strcmp($a->job_time, $b->job_time);
        }

        // A has time, B doesn't - A comes first
        if ($a->job_time !== null && $b->job_time === null) {
            return -1;
        }

        // B has time, A doesn't - B comes first
        if ($a->job_time === null && $b->job_time !== null) {
            return 1;
        }

        // Neither has specific time - sort by fitting_time_period (AM before PM)
        $a_period = strtolower($a->fitting_time_period ?? '');
        $b_period = strtolower($b->fitting_time_period ?? '');

        if ($a_period === 'am' && $b_period !== 'am') return -1;
        if ($b_period === 'am' && $a_period !== 'am') return 1;
        if ($a_period === 'pm' && $b_period !== 'pm') return -1;
        if ($b_period === 'pm' && $a_period !== 'pm') return 1;

        return 0;
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

// Get fitter availability for the current week (and a bit beyond for warnings)
$table_availability = $wpdb->prefix . 'staff_diary_fitter_availability';
$availability_start = clone $week_start;
$availability_start->modify('-7 days'); // Look back 1 week
$availability_end = clone $end_date;
$availability_end->modify('+14 days'); // Look ahead 2 weeks
$fitter_availability = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_availability
     WHERE end_date >= %s AND start_date <= %s
     ORDER BY start_date ASC",
    $availability_start->format('Y-m-d'),
    $availability_end->format('Y-m-d')
));

// Build a helper function to check if a fitter is unavailable on a specific date
function is_fitter_unavailable($fitter_id, $date, $availability_records) {
    foreach ($availability_records as $record) {
        if ($record->fitter_id == $fitter_id) {
            if ($date >= $record->start_date && $date <= $record->end_date) {
                return $record; // Return the availability record
            }
        }
    }
    return false;
}
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
            <button type="button" class="button" id="toggle-calendar-view">
                <span class="dashicons dashicons-calendar-alt"></span> <span id="view-toggle-text">Day View</span>
            </button>
            <a href="?page=wp-staff-diary&view=list" class="button">
                <span class="dashicons dashicons-list-view"></span> List View
            </a>
            <a href="?page=wp-staff-diary-holidays" class="button" style="background: #f0ad4e; color: white; border-color: #ec971f;">
                <span class="dashicons dashicons-palmtree"></span> Add Holiday
            </a>
            <button type="button" class="button" id="add-new-measure" style="background: #9b59b6; color: white; border-color: #8e44ad;">
                <span class="dashicons dashicons-location"></span> Add Measure
            </button>
            <a href="?page=wp-staff-diary-quotes&action=new" class="button" style="background: #00a0d2; color: white; border-color: #0085ba;">
                <span class="dashicons dashicons-plus-alt"></span> Add New Quote
            </a>
            <button type="button" class="button button-primary" id="add-new-entry">
                <span class="dashicons dashicons-plus-alt"></span> Add New Job
            </button>
        </div>
    </div>

    <!-- Day View Navigation (shown only in day view mode) -->
    <div class="day-view-navigation">
        <button type="button" class="button" id="day-view-prev">
            <span class="dashicons dashicons-arrow-left-alt2"></span> <span class="nav-text">Previous</span>
        </button>
        <span class="day-view-date" id="day-view-current-date">
            <!-- Will be populated by JavaScript -->
        </span>
        <button type="button" class="button" id="day-view-next">
            <span class="nav-text">Next</span> <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    </div>

    <!-- Fitter Availability Info -->
    <?php if (!empty($fitter_availability)): ?>
        <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 12px 15px; margin: 15px 0; border-radius: 4px;">
            <strong style="color: #1976d2; display: block; margin-bottom: 8px;">
                <span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle;"></span>
                Fitter Availability Alerts
            </strong>
            <div style="font-size: 13px; color: #555;">
                <?php
                // Group availability by fitter for display
                $availability_by_fitter = array();
                foreach ($fitter_availability as $avail) {
                    if (!isset($availability_by_fitter[$avail->fitter_id])) {
                        $availability_by_fitter[$avail->fitter_id] = array();
                    }
                    $availability_by_fitter[$avail->fitter_id][] = $avail;
                }

                foreach ($availability_by_fitter as $fitter_id => $availabilities) {
                    $fitter_name = isset($fitters[$fitter_id]) ? $fitters[$fitter_id]['name'] : 'Unknown Fitter';
                    $fitter_color = isset($fitters[$fitter_id]) ? $fitters[$fitter_id]['color'] : '#ccc';

                    foreach ($availabilities as $avail) {
                        echo '<div style="margin: 4px 0;">';
                        echo '<span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: ' . esc_attr($fitter_color) . '; margin-right: 6px; vertical-align: middle;"></span>';
                        echo '<strong>' . esc_html($fitter_name) . '</strong>: ';
                        echo '<span style="color: #d32f2f; font-weight: 600;">' . esc_html(ucfirst($avail->availability_type)) . '</span> ';
                        echo date('d/m/Y', strtotime($avail->start_date));
                        if ($avail->start_date != $avail->end_date) {
                            echo ' - ' . date('d/m/Y', strtotime($avail->end_date));
                        }
                        if (!empty($avail->reason)) {
                            echo ' <span style="color: #666;">(' . esc_html($avail->reason) . ')</span>';
                        }
                        echo '</div>';
                    }
                }
                ?>
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #bbdefb; font-size: 12px; color: #666;">
                    <strong>Legend:</strong> Jobs with unavailable fitters are marked with <span style="display: inline-block; padding: 1px 4px; background: #f44336; color: white; border-radius: 2px; font-size: 9px; font-weight: 600;">⚠</span> or <span style="padding: 2px 6px; background: #f44336; color: white; border-radius: 3px; font-size: 10px; font-weight: 600;">UNAVAILABLE</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
                            <td>
                                <?php
                                // Show fitting/measure date if available, otherwise show job date
                                $display_date = !empty($entry->fitting_date) ? $entry->fitting_date : $entry->job_date;
                                echo esc_html(date('d/m/Y', strtotime($display_date)));
                                ?>
                            </td>
                            <td>
                                <?php if ($customer): ?>
                                    <strong><?php echo esc_html($customer->customer_name ?? ''); ?></strong>
                                    <?php if ($customer->customer_phone): ?>
                                        <br><small><?php echo esc_html($customer->customer_phone ?? ''); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">No customer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($fitter): ?>
                                    <span class="fitter-badge" style="display: inline-block; padding: 3px 8px; border-radius: 3px; background-color: <?php echo esc_attr($fitter['color'] ?? '#ddd'); ?>; color: white; font-size: 11px; font-weight: 600;">
                                        <?php echo esc_html($fitter['name'] ?? ''); ?>
                                    </span>
                                    <?php
                                    // Check if fitter has upcoming unavailability
                                    $display_date = !empty($entry->fitting_date) ? $entry->fitting_date : $entry->job_date;
                                    if ($fitter_id !== null && $display_date) {
                                        $unavailable = is_fitter_unavailable($fitter_id, $display_date, $fitter_availability);
                                        if ($unavailable) {
                                            echo '<span style="display: inline-block; margin-left: 8px; padding: 2px 6px; background: #f44336; color: white; border-radius: 3px; font-size: 10px; font-weight: 600;" title="' . esc_attr(ucfirst($unavailable->availability_type) . ': ' . date('d/m/Y', strtotime($unavailable->start_date)) . ' - ' . date('d/m/Y', strtotime($unavailable->end_date))) . '">UNAVAILABLE</span>';
                                        }
                                    }
                                    ?>
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

            // Get unavailable fitters for this specific day
            $unavailable_fitters = array();
            foreach ($fitter_availability as $avail) {
                if ($date_key >= $avail->start_date && $date_key <= $avail->end_date) {
                    if (isset($fitters[$avail->fitter_id])) {
                        $unavailable_fitters[] = array(
                            'name' => $fitters[$avail->fitter_id]['name'],
                            'color' => $fitters[$avail->fitter_id]['color'],
                            'type' => $avail->availability_type
                        );
                    }
                }
            }
        ?>
            <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>" data-date="<?php echo $date_key; ?>">
                <div class="day-header">
                    <div class="day-date-number"><?php echo $current_day->format('d'); ?></div>
                    <?php if (count($day_entries) > 0): ?>
                        <div class="day-job-count"><?php echo count($day_entries); ?> job<?php echo count($day_entries) != 1 ? 's' : ''; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($unavailable_fitters)): ?>
                        <div class="day-fitter-availability" style="margin-top: 6px; padding: 4px 0; border-top: 1px solid #e0e0e0;">
                            <?php foreach ($unavailable_fitters as $unavail): ?>
                                <div style="display: flex; align-items: center; margin: 2px 0; font-size: 10px;">
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo esc_attr($unavail['color']); ?>; margin-right: 4px; flex-shrink: 0;"></span>
                                    <span style="color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($unavail['name']); ?>: <span style="color: #d32f2f; font-weight: 600;"><?php echo esc_html(ucfirst($unavail['type'])); ?></span></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
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

                            // Get fitter info and set color
                            $fitter_id = isset($entry->fitter_id) ? $entry->fitter_id : null;
                            $fitter = null;
                            $fitter_color = '#ddd';

                            // Purple color for measures
                            if ($entry->status === 'measure') {
                                $fitter_color = '#9b59b6';
                            } elseif ($fitter_id !== null && isset($fitters[$fitter_id])) {
                                $fitter = $fitters[$fitter_id];
                                $fitter_color = $fitter['color'];
                            }

                            $is_cancelled = isset($entry->is_cancelled) ? $entry->is_cancelled : 0;
                            $status_class = $is_cancelled ? 'cancelled' : $entry->status;
                            $order_number = isset($entry->order_number) ? $entry->order_number : 'Job #' . $entry->id;
                            $product_desc = isset($entry->product_description) ? $entry->product_description : '';
                            ?>
                            <div class="calendar-entry status-<?php echo esc_attr($status_class); ?> <?php echo $entry->status === 'measure' ? 'measure-entry' : ''; ?>"
                                 data-entry-id="<?php echo esc_attr($entry->id); ?>"
                                 style="border-left: 4px solid <?php echo esc_attr($fitter_color); ?>;<?php echo $is_cancelled ? ' opacity: 0.6;' : ''; ?><?php echo $entry->status === 'measure' ? ' background: #f3e5f5; padding: 6px 10px;' : ''; ?>">
                                <div class="entry-order">
                                    <strong><?php echo esc_html($order_number); ?></strong>
                                </div>
                                <div class="entry-time">
                                    <?php
                                    // For measures, show specific time (job_time)
                                    // For jobs (including converted measures), prioritize fitting_time_period (AM/PM)
                                    if ($entry->status === 'measure' && $entry->job_time) {
                                        echo esc_html(date('H:i', strtotime($entry->job_time)));
                                    } elseif (!empty($entry->fitting_time_period)) {
                                        echo '<span style="font-weight: 600;">' . esc_html(strtoupper($entry->fitting_time_period)) . '</span>';
                                    } elseif ($entry->job_time) {
                                        echo esc_html(date('H:i', strtotime($entry->job_time)));
                                    } else {
                                        echo '<span style="color: #999;">No time</span>';
                                    }
                                    ?>
                                </div>
                                <div class="entry-customer">
                                    <?php if ($customer): ?>
                                        <?php echo esc_html($customer->customer_name ?? ''); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No customer</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($entry->status !== 'measure' && $fitter): ?>
                                    <div class="entry-fitter" style="font-size: 11px; color: #666; margin-top: 2px;">
                                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo esc_attr($fitter_color ?? '#ddd'); ?>; margin-right: 4px;"></span>
                                        <?php echo esc_html($fitter['name'] ?? ''); ?>
                                        <?php
                                        // Check if fitter is unavailable on this date
                                        $job_date = !empty($entry->fitting_date) ? $entry->fitting_date : $entry->job_date;
                                        if ($fitter_id !== null && $job_date) {
                                            $unavailable = is_fitter_unavailable($fitter_id, $job_date, $fitter_availability);
                                            if ($unavailable) {
                                                echo '<span style="display: inline-block; margin-left: 4px; padding: 1px 4px; background: #f44336; color: white; border-radius: 2px; font-size: 9px; font-weight: 600;" title="' . esc_attr(ucfirst($unavailable->availability_type) . ': ' . date('d/m/Y', strtotime($unavailable->start_date)) . ' - ' . date('d/m/Y', strtotime($unavailable->end_date))) . '">⚠</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($entry->status !== 'measure'): ?>
                                    <div class="entry-product">
                                        <?php echo $product_desc ? esc_html(wp_trim_words($product_desc, 5)) : '<span style="color: #999;">—</span>'; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="entry-status-badge" style="<?php echo $entry->status === 'measure' ? 'display: none;' : ''; ?>">
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

    <!-- Recent Quotes Widget -->
    <div class="recent-quotes-section" style="margin: 20px 0;">
        <div style="background: white; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
            <div style="padding: 15px 20px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7;">
                <h2 style="margin: 0; font-size: 16px; color: #1d2327;">
                    <span class="dashicons dashicons-portfolio" style="font-size: 18px; vertical-align: middle; margin-right: 5px;"></span>
                    Recent Quotes
                </h2>
            </div>
            <?php
            // Get recent quotes for the widget (last 10)
            if (current_user_can('manage_options')) {
                // Administrator - show ALL recent quotes
                $recent_quotes = $wpdb->get_results(
                    "SELECT * FROM $table_diary
                     WHERE status = 'quotation'
                     AND is_cancelled = 0
                     ORDER BY created_at DESC
                     LIMIT 10"
                );
            } else {
                // Regular user - show only their recent quotes
                $recent_quotes = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_diary
                     WHERE user_id = %d
                     AND status = 'quotation'
                     AND is_cancelled = 0
                     ORDER BY created_at DESC
                     LIMIT 10",
                    $current_user->ID
                ));
            }

            // Enrich quotes with customer data and totals
            foreach ($recent_quotes as $quote) {
                if ($quote->customer_id) {
                    $quote->customer = $db->get_customer($quote->customer_id);
                }

                // Calculate quote total
                $subtotal = $db->calculate_job_subtotal($quote->id);
                if ($vat_enabled == '1') {
                    $quote->total = $subtotal * (1 + ($vat_rate / 100));
                } else {
                    $quote->total = $subtotal;
                }
            }

            // Set $quotes for the widget
            $quotes = $recent_quotes;

            // Include the quotes widget view
            include WP_STAFF_DIARY_PATH . 'admin/views/quotes-widget.php';
            ?>
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
                            <strong style="font-size: 14px; color: #2271b1;"><?php echo esc_html($quote->order_number ?? ''); ?></strong>
                            <?php if ($customer): ?>
                                <div style="font-size: 12px; color: #666; margin-top: 3px;">
                                    <?php echo esc_html($customer->customer_name ?? ''); ?>
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
                            <?php echo esc_html($quote->product_description ?? ''); ?>
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

<!-- Convert to Job Modal -->
<div id="convert-to-job-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 500px;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2>Convert Quote to Job</h2>
        <p style="margin: 15px 0; color: #666;">Please provide the fitting details to convert this quote into a job.</p>

        <form id="convert-to-job-form">
            <input type="hidden" id="convert-quote-id">

            <div class="form-field">
                <label for="convert-fitting-date">Fitting Date <span class="required">*</span></label>
                <input type="date" id="convert-fitting-date" required>
            </div>

            <div class="form-field">
                <label for="convert-fitting-time-period">Time Period <span class="required">*</span></label>
                <select id="convert-fitting-time-period" required>
                    <option value="">Select time period...</option>
                    <option value="am">Morning (AM)</option>
                    <option value="pm">Afternoon (PM)</option>
                    <option value="all-day">All Day</option>
                </select>
                <p class="description">Select AM or PM to view availability across all fitters</p>
            </div>

            <div class="form-field">
                <label for="convert-fitter">Fitter <span class="required">*</span></label>
                <select id="convert-fitter" required>
                    <option value="">Select a fitter...</option>
                    <?php foreach ($fitters as $fitter_id => $fitter): ?>
                        <option value="<?php echo esc_attr($fitter_id); ?>">
                            <?php echo esc_html($fitter['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Available fitter will be auto-assigned when you select a date</p>
            </div>

            <!-- Availability Display -->
            <div id="fitter-availability-display" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">Fitter Availability (Next 2 Weeks)</h4>
                <div id="availability-loading" style="display: none; text-align: center; padding: 20px;">
                    <span class="dashicons dashicons-update dashicons-spin" style="font-size: 24px;"></span>
                    <p>Loading availability...</p>
                </div>
                <div id="availability-calendar" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
                    <!-- Availability will be populated here -->
                </div>
                <div id="availability-legend" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px;">
                    <strong>Legend:</strong>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #4caf50; border-radius: 2px; margin-right: 5px;"></span>
                        Available
                    </span>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #ff9800; border-radius: 2px; margin-right: 5px;"></span>
                        Partially Booked
                    </span>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #f44336; border-radius: 2px; margin-right: 5px;"></span>
                        Fully Booked
                    </span>
                </div>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" id="convert-fitting-date-unknown">
                    Fitting date not yet confirmed
                </label>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary">Convert to Job</button>
                <button type="button" class="button cancel-convert">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Convert Measure to Job Modal -->
<div id="convert-measure-to-job-modal" class="wp-staff-diary-modal" style="display: none;">
    <div class="wp-staff-diary-modal-content" style="max-width: 500px;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2>Convert Measure to Job</h2>
        <p style="margin: 15px 0; color: #666;">Please provide the fitting details to convert this measure into a job.</p>

        <form id="convert-measure-to-job-form">
            <input type="hidden" id="convert-measure-id">

            <div class="form-field">
                <label for="convert-measure-fitting-date">Fitting Date <span class="required">*</span></label>
                <input type="date" id="convert-measure-fitting-date" required>
            </div>

            <div class="form-field">
                <label for="convert-measure-fitting-time-period">Time Period <span class="required">*</span></label>
                <select id="convert-measure-fitting-time-period" required>
                    <option value="">Select time period...</option>
                    <option value="am">Morning (AM)</option>
                    <option value="pm">Afternoon (PM)</option>
                    <option value="all-day">All Day</option>
                </select>
                <p class="description">Select AM or PM to view availability across all fitters</p>
            </div>

            <div class="form-field">
                <label for="convert-measure-fitter">Fitter <span class="required">*</span></label>
                <select id="convert-measure-fitter" required>
                    <option value="">Select a fitter...</option>
                    <?php foreach ($fitters as $fitter_id => $fitter): ?>
                        <option value="<?php echo esc_attr($fitter_id); ?>">
                            <?php echo esc_html($fitter['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Available fitter will be auto-assigned when you select a date</p>
            </div>

            <!-- Availability Display -->
            <div id="measure-fitter-availability-display" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;">Fitter Availability (Next 2 Weeks)</h4>
                <div id="measure-availability-loading" style="display: none; text-align: center; padding: 20px;">
                    <span class="dashicons dashicons-update dashicons-spin" style="font-size: 24px;"></span>
                    <p>Loading availability...</p>
                </div>
                <div id="measure-availability-calendar" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
                    <!-- Availability will be populated here -->
                </div>
                <div id="measure-availability-legend" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px;">
                    <strong>Legend:</strong>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #4caf50; border-radius: 2px; margin-right: 5px;"></span>
                        Available
                    </span>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #ff9800; border-radius: 2px; margin-right: 5px;"></span>
                        Partially Booked
                    </span>
                    <span style="display: inline-block; margin-left: 15px;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: #f44336; border-radius: 2px; margin-right: 5px;"></span>
                        Fully Booked
                    </span>
                </div>
            </div>

            <div class="form-field">
                <label>
                    <input type="checkbox" id="convert-measure-fitting-date-unknown">
                    Fitting date not yet confirmed
                </label>
            </div>

            <div class="modal-footer">
                <button type="submit" class="button button-primary">Convert to Job</button>
                <button type="button" class="button cancel-convert-measure">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Add Customer Modal -->
<div id="quick-add-customer-modal" class="wp-staff-diary-modal" style="display: none; z-index: 100002;">
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

<!-- Add/Edit Measure Modal -->
<div id="measure-modal" class="wp-staff-diary-modal" style="display: none; z-index: 100001;">
    <div class="wp-staff-diary-modal-content" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <span class="wp-staff-diary-modal-close">&times;</span>
        <h2 id="measure-modal-title">Add New Measure</h2>

        <?php include WP_STAFF_DIARY_PATH . 'admin/views/partials/measure-form.php'; ?>
    </div>
</div>

<script type="text/javascript">
    var weekOffset = <?php echo $week_offset; ?>;
    var vatEnabled = <?php echo $vat_enabled; ?>;
    var vatRate = <?php echo $vat_rate; ?>;
</script>
