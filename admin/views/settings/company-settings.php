<?php
/**
 * Company Settings Page
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

// Get current settings
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

// Fitters and statuses (for display)
$fitters = get_option('wp_staff_diary_fitters', array());
$statuses = get_option('wp_staff_diary_statuses', array(
    'pending' => 'Pending',
    'in-progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
));
$default_status = get_option('wp_staff_diary_default_status', 'pending');
?>

<div class="wrap">
    <h1>Company Settings</h1>
    <p>Manage your company information, fitters, and job statuses.</p>

    <!-- Company Details -->
    <div class="settings-section" style="margin-top: 30px;">
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

    <hr style="margin: 40px 0;">

    <!-- Fitters (Display Only - Managed via AJAX) -->
    <div class="settings-section">
        <h2>Fitters</h2>
        <p>Fitters are managed dynamically in the main settings page with advanced editing features.</p>

        <?php if (!empty($fitters)): ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Color</th>
                    <th>Email</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fitters as $fitter_id => $fitter): ?>
                <tr>
                    <td><strong><?php echo esc_html($fitter['name']); ?></strong></td>
                    <td><span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo esc_attr($fitter['color']); ?>; border: 1px solid #ccc;"></span> <?php echo esc_html($fitter['color']); ?></td>
                    <td><?php echo esc_html($fitter['email'] ?? 'N/A'); ?></td>
                    <td><?php echo esc_html($fitter['phone'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="description">No fitters configured yet.</p>
        <?php endif; ?>

        <p class="description" style="margin-top: 15px;">
            To add, edit, or remove fitters, go to the <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-settings#fitters'); ?>">main Settings page</a>.
        </p>
    </div>

    <hr style="margin: 40px 0;">

    <!-- Job Statuses (Display Only - Managed via AJAX) -->
    <div class="settings-section">
        <h2>Job Statuses</h2>
        <p>Job statuses are managed dynamically in the main settings page.</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Label</th>
                    <th>Default</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statuses as $key => $label): ?>
                <tr>
                    <td><code><?php echo esc_html($key); ?></code></td>
                    <td><?php echo esc_html($label); ?></td>
                    <td><?php echo ($key === $default_status) ? '<span style="color: #2271b1;">✓ Default</span>' : ''; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="description" style="margin-top: 15px;">
            To add, edit, or remove job statuses, go to the <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-settings#statuses'); ?>">main Settings page</a>.
        </p>
    </div>
</div>
