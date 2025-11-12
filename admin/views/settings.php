<?php
/**
 * Settings Page
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Save settings
if (isset($_POST['wp_staff_diary_save_settings'])) {
    check_admin_referer('wp_staff_diary_settings_nonce');

    update_option('wp_staff_diary_date_format', sanitize_text_field($_POST['date_format']));
    update_option('wp_staff_diary_time_format', sanitize_text_field($_POST['time_format']));
    update_option('wp_staff_diary_week_start', sanitize_text_field($_POST['week_start']));
    update_option('wp_staff_diary_default_status', sanitize_text_field($_POST['default_status']));

    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
$time_format = get_option('wp_staff_diary_time_format', 'H:i');
$week_start = get_option('wp_staff_diary_week_start', 'monday');
$default_status = get_option('wp_staff_diary_default_status', 'pending');
?>

<div class="wrap wp-staff-diary-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('wp_staff_diary_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="date_format">Date Format</label>
                    </th>
                    <td>
                        <select name="date_format" id="date_format" class="regular-text">
                            <option value="d/m/Y" <?php selected($date_format, 'd/m/Y'); ?>>DD/MM/YYYY (<?php echo date('d/m/Y'); ?>)</option>
                            <option value="m/d/Y" <?php selected($date_format, 'm/d/Y'); ?>>MM/DD/YYYY (<?php echo date('m/d/Y'); ?>)</option>
                            <option value="Y-m-d" <?php selected($date_format, 'Y-m-d'); ?>>YYYY-MM-DD (<?php echo date('Y-m-d'); ?>)</option>
                            <option value="d-m-Y" <?php selected($date_format, 'd-m-Y'); ?>>DD-MM-YYYY (<?php echo date('d-m-Y'); ?>)</option>
                            <option value="m-d-Y" <?php selected($date_format, 'm-d-Y'); ?>>MM-DD-YYYY (<?php echo date('m-d-Y'); ?>)</option>
                            <option value="d M Y" <?php selected($date_format, 'd M Y'); ?>>DD Mon YYYY (<?php echo date('d M Y'); ?>)</option>
                            <option value="M d, Y" <?php selected($date_format, 'M d, Y'); ?>>Mon DD, YYYY (<?php echo date('M d, Y'); ?>)</option>
                        </select>
                        <p class="description">Choose how dates are displayed throughout the plugin.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="time_format">Time Format</label>
                    </th>
                    <td>
                        <select name="time_format" id="time_format" class="regular-text">
                            <option value="H:i" <?php selected($time_format, 'H:i'); ?>>24-hour (<?php echo date('H:i'); ?>)</option>
                            <option value="h:i A" <?php selected($time_format, 'h:i A'); ?>>12-hour (<?php echo date('h:i A'); ?>)</option>
                            <option value="h:i a" <?php selected($time_format, 'h:i a'); ?>>12-hour lowercase (<?php echo date('h:i a'); ?>)</option>
                        </select>
                        <p class="description">Choose how times are displayed throughout the plugin.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="week_start">Week Starts On</label>
                    </th>
                    <td>
                        <select name="week_start" id="week_start" class="regular-text">
                            <option value="monday" <?php selected($week_start, 'monday'); ?>>Monday</option>
                            <option value="sunday" <?php selected($week_start, 'sunday'); ?>>Sunday</option>
                        </select>
                        <p class="description">Choose which day the calendar week starts on.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="default_status">Default Job Status</label>
                    </th>
                    <td>
                        <select name="default_status" id="default_status" class="regular-text">
                            <option value="pending" <?php selected($default_status, 'pending'); ?>>Pending</option>
                            <option value="in-progress" <?php selected($default_status, 'in-progress'); ?>>In Progress</option>
                            <option value="completed" <?php selected($default_status, 'completed'); ?>>Completed</option>
                        </select>
                        <p class="description">The default status for new job entries.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="wp_staff_diary_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>

    <hr>

    <!-- Status Management Section -->
    <h2>Job Statuses</h2>
    <p>Manage available job statuses. Default statuses (Pending, In Progress, Completed, Cancelled) cannot be removed.</p>

    <div id="status-management" class="wp-staff-diary-management-section">
        <div class="status-list">
            <?php
            $statuses = get_option('wp_staff_diary_statuses', array(
                'pending' => 'Pending',
                'in-progress' => 'In Progress',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ));

            $default_statuses = array('pending', 'in-progress', 'completed', 'cancelled');

            foreach ($statuses as $key => $label) {
                $is_default = in_array($key, $default_statuses);
                echo '<div class="status-item" data-status-key="' . esc_attr($key) . '">';
                echo '<span class="status-label">' . esc_html($label) . '</span>';
                if (!$is_default) {
                    echo '<button type="button" class="button button-small delete-status" data-status-key="' . esc_attr($key) . '">Remove</button>';
                } else {
                    echo '<span class="status-default-badge">(Default)</span>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <div class="add-status-form">
            <h3>Add New Status</h3>
            <input type="text" id="new-status-label" class="regular-text" placeholder="Enter status name (e.g., Awaiting Approval)">
            <button type="button" id="add-status-btn" class="button button-secondary">Add Status</button>
        </div>
    </div>

    <hr>

    <!-- Payment Methods Management Section -->
    <h2>Payment Methods</h2>
    <p>Manage available payment methods for recording payments.</p>

    <div id="payment-methods-management" class="wp-staff-diary-management-section">
        <div class="payment-methods-list">
            <?php
            $payment_methods = get_option('wp_staff_diary_payment_methods', array(
                'cash' => 'Cash',
                'bank-transfer' => 'Bank Transfer',
                'card-payment' => 'Card Payment'
            ));

            foreach ($payment_methods as $key => $label) {
                echo '<div class="payment-method-item" data-method-key="' . esc_attr($key) . '">';
                echo '<span class="payment-method-label">' . esc_html($label) . '</span>';
                echo '<button type="button" class="button button-small delete-payment-method" data-method-key="' . esc_attr($key) . '">Remove</button>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="add-payment-method-form">
            <h3>Add New Payment Method</h3>
            <input type="text" id="new-payment-method-label" class="regular-text" placeholder="Enter payment method name (e.g., Check, PayPal)">
            <button type="button" id="add-payment-method-btn" class="button button-secondary">Add Payment Method</button>
        </div>
    </div>

    <hr>

    <h2>Plugin Information</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row">Plugin Version</th>
                <td><?php echo WP_STAFF_DIARY_VERSION; ?></td>
            </tr>
            <tr>
                <th scope="row">Database Version</th>
                <td><?php echo get_option('wp_staff_diary_version', 'N/A'); ?></td>
            </tr>
            <tr>
                <th scope="row">Total Job Entries</th>
                <td>
                    <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'staff_diary_entries';
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                    echo number_format($count);
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Total Images Uploaded</th>
                <td>
                    <?php
                    $table_images = $wpdb->prefix . 'staff_diary_images';
                    $image_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_images");
                    echo number_format($image_count);
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>
