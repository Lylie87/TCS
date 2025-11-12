<?php
/**
 * Calendar View - Main Dashboard
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
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

// Organize entries by date
$entries_by_date = array();
foreach ($entries as $entry) {
    $date_key = $entry->job_date;
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
?>

<div class="wrap wp-staff-diary-wrap">
    <div class="plugin-branding">
        <img src="<?php echo WP_STAFF_DIARY_URL; ?>assets/images/staff-daily-job-planner-logo.svg" alt="Staff Daily Job Planner" class="plugin-logo">
        <div class="plugin-title">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        </div>
    </div>

    <div class="calendar-header">
        <div class="calendar-navigation">
            <a href="?page=wp-staff-diary&week=<?php echo ($week_offset - 1); ?>" class="button">
                &laquo; Previous Week
            </a>
            <span class="current-week">
                Week of <?php echo $week_start->format('d M Y'); ?>
            </span>
            <a href="?page=wp-staff-diary&week=<?php echo ($week_offset + 1); ?>" class="button">
                Next Week &raquo;
            </a>
            <?php if ($week_offset != 0): ?>
                <a href="?page=wp-staff-diary" class="button">This Week</a>
            <?php endif; ?>
        </div>

        <div class="calendar-actions">
            <a href="?page=wp-staff-diary&view=list" class="button">List View</a>
            <button type="button" class="button button-primary" id="add-new-entry">
                Add New Job
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
                        <div class="no-jobs"></div>
                    <?php else: ?>
                        <?php foreach ($day_entries as $entry): ?>
                            <div class="calendar-entry status-<?php echo esc_attr($entry->status); ?>"
                                 data-entry-id="<?php echo esc_attr($entry->id); ?>">
                                <div class="entry-time">
                                    <?php echo $entry->job_time ? esc_html(date('H:i', strtotime($entry->job_time))) : 'No time set'; ?>
                                </div>
                                <div class="entry-client">
                                    <?php echo esc_html($entry->client_name ?: 'No client'); ?>
                                </div>
                                <div class="entry-description">
                                    <?php echo esc_html(wp_trim_words($entry->job_description, 8)); ?>
                                </div>
                                <div class="entry-actions">
                                    <button class="button-link view-entry" data-id="<?php echo esc_attr($entry->id); ?>">
                                        View
                                    </button>
                                    <button class="button-link edit-entry" data-id="<?php echo esc_attr($entry->id); ?>">
                                        Edit
                                    </button>
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

<!-- Entry Modal (for add/edit) -->
<div id="entry-modal" class="entry-modal" style="display: none;">
    <div class="entry-modal-content">
        <span class="entry-modal-close">&times;</span>
        <h2 id="modal-title">Add New Job Entry</h2>

        <form id="diary-entry-form">
            <input type="hidden" id="entry-id" name="entry_id" value="">

            <table class="form-table">
                <tr>
                    <th><label for="job-date">Job Date *</label></th>
                    <td><input type="date" id="job-date" name="job_date" required></td>
                </tr>
                <tr>
                    <th><label for="job-time">Job Time</label></th>
                    <td><input type="time" id="job-time" name="job_time" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="client-name">Client Name</label></th>
                    <td><input type="text" id="client-name" name="client_name" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="client-address">Address</label></th>
                    <td><textarea id="client-address" name="client_address" rows="3" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="client-phone">Phone Number</label></th>
                    <td><input type="tel" id="client-phone" name="client_phone" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="job-description">Job Description</label></th>
                    <td><textarea id="job-description" name="job_description" rows="4" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="plans">Plans/Notes</label></th>
                    <td><textarea id="plans" name="plans" rows="4" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="notes">Additional Notes</label></th>
                    <td><textarea id="notes" name="notes" rows="3" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select id="status" name="status">
                            <?php
                            $statuses = get_option('wp_staff_diary_statuses', array(
                                'pending' => 'Pending',
                                'in-progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled'
                            ));
                            foreach ($statuses as $key => $label) {
                                echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Job Images</label></th>
                    <td>
                        <div id="image-gallery"></div>
                        <button type="button" class="button" id="upload-image-btn">Upload Image</button>
                        <input type="file" id="image-upload-input" style="display: none;" accept="image/*">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Save Entry</button>
                <button type="button" class="button button-secondary" id="cancel-entry">Cancel</button>
            </p>
        </form>
    </div>
</div>

<!-- View Entry Modal -->
<div id="view-entry-modal" class="entry-modal" style="display: none;">
    <div class="entry-modal-content">
        <span class="entry-modal-close">&times;</span>
        <h2>Job Entry Details</h2>
        <div id="entry-details-content"></div>
    </div>
</div>

<script type="text/javascript">
    var currentWeekOffset = <?php echo $week_offset; ?>;
</script>
