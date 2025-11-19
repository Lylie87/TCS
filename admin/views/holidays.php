<?php
/**
 * Holidays & Availability Page
 *
 * @since      3.3.1
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

// Get fitters for the form
$fitters = get_option('wp_staff_diary_fitters', array());

// Get fitter availability records
global $wpdb;
$table_availability = $wpdb->prefix . 'staff_diary_fitter_availability';
$availability_records = $wpdb->get_results(
    "SELECT * FROM $table_availability ORDER BY start_date DESC LIMIT 100"
);
?>

<div class="wrap wp-staff-diary-wrap">
    <h1>
        <span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
        Track when fitters are unavailable due to holidays, sick leave, or other reasons. This helps prevent double-booking and assists with scheduling.
    </p>

    <!-- Add Availability Form -->
    <div class="add-availability-form" style="margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 4px; border: 1px solid #ddd;">
        <h2 style="margin-top: 0;">Add Unavailable Period</h2>
        <table class="form-table">
            <tr>
                <th style="width: 150px;"><label for="new-availability-fitter">Fitter <span style="color: #d63638;">*</span></label></th>
                <td>
                    <select id="new-availability-fitter" class="regular-text" required>
                        <option value="">Select a fitter...</option>
                        <?php foreach ($fitters as $fitter_id => $fitter): ?>
                            <option value="<?php echo esc_attr($fitter_id); ?>">
                                <?php echo esc_html($fitter['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="new-availability-start">Start Date <span style="color: #d63638;">*</span></label></th>
                <td><input type="date" id="new-availability-start" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="new-availability-end">End Date <span style="color: #d63638;">*</span></label></th>
                <td><input type="date" id="new-availability-end" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="new-availability-type">Type <span style="color: #d63638;">*</span></label></th>
                <td>
                    <select id="new-availability-type" class="regular-text" required>
                        <option value="holiday">Holiday</option>
                        <option value="sick">Sick Leave</option>
                        <option value="unavailable">Unavailable</option>
                        <option value="other">Other</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="new-availability-reason">Reason / Notes</label></th>
                <td>
                    <textarea id="new-availability-reason" class="large-text" rows="3" placeholder="Optional notes about this unavailable period..."></textarea>
                </td>
            </tr>
        </table>
        <button type="button" id="add-availability-btn" class="button button-primary" style="margin-top: 10px;">
            <span class="dashicons dashicons-calendar-alt"></span> Add Unavailable Period
        </button>
    </div>

    <!-- Availability Records Table -->
    <h2>Unavailability Records</h2>
    <table class="wp-list-table widefat fixed striped" id="fitter-availability-table">
        <thead>
            <tr>
                <th style="width: 20%;">Fitter</th>
                <th style="width: 15%;">Start Date</th>
                <th style="width: 15%;">End Date</th>
                <th style="width: 15%;">Type</th>
                <th style="width: 25%;">Reason</th>
                <th style="width: 10%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($availability_records)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                        <span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></span>
                        No availability records yet. Add your first entry using the form above.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($availability_records as $record): ?>
                    <?php
                    // Get fitter name from fitters array
                    $fitter_name = isset($fitters[$record->fitter_id]) ? $fitters[$record->fitter_id]['name'] : 'Unknown Fitter';
                    $fitter_color = isset($fitters[$record->fitter_id]) ? $fitters[$record->fitter_id]['color'] : '#cccccc';
                    ?>
                    <tr data-availability-id="<?php echo esc_attr($record->id); ?>">
                        <td>
                            <span class="color-preview" style="display: inline-block; width: 15px; height: 15px; background-color: <?php echo esc_attr($fitter_color); ?>; border: 1px solid #ddd; border-radius: 50%; vertical-align: middle; margin-right: 8px;"></span>
                            <?php echo esc_html($fitter_name); ?>
                        </td>
                        <td><?php echo esc_html(date('d/m/Y', strtotime($record->start_date))); ?></td>
                        <td><?php echo esc_html(date('d/m/Y', strtotime($record->end_date))); ?></td>
                        <td>
                            <span class="availability-type-badge" style="padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;
                                <?php
                                switch($record->availability_type) {
                                    case 'holiday':
                                        echo 'background: #d4edda; color: #155724;';
                                        break;
                                    case 'sick':
                                        echo 'background: #f8d7da; color: #721c24;';
                                        break;
                                    case 'unavailable':
                                        echo 'background: #fff3cd; color: #856404;';
                                        break;
                                    default:
                                        echo 'background: #e2e3e5; color: #383d41;';
                                }
                                ?>">
                                <?php echo esc_html(ucfirst($record->availability_type)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($record->reason ?? '—'); ?></td>
                        <td>
                            <button type="button" class="button button-small button-link-delete delete-availability" data-id="<?php echo esc_attr($record->id); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Add Fitter Availability
    $('#add-availability-btn').on('click', function() {
        var fitterId = $('#new-availability-fitter').val();
        var startDate = $('#new-availability-start').val();
        var endDate = $('#new-availability-end').val();
        var type = $('#new-availability-type').val();
        var reason = $('#new-availability-reason').val();

        if (!fitterId || !startDate || !endDate || !type) {
            alert('Please fill in all required fields (Fitter, Start Date, End Date, and Type)');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_add_availability',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                fitter_id: fitterId,
                start_date: startDate,
                end_date: endDate,
                availability_type: type,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    alert('Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Error adding availability record: ' + error);
            }
        });
    });

    // Delete Fitter Availability
    $('.delete-availability').on('click', function() {
        if (!confirm('Are you sure you want to remove this availability record?')) {
            return;
        }

        var availabilityId = $(this).data('id');
        var $row = $(this).closest('tr');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_delete_availability',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                availability_id: availabilityId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Show empty message if no more records
                        if ($('#fitter-availability-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    alert('Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Error removing availability record: ' + error);
            }
        });
    });
});
</script>
