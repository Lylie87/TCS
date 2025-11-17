<?php
/**
 * Template Service - Variable Replacement Engine
 *
 * Handles email template variable replacement with actual data
 *
 * @since      2.7.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Template_Service {

    /**
     * Replace template variables with actual data
     *
     * @param string $template The template string containing variables
     * @param array $data Associative array of data to replace variables
     * @return string Template with variables replaced
     */
    public static function replace_variables($template, $data) {
        if (empty($template)) {
            return '';
        }

        // Get company and bank details
        $company_data = self::get_company_data();
        $bank_data = self::get_bank_data();

        // Merge all data together
        $all_data = array_merge($data, $company_data, $bank_data);

        // Add current date if not provided
        if (!isset($all_data['current_date'])) {
            $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
            $all_data['current_date'] = date($date_format);
        }

        // Replace each variable in the template
        foreach ($all_data as $key => $value) {
            // Handle both {{key}} and {key} formats for compatibility
            $template = str_replace('{{' . $key . '}}', $value, $template);
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        // Clean up any remaining unreplaced variables
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
        $template = preg_replace('/\{[^}]+\}/', '', $template);

        return $template;
    }

    /**
     * Prepare data array from a diary entry
     *
     * @param object $entry Diary entry object
     * @param object|null $customer Customer object (optional)
     * @param array|null $payments Array of payment objects (optional)
     * @return array Associative array of template variables
     */
    public static function prepare_entry_data($entry, $customer = null, $payments = null) {
        $data = array();

        // Date formatting
        $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');

        // Entry/Job data
        if ($entry) {
            $data['job_number'] = $entry->order_number ?? '';
            $data['order_number'] = $entry->order_number ?? '';
            $data['job_date'] = !empty($entry->job_date) ? date($date_format, strtotime($entry->job_date)) : '';
            $data['quote_date'] = !empty($entry->quote_date) ? date($date_format, strtotime($entry->quote_date)) : '';
            $data['fitting_date'] = !empty($entry->fitting_date) ? date($date_format, strtotime($entry->fitting_date)) : '';
            $data['job_description'] = $entry->notes ?? '';
            $data['product_description'] = $entry->product_description ?? '';
            $data['area'] = $entry->area ?? '';
            $data['size'] = $entry->size ?? '';
        }

        // Customer data
        if ($customer) {
            $data['customer_name'] = $customer->customer_name ?? '';
            $data['customer_email'] = $customer->customer_email ?? '';
            $data['customer_phone'] = $customer->customer_phone ?? '';

            // Build full address
            $address_parts = array_filter(array(
                $customer->address_line_1 ?? '',
                $customer->address_line_2 ?? '',
                $customer->address_line_3 ?? '',
                $customer->postcode ?? ''
            ));
            $data['customer_address'] = implode(', ', $address_parts);

            // Individual address components
            $data['address_line_1'] = $customer->address_line_1 ?? '';
            $data['address_line_2'] = $customer->address_line_2 ?? '';
            $data['address_line_3'] = $customer->address_line_3 ?? '';
            $data['postcode'] = $customer->postcode ?? '';
        }

        // Financial data
        if ($entry) {
            $data['total_amount'] = self::format_currency(self::calculate_total($entry));
            $data['paid_amount'] = '0.00';
            $data['balance_due'] = $data['total_amount'];

            // Calculate from payments if provided
            if ($payments && is_array($payments)) {
                $total_paid = array_sum(array_column($payments, 'amount'));
                $data['paid_amount'] = self::format_currency($total_paid);
                $balance = self::calculate_total($entry) - $total_paid;
                $data['balance_due'] = self::format_currency($balance);
            }

            // Discount data
            if (!empty($entry->discount_type) && !empty($entry->discount_value)) {
                $data['discount_type'] = $entry->discount_type;
                $data['discount_value'] = self::format_currency($entry->discount_value);

                if ($entry->discount_type === 'percentage') {
                    $data['discount_display'] = $entry->discount_value . '%';
                    $data['discount_type_label'] = $entry->discount_value . '% discount';
                } else {
                    $data['discount_display'] = self::format_currency($entry->discount_value);
                    $data['discount_type_label'] = self::format_currency($entry->discount_value) . ' discount';
                }
            } else {
                $data['discount_value'] = '0.00';
                $data['discount_display'] = 'No discount';
                $data['discount_type_label'] = 'no discount';
            }
        }

        // Quote acceptance link
        if ($entry && !empty($entry->acceptance_token)) {
            $data['quote_link'] = home_url('/quote-accept/' . $entry->acceptance_token);
        } else {
            $data['quote_link'] = '';
        }

        return $data;
    }

    /**
     * Calculate total for a diary entry (including VAT, accessories, etc.)
     *
     * @param object $entry Diary entry object
     * @return float Total amount
     */
    public static function calculate_total($entry) {
        global $wpdb;

        $subtotal = 0;

        // Main product cost
        if (!empty($entry->sq_mtr_qty) && !empty($entry->price_per_sq_mtr)) {
            $subtotal += floatval($entry->sq_mtr_qty) * floatval($entry->price_per_sq_mtr);
        }

        // Fitting cost
        if (!empty($entry->fitting_cost)) {
            $subtotal += floatval($entry->fitting_cost);
        }

        // Accessories
        $table_accessories = $wpdb->prefix . 'staff_diary_job_accessories';
        $accessories = $wpdb->get_results($wpdb->prepare(
            "SELECT total_price FROM $table_accessories WHERE diary_entry_id = %d",
            $entry->id
        ));

        foreach ($accessories as $accessory) {
            $subtotal += floatval($accessory->total_price);
        }

        // Apply discount
        if (!empty($entry->discount_type) && !empty($entry->discount_value)) {
            if ($entry->discount_type === 'percentage') {
                $discount_amount = $subtotal * (floatval($entry->discount_value) / 100);
                $subtotal -= $discount_amount;
            } else {
                $subtotal -= floatval($entry->discount_value);
            }
        }

        // Add VAT if enabled
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        if ($vat_enabled === '1') {
            $vat_rate = floatval(get_option('wp_staff_diary_vat_rate', '20'));
            $vat_amount = $subtotal * ($vat_rate / 100);
            $subtotal += $vat_amount;
        }

        return max(0, $subtotal);
    }

    /**
     * Format currency value
     *
     * @param float $amount Amount to format
     * @return string Formatted currency string
     */
    private static function format_currency($amount) {
        $currency_symbol = get_option('wp_staff_diary_currency_symbol', '£');
        $currency_position = get_option('wp_staff_diary_currency_position', 'left');
        $decimal_separator = get_option('wp_staff_diary_decimal_separator', '.');
        $thousands_separator = get_option('wp_staff_diary_thousands_separator', ',');

        $formatted_number = number_format(floatval($amount), 2, $decimal_separator, $thousands_separator);

        if ($currency_position === 'left') {
            return $currency_symbol . $formatted_number;
        } elseif ($currency_position === 'left_space') {
            return $currency_symbol . ' ' . $formatted_number;
        } elseif ($currency_position === 'right') {
            return $formatted_number . $currency_symbol;
        } else { // right_space
            return $formatted_number . ' ' . $currency_symbol;
        }
    }

    /**
     * Get company data for template variables
     *
     * @return array Company data
     */
    private static function get_company_data() {
        return array(
            'company_name' => get_option('wp_staff_diary_company_name', ''),
            'company_email' => get_option('wp_staff_diary_company_email', ''),
            'company_phone' => get_option('wp_staff_diary_company_phone', ''),
            'company_address' => get_option('wp_staff_diary_company_address', ''),
            'company_vat_number' => get_option('wp_staff_diary_company_vat_number', ''),
            'company_reg_number' => get_option('wp_staff_diary_company_reg_number', '')
        );
    }

    /**
     * Get bank data for template variables
     *
     * @return array Bank data
     */
    private static function get_bank_data() {
        return array(
            'bank_name' => get_option('wp_staff_diary_bank_name', ''),
            'bank_account_name' => get_option('wp_staff_diary_bank_account_name', ''),
            'bank_account_number' => get_option('wp_staff_diary_bank_account_number', ''),
            'bank_sort_code' => get_option('wp_staff_diary_bank_sort_code', ''),
            // Backward compatibility with old single field
            'bank_details' => get_option('wp_staff_diary_company_bank_details', '')
        );
    }

    /**
     * Get and process email template by slug
     *
     * @param string $slug Template slug
     * @param array $data Data for variable replacement
     * @return array|false Array with 'subject' and 'body' keys, or false if template not found
     */
    public static function get_processed_template($slug, $data = array()) {
        $db = new WP_Staff_Diary_Database();
        $template = $db->get_email_template_by_slug($slug);

        if (!$template || !$template->is_active) {
            return false;
        }

        return array(
            'subject' => self::replace_variables($template->subject, $data),
            'body' => self::replace_variables($template->body, $data)
        );
    }

    /**
     * Send email using template
     *
     * @param string $to Recipient email address
     * @param string $template_slug Template slug to use
     * @param array $data Data for variable replacement
     * @return bool True on success, false on failure
     */
    public static function send_templated_email($to, $template_slug, $data = array()) {
        $processed = self::get_processed_template($template_slug, $data);

        if (!$processed) {
            error_log("WP Staff Diary: Email template '$template_slug' not found or inactive");
            return false;
        }

        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_option('wp_staff_diary_company_name', get_bloginfo('name')) . ' <' . get_option('wp_staff_diary_company_email', get_option('admin_email')) . '>'
        );

        // Send email
        $sent = wp_mail($to, $processed['subject'], $processed['body'], $headers);

        // Log to notification logs if email sending failed
        if (!$sent) {
            error_log("WP Staff Diary: Failed to send email to $to using template $template_slug");
        }

        return $sent;
    }

    /**
     * Preview template with sample data (for testing)
     *
     * @param string $template_slug Template slug
     * @return array|false Preview with subject and body
     */
    public static function preview_template($template_slug) {
        $sample_data = array(
            'customer_name' => 'John Smith',
            'customer_email' => 'john.smith@example.com',
            'customer_phone' => '01234 567890',
            'customer_address' => '123 Main Street, London, SW1A 1AA',
            'job_number' => 'JOB-00123',
            'order_number' => 'JOB-00123',
            'job_date' => date('d/m/Y'),
            'job_description' => 'Kitchen floor installation',
            'product_description' => 'Premium Oak Laminate Flooring',
            'total_amount' => '£1,250.00',
            'paid_amount' => '£500.00',
            'balance_due' => '£750.00',
            'discount_value' => '£50.00',
            'quote_link' => home_url('/quote-accept/sample-token')
        );

        return self::get_processed_template($template_slug, $sample_data);
    }
}
