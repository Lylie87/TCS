<?php
/**
 * Payment Settings Page
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

// Get current settings
// VAT settings
$vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
$vat_rate = get_option('wp_staff_diary_vat_rate', '20');

// Currency settings
$currency_symbol = get_option('wp_staff_diary_currency_symbol', '£');
$currency_code = get_option('wp_staff_diary_currency_code', 'GBP');
$currency_position = get_option('wp_staff_diary_currency_position', 'left');
$decimal_separator = get_option('wp_staff_diary_decimal_separator', '.');
$thousands_separator = get_option('wp_staff_diary_thousands_separator', ',');

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

// Payment methods
$payment_methods = get_option('wp_staff_diary_payment_methods', array(
    'cash' => 'Cash',
    'bank-transfer' => 'Bank Transfer',
    'card-payment' => 'Card Payment'
));
?>

<div class="wrap">
    <h1>Payment Settings</h1>
    <p>Configure VAT, currency, payment methods, and automatic payment reminders.</p>

    <!-- VAT Settings -->
    <div class="settings-section" style="margin-top: 30px;">
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

    <hr style="margin: 40px 0;">

    <!-- Currency Settings (Display Only - Edit in General Settings) -->
    <div class="settings-section">
        <h2>Currency Settings</h2>
        <p>Currency settings are managed in <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-general-settings'); ?>">General Settings</a>.</p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Currency Symbol</th>
                    <td><strong><?php echo esc_html($currency_symbol); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Currency Code</th>
                    <td><strong><?php echo esc_html($currency_code); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Currency Position</th>
                    <td><strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $currency_position))); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Number Format</th>
                    <td><strong><?php echo WP_Staff_Diary_Currency_Helper::format(1234.56); ?></strong> <span class="description">(Example: 1234.56)</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr style="margin: 40px 0;">

    <!-- Payment Methods (Display Only - Managed via AJAX in original settings) -->
    <div class="settings-section">
        <h2>Payment Methods</h2>
        <p>Payment methods are managed dynamically in the main settings page.</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Label</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_methods as $key => $label): ?>
                <tr>
                    <td><code><?php echo esc_html($key); ?></code></td>
                    <td><?php echo esc_html($label); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description">To manage payment methods, go to the <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-settings#payment-methods'); ?>">main Settings page</a>.</p>
    </div>

    <hr style="margin: 40px 0;">

    <!-- Payment Reminder Settings -->
    <div class="settings-section">
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
                                <option value="both" <?php selected($payment_policy, 'both'); ?>>Both Domestic & Commercial can work before full payment</option>
                                <option value="commercial" <?php selected($payment_policy, 'commercial'); ?>>Only Commercial jobs can work before full payment</option>
                                <option value="domestic" <?php selected($payment_policy, 'domestic'); ?>>Only Domestic jobs can work before full payment</option>
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
</div>
