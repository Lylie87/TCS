<?php
/**
 * General Settings Page
 *
 * @since      3.5.0
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

// Save general settings
if (isset($_POST['wp_staff_diary_save_settings'])) {
    check_admin_referer('wp_staff_diary_settings_nonce');

    update_option('wp_staff_diary_date_format', sanitize_text_field($_POST['date_format']));
    update_option('wp_staff_diary_time_format', sanitize_text_field($_POST['time_format']));
    update_option('wp_staff_diary_week_start', sanitize_text_field($_POST['week_start']));
    update_option('wp_staff_diary_default_status', sanitize_text_field($_POST['default_status']));

    // Job time options
    update_option('wp_staff_diary_job_time_type', sanitize_text_field($_POST['job_time_type']));
    update_option('wp_staff_diary_fitting_time_length', isset($_POST['fitting_time_length']) ? '1' : '0');

    // Currency settings
    update_option('wp_staff_diary_currency_symbol', sanitize_text_field($_POST['currency_symbol']));
    update_option('wp_staff_diary_currency_code', sanitize_text_field($_POST['currency_code']));
    update_option('wp_staff_diary_currency_position', sanitize_text_field($_POST['currency_position']));
    update_option('wp_staff_diary_decimal_separator', sanitize_text_field($_POST['decimal_separator']));
    update_option('wp_staff_diary_thousands_separator', sanitize_text_field($_POST['thousands_separator']));

    echo '<div class="notice notice-success is-dismissible"><p>General settings saved successfully!</p></div>';
}

// Get current settings
$date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
$time_format = get_option('wp_staff_diary_time_format', 'H:i');
$week_start = get_option('wp_staff_diary_week_start', 'monday');
$default_status = get_option('wp_staff_diary_default_status', 'pending');

// Job time options
$job_time_type = get_option('wp_staff_diary_job_time_type', 'ampm');
$fitting_time_length = get_option('wp_staff_diary_fitting_time_length', '0');

// Currency settings with WooCommerce fallback
$wc_active = class_exists('WooCommerce');
$wc_currency = $wc_active ? get_woocommerce_currency() : 'GBP';
$wc_symbol = $wc_active ? get_woocommerce_currency_symbol() : '£';
$wc_position = $wc_active ? get_option('woocommerce_currency_pos', 'left') : 'left';
$wc_decimal_sep = $wc_active ? wc_get_price_decimal_separator() : '.';
$wc_thousand_sep = $wc_active ? wc_get_price_thousand_separator() : ',';

$currency_symbol = get_option('wp_staff_diary_currency_symbol', $wc_symbol);
$currency_code = get_option('wp_staff_diary_currency_code', $wc_currency);
$currency_position = get_option('wp_staff_diary_currency_position', $wc_position);
$decimal_separator = get_option('wp_staff_diary_decimal_separator', $wc_decimal_sep);
$thousands_separator = get_option('wp_staff_diary_thousands_separator', $wc_thousand_sep);

// Statuses
$statuses = get_option('wp_staff_diary_statuses', array(
    'pending' => 'Pending',
    'in-progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
));
?>

<div class="wrap">
    <h1>General Settings</h1>
    <p>Configure date/time formats, currency settings, and general preferences.</p>

    <form method="post" action="">
        <?php wp_nonce_field('wp_staff_diary_settings_nonce'); ?>

        <!-- Date & Time Settings -->
        <div class="settings-section" style="margin-top: 30px;">
            <h2>Date & Time Settings</h2>

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
                </tbody>
            </table>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Job Settings -->
        <div class="settings-section">
            <h2>Job Settings</h2>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="default_status">Default Job Status</label>
                        </th>
                        <td>
                            <select name="default_status" id="default_status" class="regular-text">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($default_status, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">The default status for new job entries.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="job_time_type">Job Time Selection</label>
                        </th>
                        <td>
                            <select name="job_time_type" id="job_time_type" class="regular-text">
                                <option value="none" <?php selected($job_time_type, 'none'); ?>>No Time Selection</option>
                                <option value="ampm" <?php selected($job_time_type, 'ampm'); ?>>AM/PM Only</option>
                                <option value="time" <?php selected($job_time_type, 'time'); ?>>Specific Time</option>
                            </select>
                            <p class="description">Choose how users select job time: No time, AM/PM period only, or specific start time.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="fitting_time_length">Enable Fitting Time Length</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="fitting_time_length" id="fitting_time_length" value="1" <?php checked($fitting_time_length, '1'); ?>>
                                Allow users to specify job duration (e.g., 3 hours)
                            </label>
                            <p class="description">When enabled, users can allocate a specific time length to each job.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Currency Settings -->
        <div class="settings-section">
            <h2>Currency Settings</h2>
            <?php if ($wc_active): ?>
                <div class="notice notice-info inline" style="margin: 10px 0; padding: 10px;">
                    <p><strong>WooCommerce Detected:</strong> Default values are pulled from your WooCommerce settings. You can override them below.</p>
                </div>
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="currency_code">Currency Code</label>
                        </th>
                        <td>
                            <select name="currency_code" id="currency_code" class="regular-text">
                                <option value="GBP" <?php selected($currency_code, 'GBP'); ?>>GBP - British Pound</option>
                                <option value="USD" <?php selected($currency_code, 'USD'); ?>>USD - US Dollar</option>
                                <option value="EUR" <?php selected($currency_code, 'EUR'); ?>>EUR - Euro</option>
                                <option value="AUD" <?php selected($currency_code, 'AUD'); ?>>AUD - Australian Dollar</option>
                                <option value="CAD" <?php selected($currency_code, 'CAD'); ?>>CAD - Canadian Dollar</option>
                                <option value="NZD" <?php selected($currency_code, 'NZD'); ?>>NZD - New Zealand Dollar</option>
                                <option value="JPY" <?php selected($currency_code, 'JPY'); ?>>JPY - Japanese Yen</option>
                                <option value="CHF" <?php selected($currency_code, 'CHF'); ?>>CHF - Swiss Franc</option>
                                <option value="SEK" <?php selected($currency_code, 'SEK'); ?>>SEK - Swedish Krona</option>
                                <option value="NOK" <?php selected($currency_code, 'NOK'); ?>>NOK - Norwegian Krone</option>
                                <option value="DKK" <?php selected($currency_code, 'DKK'); ?>>DKK - Danish Krone</option>
                            </select>
                            <p class="description">Select your business currency.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="currency_symbol">Currency Symbol</label>
                        </th>
                        <td>
                            <input type="text" name="currency_symbol" id="currency_symbol" value="<?php echo esc_attr($currency_symbol); ?>" class="small-text">
                            <p class="description">The symbol to display for your currency (e.g., £, $, €).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="currency_position">Currency Position</label>
                        </th>
                        <td>
                            <select name="currency_position" id="currency_position" class="regular-text">
                                <option value="left" <?php selected($currency_position, 'left'); ?>>Left (£99.00)</option>
                                <option value="right" <?php selected($currency_position, 'right'); ?>>Right (99.00£)</option>
                                <option value="left_space" <?php selected($currency_position, 'left_space'); ?>>Left with space (£ 99.00)</option>
                                <option value="right_space" <?php selected($currency_position, 'right_space'); ?>>Right with space (99.00 £)</option>
                            </select>
                            <p class="description">Where to display the currency symbol relative to the amount.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="decimal_separator">Decimal Separator</label>
                        </th>
                        <td>
                            <input type="text" name="decimal_separator" id="decimal_separator" value="<?php echo esc_attr($decimal_separator); ?>" class="small-text" maxlength="1">
                            <p class="description">Character for decimal separator (usually . or ,).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="thousands_separator">Thousands Separator</label>
                        </th>
                        <td>
                            <input type="text" name="thousands_separator" id="thousands_separator" value="<?php echo esc_attr($thousands_separator); ?>" class="small-text" maxlength="1">
                            <p class="description">Character for thousands separator (usually , or . or leave blank).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Currency Preview</th>
                        <td>
                            <strong><?php echo WP_Staff_Diary_Currency_Helper::format(1234.56); ?></strong>
                            <p class="description">Example of how currency will be displayed with current settings.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="wp_staff_diary_save_settings" class="button button-primary" value="Save General Settings">
        </p>
    </form>
</div>
