<?php
/**
 * Automated Discount Email Scheduler
 *
 * Schedules and sends automatic discount emails for outstanding quotes
 *
 * @since      2.3.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Auto_Discount_Scheduler {

    /**
     * Cron hook name
     */
    const CRON_HOOK = 'wp_staff_diary_auto_discount_check';

    public function __construct() {
        // Schedule cron event on initialization
        add_action('init', array($this, 'schedule_event'));

        // Register the cron handler
        add_action(self::CRON_HOOK, array($this, 'process_auto_discounts'));

        // Clear scheduled event on plugin deactivation
        register_deactivation_hook(WP_STAFF_DIARY_PATH . 'wp-staff-diary.php', array($this, 'clear_scheduled_event'));
    }

    /**
     * Schedule the daily cron event
     */
    public function schedule_event() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Schedule to run daily at 9:00 AM
            $timestamp = strtotime('tomorrow 09:00:00');
            wp_schedule_event($timestamp, 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Clear the scheduled event
     */
    public function clear_scheduled_event() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    /**
     * Process automatic discount emails
     */
    public function process_auto_discounts() {
        // Check if auto discount is enabled
        $auto_discount_enabled = get_option('wp_staff_diary_quote_enable_auto_discount', '0');

        if ($auto_discount_enabled != '1') {
            return; // Feature is disabled
        }

        // Get settings
        $days_threshold = get_option('wp_staff_diary_quote_auto_discount_days', '7');
        $discount_type = get_option('wp_staff_diary_quote_auto_discount_type', 'percentage');
        $discount_value = get_option('wp_staff_diary_quote_auto_discount_value', '5');

        // Find outstanding quotes that are X days old and haven't received discount
        $target_date = date('Y-m-d', strtotime("-{$days_threshold} days"));

        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';

        // Get quotes that:
        // 1. Have a quote_date
        // 2. Quote date is older than threshold
        // 3. Haven't been accepted yet (accepted_date IS NULL)
        // 4. Haven't received a discount email yet (discount_applied_date IS NULL)
        // 5. Are not cancelled
        $eligible_quotes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_diary
             WHERE quote_date IS NOT NULL
             AND quote_date <= %s
             AND accepted_date IS NULL
             AND discount_applied_date IS NULL
             AND is_cancelled = 0
             ORDER BY quote_date ASC",
            $target_date
        ));

        if (empty($eligible_quotes)) {
            return; // No eligible quotes
        }

        // Process each eligible quote
        $sent_count = 0;
        $failed_count = 0;

        foreach ($eligible_quotes as $quote) {
            // Check if customer has an email
            if (empty($quote->customer_id)) {
                continue;
            }

            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT customer_email FROM {$wpdb->prefix}staff_diary_customers WHERE id = %d",
                $quote->customer_id
            ));

            if (!$customer || empty($customer->customer_email)) {
                continue;
            }

            // Send discount email
            $result = WP_Staff_Diary_Email_Template_Processor::send_discount_email(
                $quote->id,
                $discount_type,
                $discount_value
            );

            if (!is_wp_error($result) && $result === true) {
                $sent_count++;
            } else {
                $failed_count++;

                // Log the failure
                error_log(sprintf(
                    'WP Staff Diary: Failed to send auto-discount email for quote #%s (Entry ID: %d). Error: %s',
                    $quote->order_number,
                    $quote->id,
                    is_wp_error($result) ? $result->get_error_message() : 'Unknown error'
                ));
            }
        }

        // Log summary
        if ($sent_count > 0 || $failed_count > 0) {
            error_log(sprintf(
                'WP Staff Diary Auto-Discount: Processed %d eligible quotes. Sent: %d, Failed: %d',
                count($eligible_quotes),
                $sent_count,
                $failed_count
            ));
        }
    }

    /**
     * Manually trigger auto discount check (for testing/debugging)
     * Can be called via admin AJAX or custom admin action
     */
    public static function manual_trigger() {
        $scheduler = new self();
        $scheduler->process_auto_discounts();

        return array(
            'success' => true,
            'message' => 'Auto-discount check completed. Check error logs for details.'
        );
    }
}
