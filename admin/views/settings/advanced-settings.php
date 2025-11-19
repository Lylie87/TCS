<?php
/**
 * Advanced Settings Page
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
$github_token = get_option('wp_staff_diary_github_token', '');

// SMS Settings
$sms_enabled = get_option('wp_staff_diary_sms_enabled', '0');
$sms_test_mode = get_option('wp_staff_diary_sms_test_mode', '1');
$twilio_account_sid = get_option('wp_staff_diary_twilio_account_sid', '');
$twilio_auth_token = get_option('wp_staff_diary_twilio_auth_token', '');
$twilio_phone_number = get_option('wp_staff_diary_twilio_phone_number', '');
$sms_cost_per_message = get_option('wp_staff_diary_sms_cost_per_message', '0.04');
?>

<div class="wrap">
    <h1>Advanced Settings</h1>
    <p>Configure advanced features including GitHub auto-updates and SMS notifications.</p>

    <!-- SMS/Twilio Settings -->
    <div class="settings-section" style="margin-top: 30px;">
        <h2>SMS Notifications (Twilio)</h2>
        <p>Configure Twilio integration for sending SMS notifications to customers.</p>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_communications_nonce'); ?>

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
                            <input type="text" name="twilio_account_sid" id="twilio_account_sid" value="<?php echo esc_attr($twilio_account_sid); ?>" class="regular-text">
                            <p class="description">Your Twilio Account SID from the <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a>.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="twilio_auth_token">Twilio Auth Token</label>
                        </th>
                        <td>
                            <input type="password" name="twilio_auth_token" id="twilio_auth_token" value="<?php echo esc_attr($twilio_auth_token); ?>" class="regular-text">
                            <p class="description">Your Twilio Auth Token from the <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a>. Keep this secure!</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="twilio_phone_number">Twilio Phone Number</label>
                        </th>
                        <td>
                            <input type="text" name="twilio_phone_number" id="twilio_phone_number" value="<?php echo esc_attr($twilio_phone_number); ?>" class="regular-text">
                            <p class="description">Your Twilio phone number in E.164 format (e.g., +441234567890).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="sms_cost_per_message">Cost Per SMS (<?php echo WP_Staff_Diary_Currency_Helper::get_symbol(); ?>)</label>
                        </th>
                        <td>
                            <input type="number" name="sms_cost_per_message" id="sms_cost_per_message" value="<?php echo esc_attr($sms_cost_per_message); ?>" class="small-text" step="0.01" min="0">
                            <p class="description">Estimated cost per SMS for tracking purposes. Default: £0.04</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_communications" class="button button-primary" value="Save SMS Settings">
            </p>
        </form>

        <div style="margin-top: 30px; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
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

    <hr style="margin: 40px 0;">

    <!-- GitHub Auto-Updates -->
    <div class="settings-section">
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
                            <input type="password" name="github_token" id="github_token" value="<?php echo esc_attr($github_token); ?>" class="regular-text" placeholder="ghp_xxxxxxxxxxxxxxxxxxxx">
                            <p class="description">Enter your GitHub Personal Access Token with 'repo' scope.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Clear Token</th>
                        <td>
                            <label>
                                <input type="checkbox" name="clear_token" value="1">
                                Clear the stored token
                            </label>
                            <p class="description">Check this box to remove the stored token.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Token Status</th>
                        <td>
                            <?php if (!empty($github_token)): ?>
                                <span style="color: #46b450;">✓ Token is set</span>
                                <p class="description">Automatic updates are enabled.</p>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ No token configured</span>
                                <p class="description">Automatic updates are disabled.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_github" class="button button-primary" value="Save GitHub Settings">
            </p>
        </form>

        <!-- GitHub Instructions -->
        <div style="margin-top: 30px; padding: 15px; background: #fff; border-left: 4px solid #0073aa;">
            <h3 style="margin-top: 0;">How to Create a GitHub Personal Access Token</h3>
            <ol style="padding-left: 20px;">
                <li>Go to <a href="https://github.com/settings/tokens" target="_blank">GitHub Settings → Developer settings → Personal access tokens → Tokens (classic)</a></li>
                <li>Click <strong>"Generate new token (classic)"</strong></li>
                <li>Give it a descriptive name (e.g., "WordPress Plugin Updates")</li>
                <li>Set an expiration date (recommended: 90 days, then update)</li>
                <li>Select the <strong>"repo"</strong> scope (this grants access to private repositories)</li>
                <li>Click <strong>"Generate token"</strong></li>
                <li>Copy the token (you won't be able to see it again!)</li>
                <li>Paste it into the field above and save</li>
            </ol>

            <h4>Security Best Practices:</h4>
            <ul style="list-style: disc; padding-left: 20px;">
                <li>Never share your token or commit it to version control</li>
                <li>Use tokens with minimal required permissions (just 'repo' scope)</li>
                <li>Set an expiration date and update periodically</li>
                <li>Revoke the token if you suspect it has been compromised</li>
            </ul>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #46b450;">
            <h3 style="margin-top: 0;">Testing Your Configuration</h3>
            <ol style="padding-left: 20px;">
                <li>Save your GitHub token using the form above</li>
                <li>Go to <strong>Dashboard → Updates</strong></li>
                <li>Click <strong>"Check Again"</strong> to force WordPress to check for plugin updates</li>
                <li>If configured correctly, you should see any available updates for this plugin</li>
            </ol>
            <p><strong>Troubleshooting:</strong> If updates aren't showing:  </p>
            <ul style="list-style: disc; padding-left: 20px;">
                <li>Verify your token has the 'repo' scope</li>
                <li>Ensure the token hasn't expired</li>
                <li>Check that your repository has releases tagged</li>
                <li>Look for errors in <code>wp-content/debug.log</code> (with WP_DEBUG enabled)</li>
            </ul>
        </div>
    </div>

    <hr style="margin: 40px 0;">

    <!-- Plugin Information -->
    <div class="settings-section">
        <h2>Plugin Information</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Plugin Version</th>
                    <td><strong><?php echo WP_STAFF_DIARY_VERSION; ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Database Version</th>
                    <td><?php echo get_option('wp_staff_diary_version', 'N/A'); ?></td>
                </tr>
                <tr>
                    <th scope="row">WordPress Version</th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th scope="row">PHP Version</th>
                    <td><?php echo phpversion(); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
