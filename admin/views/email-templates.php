<?php
/**
 * Email Templates Editor Page
 *
 * @since      2.7.0
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

$db = new WP_Staff_Diary_Database();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$template_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['wp_staff_diary_save_template'])) {
        check_admin_referer('wp_staff_diary_template_nonce');

        $template_data = array(
            'template_name' => sanitize_text_field($_POST['template_name']),
            'template_slug' => sanitize_title($_POST['template_slug']),
            'subject' => sanitize_text_field($_POST['subject']),
            'body' => sanitize_textarea_field($_POST['body']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        if ($template_id > 0) {
            // Update existing template
            if ($db->update_email_template($template_id, $template_data)) {
                echo '<div class="notice notice-success is-dismissible"><p>Email template updated successfully!</p></div>';
                $action = 'edit'; // Stay on edit page
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to update template. Please try again.</p></div>';
            }
        } else {
            // Create new template
            $new_id = $db->create_email_template($template_data);
            if ($new_id) {
                echo '<div class="notice notice-success is-dismissible"><p>Email template created successfully!</p></div>';
                $template_id = $new_id;
                $action = 'edit';
                // Update URL to reflect new template ID
                echo '<script>window.history.replaceState({}, "", "' . admin_url('admin.php?page=wp-staff-diary-email-templates&action=edit&id=' . $new_id) . '");</script>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to create template. Please try again.</p></div>';
            }
        }
    }

    if (isset($_POST['wp_staff_diary_delete_template'])) {
        check_admin_referer('wp_staff_diary_delete_template_nonce');

        if ($db->delete_email_template($template_id)) {
            echo '<div class="notice notice-success is-dismissible"><p>Email template deleted successfully!</p></div>';
            $action = 'list';
            $template_id = 0;
            // Redirect to list view
            echo '<script>window.location.href = "' . admin_url('admin.php?page=wp-staff-diary-email-templates') . '";</script>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to delete template. Default templates cannot be deleted.</p></div>';
        }
    }
}

// Get template data if editing
$template = null;
if ($action === 'edit' && $template_id > 0) {
    $template = $db->get_email_template($template_id);
    if (!$template) {
        echo '<div class="notice notice-error"><p>Template not found.</p></div>';
        $action = 'list';
    }
}

// Set defaults for new template
if ($action === 'add') {
    $template = (object) array(
        'template_name' => '',
        'template_slug' => '',
        'subject' => '',
        'body' => '',
        'is_active' => 1,
        'is_default' => 0
    );
}
?>

<div class="wrap wp-staff-diary-wrap">
    <h1>
        <?php
        if ($action === 'add') {
            echo 'Add New Email Template';
        } elseif ($action === 'edit') {
            echo 'Edit Email Template';
        } else {
            echo 'Email Templates';
        }
        ?>
    </h1>

    <?php if ($action === 'list') : ?>

        <p>Manage email templates for customer notifications. Templates use variable placeholders like {{customer_name}} and {{job_number}}.</p>

        <p style="margin-bottom: 20px;">
            <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-email-templates&action=add'); ?>" class="button button-primary">Add New Template</a>
            <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-settings#communications'); ?>" class="button">Back to Settings</a>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;">Template Name</th>
                    <th style="width: 20%;">Slug</th>
                    <th style="width: 35%;">Subject</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $templates = $db->get_all_email_templates();
                if (!empty($templates)) :
                    foreach ($templates as $tpl) :
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($tpl->template_name); ?></strong>
                            <?php if ($tpl->is_default) : ?>
                                <br><span class="description">(Default Template)</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($tpl->template_slug); ?></code></td>
                        <td><?php echo esc_html($tpl->subject); ?></td>
                        <td>
                            <?php if ($tpl->is_active) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Active
                            <?php else : ?>
                                <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> Inactive
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-email-templates&action=edit&id=' . $tpl->id); ?>" class="button button-small">Edit</a>
                        </td>
                    </tr>
                <?php
                    endforeach;
                else :
                ?>
                    <tr>
                        <td colspan="5"><em>No email templates found. Click "Add New Template" to create one.</em></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="wp-staff-diary-info-box" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-top: 30px;">
            <h3 style="margin-top: 0;">Available Template Variables</h3>
            <p>You can use the following variables in your email subject and body. They will be automatically replaced with actual data when sending emails.</p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <h4>Customer Variables</h4>
                    <ul style="list-style: disc; padding-left: 20px; margin: 5px 0;">
                        <li><code>{{customer_name}}</code></li>
                        <li><code>{{customer_email}}</code></li>
                        <li><code>{{customer_phone}}</code></li>
                        <li><code>{{customer_address}}</code></li>
                    </ul>
                </div>

                <div>
                    <h4>Job Variables</h4>
                    <ul style="list-style: disc; padding-left: 20px; margin: 5px 0;">
                        <li><code>{{job_number}}</code> or <code>{{order_number}}</code></li>
                        <li><code>{{job_date}}</code> or <code>{{quote_date}}</code></li>
                        <li><code>{{job_description}}</code></li>
                        <li><code>{{product_description}}</code></li>
                    </ul>
                </div>

                <div>
                    <h4>Financial Variables</h4>
                    <ul style="list-style: disc; padding-left: 20px; margin: 5px 0;">
                        <li><code>{{total_amount}}</code></li>
                        <li><code>{{paid_amount}}</code></li>
                        <li><code>{{balance_due}}</code></li>
                        <li><code>{{discount_value}}</code></li>
                    </ul>
                </div>

                <div>
                    <h4>Company Variables</h4>
                    <ul style="list-style: disc; padding-left: 20px; margin: 5px 0;">
                        <li><code>{{company_name}}</code></li>
                        <li><code>{{company_email}}</code></li>
                        <li><code>{{company_phone}}</code></li>
                        <li><code>{{company_address}}</code></li>
                    </ul>
                </div>

                <div>
                    <h4>Bank Details</h4>
                    <ul style="list-style: disc; padding-left: 20px; margin: 5px 0;">
                        <li><code>{{bank_name}}</code></li>
                        <li><code>{{bank_account_name}}</code></li>
                        <li><code>{{bank_account_number}}</code></li>
                        <li><code>{{bank_sort_code}}</code></li>
                    </ul>
                </div>

                <div>
                    <h4>Other Variables</h4>
                    <ul style="list-style: disc; padding-left: 20px; margin: 5px 0;">
                        <li><code>{{current_date}}</code></li>
                        <li><code>{{quote_link}}</code> (for quote acceptance)</li>
                    </ul>
                </div>
            </div>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit') : ?>

        <form method="post" action="">
            <?php wp_nonce_field('wp_staff_diary_template_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="template_name">Template Name <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="template_name" id="template_name" value="<?php echo esc_attr($template->template_name); ?>" class="regular-text" required>
                            <p class="description">A descriptive name for this template (e.g., "Payment Reminder", "Quote Approved").</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="template_slug">Template Slug <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="template_slug" id="template_slug" value="<?php echo esc_attr($template->template_slug); ?>" class="regular-text" required pattern="[a-z0-9_-]+" <?php echo $template->is_default ? 'readonly' : ''; ?>>
                            <p class="description">Unique identifier for this template. Use lowercase letters, numbers, hyphens, and underscores only (e.g., "payment_reminder").
                            <?php if ($template->is_default) : ?>
                                <br><strong>This is a default template - slug cannot be changed.</strong>
                            <?php endif; ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="subject">Email Subject <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="subject" id="subject" value="<?php echo esc_attr($template->subject); ?>" class="large-text" required>
                            <p class="description">Use variables like {{customer_name}} and {{job_number}}. See variable list below.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="body">Email Body <span class="required">*</span></label>
                        </th>
                        <td>
                            <textarea name="body" id="body" rows="20" class="large-text code" required><?php echo esc_textarea($template->body); ?></textarea>
                            <p class="description">The main content of your email. Use variables for dynamic content. Line breaks will be preserved.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($template->is_active, 1); ?>>
                                Active (enabled for use)
                            </label>
                            <p class="description">Inactive templates will not be available for selection when sending emails.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="wp_staff_diary_save_template" class="button button-primary" value="<?php echo $action === 'add' ? 'Create Template' : 'Update Template'; ?>">
                <a href="<?php echo admin_url('admin.php?page=wp-staff-diary-email-templates'); ?>" class="button">Cancel</a>

                <?php if ($action === 'edit' && !$template->is_default) : ?>
                    <span style="float: right;">
                        <button type="button" class="button button-link-delete" id="delete-template-btn" style="color: #b32d2e;">Delete Template</button>
                    </span>
                <?php endif; ?>
            </p>
        </form>

        <?php if ($action === 'edit' && !$template->is_default) : ?>
            <!-- Delete confirmation form (hidden) -->
            <form method="post" id="delete-template-form" style="display: none;">
                <?php wp_nonce_field('wp_staff_diary_delete_template_nonce'); ?>
                <input type="hidden" name="wp_staff_diary_delete_template" value="1">
            </form>

            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#delete-template-btn').on('click', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                        $('#delete-template-form').submit();
                    }
                });
            });
            </script>
        <?php endif; ?>

        <div class="wp-staff-diary-info-box" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-top: 30px;">
            <h3 style="margin-top: 0;">Available Template Variables</h3>
            <p>Copy and paste these variables into your subject and body. They will be replaced with actual data when sending.</p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
                <div>
                    <strong>Customer:</strong><br>
                    <code>{{customer_name}}</code><br>
                    <code>{{customer_email}}</code><br>
                    <code>{{customer_phone}}</code><br>
                    <code>{{customer_address}}</code>
                </div>

                <div>
                    <strong>Job:</strong><br>
                    <code>{{job_number}}</code><br>
                    <code>{{job_date}}</code><br>
                    <code>{{job_description}}</code><br>
                    <code>{{product_description}}</code>
                </div>

                <div>
                    <strong>Financial:</strong><br>
                    <code>{{total_amount}}</code><br>
                    <code>{{paid_amount}}</code><br>
                    <code>{{balance_due}}</code>
                </div>

                <div>
                    <strong>Company:</strong><br>
                    <code>{{company_name}}</code><br>
                    <code>{{company_email}}</code><br>
                    <code>{{company_phone}}</code>
                </div>

                <div>
                    <strong>Bank:</strong><br>
                    <code>{{bank_name}}</code><br>
                    <code>{{bank_account_name}}</code><br>
                    <code>{{bank_sort_code}}</code><br>
                    <code>{{bank_account_number}}</code>
                </div>

                <div>
                    <strong>Other:</strong><br>
                    <code>{{current_date}}</code><br>
                    <code>{{quote_link}}</code>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<style>
.required {
    color: #d63638;
}

.wp-staff-diary-info-box h4 {
    margin-top: 0;
    margin-bottom: 5px;
}

.wp-staff-diary-info-box code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
