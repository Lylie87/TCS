<?php
/**
 * Staff Overview Page
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check permissions
if (!current_user_can('edit_users')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

$db = new WP_Staff_Diary_Database();

// Get current date range
$current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
$start_date = $current_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$all_entries = $db->get_all_entries($start_date, $end_date, 200);

// Group entries by staff member
$entries_by_staff = array();
foreach ($all_entries as $entry) {
    $staff_id = $entry->user_id;
    if (!isset($entries_by_staff[$staff_id])) {
        $entries_by_staff[$staff_id] = array(
            'name' => $entry->staff_name,
            'entries' => array()
        );
    }
    $entries_by_staff[$staff_id]['entries'][] = $entry;
}
?>

<div class="wrap wp-staff-diary-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="diary-header">
        <div class="date-selector">
            <label for="month-select-overview">View Month:</label>
            <input type="month" id="month-select-overview" value="<?php echo esc_attr($current_month); ?>">
        </div>

        <div class="filter-options">
            <label for="staff-filter">Filter by Staff:</label>
            <select id="staff-filter">
                <option value="">All Staff</option>
                <?php
                $staff_users = get_users(array('orderby' => 'display_name'));
                foreach ($staff_users as $user) {
                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <div class="staff-overview-content">
        <?php if (empty($entries_by_staff)): ?>
            <p>No entries found for this month.</p>
        <?php else: ?>
            <?php foreach ($entries_by_staff as $staff_id => $staff_data): ?>
                <div class="staff-section" data-staff-id="<?php echo esc_attr($staff_id); ?>">
                    <h2><?php echo esc_html($staff_data['name']); ?></h2>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Job Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_data['entries'] as $entry): ?>
                                <tr data-entry-id="<?php echo esc_attr($entry->id); ?>">
                                    <td><?php echo esc_html(date('d/m/Y', strtotime($entry->job_date))); ?></td>
                                    <td><?php echo esc_html($entry->client_name); ?></td>
                                    <td><?php echo esc_html($entry->client_address); ?></td>
                                    <td><?php echo esc_html($entry->client_phone); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($entry->job_description, 10)); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($entry->status); ?>">
                                            <?php echo esc_html(ucfirst($entry->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="button button-small view-entry" data-id="<?php echo esc_attr($entry->id); ?>">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
    var currentMonth = '<?php echo esc_js($current_month); ?>';
</script>
