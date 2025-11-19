<?php
/**
 * Dashboard Widget - Weekly Calendar View
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wp-staff-diary-dashboard-widget">
    <div class="dashboard-calendar-header">
        <span class="current-week">Week of <?php echo $week_start->format('d M Y'); ?></span>
        <a href="<?php echo admin_url('admin.php?page=wp-staff-diary'); ?>" class="button button-small">View Full Calendar</a>
    </div>

    <div class="dashboard-calendar-grid">
        <?php
        for ($i = 0; $i < 7; $i++) {
            $current_day = clone $week_start;
            $current_day->modify("+$i day");
            $date_key = $current_day->format('Y-m-d');
            $is_today = $current_day->format('Y-m-d') === date('Y-m-d');
            $day_entries = isset($entries_by_date[$date_key]) ? $entries_by_date[$date_key] : array();
            $day_name = $current_day->format('D');
        ?>
            <div class="dashboard-calendar-day <?php echo $is_today ? 'today' : ''; ?>">
                <div class="dashboard-day-header">
                    <span class="day-name"><?php echo $day_name; ?></span>
                    <span class="day-date"><?php echo $current_day->format('d'); ?></span>
                </div>
                <div class="dashboard-day-entries">
                    <?php if (empty($day_entries)): ?>
                        <div class="no-jobs-dashboard">No jobs</div>
                    <?php else: ?>
                        <?php foreach ($day_entries as $entry): ?>
                            <?php
                            $customer = isset($entry->customer_id) && $entry->customer_id ? $db->get_customer($entry->customer_id) : null;
                            $client_name = $customer && isset($customer->customer_name) ? $customer->customer_name : 'No client';
                            ?>
                            <div class="dashboard-entry status-<?php echo esc_attr($entry->status ?? 'pending'); ?>">
                                <div class="dashboard-entry-time">
                                    <?php echo $entry->job_time ? esc_html(date('H:i', strtotime($entry->job_time))) : '--:--'; ?>
                                </div>
                                <div class="dashboard-entry-client">
                                    <?php echo esc_html(wp_trim_words($client_name, 3)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="dashboard-widget-footer">
        <a href="<?php echo admin_url('admin.php?page=wp-staff-diary'); ?>" class="button button-primary">
            Add New Job
        </a>
    </div>
</div>
