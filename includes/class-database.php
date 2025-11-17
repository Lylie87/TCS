<?php
/**
 * Database operations for diary entries
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Database {

    private $table_diary;
    private $table_images;

    public function __construct() {
        global $wpdb;
        $this->table_diary = $wpdb->prefix . 'staff_diary_entries';
        $this->table_images = $wpdb->prefix . 'staff_diary_images';
    }

    /**
     * Get diary entries for a specific user
     * Excludes quotes (status = 'quotation') - quotes have their own page
     */
    public function get_user_entries($user_id, $start_date = null, $end_date = null) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table_diary} WHERE user_id = %d AND status != 'quotation'";
        $params = array($user_id);

        if ($start_date && $end_date) {
            $sql .= " AND job_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY job_date DESC, created_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Get all diary entries (for overview)
     */
    public function get_all_entries($start_date = null, $end_date = null, $limit = 100) {
        global $wpdb;

        $sql = "SELECT d.*, u.display_name as staff_name
                FROM {$this->table_diary} d
                LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID";

        $params = array();

        if ($start_date && $end_date) {
            $sql .= " WHERE job_date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        $sql .= " ORDER BY job_date DESC, created_at DESC LIMIT %d";
        $params[] = $limit;

        if (empty($params)) {
            $params[] = $limit;
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        }

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Get a single diary entry
     */
    public function get_entry($entry_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_diary} WHERE id = %d",
            $entry_id
        ));
    }

    /**
     * Create a new diary entry
     */
    public function create_entry($data) {
        global $wpdb;

        $wpdb->insert($this->table_diary, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update a diary entry
     */
    public function update_entry($entry_id, $data) {
        global $wpdb;

        return $wpdb->update(
            $this->table_diary,
            $data,
            array('id' => $entry_id)
        );
    }

    /**
     * Delete a diary entry
     */
    public function delete_entry($entry_id) {
        global $wpdb;

        // Delete associated images first
        $wpdb->delete($this->table_images, array('diary_entry_id' => $entry_id));

        // Delete the entry
        return $wpdb->delete($this->table_diary, array('id' => $entry_id));
    }

    /**
     * Add image to diary entry
     */
    public function add_image($diary_entry_id, $image_url, $attachment_id = null, $caption = '') {
        global $wpdb;

        $wpdb->insert($this->table_images, array(
            'diary_entry_id' => $diary_entry_id,
            'image_url' => $image_url,
            'attachment_id' => $attachment_id,
            'image_caption' => $caption
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get images for a diary entry
     */
    public function get_entry_images($diary_entry_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_images} WHERE diary_entry_id = %d ORDER BY uploaded_at ASC",
            $diary_entry_id
        ));
    }

    /**
     * Delete an image
     */
    public function delete_image($image_id) {
        global $wpdb;

        return $wpdb->delete($this->table_images, array('id' => $image_id));
    }

    /**
     * Add a payment record
     */
    public function add_payment($diary_entry_id, $amount, $payment_method, $payment_type, $notes, $recorded_by) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        $wpdb->insert($table_payments, array(
            'diary_entry_id' => $diary_entry_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'notes' => $notes,
            'recorded_by' => $recorded_by
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get payments for a diary entry
     */
    public function get_entry_payments($diary_entry_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE diary_entry_id = %d ORDER BY recorded_at ASC",
            $diary_entry_id
        ));
    }

    /**
     * Delete a payment record
     */
    public function delete_payment($payment_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        return $wpdb->delete($table_payments, array('id' => $payment_id));
    }

    /**
     * Get total payments for a diary entry
     */
    public function get_entry_total_payments($diary_entry_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_payments WHERE diary_entry_id = %d",
            $diary_entry_id
        ));
    }

    // ==================== CUSTOMER METHODS ====================

    /**
     * Get all customers
     */
    public function get_all_customers($search = '') {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        if (!empty($search)) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_customers
                WHERE customer_name LIKE %s
                OR customer_phone LIKE %s
                OR customer_email LIKE %s
                ORDER BY customer_name ASC",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            ));
        }

        return $wpdb->get_results("SELECT * FROM $table_customers ORDER BY customer_name ASC");
    }

    /**
     * Get a single customer
     */
    public function get_customer($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_customers WHERE id = %d",
            $customer_id
        ));
    }

    /**
     * Create a new customer
     */
    public function create_customer($data) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        $wpdb->insert($table_customers, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update a customer
     */
    public function update_customer($customer_id, $data) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->update($table_customers, $data, array('id' => $customer_id));
    }

    /**
     * Delete a customer
     */
    public function delete_customer($customer_id) {
        global $wpdb;
        $table_customers = $wpdb->prefix . 'staff_diary_customers';

        return $wpdb->delete($table_customers, array('id' => $customer_id));
    }

    /**
     * Get customer jobs count
     */
    public function get_customer_jobs_count($customer_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_diary} WHERE customer_id = %d",
            $customer_id
        ));
    }

    // ==================== ACCESSORY METHODS ====================

    /**
     * Get all accessories
     */
    public function get_all_accessories($active_only = false) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        $sql = "SELECT * FROM $table_accessories";
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY display_order ASC, accessory_name ASC";

        return $wpdb->get_results($sql);
    }

    /**
     * Get a single accessory
     */
    public function get_accessory($accessory_id) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_accessories WHERE id = %d",
            $accessory_id
        ));
    }

    /**
     * Create a new accessory
     */
    public function create_accessory($data) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        $wpdb->insert($table_accessories, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update an accessory
     */
    public function update_accessory($accessory_id, $data) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        return $wpdb->update($table_accessories, $data, array('id' => $accessory_id));
    }

    /**
     * Delete an accessory
     */
    public function delete_accessory($accessory_id) {
        global $wpdb;
        $table_accessories = $wpdb->prefix . 'staff_diary_accessories';

        return $wpdb->delete($table_accessories, array('id' => $accessory_id));
    }

    // ==================== JOB ACCESSORY METHODS ====================

    /**
     * Add accessories to a job
     */
    public function add_job_accessory($diary_entry_id, $accessory_id, $accessory_name, $quantity, $price_per_unit) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        $total_price = $quantity * $price_per_unit;

        $wpdb->insert($table_job_accessories, array(
            'diary_entry_id' => $diary_entry_id,
            'accessory_id' => $accessory_id,
            'accessory_name' => $accessory_name,
            'quantity' => $quantity,
            'price_per_unit' => $price_per_unit,
            'total_price' => $total_price
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get all accessories for a job
     */
    public function get_job_accessories($diary_entry_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_job_accessories WHERE diary_entry_id = %d ORDER BY id ASC",
            $diary_entry_id
        ));
    }

    /**
     * Delete job accessory
     */
    public function delete_job_accessory($job_accessory_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->delete($table_job_accessories, array('id' => $job_accessory_id));
    }

    /**
     * Delete all accessories for a job
     */
    public function delete_all_job_accessories($diary_entry_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->delete($table_job_accessories, array('diary_entry_id' => $diary_entry_id));
    }

    /**
     * Get total accessories cost for a job
     */
    public function get_job_accessories_total($diary_entry_id) {
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_price), 0) FROM $table_job_accessories WHERE diary_entry_id = %d",
            $diary_entry_id
        ));
    }

    // ==================== ORDER NUMBER METHODS ====================

    /**
     * Generate next order number
     */
    public function generate_order_number() {
        $prefix = get_option('wp_staff_diary_order_prefix', '');
        $current = get_option('wp_staff_diary_order_current', '01100');

        // Increment the order number
        $next_number = str_pad((int)$current + 1, strlen($current), '0', STR_PAD_LEFT);

        // Update the current order number
        update_option('wp_staff_diary_order_current', $next_number);

        return $prefix . $next_number;
    }

    /**
     * Check if order number exists
     */
    public function order_number_exists($order_number) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_diary} WHERE order_number = %s",
            $order_number
        ));

        return $count > 0;
    }

    // ==================== CALCULATION METHODS ====================

    /**
     * Calculate job subtotal (product + accessories)
     */
    public function calculate_job_subtotal($diary_entry_id) {
        global $wpdb;

        $entry = $this->get_entry($diary_entry_id);

        // Calculate product total
        $product_total = 0;
        if ($entry && $entry->sq_mtr_qty && $entry->price_per_sq_mtr) {
            $product_total = $entry->sq_mtr_qty * $entry->price_per_sq_mtr;
        }

        // Add fitting cost
        $fitting_cost = 0;
        if ($entry && isset($entry->fitting_cost)) {
            $fitting_cost = floatval($entry->fitting_cost);
        }

        // Get accessories total
        $accessories_total = $this->get_job_accessories_total($diary_entry_id);

        return $product_total + $fitting_cost + $accessories_total;
    }

    /**
     * Calculate job balance (subtotal + VAT - payments)
     */
    public function calculate_job_balance($diary_entry_id) {
        $subtotal = $this->calculate_job_subtotal($diary_entry_id);

        // Add VAT if enabled
        $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
        $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

        $total = $subtotal;
        if ($vat_enabled == '1') {
            $vat_amount = $subtotal * ($vat_rate / 100);
            $total = $subtotal + $vat_amount;
        }

        // Subtract payments
        $payments = $this->get_entry_total_payments($diary_entry_id);
        $balance = $total - $payments;

        return $balance;
    }

    /**
     * Mark entry as cancelled
     */
    public function cancel_entry($entry_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_diary,
            array(
                'is_cancelled' => 1,
                'status' => 'cancelled'
            ),
            array('id' => $entry_id),
            array('%d', '%s'),  // Data format
            array('%d')         // Where format
        );
    }

    // ==================== NOTIFICATION & REMINDER METHODS ====================

    /**
     * Log a notification
     */
    public function log_notification($diary_entry_id, $notification_type, $recipient, $method, $status, $error_message = null) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'staff_diary_notification_logs';

        $wpdb->insert($table_logs, array(
            'diary_entry_id' => $diary_entry_id,
            'notification_type' => $notification_type,
            'recipient' => $recipient,
            'method' => $method,
            'status' => $status,
            'error_message' => $error_message
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get notification logs for a job
     */
    public function get_notification_logs($diary_entry_id) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'staff_diary_notification_logs';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_logs WHERE diary_entry_id = %d ORDER BY sent_at DESC",
            $diary_entry_id
        ));
    }

    /**
     * Schedule a payment reminder
     */
    public function schedule_payment_reminder($diary_entry_id, $reminder_type, $scheduled_for) {
        global $wpdb;
        $table_schedule = $wpdb->prefix . 'staff_diary_reminder_schedule';

        // Check if reminder already scheduled
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_schedule
             WHERE diary_entry_id = %d
             AND reminder_type = %s
             AND status = 'pending'",
            $diary_entry_id,
            $reminder_type
        ));

        if ($exists) {
            // Update scheduled time
            return $wpdb->update(
                $table_schedule,
                array('scheduled_for' => $scheduled_for),
                array('id' => $exists)
            );
        }

        $wpdb->insert($table_schedule, array(
            'diary_entry_id' => $diary_entry_id,
            'reminder_type' => $reminder_type,
            'scheduled_for' => $scheduled_for,
            'status' => 'pending'
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get pending reminders
     */
    public function get_pending_reminders($before_time = null) {
        global $wpdb;
        $table_schedule = $wpdb->prefix . 'staff_diary_reminder_schedule';

        if ($before_time === null) {
            $before_time = current_time('mysql');
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_schedule
             WHERE status = 'pending'
             AND scheduled_for <= %s
             ORDER BY scheduled_for ASC",
            $before_time
        ));
    }

    /**
     * Mark reminder as sent
     */
    public function mark_reminder_sent($reminder_id) {
        global $wpdb;
        $table_schedule = $wpdb->prefix . 'staff_diary_reminder_schedule';

        return $wpdb->update(
            $table_schedule,
            array(
                'sent_at' => current_time('mysql'),
                'status' => 'sent'
            ),
            array('id' => $reminder_id)
        );
    }

    /**
     * Cancel scheduled reminders for a job
     */
    public function cancel_scheduled_reminders($diary_entry_id) {
        global $wpdb;
        $table_schedule = $wpdb->prefix . 'staff_diary_reminder_schedule';

        return $wpdb->update(
            $table_schedule,
            array('status' => 'cancelled'),
            array(
                'diary_entry_id' => $diary_entry_id,
                'status' => 'pending'
            )
        );
    }

    /**
     * Get jobs with outstanding balance for reminders
     */
    public function get_jobs_needing_reminders() {
        global $wpdb;

        // Get all non-cancelled, non-quotation jobs
        $jobs = $wpdb->get_results(
            "SELECT * FROM {$this->table_diary}
             WHERE is_cancelled = 0
             AND status != 'quotation'
             AND status != 'cancelled'
             ORDER BY job_date DESC"
        );

        $jobs_needing_reminders = array();

        foreach ($jobs as $job) {
            $balance = $this->calculate_job_balance($job->id);

            if ($balance > 0.01) {
                $job->balance = $balance;
                $jobs_needing_reminders[] = $job;
            }
        }

        return $jobs_needing_reminders;
    }

    // ==================== JOB TEMPLATE METHODS ====================

    /**
     * Get all job templates
     */
    public function get_all_job_templates($user_id = null) {
        global $wpdb;
        $table_templates = $wpdb->prefix . 'staff_diary_job_templates';

        if ($user_id) {
            // Get templates created by user OR global templates
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_templates
                 WHERE created_by = %d OR is_global = 1
                 ORDER BY template_name ASC",
                $user_id
            ));
        }

        // Get all templates (admin view)
        return $wpdb->get_results(
            "SELECT * FROM $table_templates ORDER BY template_name ASC"
        );
    }

    /**
     * Get a single job template
     */
    public function get_job_template($template_id) {
        global $wpdb;
        $table_templates = $wpdb->prefix . 'staff_diary_job_templates';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_templates WHERE id = %d",
            $template_id
        ));
    }

    /**
     * Create a job template
     */
    public function create_job_template($data) {
        global $wpdb;
        $table_templates = $wpdb->prefix . 'staff_diary_job_templates';

        $wpdb->insert($table_templates, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update a job template
     */
    public function update_job_template($template_id, $data) {
        global $wpdb;
        $table_templates = $wpdb->prefix . 'staff_diary_job_templates';

        return $wpdb->update($table_templates, $data, array('id' => $template_id));
    }

    /**
     * Delete a job template
     */
    public function delete_job_template($template_id) {
        global $wpdb;
        $table_templates = $wpdb->prefix . 'staff_diary_job_templates';

        return $wpdb->delete($table_templates, array('id' => $template_id));
    }

    // ==================== ACTIVITY LOG METHODS ====================

    /**
     * Log an activity for a job
     */
    public function log_activity($diary_entry_id, $activity_type, $activity_description, $old_value = null, $new_value = null, $metadata = null) {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'staff_diary_activity_log';

        $user_id = get_current_user_id();

        $wpdb->insert($table_activity, array(
            'diary_entry_id' => $diary_entry_id,
            'activity_type' => $activity_type,
            'activity_description' => $activity_description,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'user_id' => $user_id
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get activity log for a job
     */
    public function get_activity_log($diary_entry_id) {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'staff_diary_activity_log';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as user_name
             FROM $table_activity a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.diary_entry_id = %d
             ORDER BY a.created_at DESC",
            $diary_entry_id
        ));
    }

    /**
     * Get recent activity across all jobs
     */
    public function get_recent_activity($user_id = null, $limit = 20) {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'staff_diary_activity_log';

        if ($user_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, d.order_number, u.display_name as user_name
                 FROM $table_activity a
                 LEFT JOIN {$this->table_diary} d ON a.diary_entry_id = d.id
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                 WHERE d.user_id = %d
                 ORDER BY a.created_at DESC
                 LIMIT %d",
                $user_id,
                $limit
            ));
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, d.order_number, u.display_name as user_name
             FROM $table_activity a
             LEFT JOIN {$this->table_diary} d ON a.diary_entry_id = d.id
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             ORDER BY a.created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get all email templates
     *
     * @return array
     */
    public function get_all_email_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_email_templates';

        return $wpdb->get_results("SELECT * FROM $table ORDER BY template_name ASC");
    }

    /**
     * Get a single email template by ID
     *
     * @param int $id Template ID
     * @return object|null
     */
    public function get_email_template($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_email_templates';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Get email template by slug
     *
     * @param string $slug Template slug
     * @return object|null
     */
    public function get_email_template_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_email_templates';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE template_slug = %s AND is_active = 1",
            $slug
        ));
    }

    /**
     * Create email template
     *
     * @param array $data Template data
     * @return int|false Template ID on success, false on failure
     */
    public function create_email_template($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_email_templates';

        $result = $wpdb->insert($table, array(
            'template_name' => $data['template_name'],
            'template_slug' => $data['template_slug'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'is_active' => isset($data['is_active']) ? $data['is_active'] : 1,
            'is_default' => isset($data['is_default']) ? $data['is_default'] : 0
        ));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update email template
     *
     * @param int $id Template ID
     * @param array $data Template data
     * @return bool
     */
    public function update_email_template($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_email_templates';

        return $wpdb->update(
            $table,
            array(
                'template_name' => $data['template_name'],
                'template_slug' => $data['template_slug'],
                'subject' => $data['subject'],
                'body' => $data['body'],
                'is_active' => isset($data['is_active']) ? $data['is_active'] : 1
            ),
            array('id' => $id)
        ) !== false;
    }

    /**
     * Delete email template
     *
     * @param int $id Template ID
     * @return bool
     */
    public function delete_email_template($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_email_templates';

        // Don't delete default templates
        $template = $this->get_email_template($id);
        if ($template && $template->is_default) {
            return false;
        }

        return $wpdb->delete($table, array('id' => $id)) !== false;
    }

    /**
     * Log SMS message
     *
     * @param array $data SMS log data
     * @return int|false Log ID on success, false on failure
     */
    public function log_sms($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_sms_log';

        $result = $wpdb->insert($table, array(
            'diary_entry_id' => isset($data['diary_entry_id']) ? $data['diary_entry_id'] : null,
            'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : null,
            'phone_number' => $data['phone_number'],
            'message' => $data['message'],
            'status' => isset($data['status']) ? $data['status'] : 'pending',
            'twilio_sid' => isset($data['twilio_sid']) ? $data['twilio_sid'] : null,
            'cost' => isset($data['cost']) ? $data['cost'] : 0.0000,
            'error_message' => isset($data['error_message']) ? $data['error_message'] : null
        ));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get SMS logs for a diary entry
     *
     * @param int $diary_entry_id Diary entry ID
     * @return array
     */
    public function get_sms_logs($diary_entry_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_sms_log';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE diary_entry_id = %d ORDER BY sent_at DESC",
            $diary_entry_id
        ));
    }

    /**
     * Get total SMS cost for reporting
     *
     * @param string $start_date Optional start date
     * @param string $end_date Optional end date
     * @return float
     */
    public function get_total_sms_cost($start_date = null, $end_date = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'staff_diary_sms_log';

        $sql = "SELECT SUM(cost) as total FROM $table WHERE status = 'sent'";
        $params = array();

        if ($start_date && $end_date) {
            $sql .= " AND sent_at BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }

        if (!empty($params)) {
            $result = $wpdb->get_var($wpdb->prepare($sql, $params));
        } else {
            $result = $wpdb->get_var($sql);
        }

        return $result ? floatval($result) : 0.0;
    }
}
