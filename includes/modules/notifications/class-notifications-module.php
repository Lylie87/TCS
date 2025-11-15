<?php
/**
 * Notifications Module
 *
 * Manages email and SMS notifications for appointments and payments.
 *
 * @since      2.2.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Notifications_Module extends WP_Staff_Diary_Base_Module {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'notifications';

        $repository = new WP_Staff_Diary_Notifications_Repository();
        $this->controller = new WP_Staff_Diary_Notifications_Controller($repository);
    }

    /**
     * Initialize the module
     */
    public function init() {
        // Hook into payment creation to send confirmation
        add_action('wp_staff_diary_payment_recorded', array($this, 'on_payment_recorded'), 10, 2);

        // Hook into job save to schedule reminders
        add_action('wp_staff_diary_job_saved', array($this, 'on_job_saved'), 10, 2);
    }

    /**
     * Register hooks with the loader
     *
     * @param WP_Staff_Diary_Loader $loader The loader instance
     */
    public function register_hooks($loader) {
        // AJAX for test notification
        $this->register_ajax_action($loader, 'send_test_notification', 'send_test_notification');
    }

    /**
     * Handle payment recorded event
     *
     * @param int $payment_id Payment ID
     * @param int $entry_id Job entry ID
     */
    public function on_payment_recorded($payment_id, $entry_id) {
        // Check if payment confirmations are enabled
        $payment_confirmations_enabled = get_option('wp_staff_diary_payment_confirmations_enabled', '0');

        if ($payment_confirmations_enabled == '1') {
            $method = get_option('wp_staff_diary_payment_confirmation_method', 'email');
            $this->controller->send_payment_confirmation($payment_id, $method);
        }
    }

    /**
     * Handle job saved event
     *
     * @param int $entry_id Job entry ID
     * @param array $data Job data
     */
    public function on_job_saved($entry_id, $data) {
        // Check if appointment reminders are enabled
        $reminders_enabled = get_option('wp_staff_diary_appointment_reminders_enabled', '0');

        if ($reminders_enabled == '1' && !empty($data['fitting_date'])) {
            // Schedule a reminder for 24 hours before the appointment
            $fitting_date = strtotime($data['fitting_date']);
            $reminder_time = $fitting_date - (24 * 60 * 60); // 24 hours before

            // Only schedule if the fitting date is in the future
            if ($reminder_time > current_time('timestamp')) {
                // Use WordPress cron to schedule the reminder
                wp_schedule_single_event(
                    $reminder_time,
                    'wp_staff_diary_send_appointment_reminder',
                    array($entry_id)
                );
            }
        }
    }

    /**
     * Get the module name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get the controller instance
     *
     * @return WP_Staff_Diary_Notifications_Controller
     */
    public function get_controller() {
        return $this->controller;
    }
}
