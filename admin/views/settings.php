<?php
/**
 * Settings Page
 *
 * @since      2.0.0
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

// Run database migration
if (isset($_POST['wp_staff_diary_run_migration'])) {
    check_admin_referer('wp_staff_diary_migration_nonce');

    require_once WP_STAFF_DIARY_PATH . 'includes/class-upgrade.php';
    WP_Staff_Diary_Upgrade::force_upgrade();

    echo '<div class="notice notice-success is-dismissible"><p><strong>Database migration completed!</strong> All v2.0.0 tables have been created.</p></div>';
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

// Save company details
if (isset($_POST['wp_staff_diary_save_company'])) {
    check_admin_referer('wp_staff_diary_company_nonce');

    update_option('wp_staff_diary_company_name', sanitize_text_field($_POST['company_name']));
    update_option('wp_staff_diary_company_address', sanitize_textarea_field($_POST['company_address']));
    update_option('wp_staff_diary_company_phone', sanitize_text_field($_POST['company_phone']));
    update_option('wp_staff_diary_company_email', sanitize_email($_POST['company_email']));
    update_option('wp_staff_diary_company_vat_number', sanitize_text_field($_POST['company_vat_number']));
    update_option('wp_staff_diary_company_reg_number', sanitize_text_field($_POST['company_reg_number']));

    // Bank details - keep old field for backwards compatibility
    update_option('wp_staff_diary_company_bank_details', sanitize_textarea_field($_POST['company_bank_details']));

    // New structured bank details
    update_option('wp_staff_diary_bank_name', sanitize_text_field($_POST['bank_name']));
    update_option('wp_staff_diary_bank_account_name', sanitize_text_field($_POST['bank_account_name']));
    update_option('wp_staff_diary_bank_account_number', sanitize_text_field($_POST['bank_account_number']));
    update_option('wp_staff_diary_bank_sort_code', sanitize_text_field($_POST['bank_sort_code']));

    // Handle logo upload
    if (isset($_POST['company_logo'])) {
        update_option('wp_staff_diary_company_logo', sanitize_text_field($_POST['company_logo']));
    }

    echo '<div class="notice notice-success is-dismissible"><p>Company details saved successfully!</p></div>';
}

// Save order settings
if (isset($_POST['wp_staff_diary_save_order_settings'])) {
    check_admin_referer('wp_staff_diary_order_nonce');

    update_option('wp_staff_diary_order_start', sanitize_text_field($_POST['order_start']));
    update_option('wp_staff_diary_order_prefix', sanitize_text_field($_POST['order_prefix']));
    // Don't update current - it increments automatically

    echo '<div class="notice notice-success is-dismissible"><p>Order settings saved successfully!</p></div>';
}

// Save VAT settings
if (isset($_POST['wp_staff_diary_save_vat'])) {
    check_admin_referer('wp_staff_diary_vat_nonce');

    update_option('wp_staff_diary_vat_enabled', isset($_POST['vat_enabled']) ? '1' : '0');
    update_option('wp_staff_diary_vat_rate', sanitize_text_field($_POST['vat_rate']));

    echo '<div class="notice notice-success is-dismissible"><p>VAT settings saved successfully!</p></div>';
}

// Save payment reminder settings
if (isset($_POST['wp_staff_diary_save_reminders'])) {
    check_admin_referer('wp_staff_diary_reminders_nonce');

    update_option('wp_staff_diary_payment_reminders_enabled', isset($_POST['payment_reminders_enabled']) ? '1' : '0');
    update_option('wp_staff_diary_payment_reminder_1_days', sanitize_text_field($_POST['payment_reminder_1_days']));
    update_option('wp_staff_diary_payment_reminder_2_days', sanitize_text_field($_POST['payment_reminder_2_days']));
    update_option('wp_staff_diary_payment_reminder_3_days', sanitize_text_field($_POST['payment_reminder_3_days']));
    update_option('wp_staff_diary_payment_reminder_subject', sanitize_text_field($_POST['payment_reminder_subject']));
    update_option('wp_staff_diary_payment_reminder_message', sanitize_textarea_field($_POST['payment_reminder_message']));

    // Payment terms and policy
    update_option('wp_staff_diary_payment_terms_number', sanitize_text_field($_POST['payment_terms_number']));
    update_option('wp_staff_diary_payment_terms_unit', sanitize_text_field($_POST['payment_terms_unit']));
    update_option('wp_staff_diary_payment_policy', sanitize_text_field($_POST['payment_policy']));
    update_option('wp_staff_diary_overdue_notification_email', sanitize_email($_POST['overdue_notification_email']));

    echo '<div class="notice notice-success is-dismissible"><p>Payment settings saved successfully!</p></div>';
}

// Save terms and conditions
if (isset($_POST['wp_staff_diary_save_terms'])) {
    check_admin_referer('wp_staff_diary_terms_nonce');

    update_option('wp_staff_diary_terms_conditions', wp_kses_post($_POST['terms_conditions']));

    echo '<div class="notice notice-success is-dismissible"><p>Terms and conditions saved successfully!</p></div>';
}

// Save GitHub settings
if (isset($_POST['wp_staff_diary_save_github'])) {
    check_admin_referer('wp_staff_diary_github_nonce');

    $github_token = sanitize_text_field($_POST['github_token']);

    // Only update if token provided or if clearing
    if (!empty($github_token) || isset($_POST['clear_token'])) {
        update_option('wp_staff_diary_github_token', $github_token);

        // Clear update cache to force recheck with new token
        delete_site_transient('update_plugins');

        echo '<div class="notice notice-success is-dismissible"><p>GitHub settings saved successfully! Update check cache has been cleared.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Please enter a GitHub token or check "Clear Token" to remove it.</p></div>';
    }
}

// Save quotation settings
if (isset($_POST['wp_staff_diary_save_quotation'])) {
    check_admin_referer('wp_staff_diary_quotation_nonce');

    update_option('wp_staff_diary_quote_enable_auto_discount', isset($_POST['quote_enable_auto_discount']) ? '1' : '0');
    update_option('wp_staff_diary_quote_auto_discount_days', absint($_POST['quote_auto_discount_days']));
    update_option('wp_staff_diary_quote_auto_discount_type', sanitize_text_field($_POST['quote_auto_discount_type']));
    update_option('wp_staff_diary_quote_auto_discount_value', floatval($_POST['quote_auto_discount_value']));
    update_option('wp_staff_diary_quote_validity_days', absint($_POST['quote_validity_days']));
    update_option('wp_staff_diary_quote_default_fitting_cost', floatval($_POST['quote_default_fitting_cost']));
    update_option('wp_staff_diary_quote_email_template', wp_kses_post($_POST['quote_email_template']));

    echo '<div class="notice notice-success is-dismissible"><p>Quotation settings saved successfully!</p></div>';
}

// Save communications settings
if (isset($_POST['wp_staff_diary_save_communications'])) {
    check_admin_referer('wp_staff_diary_communications_nonce');

    // SMS Settings
    update_option('wp_staff_diary_sms_enabled', isset($_POST['sms_enabled']) ? '1' : '0');
    update_option('wp_staff_diary_sms_test_mode', isset($_POST['sms_test_mode']) ? '1' : '0');
    update_option('wp_staff_diary_twilio_account_sid', sanitize_text_field($_POST['twilio_account_sid']));
    update_option('wp_staff_diary_twilio_auth_token', sanitize_text_field($_POST['twilio_auth_token']));
    update_option('wp_staff_diary_twilio_phone_number', sanitize_text_field($_POST['twilio_phone_number']));
    update_option('wp_staff_diary_sms_cost_per_message', floatval($_POST['sms_cost_per_message']));

    echo '<div class="notice notice-success is-dismissible"><p>Communications settings saved successfully!</p></div>';
}

// Get current settings
$date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
$time_format = get_option('wp_staff_diary_time_format', 'H:i');
$week_start = get_option('wp_staff_diary_week_start', 'monday');
$default_status = get_option('wp_staff_diary_default_status', 'pending');

// Job time options
$job_time_type = get_option('wp_staff_diary_job_time_type', 'ampm'); // 'ampm' or 'time' or 'none'
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

// Company details
$company_name = get_option('wp_staff_diary_company_name', '');
$company_address = get_option('wp_staff_diary_company_address', '');
$company_phone = get_option('wp_staff_diary_company_phone', '');
$company_email = get_option('wp_staff_diary_company_email', '');
$company_vat_number = get_option('wp_staff_diary_company_vat_number', '');
$company_reg_number = get_option('wp_staff_diary_company_reg_number', '');
$company_bank_details = get_option('wp_staff_diary_company_bank_details', '');
$company_logo = get_option('wp_staff_diary_company_logo', '');

// Bank details (structured)
$bank_name = get_option('wp_staff_diary_bank_name', '');
$bank_account_name = get_option('wp_staff_diary_bank_account_name', '');
$bank_account_number = get_option('wp_staff_diary_bank_account_number', '');
$bank_sort_code = get_option('wp_staff_diary_bank_sort_code', '');

// Order settings
$order_start = get_option('wp_staff_diary_order_start', '01100');
$order_prefix = get_option('wp_staff_diary_order_prefix', '');
$order_current = get_option('wp_staff_diary_order_current', '01100');

// VAT settings
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');

// Payment reminder settings
$payment_reminders_enabled = get_option('wp_staff_diary_payment_reminders_enabled', '1');
$payment_reminder_1_days = get_option('wp_staff_diary_payment_reminder_1_days', '7');
$payment_reminder_2_days = get_option('wp_staff_diary_payment_reminder_2_days', '14');
$payment_reminder_3_days = get_option('wp_staff_diary_payment_reminder_3_days', '21');
$payment_reminder_subject = get_option('wp_staff_diary_payment_reminder_subject', 'Payment Reminder - Invoice {order_number}');
$payment_reminder_message = get_option('wp_staff_diary_payment_reminder_message', "Dear {customer_name},\n\nThis is a friendly reminder that payment is still outstanding for the following job:\n\nInvoice Number: {order_number}\nJob Date: {job_date}\nTotal Amount: {total_amount}\nAmount Outstanding: {balance}\n\nIf you have already made this payment, please disregard this reminder.\n\nThank you for your business.");

// Payment terms and policy settings
$payment_terms_number = get_option('wp_staff_diary_payment_terms_number', '30');
$payment_terms_unit = get_option('wp_staff_diary_payment_terms_unit', 'days');
$payment_policy = get_option('wp_staff_diary_payment_policy', 'both');
$overdue_notification_email = get_option('wp_staff_diary_overdue_notification_email', get_option('admin_email'));

// Terms and conditions
$terms_conditions = get_option('wp_staff_diary_terms_conditions', '');

// Statuses
$statuses = get_option('wp_staff_diary_statuses', array(
    'pending' => 'Pending',
    'in-progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
));

// Payment methods
$payment_methods = get_option('wp_staff_diary_payment_methods', array(
    'cash' => 'Cash',
    'bank-transfer' => 'Bank Transfer',
    'card-payment' => 'Card Payment'
));

// Accessories
$db = new WP_Staff_Diary_Database();
$accessories = $db->get_all_accessories();

// Fitters
$fitters = get_option('wp_staff_diary_fitters', array());

// GitHub settings
$github_token = get_option('wp_staff_diary_github_token', '');

// Quotation settings
$quote_enable_auto_discount = get_option('wp_staff_diary_quote_enable_auto_discount', '0');
$quote_auto_discount_days = get_option('wp_staff_diary_quote_auto_discount_days', '7');
$quote_auto_discount_type = get_option('wp_staff_diary_quote_auto_discount_type', 'percentage');
$quote_auto_discount_value = get_option('wp_staff_diary_quote_auto_discount_value', '5');
$quote_validity_days = get_option('wp_staff_diary_quote_validity_days', '30');
$quote_default_fitting_cost = get_option('wp_staff_diary_quote_default_fitting_cost', '15');
$quote_email_template = get_option('wp_staff_diary_quote_email_template', '');

// If no template set, use default
if (empty($quote_email_template)) {
    $quote_email_template = "Dear {customer_name},

Thank you for your interest in our services. We provided you with a quote on {quote_date} for {product_description}.

We're pleased to offer you a special {discount_type_label} discount on this quote:

Original Amount: {original_amount}
Discount: {discount_display}
Final Amount: {final_amount}

This offer is valid until {expiry_date}. To accept this quote and secure your booking, please click the link below:

{quote_link}

If you have any questions, please don't hesitate to contact us.

Best regards,
{company_name}";
}

// Communications settings (SMS & Email Templates)
$sms_enabled = get_option('wp_staff_diary_sms_enabled', '0');
$sms_test_mode = get_option('wp_staff_diary_sms_test_mode', '1');
$twilio_account_sid = get_option('wp_staff_diary_twilio_account_sid', '');
$twilio_auth_token = get_option('wp_staff_diary_twilio_auth_token', '');
$twilio_phone_number = get_option('wp_staff_diary_twilio_phone_number', '');
$sms_cost_per_message = get_option('wp_staff_diary_sms_cost_per_message', '0.04');

// Get email templates
$email_templates = $db->get_all_email_templates();
?>

<div class="wrap wp-staff-diary-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general">General</a>
        <a href="#company" class="nav-tab" data-tab="company">Company Details</a>
        <a href="#orders" class="nav-tab" data-tab="orders">Order Settings</a>
        <a href="#vat" class="nav-tab" data-tab="vat">VAT</a>
        <a href="#payment-reminders" class="nav-tab" data-tab="payment-reminders">Payment Reminders</a>
        <a href="#communications" class="nav-tab" data-tab="communications">Communications</a>
        <a href="#statuses" class="nav-tab" data-tab="statuses">Job Statuses</a>
        <a href="#payment-methods" class="nav-tab" data-tab="payment-methods">Payment Methods</a>
        <a href="#accessories" class="nav-tab" data-tab="accessories">Accessories</a>
        <a href="#fitters" class="nav-tab" data-tab="fitters">Fitters</a>
        <a href="#quotation" class="nav-tab" data-tab="quotation">Quotation Settings</a>
        <a href="#github" class="nav-tab" data-tab="github">GitHub Updates</a>
        <a href="#terms" class="nav-tab" data-tab="terms">Terms & Conditions</a>
        <a href="#info" class="nav-tab" data-tab="info">Plugin Info</a>
    </nav>

    <!-- General Settings Tab -->
    <div id="general-tab" class="settings-tab">
        <h2>General Settings</h2>
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

            <h3 style="margin-top: 30px;">Currency Settings</h3>
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
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_settings" class="button button-primary" value="Save General Settings">
            </p>
        </form>
    </div>

    <!-- Company Details Tab -->
    <div id="company-tab" class="settings-tab" style="display:none;">
        <h2>Company Details</h2>
        <p>These details will appear on job sheets and invoices.</p>

        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('wp_staff_diary_company_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="company_name">Company Name</label>
                        </th>
                        <td>
                            <input type="text" name="company_name" id="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_address">Company Address</label>
                        </th>
                        <td>
                            <textarea name="company_address" id="company_address" rows="4" class="large-text"><?php echo esc_textarea($company_address); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_phone">Phone Number</label>
                        </th>
                        <td>
                            <input type="text" name="company_phone" id="company_phone" value="<?php echo esc_attr($company_phone); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_email">Email Address</label>
                        </th>
                        <td>
                            <input type="email" name="company_email" id="company_email" value="<?php echo esc_attr($company_email); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_vat_number">VAT Number</label>
                        </th>
                        <td>
                            <input type="text" name="company_vat_number" id="company_vat_number" value="<?php echo esc_attr($company_vat_number); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_reg_number">Company Registration Number</label>
                        </th>
                        <td>
                            <input type="text" name="company_reg_number" id="company_reg_number" value="<?php echo esc_attr($company_reg_number); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label>Bank Details</label>
                        </th>
                        <td>
                            <div style="margin-bottom: 10px;">
                                <label for="bank_name" style="display: block; margin-bottom: 5px;">Bank Name</label>
                                <input type="text" name="bank_name" id="bank_name" value="<?php echo esc_attr($bank_name); ?>" class="regular-text" placeholder="e.g., Barclays">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label for="bank_account_name" style="display: block; margin-bottom: 5px;">Account Name</label>
                                <input type="text" name="bank_account_name" id="bank_account_name" value="<?php echo esc_attr($bank_account_name); ?>" class="regular-text" placeholder="e.g., Your Company Ltd">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label for="bank_sort_code" style="display: block; margin-bottom: 5px;">Sort Code</label>
                                <input type="text" name="bank_sort_code" id="bank_sort_code" value="<?php echo esc_attr($bank_sort_code); ?>" class="regular-text" placeholder="e.g., 12-34-56">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label for="bank_account_number" style="display: block; margin-bottom: 5px;">Account Number</label>
                                <input type="text" name="bank_account_number" id="bank_account_number" value="<?php echo esc_attr($bank_account_number); ?>" class="regular-text" placeholder="e.g., 12345678">
                            </div>
                            <input type="hidden" name="company_bank_details" value="<?php echo esc_attr($company_bank_details); ?>">
                            <p class="description">Bank account details will be included in payment reminders and invoices.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_logo">Company Logo</label>
                        </th>
                        <td>
                            <input type="hidden" name="company_logo" id="company_logo" value="<?php echo esc_attr($company_logo); ?>">
                            <button type="button" class="button" id="upload_logo_button">Upload Logo</button>
                            <button type="button" class="button" id="remove_logo_button" style="<?php echo empty($company_logo) ? 'display:none;' : ''; ?>">Remove Logo</button>
                            <div id="logo_preview" style="margin-top: 10px;">
                                <?php if ($company_logo): ?>
                                    <img src="<?php echo esc_url($company_logo); ?>" style="max-width: 200px; height: auto;">
                                <?php endif; ?>
                            </div>
                            <p class="description">Logo will appear on PDF job sheets and invoices.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_company" class="button button-primary" value="Save Company Details">
            </p>
        </form>
    </div>

    <!-- Order Settings Tab -->
    <div id="orders-tab" class="settings-tab" style="display:none;">
        <h2>Order Number Settings</h2>
        <p>Configure how order numbers are generated for jobs.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_order_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="order_prefix">Order Number Prefix</label>
                        </th>
                        <td>
                            <input type="text" name="order_prefix" id="order_prefix" value="<?php echo esc_attr($order_prefix); ?>" class="small-text">
                            <p class="description">Optional prefix for order numbers (e.g., "ORD-", "JOB-"). Leave blank for none.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="order_start">Starting Order Number</label>
                        </th>
                        <td>
                            <input type="text" name="order_start" id="order_start" value="<?php echo esc_attr($order_start); ?>" class="small-text">
                            <p class="description">The starting number for new orders (e.g., "01100", "1000"). Format will be maintained.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Current Order Number</th>
                        <td>
                            <strong><?php echo esc_html($order_prefix . $order_current); ?></strong>
                            <p class="description">This is the current order number. It increments automatically with each new job.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Preview</th>
                        <td>
                            <p>Next order number will be: <strong id="order-preview"><?php echo esc_html($order_prefix . str_pad((int)$order_current + 1, strlen($order_current), '0', STR_PAD_LEFT)); ?></strong></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_order_settings" class="button button-primary" value="Save Order Settings">
            </p>
        </form>
    </div>

    <!-- VAT Settings Tab -->
    <div id="vat-tab" class="settings-tab" style="display:none;">
        <h2>VAT Settings</h2>
        <p>Configure VAT (Value Added Tax) for invoices.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_vat_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">Enable VAT</th>
                        <td>
                            <label>
                                <input type="checkbox" name="vat_enabled" id="vat_enabled" value="1" <?php checked($vat_enabled, '1'); ?>>
                                Add VAT to invoices
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="vat_rate">VAT Rate (%)</label>
                        </th>
                        <td>
                            <input type="number" name="vat_rate" id="vat_rate" value="<?php echo esc_attr($vat_rate); ?>" class="small-text" step="0.01" min="0" max="100">
                            <span>%</span>
                            <p class="description">Standard UK VAT rate is 20%. Adjust as needed for your region.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_vat" class="button button-primary" value="Save VAT Settings">
            </p>
        </form>
    </div>

    <!-- Payment Reminders Tab -->
    <div id="payment-reminders-tab" class="settings-tab" style="display:none;">
        <h2>Payment Reminder Settings</h2>
        <p>Configure automatic payment reminders for jobs with outstanding balances.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_reminders_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">Enable Payment Reminders</th>
                        <td>
                            <label>
                                <input type="checkbox" name="payment_reminders_enabled" id="payment_reminders_enabled" value="1" <?php checked($payment_reminders_enabled, '1'); ?>>
                                Automatically schedule payment reminders for jobs with outstanding balances
                            </label>
                            <p class="description">When enabled, reminders will be automatically scheduled based on the job date.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_reminder_1_days">First Reminder (Days)</label>
                        </th>
                        <td>
                            <input type="number" name="payment_reminder_1_days" id="payment_reminder_1_days" value="<?php echo esc_attr($payment_reminder_1_days); ?>" class="small-text" min="0" step="1">
                            <span>days after job date</span>
                            <p class="description">Set to 0 to disable first reminder.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_reminder_2_days">Second Reminder (Days)</label>
                        </th>
                        <td>
                            <input type="number" name="payment_reminder_2_days" id="payment_reminder_2_days" value="<?php echo esc_attr($payment_reminder_2_days); ?>" class="small-text" min="0" step="1">
                            <span>days after job date</span>
                            <p class="description">Set to 0 to disable second reminder.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_reminder_3_days">Final Reminder (Days)</label>
                        </th>
                        <td>
                            <input type="number" name="payment_reminder_3_days" id="payment_reminder_3_days" value="<?php echo esc_attr($payment_reminder_3_days); ?>" class="small-text" min="0" step="1">
                            <span>days after job date</span>
                            <p class="description">Set to 0 to disable final reminder.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_reminder_subject">Email Subject</label>
                        </th>
                        <td>
                            <input type="text" name="payment_reminder_subject" id="payment_reminder_subject" value="<?php echo esc_attr($payment_reminder_subject); ?>" class="regular-text">
                            <p class="description">Available placeholders: {order_number}, {customer_name}</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_reminder_message">Email Message Template</label>
                        </th>
                        <td>
                            <textarea name="payment_reminder_message" id="payment_reminder_message" rows="10" class="large-text"><?php echo esc_textarea($payment_reminder_message); ?></textarea>
                            <p class="description">Available placeholders: {customer_name}, {order_number}, {job_date}, {total_amount}, {balance}</p>
                            <p class="description">Company details and signature will be automatically added to the email.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 style="margin-top: 40px;">Payment Terms & Policy</h3>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="payment_terms_number">Payment Terms</label>
                        </th>
                        <td>
                            <input type="number" name="payment_terms_number" id="payment_terms_number" value="<?php echo esc_attr($payment_terms_number); ?>" class="small-text" min="1" step="1">
                            <select name="payment_terms_unit" id="payment_terms_unit">
                                <option value="days" <?php selected($payment_terms_unit, 'days'); ?>>Days</option>
                                <option value="weeks" <?php selected($payment_terms_unit, 'weeks'); ?>>Weeks</option>
                                <option value="months" <?php selected($payment_terms_unit, 'months'); ?>>Months</option>
                                <option value="years" <?php selected($payment_terms_unit, 'years'); ?>>Years</option>
                            </select>
                            <p class="description">Jobs overdue by this period will trigger notifications and appear on the overdue dashboard.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_policy">Payment Before Work Policy</label>
                        </th>
                        <td>
                            <select name="payment_policy" id="payment_policy" class="regular-text">
                                <option value="both" <?php selected($payment_policy, 'both'); ?>>Both Residential & Commercial can work before full payment</option>
                                <option value="commercial" <?php selected($payment_policy, 'commercial'); ?>>Only Commercial jobs can work before full payment</option>
                                <option value="residential" <?php selected($payment_policy, 'residential'); ?>>Only Residential jobs can work before full payment</option>
                                <option value="none" <?php selected($payment_policy, 'none'); ?>>No jobs can work before full payment</option>
                            </select>
                            <p class="description">Control which types of jobs can proceed to completion before receiving full payment.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="overdue_notification_email">Overdue Payment Notification Email</label>
                        </th>
                        <td>
                            <input type="email" name="overdue_notification_email" id="overdue_notification_email" value="<?php echo esc_attr($overdue_notification_email); ?>" class="regular-text">
                            <p class="description">Email address to receive notifications when jobs become overdue. Defaults to WordPress admin email.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_reminders" class="button button-primary" value="Save Payment Settings">
            </p>
        </form>

        <div style="margin-top: 30px; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
            <h3 style="margin-top: 0;">How Payment Reminders Work</h3>
            <ul style="list-style: disc; padding-left: 20px;">
                <li><strong>Automatic Scheduling:</strong> When a job is created or converted from a quote, payment reminders are automatically scheduled based on the settings above.</li>
                <li><strong>Smart Sending:</strong> Reminders are only sent if there's an outstanding balance. If payment is received, remaining reminders are automatically cancelled.</li>
                <li><strong>Manual Reminders:</strong> You can also send manual payment reminders from the job details page at any time.</li>
                <li><strong>Email Requirements:</strong> Customer must have a valid email address on file to receive reminders.</li>
                <li><strong>Reminder History:</strong> All sent reminders are logged and can be viewed in the job details.</li>
            </ul>
        </div>
    </div>

    <!-- Communications Tab (Email Templates & SMS) -->
    <div id="communications-tab" class="settings-tab" style="display:none;">
        <h2>Communications Settings</h2>
        <p>Manage email templates and SMS notifications for customer communication.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_communications_nonce'); ?>

            <h3>Email Templates</h3>
            <p>Customize email templates used for customer notifications. Use the Email Template Editor below to create and edit templates.</p>

            <div class="email-templates-list" style="margin-bottom: 30px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($email_templates)) : ?>
                            <?php foreach ($email_templates as $template) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($template->template_name); ?></strong></td>
                                    <td><code><?php echo esc_html($template->template_slug); ?></code></td>
                                    <td>
                                        <?php if ($template->is_active) : ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Active
                                        <?php else : ?>
                                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> Inactive
                                        <?php endif; ?>
                                        <?php if ($template->is_default) : ?>
                                            <span class="description" style="margin-left: 10px;">(Default)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-email-templates&action=edit&id=' . $template->id); ?>" class="button button-small">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4"><em>No email templates found. Default templates will be created on next activation.</em></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p style="margin-top: 10px;">
                    <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-email-templates&action=add'); ?>" class="button button-secondary">Add New Template</a>
                </p>
            </div>

            <h3 style="margin-top: 40px;">SMS Notifications (Twilio)</h3>
            <p>Configure Twilio integration for sending SMS notifications to customers.</p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">Enable SMS Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="sms_enabled" id="sms_enabled" value="1" <?php checked($sms_enabled, '1'); ?>>
                                Enable SMS notifications via Twilio
                            </label>
                            <p class="description">When enabled, you can send SMS notifications to customers who have opted in.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Test Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="sms_test_mode" id="sms_test_mode" value="1" <?php checked($sms_test_mode, '1'); ?>>
                                Enable test mode (no SMS will actually be sent)
                            </label>
                            <p class="description"><strong>Recommended:</strong> Keep this enabled until you're ready to send real SMS messages.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="twilio_account_sid">Twilio Account SID</label>
                        </th>
                        <td>
                            <input type="text" name="twilio_account_sid" id="twilio_account_sid" value="<?php echo esc_attr($twilio_account_sid); ?>" class="regular-text" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <p class="description">Your Twilio Account SID from the <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a>.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="twilio_auth_token">Twilio Auth Token</label>
                        </th>
                        <td>
                            <input type="password" name="twilio_auth_token" id="twilio_auth_token" value="<?php echo esc_attr($twilio_auth_token); ?>" class="regular-text" placeholder="********************************">
                            <p class="description">Your Twilio Auth Token from the <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a>. Keep this secure!</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="twilio_phone_number">Twilio Phone Number</label>
                        </th>
                        <td>
                            <input type="text" name="twilio_phone_number" id="twilio_phone_number" value="<?php echo esc_attr($twilio_phone_number); ?>" class="regular-text" placeholder="+441234567890">
                            <p class="description">Your Twilio phone number in E.164 format (e.g., +441234567890).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="sms_cost_per_message">Cost Per Message (£)</label>
                        </th>
                        <td>
                            <input type="number" name="sms_cost_per_message" id="sms_cost_per_message" value="<?php echo esc_attr($sms_cost_per_message); ?>" class="small-text" step="0.0001" min="0">
                            <p class="description">Estimated cost per SMS for tracking purposes. Default: £0.04</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_communications" class="button button-primary" value="Save Communications Settings">
            </p>
        </form>

        <div class="wp-staff-diary-info-box" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-top: 30px;">
            <h3 style="margin-top: 0;">How SMS Notifications Work</h3>
            <ul style="list-style: disc; padding-left: 20px;">
                <li><strong>Customer Opt-In:</strong> Customers must opt-in to receive SMS notifications. This is managed in the customer details.</li>
                <li><strong>Test Mode:</strong> When enabled, SMS messages are logged but not actually sent. Use this to test your workflows safely.</li>
                <li><strong>Twilio Account:</strong> You need a Twilio account with an active phone number. Sign up at <a href="https://www.twilio.com" target="_blank">twilio.com</a>.</li>
                <li><strong>Cost Tracking:</strong> All SMS messages are logged with estimated costs for your records.</li>
                <li><strong>Available Variables:</strong> Use {{customer_name}}, {{job_number}}, {{balance_due}}, {{company_name}}, and other variables in your SMS templates.</li>
            </ul>
        </div>
    </div>

    <!-- Job Statuses Tab -->
    <div id="statuses-tab" class="settings-tab" style="display:none;">
        <h2>Job Statuses</h2>
        <p>Manage available job statuses. Default statuses (Pending, In Progress, Completed, Cancelled) cannot be removed.</p>

        <div id="status-management" class="wp-staff-diary-management-section">
            <div class="status-list">
                <?php
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
    </div>

    <!-- Payment Methods Tab -->
    <div id="payment-methods-tab" class="settings-tab" style="display:none;">
        <h2>Payment Methods</h2>
        <p>Manage available payment methods for recording payments.</p>

        <div id="payment-methods-management" class="wp-staff-diary-management-section">
            <div class="payment-methods-list">
                <?php
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
    </div>

    <!-- Accessories Tab -->
    <div id="accessories-tab" class="settings-tab" style="display:none;">
        <h2>Accessories</h2>
        <p>Manage accessories that can be added to jobs. Set pricing for each accessory.</p>

        <div id="accessories-management" class="wp-staff-diary-management-section">
            <table class="wp-list-table widefat fixed striped" id="accessories-table">
                <thead>
                    <tr>
                        <th>Accessory Name</th>
                        <th style="width: 150px;">Price (£)</th>
                        <th style="width: 100px;">Active</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accessories as $accessory): ?>
                        <tr data-accessory-id="<?php echo esc_attr($accessory->id); ?>">
                            <td>
                                <span class="accessory-name-display"><?php echo esc_html($accessory->accessory_name); ?></span>
                                <input type="text" class="accessory-name-edit regular-text" value="<?php echo esc_attr($accessory->accessory_name); ?>" style="display:none;">
                            </td>
                            <td>
                                <span class="accessory-price-display">£<?php echo number_format($accessory->price, 2); ?></span>
                                <input type="number" class="accessory-price-edit small-text" value="<?php echo esc_attr($accessory->price); ?>" step="0.01" min="0" style="display:none;">
                            </td>
                            <td>
                                <span class="accessory-active-display"><?php echo $accessory->is_active ? 'Yes' : 'No'; ?></span>
                                <input type="checkbox" class="accessory-active-edit" <?php checked($accessory->is_active, 1); ?> style="display:none;">
                            </td>
                            <td>
                                <button type="button" class="button button-small edit-accessory" data-id="<?php echo esc_attr($accessory->id); ?>">Edit</button>
                                <button type="button" class="button button-small save-accessory" data-id="<?php echo esc_attr($accessory->id); ?>" style="display:none;">Save</button>
                                <button type="button" class="button button-small cancel-accessory-edit" style="display:none;">Cancel</button>
                                <button type="button" class="button button-small button-link-delete delete-accessory" data-id="<?php echo esc_attr($accessory->id); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="add-accessory-form" style="margin-top: 20px;">
                <h3>Add New Accessory</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="new-accessory-name">Accessory Name:</label></th>
                        <td><input type="text" id="new-accessory-name" class="regular-text" placeholder="e.g., Grippers, Beading"></td>
                    </tr>
                    <tr>
                        <th><label for="new-accessory-price">Price (£):</label></th>
                        <td><input type="number" id="new-accessory-price" class="small-text" step="0.01" min="0" value="0.00"></td>
                    </tr>
                </table>
                <button type="button" id="add-accessory-btn" class="button button-secondary">Add Accessory</button>
            </div>
        </div>
    </div>

    <!-- Fitters Tab -->
    <div id="fitters-tab" class="settings-tab" style="display:none;">
        <h2>Fitters</h2>
        <p>Manage your team of fitters. Assign colors to each fitter for easy identification in the calendar view.</p>

        <div id="fitters-management" class="wp-staff-diary-management-section">
            <table class="wp-list-table widefat fixed striped" id="fitters-table">
                <thead>
                    <tr>
                        <th>Fitter Name</th>
                        <th style="width: 150px;">Color</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fitters)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #666;">No fitters added yet. Add your first fitter below.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fitters as $index => $fitter): ?>
                            <tr data-fitter-index="<?php echo esc_attr($index); ?>">
                                <td><?php echo esc_html($fitter['name']); ?></td>
                                <td>
                                    <span class="color-preview" style="display: inline-block; width: 30px; height: 30px; background-color: <?php echo esc_attr($fitter['color']); ?>; border: 1px solid #ddd; border-radius: 3px; vertical-align: middle;"></span>
                                    <span style="margin-left: 10px;"><?php echo esc_html($fitter['color']); ?></span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small button-link-delete delete-fitter" data-index="<?php echo esc_attr($index); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="add-fitter-form" style="margin-top: 20px;">
                <h3>Add New Fitter</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="new-fitter-name">Fitter Name:</label></th>
                        <td><input type="text" id="new-fitter-name" class="regular-text" placeholder="e.g., John Smith"></td>
                    </tr>
                    <tr>
                        <th><label for="new-fitter-color">Color:</label></th>
                        <td>
                            <input type="color" id="new-fitter-color" value="#3498db">
                            <p class="description">Choose a color to identify this fitter in the calendar view.</p>
                        </td>
                    </tr>
                </table>
                <button type="button" id="add-fitter-btn" class="button button-secondary">Add Fitter</button>
            </div>

            <!-- Link to Holidays Page -->
            <div style="margin-top: 40px; padding: 20px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #1976d2;">
                    <span class="dashicons dashicons-calendar-alt" style="vertical-align: middle;"></span>
                    Fitter Availability & Holidays
                </h3>
                <p style="margin-bottom: 15px;">Manage fitter holidays, sick leave, and unavailable periods from the dedicated Holidays page.</p>
                <a href="?page=wp-staff-diary-holidays" class="button button-primary">
                    <span class="dashicons dashicons-palmtree"></span> Go to Holidays Page
                </a>
            </div>
        </div>
    </div>

    <!-- Quotation Settings Tab -->
    <div id="quotation-tab" class="settings-tab" style="display:none;">
        <h2>Quotation & Discount Settings</h2>
        <p>Configure automatic discount offers for outstanding quotes and customize the email template.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_quotation_nonce'); ?>

            <h3>Automatic Discount Offers</h3>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="quote_enable_auto_discount">Enable Auto Discount</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="quote_enable_auto_discount" id="quote_enable_auto_discount" value="1" <?php checked($quote_enable_auto_discount, '1'); ?>>
                                Automatically send discount offers for outstanding quotes
                            </label>
                            <p class="description">When enabled, the system will automatically send discount offers to customers after a specified number of days.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="quote_auto_discount_days">Send Discount After</label>
                        </th>
                        <td>
                            <input type="number" name="quote_auto_discount_days" id="quote_auto_discount_days" value="<?php echo esc_attr($quote_auto_discount_days); ?>" min="1" max="365" class="small-text"> days
                            <p class="description">Number of days to wait after quote date before sending discount offer.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="quote_auto_discount_type">Default Discount Type</label>
                        </th>
                        <td>
                            <select name="quote_auto_discount_type" id="quote_auto_discount_type" class="regular-text">
                                <option value="percentage" <?php selected($quote_auto_discount_type, 'percentage'); ?>>Percentage (%)</option>
                                <option value="fixed" <?php selected($quote_auto_discount_type, 'fixed'); ?>>Fixed Amount (<?php echo WP_Staff_Diary_Currency_Helper::get_symbol(); ?>)</option>
                            </select>
                            <p class="description">Choose whether automatic discounts are a percentage or fixed amount.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="quote_auto_discount_value">Default Discount Value</label>
                        </th>
                        <td>
                            <input type="number" name="quote_auto_discount_value" id="quote_auto_discount_value" value="<?php echo esc_attr($quote_auto_discount_value); ?>" step="0.01" min="0" class="small-text">
                            <p class="description">The default discount amount (e.g., 5 for 5% or 50 for <?php echo WP_Staff_Diary_Currency_Helper::get_symbol(); ?>50).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="quote_validity_days">Quote Validity Period</label>
                        </th>
                        <td>
                            <input type="number" name="quote_validity_days" id="quote_validity_days" value="<?php echo esc_attr($quote_validity_days); ?>" min="1" max="365" class="small-text"> days
                            <p class="description">How long quotes remain valid after sending (used in discount emails).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="quote_default_fitting_cost">Default Fitting Cost per m²</label>
                        </th>
                        <td>
                            <?php echo WP_Staff_Diary_Currency_Helper::get_symbol(); ?><input type="number" name="quote_default_fitting_cost" id="quote_default_fitting_cost" value="<?php echo esc_attr($quote_default_fitting_cost); ?>" step="0.01" min="0" class="small-text">
                            <p class="description">Default cost per square metre for fitting (used for automatic calculation in quotes). You can override this manually on individual quotes.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 style="margin-top: 30px;">Discount Email Template</h3>
            <p>Customize the email template sent to customers with discount offers. You can use the following merge tags:</p>
            <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2271b1;">
                <strong>Available Merge Tags:</strong><br>
                <code>{customer_name}</code> - Customer's name<br>
                <code>{quote_date}</code> - Date the quote was created<br>
                <code>{product_description}</code> - Product/service description<br>
                <code>{order_number}</code> - Quote/order number<br>
                <code>{original_amount}</code> - Original quote amount with currency<br>
                <code>{discount_display}</code> - Discount amount (e.g., "5%" or "£50.00")<br>
                <code>{discount_type_label}</code> - "percentage" or "fixed price"<br>
                <code>{final_amount}</code> - Final amount after discount with currency<br>
                <code>{expiry_date}</code> - Date when the discount offer expires<br>
                <code>{quote_link}</code> - Link for customer to accept the quote<br>
                <code>{company_name}</code> - Your company name
            </div>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="quote_email_template">Email Template</label>
                        </th>
                        <td>
                            <textarea name="quote_email_template" id="quote_email_template" rows="15" class="large-text code"><?php echo esc_textarea($quote_email_template); ?></textarea>
                            <p class="description">The email template sent to customers. HTML is not supported - use plain text.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_quotation" class="button button-primary" value="Save Quotation Settings">
            </p>
        </form>
    </div>

    <!-- GitHub Updates Tab -->
    <div id="github-tab" class="settings-tab" style="display:none;">
        <h2>GitHub Auto-Updates</h2>
        <p>Configure GitHub authentication to enable automatic plugin updates from your private repository.</p>

        <div class="notice notice-info inline" style="margin: 20px 0; padding: 12px;">
            <h3 style="margin-top: 0;">Why do I need this?</h3>
            <p>Your plugin repository is <strong>private</strong>, which means WordPress cannot check for updates without authentication. By providing a GitHub Personal Access Token, the plugin can securely access your private repository to check for new releases.</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_github_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="github_token">GitHub Personal Access Token</label>
                        </th>
                        <td>
                            <input type="password" name="github_token" id="github_token" value="<?php echo esc_attr($github_token); ?>" class="large-text" placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <button type="button" id="toggle_token_visibility" class="button button-small" style="margin-left: 10px;">Show/Hide</button>
                            <p class="description">
                                Enter your GitHub Personal Access Token to enable auto-updates from the private repository.<br>
                                <?php if (!empty($github_token)): ?>
                                    <span style="color: green;">✓ Token is currently set</span>
                                <?php else: ?>
                                    <span style="color: orange;">⚠ No token configured - auto-updates will not work</span>
                                <?php endif; ?>
                            </p>
                            <label style="margin-top: 10px; display: block;">
                                <input type="checkbox" name="clear_token" value="1">
                                Clear token (remove authentication)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Token Status</th>
                        <td>
                            <?php if (!empty($github_token)): ?>
                                <span style="color: green; font-weight: bold;">✓ Configured</span> - Auto-updates are enabled
                            <?php else: ?>
                                <span style="color: red; font-weight: bold;">✗ Not configured</span> - Auto-updates are disabled
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_github" class="button button-primary" value="Save GitHub Settings">
            </p>
        </form>

        <hr style="margin: 40px 0;">

        <h2>How to Create a GitHub Personal Access Token</h2>
        <div class="instructions-box" style="background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
            <ol style="line-height: 2;">
                <li>Go to <a href="https://github.com/settings/tokens" target="_blank">https://github.com/settings/tokens</a> (opens in new tab)</li>
                <li>Click <strong>"Generate new token"</strong> → <strong>"Generate new token (classic)"</strong></li>
                <li>Give it a descriptive name: <code>WP Staff Diary Auto-Updates</code></li>
                <li>Set expiration: Choose <strong>"No expiration"</strong> or a long duration (90 days, 1 year, etc.)</li>
                <li>Select scopes: Check <strong>"repo"</strong> (Full control of private repositories)
                    <ul style="margin-left: 20px; list-style-type: disc;">
                        <li>This gives read access to your private repository</li>
                        <li>Required for WordPress to download releases</li>
                    </ul>
                </li>
                <li>Click <strong>"Generate token"</strong> at the bottom</li>
                <li><strong>Copy the token immediately</strong> (you won't be able to see it again!)</li>
                <li>Paste it into the field above and click "Save GitHub Settings"</li>
            </ol>

            <div class="notice notice-warning inline" style="margin-top: 20px;">
                <p><strong>Security Note:</strong> Keep your token secure! It provides access to your private repository. Never share it or commit it to your code.</p>
            </div>
        </div>

        <hr style="margin: 40px 0;">

        <h2>Testing Your Configuration</h2>
        <p>After saving your token, go to the <strong>Plugins</strong> page to verify the connection:</p>
        <ul style="line-height: 2; margin-left: 20px;">
            <li>Check the "WP Staff Diary Update Diagnostics" box at the top of the Plugins page</li>
            <li><strong>"GitHub API Status"</strong> should show <span style="color: green; font-weight: bold;">SUCCESS</span></li>
            <li><strong>"Remote Version"</strong> should show the latest version number from GitHub</li>
            <li><strong>"Update Available"</strong> will show "YES" if a newer version is available</li>
        </ul>

        <p style="margin-top: 20px;">
            <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-secondary">Go to Plugins Page</a>
        </p>
    </div>

    <!-- Terms & Conditions Tab -->
    <div id="terms-tab" class="settings-tab" style="display:none;">
        <h2>Terms & Conditions</h2>
        <p>Enter your terms and conditions. These will appear at the bottom of job sheets and invoices.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_terms_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="terms_conditions">Terms & Conditions</label>
                        </th>
                        <td>
                            <?php
                            wp_editor($terms_conditions, 'terms_conditions', array(
                                'textarea_name' => 'terms_conditions',
                                'textarea_rows' => 15,
                                'media_buttons' => false,
                                'teeny' => false,
                                'quicktags' => true
                            ));
                            ?>
                            <p class="description">Use the editor above to format your terms and conditions.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_terms" class="button button-primary" value="Save Terms & Conditions">
            </p>
        </form>
    </div>

    <!-- Plugin Info Tab -->
    <div id="info-tab" class="settings-tab" style="display:none;">
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
                    <th scope="row">Total Customers</th>
                    <td>
                        <?php
                        $table_customers = $wpdb->prefix . 'staff_diary_customers';
                        $customer_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_customers");
                        echo number_format($customer_count);
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
                <tr>
                    <th scope="row">Total Payments Recorded</th>
                    <td>
                        <?php
                        $table_payments = $wpdb->prefix . 'staff_diary_payments';
                        $payment_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_payments");
                        echo number_format($payment_count);
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top: 40px;">Database Management</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Database Migration</th>
                    <td>
                        <form method="post" action="" style="margin: 0;">
                            <?php wp_nonce_field('wp_staff_diary_migration_nonce'); ?>
                            <button type="submit" name="wp_staff_diary_run_migration" class="button button-secondary">
                                <span class="dashicons dashicons-database" style="vertical-align: middle;"></span>
                                Run Database Migration
                            </button>
                            <p class="description">
                                Click this button to manually create or update all database tables for v2.0.0.<br>
                                <strong>Use this if you're seeing "table doesn't exist" errors.</strong>
                            </p>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <span style="color: #d63638; font-weight: 600;">⚠️ Danger Zone</span>
                    </th>
                    <td>
                        <div style="margin-bottom: 25px;">
                            <button type="button" id="run-diagnostics-btn" class="button button-secondary" style="background: #2271b1; color: white; border-color: #2271b1; margin-right: 10px;">
                                <span class="dashicons dashicons-dashboard" style="vertical-align: middle;"></span>
                                Run Database Diagnostics
                            </button>
                            <p class="description">
                                <strong>Diagnostic Tool:</strong> Scan the database to check for sync issues, orphaned records, or hidden jobs.
                                <br>This is <strong>safe and non-destructive</strong> - it only reads data and shows you what's in the database.
                                <br>Use this if jobs are missing from your views or after plugin reinstallation.
                            </p>
                        </div>

                        <div id="diagnostics-results" style="display: none; margin-bottom: 25px; padding: 15px; background: #fff; border: 2px solid #2271b1; border-radius: 4px;">
                            <h3 style="margin-top: 0;">Database Diagnostic Results</h3>
                            <div id="diagnostics-content"></div>
                        </div>

                        <div style="border-top: 2px solid #d63638; padding-top: 20px; margin-top: 20px;">
                            <button type="button" id="delete-all-jobs-btn" class="button button-secondary" style="background: #d63638; color: white; border-color: #d63638;">
                                <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                Delete All Jobs (Testing Only)
                            </button>
                            <p class="description" style="color: #d63638;">
                                <strong>⚠️ WARNING:</strong> This will permanently delete ALL job entries, payments, images, and job accessories from the database.
                                <br>This action <strong>CANNOT be undone</strong>.
                                <br><strong>Use this for testing only</strong> - will reset order numbers to start fresh.
                                <br>Customers, accessories master list, and settings will NOT be deleted.
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();

        // Update tab styling
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Show/hide tab content
        $('.settings-tab').hide();
        var tab = $(this).data('tab');
        $('#' + tab + '-tab').show();
    });

    // Company logo upload
    var logoUploader;
    $('#upload_logo_button').on('click', function(e) {
        e.preventDefault();

        if (logoUploader) {
            logoUploader.open();
            return;
        }

        logoUploader = wp.media({
            title: 'Choose Company Logo',
            button: {
                text: 'Use this logo'
            },
            multiple: false
        });

        logoUploader.on('select', function() {
            var attachment = logoUploader.state().get('selection').first().toJSON();
            $('#company_logo').val(attachment.url);
            $('#logo_preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
            $('#remove_logo_button').show();
        });

        logoUploader.open();
    });

    // Remove logo
    $('#remove_logo_button').on('click', function(e) {
        e.preventDefault();
        $('#company_logo').val('');
        $('#logo_preview').html('');
        $(this).hide();
    });

    // Order number preview update
    $('#order_prefix, #order_start').on('input', function() {
        var prefix = $('#order_prefix').val();
        var start = $('#order_start').val();
        var current = parseInt(start) || 0;
        var next = current + 1;
        var nextStr = String(next).padStart(start.length, '0');
        $('#order-preview').text(prefix + nextStr);
    });

    // Add Status
    $('#add-status-btn').on('click', function() {
        var label = $('#new-status-label').val().trim();
        if (!label) {
            alert('Please enter a status name');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_add_status',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                label: label
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#statuses';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to add status');
                }
            },
            error: function() {
                alert('Error adding status');
            }
        });
    });

    // Delete Status
    $('.delete-status').on('click', function() {
        if (!confirm('Are you sure you want to remove this status?')) {
            return;
        }

        var statusKey = $(this).data('status-key');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_delete_status',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                status_key: statusKey
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#statuses';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to remove status');
                }
            },
            error: function() {
                alert('Error removing status');
            }
        });
    });

    // Add Payment Method
    $('#add-payment-method-btn').on('click', function() {
        var label = $('#new-payment-method-label').val().trim();
        if (!label) {
            alert('Please enter a payment method name');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_add_payment_method',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                label: label
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#payment-methods';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to add payment method');
                }
            },
            error: function() {
                alert('Error adding payment method');
            }
        });
    });

    // Delete Payment Method
    $('.delete-payment-method').on('click', function() {
        if (!confirm('Are you sure you want to remove this payment method?')) {
            return;
        }

        var methodKey = $(this).data('method-key');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_delete_payment_method',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                method_key: methodKey
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#payment-methods';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to remove payment method');
                }
            },
            error: function() {
                alert('Error removing payment method');
            }
        });
    });

    // Add Fitter
    $('#add-fitter-btn').on('click', function() {
        var name = $('#new-fitter-name').val().trim();
        var color = $('#new-fitter-color').val();

        if (!name) {
            alert('Please enter a fitter name');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_add_fitter',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                name: name,
                color: color
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#fitters';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to add fitter');
                }
            },
            error: function() {
                alert('Error adding fitter');
            }
        });
    });

    // Delete Fitter
    $('.delete-fitter').on('click', function() {
        if (!confirm('Are you sure you want to remove this fitter?')) {
            return;
        }

        var index = $(this).data('index');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_delete_fitter',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                index: index
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#fitters';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to remove fitter');
                }
            },
            error: function() {
                alert('Error removing fitter');
            }
        });
    });

    // Add Fitter Availability
    $('#add-availability-btn').on('click', function() {
        var fitterId = $('#new-availability-fitter').val();
        var startDate = $('#new-availability-start').val();
        var endDate = $('#new-availability-end').val();
        var type = $('#new-availability-type').val();
        var reason = $('#new-availability-reason').val().trim();

        if (!fitterId || !startDate || !endDate || !type) {
            alert('Please fill in all required fields');
            return;
        }

        // Validate dates
        if (new Date(startDate) > new Date(endDate)) {
            alert('End date must be after start date');
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
                    location.href = location.href.split('#')[0] + '#fitters';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to add availability record');
                }
            },
            error: function() {
                alert('Error adding availability record');
            }
        });
    });

    // Delete Fitter Availability
    $('.delete-availability').on('click', function() {
        if (!confirm('Are you sure you want to remove this availability record?')) {
            return;
        }

        var id = $(this).data('id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_delete_availability',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#fitters';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to remove availability record');
                }
            },
            error: function() {
                alert('Error removing availability record');
            }
        });
    });

    // Delete Accessory (using event delegation for dynamically loaded content)
    $(document).on('click', '.delete-accessory', function() {
        if (!confirm('Are you sure you want to delete this accessory?')) {
            return;
        }

        var accessoryId = $(this).data('id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_accessory',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_settings_nonce'); ?>',
                accessory_id: accessoryId
            },
            success: function(response) {
                if (response.success) {
                    location.href = location.href.split('#')[0] + '#accessories';
                    location.reload();
                } else {
                    alert(response.data || 'Failed to delete accessory');
                }
            },
            error: function() {
                alert('Error deleting accessory');
            }
        });
    });

    // Toggle GitHub token visibility
    $('#toggle_token_visibility').on('click', function(e) {
        e.preventDefault();
        var tokenField = $('#github_token');
        if (tokenField.attr('type') === 'password') {
            tokenField.attr('type', 'text');
        } else {
            tokenField.attr('type', 'password');
        }
    });

    // Run Database Diagnostics
    $('#run-diagnostics-btn').on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        $btn.prop('disabled', true).text('Running diagnostics...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_diagnostics',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_diagnostics'); ?>'
            },
            success: function(response) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-dashboard" style="vertical-align: middle;"></span> Run Database Diagnostics');

                if (response.success) {
                    var diag = response.data.diagnostics;
                    var html = '<div style="font-family: monospace;">';

                    // Summary
                    html += '<h4 style="margin-top: 0; color: #2271b1;">📊 Database Summary</h4>';
                    html += '<table style="width: 100%; border-collapse: collapse;">';
                    html += '<tr style="background: #f0f0f1;"><td style="padding: 8px;"><strong>Total Jobs in Database:</strong></td><td style="padding: 8px;"><strong style="font-size: 18px; color: #2271b1;">' + diag.total_jobs + '</strong></td></tr>';
                    html += '<tr><td style="padding: 8px;">Your Jobs (current user):</td><td style="padding: 8px;">' + diag.current_user_jobs + '</td></tr>';
                    html += '<tr style="background: #f0f0f1;"><td style="padding: 8px;">Unknown Fitting Dates:</td><td style="padding: 8px;">' + diag.unknown_fitting_dates + '</td></tr>';
                    html += '<tr><td style="padding: 8px;">Cancelled Jobs:</td><td style="padding: 8px;">' + diag.cancelled_jobs + '</td></tr>';
                    html += '</table>';

                    // Jobs by user
                    if (diag.jobs_by_user.length > 0) {
                        html += '<h4 style="margin-top: 20px; color: #2271b1;">👥 Jobs by User</h4>';
                        html += '<table style="width: 100%; border-collapse: collapse;">';
                        diag.jobs_by_user.forEach(function(row, index) {
                            var user = diag.wp_users.find(u => u.id == row.user_id);
                            var userName = user ? user.display_name + ' (' + user.username + ')' : 'User ID: ' + row.user_id;
                            var bgColor = index % 2 === 0 ? '#f0f0f1' : '#fff';
                            html += '<tr style="background: ' + bgColor + ';"><td style="padding: 8px;">' + userName + '</td><td style="padding: 8px;"><strong>' + row.count + '</strong> jobs</td></tr>';
                        });
                        html += '</table>';
                    }

                    // Issues detected
                    if (response.data.issues_found) {
                        html += '<h4 style="margin-top: 20px; color: #d63638;">⚠️ Issues Detected</h4>';
                        html += '<table style="width: 100%; border-collapse: collapse; border: 2px solid #d63638;">';

                        if (diag.orphaned_payments > 0) {
                            html += '<tr style="background: #fff3cd;"><td style="padding: 8px;"><strong>Orphaned Payments:</strong></td><td style="padding: 8px; color: #d63638;"><strong>' + diag.orphaned_payments + '</strong></td></tr>';
                        }
                        if (diag.orphaned_images > 0) {
                            html += '<tr><td style="padding: 8px;"><strong>Orphaned Images:</strong></td><td style="padding: 8px; color: #d63638;"><strong>' + diag.orphaned_images + '</strong></td></tr>';
                        }
                        if (diag.orphaned_accessories > 0) {
                            html += '<tr style="background: #fff3cd;"><td style="padding: 8px;"><strong>Orphaned Accessories:</strong></td><td style="padding: 8px; color: #d63638;"><strong>' + diag.orphaned_accessories + '</strong></td></tr>';
                        }
                        if (diag.invalid_customers > 0) {
                            html += '<tr><td style="padding: 8px;"><strong>Invalid Customer Links:</strong></td><td style="padding: 8px; color: #d63638;"><strong>' + diag.invalid_customers + '</strong></td></tr>';
                        }

                        html += '</table>';

                        // Repair options
                        html += '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">';
                        html += '<h4 style="margin-top: 0;">🔧 Repair Options</h4>';

                        if (diag.orphaned_payments > 0 || diag.orphaned_images > 0 || diag.orphaned_accessories > 0) {
                            html += '<button type="button" class="button repair-btn" data-action="clean_orphaned_records" style="margin-right: 10px; margin-bottom: 10px;">';
                            html += '<span class="dashicons dashicons-admin-tools"></span> Clean Orphaned Records</button>';
                        }

                        if (diag.invalid_customers > 0) {
                            html += '<button type="button" class="button repair-btn" data-action="clear_invalid_customers" style="margin-right: 10px; margin-bottom: 10px;">';
                            html += '<span class="dashicons dashicons-admin-users"></span> Clear Invalid Customer Links</button>';
                        }

                        if (diag.total_jobs > 0 && diag.current_user_jobs === 0) {
                            html += '<button type="button" class="button repair-btn" data-action="reassign_to_current_user" style="margin-right: 10px; margin-bottom: 10px;">';
                            html += '<span class="dashicons dashicons-admin-users"></span> Reassign All Jobs to Me</button>';
                            html += '<p class="description" style="margin-top: 5px;"><strong>Note:</strong> This will reassign ALL ' + diag.total_jobs + ' jobs to your user account.</p>';
                        }

                        html += '</div>';
                    } else {
                        html += '<div style="margin-top: 20px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745;">';
                        html += '<h4 style="margin-top: 0; color: #155724;">✅ Database Health Check Passed</h4>';
                        html += '<p style="margin-bottom: 0;">No issues detected! Your database is clean and all records are properly linked.</p>';
                        html += '</div>';
                    }

                    // Order numbers
                    html += '<h4 style="margin-top: 20px; color: #2271b1;">🔢 Order Number Status</h4>';
                    html += '<table style="width: 100%; border-collapse: collapse;">';
                    html += '<tr style="background: #f0f0f1;"><td style="padding: 8px;">Current Order Number:</td><td style="padding: 8px;"><strong>' + diag.order_numbers.current + '</strong></td></tr>';
                    html += '<tr><td style="padding: 8px;">Starting Order Number:</td><td style="padding: 8px;">' + diag.order_numbers.start + '</td></tr>';
                    html += '<tr style="background: #f0f0f1;"><td style="padding: 8px;">Highest Order in DB:</td><td style="padding: 8px;">' + (diag.order_numbers.highest_in_db || 'None') + '</td></tr>';
                    html += '</table>';

                    html += '</div>';

                    $('#diagnostics-content').html(html);
                    $('#diagnostics-results').fadeIn();
                } else {
                    alert('Error: ' + (response.data || 'Failed to run diagnostics'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-dashboard" style="vertical-align: middle;"></span> Run Database Diagnostics');
                alert('Error: Server error occurred');
            }
        });
    });

    // Repair database issues
    $(document).on('click', '.repair-btn', function() {
        var $btn = $(this);
        var action = $btn.data('action');
        var confirmMsg = '';

        switch(action) {
            case 'reassign_to_current_user':
                confirmMsg = 'This will reassign ALL jobs in the database to your user account. Continue?';
                break;
            case 'clean_orphaned_records':
                confirmMsg = 'This will permanently delete orphaned payments, images, and accessories. Continue?';
                break;
            case 'clear_invalid_customers':
                confirmMsg = 'This will clear invalid customer links from jobs. Continue?';
                break;
        }

        if (!confirm(confirmMsg)) {
            return;
        }

        $btn.prop('disabled', true).text('Repairing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_staff_diary_repair',
                nonce: '<?php echo wp_create_nonce('wp_staff_diary_diagnostics'); ?>',
                repair_action: action
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message + '\n\n' + JSON.stringify(response.data.repaired, null, 2));
                    // Re-run diagnostics
                    $('#run-diagnostics-btn').click();
                } else {
                    alert('Error: ' + (response.data || 'Failed to repair'));
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Error: Server error occurred');
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete All Jobs (DANGER ZONE)
    $('#delete-all-jobs-btn').on('click', function(e) {
        e.preventDefault();

        var confirmation = confirm(
            '⚠️ FINAL WARNING ⚠️\n\n' +
            'Are you ABSOLUTELY SURE you want to delete ALL jobs?\n\n' +
            'This will permanently delete:\n' +
            '• All job entries\n' +
            '• All payments\n' +
            '• All job images\n' +
            '• All job accessories\n' +
            '• Reset order numbers\n\n' +
            'This action CANNOT be undone!\n\n' +
            'Type "DELETE ALL JOBS" in the next prompt to confirm.'
        );

        if (!confirmation) {
            return;
        }

        var confirmText = prompt('Type "DELETE ALL JOBS" (without quotes) to confirm:');

        if (confirmText !== 'DELETE ALL JOBS') {
            alert('Deletion cancelled. Text did not match.');
            return;
        }

        // Proceed with deletion
        if (confirm('Last chance! Click OK to permanently delete all jobs.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_staff_diary_delete_all_jobs',
                    nonce: '<?php echo wp_create_nonce('wp_staff_diary_delete_all_jobs'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data.message + '\n\nDeleted:\n' +
                              '• ' + response.data.deleted.jobs + ' jobs\n' +
                              '• ' + response.data.deleted.payments + ' payments\n' +
                              '• ' + response.data.deleted.images + ' images\n' +
                              '• ' + response.data.deleted.accessories + ' job accessories\n\n' +
                              'Order number reset to: ' + response.data.new_order_start);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (response.data || 'Failed to delete jobs'));
                    }
                },
                error: function() {
                    alert('❌ Error: Server error occurred');
                }
            });
        }
    });
});
</script>
