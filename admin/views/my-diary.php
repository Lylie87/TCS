<?php
/**
 * My Diary Page
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

// Get current month or selected date range
$current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
$start_date = $current_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$entries = $db->get_user_entries($current_user->ID, $start_date, $end_date);
?>

<div class="wrap wp-staff-diary-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="diary-header">
        <div class="date-selector">
            <label for="month-select">View Month:</label>
            <input type="month" id="month-select" value="<?php echo esc_attr($current_month); ?>">
        </div>

        <div class="view-actions">
            <a href="?page=wp-staff-diary" class="button">Calendar View</a>
            <button type="button" class="button button-primary" id="add-new-entry">
                Add New Job
            </button>
        </div>
    </div>

    <div class="diary-entries">
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
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No entries found for this month.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
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
                                <button class="button button-small view-entry" data-id="<?php echo esc_attr($entry->id); ?>">View</button>
                                <button class="button button-small edit-entry" data-id="<?php echo esc_attr($entry->id); ?>">Edit</button>
                                <button class="button button-small delete-entry" data-id="<?php echo esc_attr($entry->id); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
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

<script type="text/javascript">
    var currentMonth = '<?php echo esc_js($current_month); ?>';
</script>
