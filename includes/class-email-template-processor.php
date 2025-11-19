<?php
/**
 * Email Template Processor - Handle merge tags and email sending
 *
 * @since      2.3.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Email_Template_Processor {

    /**
     * Process email template and replace merge tags
     *
     * @param string $template Email template with merge tags
     * @param array $data Data to replace merge tags with
     * @return string Processed email content
     */
    public static function process_template($template, $data) {
        // Ensure template is not null
        if ($template === null) {
            $template = '';
        }

        $merge_tags = array(
            '{customer_name}' => isset($data['customer_name']) ? $data['customer_name'] : '',
            '{quote_date}' => isset($data['quote_date']) ? $data['quote_date'] : '',
            '{product_description}' => isset($data['product_description']) ? $data['product_description'] : '',
            '{order_number}' => isset($data['order_number']) ? $data['order_number'] : '',
            '{original_amount}' => isset($data['original_amount']) ? $data['original_amount'] : '',
            '{discount_display}' => isset($data['discount_display']) ? $data['discount_display'] : '',
            '{discount_type_label}' => isset($data['discount_type_label']) ? $data['discount_type_label'] : '',
            '{final_amount}' => isset($data['final_amount']) ? $data['final_amount'] : '',
            '{expiry_date}' => isset($data['expiry_date']) ? $data['expiry_date'] : '',
            '{quote_link}' => isset($data['quote_link']) ? $data['quote_link'] : '',
            '{company_name}' => isset($data['company_name']) ? $data['company_name'] : '',
        );

        return str_replace(array_keys($merge_tags), array_values($merge_tags), $template);
    }

    /**
     * Prepare data array from diary entry for email template
     *
     * @param object $entry Diary entry object
     * @param string $discount_type 'percentage' or 'fixed'
     * @param float $discount_value Discount value
     * @return array Data array for template processing
     */
    public static function prepare_entry_data($entry, $discount_type, $discount_value) {
        global $wpdb;

        // Get customer details
        $customer_name = '';
        if ($entry->customer_id) {
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_name FROM {$wpdb->prefix}staff_diary_customers WHERE id = %d",
                $entry->customer_id
            ));
            if ($customer) {
                $customer_name = $customer->customer_name;
            }
        }

        // Calculate financial totals
        $original_amount = self::calculate_entry_total($entry);
        $discount_amount = WP_Staff_Diary_Currency_Helper::calculate_discount($original_amount, $discount_type, $discount_value);
        $final_amount = $original_amount - $discount_amount;

        // Format amounts
        $original_amount_formatted = WP_Staff_Diary_Currency_Helper::format($original_amount);
        $final_amount_formatted = WP_Staff_Diary_Currency_Helper::format($final_amount);
        $discount_display = WP_Staff_Diary_Currency_Helper::format_discount($discount_type, $discount_value);
        $discount_type_label = WP_Staff_Diary_Currency_Helper::get_discount_type_label($discount_type);

        // Format dates
        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
        $quote_date = $entry->quote_date ? date($date_format, strtotime($entry->quote_date)) : ($entry->job_date ? date($date_format, strtotime($entry->job_date)) : '');

        $validity_days = get_option('wp_staff_diary_quote_validity_days', '30');
        $expiry_timestamp = strtotime($entry->quote_date ? $entry->quote_date : $entry->job_date) + ($validity_days * 24 * 60 * 60);
        $expiry_date = date($date_format, $expiry_timestamp);

        // Generate acceptance token if not exists
        $acceptance_token = $entry->acceptance_token;
        if (empty($acceptance_token)) {
            $acceptance_token = bin2hex(random_bytes(32));

            // Update entry with token
            $wpdb->update(
                $wpdb->prefix . 'staff_diary_entries',
                array('acceptance_token' => $acceptance_token),
                array('id' => $entry->id),
                array('%s'),
                array('%d')
            );
        }

        // Generate quote acceptance link
        $quote_link = home_url('/quote-accept/' . $acceptance_token);

        // Get company name
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));

        return array(
            'customer_name' => $customer_name,
            'quote_date' => $quote_date,
            'product_description' => $entry->product_description ? $entry->product_description : 'your requested service',
            'order_number' => $entry->order_number,
            'original_amount' => $original_amount_formatted,
            'discount_display' => $discount_display,
            'discount_type_label' => $discount_type_label,
            'final_amount' => $final_amount_formatted,
            'expiry_date' => $expiry_date,
            'quote_link' => $quote_link,
            'company_name' => $company_name,
        );
    }

    /**
     * Calculate total amount for entry (before discount)
     *
     * @param object $entry Diary entry object
     * @return float Total amount
     */
    private static function calculate_entry_total($entry) {
        global $wpdb;

        // Product total
        $product_total = ($entry->sq_mtr_qty * $entry->price_per_sq_mtr) + $entry->fitting_cost;

        // Get accessories total
        $accessories_total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_price) FROM {$wpdb->prefix}staff_diary_job_accessories WHERE diary_entry_id = %d",
            $entry->id
        ));
        $accessories_total = $accessories_total ? floatval($accessories_total) : 0;

        $subtotal = $product_total + $accessories_total;

        // Add VAT if enabled
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        if ($vat_enabled == '1') {
            $vat_rate = get_option('wp_staff_diary_vat_rate', '20');
            $vat_amount = ($subtotal * $vat_rate) / 100;
            return $subtotal + $vat_amount;
        }

        return $subtotal;
    }

    /**
     * Send discount email to customer
     *
     * @param int $entry_id Diary entry ID
     * @param string $discount_type 'percentage' or 'fixed'
     * @param float $discount_value Discount value
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_discount_email($entry_id, $discount_type, $discount_value) {
        global $wpdb;

        // Get entry
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}staff_diary_entries WHERE id = %d",
            $entry_id
        ));

        if (!$entry) {
            return new WP_Error('entry_not_found', 'Entry not found');
        }

        // Get customer email
        $customer_email = '';
        if ($entry->customer_id) {
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_email FROM {$wpdb->prefix}staff_diary_customers WHERE id = %d",
                $entry->customer_id
            ));
            if ($customer && !empty($customer->customer_email)) {
                $customer_email = $customer->customer_email;
            }
        }

        if (empty($customer_email)) {
            return new WP_Error('no_email', 'Customer email address not found');
        }

        // Get email template
        $template = get_option('wp_staff_diary_quote_email_template', '');
        if (empty($template)) {
            return new WP_Error('no_template', 'Email template not configured');
        }

        // Prepare data and process template
        $data = self::prepare_entry_data($entry, $discount_type, $discount_value);
        $email_content = self::process_template($template, $data);

        // Email subject
        $company_name = get_option('wp_staff_diary_company_name', get_bloginfo('name'));
        $subject = sprintf('Special Discount Offer - %s - Order #%s', $company_name, $entry->order_number);

        // Send email with company name as sender
        $admin_email = get_option('admin_email');
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $company_name . ' <' . $admin_email . '>'
        );
        $sent = wp_mail($customer_email, $subject, $email_content, $headers);

        if ($sent) {
            // Update entry with discount info
            $wpdb->update(
                $wpdb->prefix . 'staff_diary_entries',
                array(
                    'discount_type' => $discount_type,
                    'discount_value' => $discount_value,
                    'discount_applied_date' => current_time('mysql')
                ),
                array('id' => $entry_id),
                array('%s', '%f', '%s'),
                array('%d')
            );

            // Log to discount_offers table
            $wpdb->insert(
                $wpdb->prefix . 'staff_diary_discount_offers',
                array(
                    'diary_entry_id' => $entry_id,
                    'discount_type' => $discount_type,
                    'discount_value' => $discount_value,
                    'email_sent_date' => current_time('mysql'),
                    'sent_by' => get_current_user_id(),
                    'status' => 'sent',
                    'email_content' => $email_content,
                    'metadata' => json_encode(array(
                        'recipient' => $customer_email,
                        'subject' => $subject
                    ))
                ),
                array('%d', '%s', '%f', '%s', '%d', '%s', '%s', '%s')
            );

            // Log to notification logs
            $wpdb->insert(
                $wpdb->prefix . 'staff_diary_notification_logs',
                array(
                    'diary_entry_id' => $entry_id,
                    'notification_type' => 'discount_offer',
                    'recipient' => $customer_email,
                    'method' => 'email',
                    'status' => 'sent'
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );

            return true;
        } else {
            // Log failure
            $wpdb->insert(
                $wpdb->prefix . 'staff_diary_notification_logs',
                array(
                    'diary_entry_id' => $entry_id,
                    'notification_type' => 'discount_offer',
                    'recipient' => $customer_email,
                    'method' => 'email',
                    'status' => 'failed',
                    'error_message' => 'wp_mail() returned false'
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );

            return new WP_Error('send_failed', 'Failed to send email');
        }
    }
}
