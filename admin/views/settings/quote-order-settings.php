<?php
/**
 * Quote & Order Settings Page
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

// Save order settings
if (isset($_POST['wp_staff_diary_save_order_settings'])) {
    check_admin_referer('wp_staff_diary_order_nonce');

    update_option('wp_staff_diary_order_start', sanitize_text_field($_POST['order_start']));
    update_option('wp_staff_diary_order_prefix', sanitize_text_field($_POST['order_prefix']));
    // Don't update current - it increments automatically

    echo '<div class="notice notice-success is-dismissible"><p>Order settings saved successfully!</p></div>';
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
    update_option('wp_staff_diary_quote_show_discount_button', isset($_POST['quote_show_discount_button']) ? '1' : '0');

    echo '<div class="notice notice-success is-dismissible"><p>Quotation settings saved successfully!</p></div>';
}

// Save terms and conditions
if (isset($_POST['wp_staff_diary_save_terms'])) {
    check_admin_referer('wp_staff_diary_terms_nonce');

    update_option('wp_staff_diary_terms_conditions', wp_kses_post($_POST['terms_conditions']));

    echo '<div class="notice notice-success is-dismissible"><p>Terms and conditions saved successfully!</p></div>';
}

// Get current settings
$order_start = get_option('wp_staff_diary_order_start', '01100');
$order_prefix = get_option('wp_staff_diary_order_prefix', '');
$order_current = get_option('wp_staff_diary_order_current', '01100');

// Quotation settings
$quote_enable_auto_discount = get_option('wp_staff_diary_quote_enable_auto_discount', '0');
$quote_auto_discount_days = get_option('wp_staff_diary_quote_auto_discount_days', '7');
$quote_auto_discount_type = get_option('wp_staff_diary_quote_auto_discount_type', 'percentage');
$quote_auto_discount_value = get_option('wp_staff_diary_quote_auto_discount_value', '5');
$quote_validity_days = get_option('wp_staff_diary_quote_validity_days', '30');
$quote_default_fitting_cost = get_option('wp_staff_diary_quote_default_fitting_cost', '15');
$quote_email_template = get_option('wp_staff_diary_quote_email_template', '');
$quote_show_discount_button = get_option('wp_staff_diary_quote_show_discount_button', '1');

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

// Terms and conditions
$terms_conditions = get_option('wp_staff_diary_terms_conditions', '');
?>

<div class="wrap">
    <h1>Quote & Order Settings</h1>
    <p>Configure order numbering, quote settings, and terms & conditions.</p>

    <!-- Order Number Settings -->
    <div class="settings-section" style="margin-top: 30px;">
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
                            <input type="text" name="order_prefix" id="order_prefix" value="<?php echo esc_attr($order_prefix); ?>" class="regular-text">
                            <p class="description">Optional prefix for order numbers (e.g., "ORD-", "JOB-"). Leave blank for none.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="order_start">Starting Order Number</label>
                        </th>
                        <td>
                            <input type="text" name="order_start" id="order_start" value="<?php echo esc_attr($order_start); ?>" class="regular-text">
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

    <hr style="margin: 40px 0;">

    <!-- Quotation & Discount Settings -->
    <div class="settings-section">
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

                    <tr>
                        <th scope="row">
                            <label for="quote_show_discount_button">Show Discount Button on Quotes Dashboard</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="quote_show_discount_button" id="quote_show_discount_button" value="1" <?php checked($quote_show_discount_button, '1'); ?>>
                                Display the discount button on the Quotes dashboard
                            </label>
                            <p class="description">When unchecked, the "💰 Discount" button will be hidden from the quotes list. This helps keep the interface cleaner if you don't use the discount feature.</p>
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

    <hr style="margin: 40px 0;">

    <!-- Terms & Conditions -->
    <div class="settings-section">
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
</div>
