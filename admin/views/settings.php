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
    update_option('wp_staff_diary_company_bank_details', sanitize_textarea_field($_POST['company_bank_details']));

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

// Order settings
$order_start = get_option('wp_staff_diary_order_start', '01100');
$order_prefix = get_option('wp_staff_diary_order_prefix', '');
$order_current = get_option('wp_staff_diary_order_current', '01100');

// VAT settings
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');

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
?>

<div class="wrap wp-staff-diary-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general">General</a>
        <a href="#company" class="nav-tab" data-tab="company">Company Details</a>
        <a href="#orders" class="nav-tab" data-tab="orders">Order Settings</a>
        <a href="#vat" class="nav-tab" data-tab="vat">VAT</a>
        <a href="#statuses" class="nav-tab" data-tab="statuses">Job Statuses</a>
        <a href="#payment-methods" class="nav-tab" data-tab="payment-methods">Payment Methods</a>
        <a href="#accessories" class="nav-tab" data-tab="accessories">Accessories</a>
        <a href="#fitters" class="nav-tab" data-tab="fitters">Fitters</a>
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
                            <label for="company_bank_details">Bank Details</label>
                        </th>
                        <td>
                            <textarea name="company_bank_details" id="company_bank_details" rows="4" class="large-text"><?php echo esc_textarea($company_bank_details); ?></textarea>
                            <p class="description">Bank account details for customer payments (e.g., Sort Code, Account Number).</p>
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
        </div>
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
