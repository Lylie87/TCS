<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @since      1.0.0
 * @package    WP_Staff_Diary
 */
class WP_Staff_Diary_Admin {

    private $plugin_name;
    private $version;
    private $db;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new WP_Staff_Diary_Database();
    }

    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_STAFF_DIARY_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WP_STAFF_DIARY_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Get statuses and payment methods from settings
        $statuses = get_option('wp_staff_diary_statuses', array(
            'pending' => 'Pending',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ));

        $payment_methods = get_option('wp_staff_diary_payment_methods', array(
            'cash' => 'Cash',
            'bank-transfer' => 'Bank Transfer',
            'card-payment' => 'Card Payment'
        ));

        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'wpStaffDiary', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_staff_diary_nonce'),
            'statuses' => $statuses,
            'paymentMethods' => $payment_methods
        ));

        // Enqueue WordPress media uploader
        wp_enqueue_media();
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'wp_staff_diary_dashboard_widget',
            'My Jobs This Week',
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $current_user = wp_get_current_user();
        $db = new WP_Staff_Diary_Database();

        // Get current week
        $today = new DateTime();
        $week_start = clone $today;
        $week_start->modify('monday this week');

        // Get all entries for the current week
        $start_date = $week_start->format('Y-m-d');
        $end_date = clone $week_start;
        $end_date->modify('+6 days');
        $end_date_str = $end_date->format('Y-m-d');

        $entries = $db->get_user_entries($current_user->ID, $start_date, $end_date_str);

        // Organize entries by date
        $entries_by_date = array();
        foreach ($entries as $entry) {
            $date_key = $entry->job_date;
            if (!isset($entries_by_date[$date_key])) {
                $entries_by_date[$date_key] = array();
            }
            $entries_by_date[$date_key][] = $entry;
        }

        // Sort entries by time within each day
        foreach ($entries_by_date as $date => $day_entries) {
            usort($day_entries, function($a, $b) {
                if ($a->job_time === null) return 1;
                if ($b->job_time === null) return -1;
                return strcmp($a->job_time, $b->job_time);
            });
            $entries_by_date[$date] = $day_entries;
        }

        // Include the dashboard widget view
        include WP_STAFF_DIARY_PATH . 'admin/views/dashboard-widget.php';
    }

    /**
     * Add admin menu pages
     */
    public function add_plugin_admin_menu() {
        // Main menu - My Jobs
        add_menu_page(
            'My Jobs',
            'Job Planner',
            'read',
            'wp-staff-diary',
            array($this, 'display_my_diary_page'),
            'dashicons-calendar-alt',
            30
        );

        // Submenu - My Jobs (duplicate for clarity)
        add_submenu_page(
            'wp-staff-diary',
            'My Jobs',
            'My Jobs',
            'read',
            'wp-staff-diary',
            array($this, 'display_my_diary_page')
        );

        // Submenu - All Staff Jobs (only for managers/admins)
        add_submenu_page(
            'wp-staff-diary',
            'All Staff Jobs',
            'All Staff Jobs',
            'edit_users',
            'wp-staff-diary-overview',
            array($this, 'display_overview_page')
        );

        // Submenu - Settings (only for admins)
        add_submenu_page(
            'wp-staff-diary',
            'Settings',
            'Settings',
            'manage_options',
            'wp-staff-diary-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display My Diary page
     */
    public function display_my_diary_page() {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'calendar';

        if ($view === 'list') {
            require_once WP_STAFF_DIARY_PATH . 'admin/views/my-diary.php';
        } else {
            require_once WP_STAFF_DIARY_PATH . 'admin/views/calendar-view.php';
        }
    }

    /**
     * Display Staff Overview page
     */
    public function display_overview_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/staff-overview.php';
    }

    /**
     * Display Settings page
     */
    public function display_settings_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/settings.php';
    }

    /**
     * AJAX: Save diary entry
     */
    public function save_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $user_id = get_current_user_id();

        // Prepare data
        $data = array(
            'user_id' => $user_id,
            'job_date' => sanitize_text_field($_POST['job_date']),
            'job_time' => !empty($_POST['job_time']) ? sanitize_text_field($_POST['job_time']) : null,
            'client_name' => sanitize_text_field($_POST['client_name']),
            'client_address' => sanitize_textarea_field($_POST['client_address']),
            'client_phone' => sanitize_text_field($_POST['client_phone']),
            'job_description' => sanitize_textarea_field($_POST['job_description']),
            'plans' => sanitize_textarea_field($_POST['plans']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => sanitize_text_field($_POST['status'])
        );

        if ($entry_id > 0) {
            // Update existing entry
            $result = $this->db->update_entry($entry_id, $data);
            if ($result !== false) {
                wp_send_json_success(array('entry_id' => $entry_id, 'message' => 'Entry updated successfully'));
            } else {
                wp_send_json_error(array('message' => 'Failed to update entry'));
            }
        } else {
            // Create new entry
            $new_id = $this->db->create_entry($data);
            if ($new_id) {
                wp_send_json_success(array('entry_id' => $new_id, 'message' => 'Entry created successfully'));
            } else {
                wp_send_json_error(array('message' => 'Failed to create entry'));
            }
        }
    }

    /**
     * AJAX: Delete diary entry
     */
    public function delete_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();

        // Verify ownership or admin
        $entry = $this->db->get_entry($entry_id);
        if (!$entry || ($entry->user_id != $user_id && !current_user_can('delete_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $result = $this->db->delete_entry($entry_id);
        if ($result) {
            wp_send_json_success(array('message' => 'Entry deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete entry'));
        }
    }

    /**
     * AJAX: Upload job image
     */
    public function upload_job_image() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['image'];
        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // Create attachment
            $attachment = array(
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_file_name($movefile['file']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $movefile['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Save to database
            $image_id = $this->db->add_image($entry_id, $movefile['url'], $attach_id, '');

            wp_send_json_success(array(
                'image_id' => $image_id,
                'url' => $movefile['url'],
                'attachment_id' => $attach_id
            ));
        } else {
            wp_send_json_error(array('message' => $movefile['error']));
        }
    }

    /**
     * AJAX: Get diary entry
     */
    public function get_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $entry = $this->db->get_entry($entry_id);

        if ($entry) {
            // Get images for this entry
            $images = $this->db->get_entry_images($entry_id);
            $entry->images = $images;

            // Get payments for this entry
            $payments = $this->db->get_entry_payments($entry_id);

            // Add user info and formatted dates to payments
            foreach ($payments as $payment) {
                $user = get_userdata($payment->recorded_by);
                $payment->recorded_by_name = $user ? $user->display_name : 'Unknown';

                $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
                $time_format = get_option('wp_staff_diary_time_format', 'H:i');
                $payment->recorded_at_formatted = date("$date_format $time_format", strtotime($payment->recorded_at));
            }

            $entry->payments = $payments;
            $entry->total_payments = $this->db->get_entry_total_payments($entry_id);

            // Format date according to settings
            $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
            if ($entry->job_date) {
                $entry->job_date_formatted = date($date_format, strtotime($entry->job_date));
            }

            // Format time according to settings
            $time_format = get_option('wp_staff_diary_time_format', 'H:i');
            if ($entry->job_time) {
                $entry->job_time_formatted = date($time_format, strtotime($entry->job_time));
            }

            wp_send_json_success($entry);
        } else {
            wp_send_json_error(array('message' => 'Entry not found'));
        }
    }

    /**
     * AJAX: Delete diary image
     */
    public function delete_diary_image() {
        try {
            check_ajax_referer('wp_staff_diary_nonce', 'nonce');

            if (!isset($_POST['image_id'])) {
                wp_send_json_error(array('message' => 'No image ID provided'));
                return;
            }

            $image_id = intval($_POST['image_id']);

            if ($image_id <= 0) {
                wp_send_json_error(array('message' => 'Invalid image ID'));
                return;
            }

            // Get image details to delete attachment
            global $wpdb;
            $table = $wpdb->prefix . 'staff_diary_images';
            $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $image_id));

            if (!$image) {
                wp_send_json_error(array('message' => 'Image not found in database'));
                return;
            }

            // Delete WordPress attachment if exists
            if ($image->attachment_id) {
                wp_delete_attachment($image->attachment_id, true);
            }

            // Delete from database
            $result = $this->db->delete_image($image_id);

            if ($result !== false) {
                wp_send_json_success(array('message' => 'Image deleted successfully'));
            } else {
                wp_send_json_error(array('message' => 'Database deletion failed'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX: Add payment
     */
    public function add_payment() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $payment_type = sanitize_text_field($_POST['payment_type']);
        $notes = sanitize_textarea_field($_POST['notes']);
        $recorded_by = get_current_user_id();

        $payment_id = $this->db->add_payment($entry_id, $amount, $payment_method, $payment_type, $notes, $recorded_by);

        if ($payment_id) {
            // Get the payment with user info
            $payment = $this->get_payment_with_user_info($payment_id);
            wp_send_json_success(array(
                'message' => 'Payment recorded successfully',
                'payment' => $payment
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to record payment'));
        }
    }

    /**
     * AJAX: Delete payment
     */
    public function delete_payment() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $payment_id = intval($_POST['payment_id']);

        $result = $this->db->delete_payment($payment_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Payment deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete payment'));
        }
    }

    /**
     * Get payment with user info
     */
    private function get_payment_with_user_info($payment_id) {
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';

        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE id = %d",
            $payment_id
        ));

        if ($payment) {
            $user = get_userdata($payment->recorded_by);
            $payment->recorded_by_name = $user ? $user->display_name : 'Unknown';

            // Format date
            $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');
            $time_format = get_option('wp_staff_diary_time_format', 'H:i');
            $payment->recorded_at_formatted = date("$date_format $time_format", strtotime($payment->recorded_at));
        }

        return $payment;
    }

    /**
     * AJAX: Add custom status
     */
    public function add_status() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $status_label = sanitize_text_field($_POST['status_label']);

        if (empty($status_label)) {
            wp_send_json_error(array('message' => 'Status name is required'));
        }

        // Get current statuses
        $statuses = get_option('wp_staff_diary_statuses', array(
            'pending' => 'Pending',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ));

        // Create key from label
        $status_key = sanitize_title($status_label);

        // Check if status already exists
        if (isset($statuses[$status_key])) {
            wp_send_json_error(array('message' => 'Status already exists'));
        }

        // Add new status
        $statuses[$status_key] = $status_label;
        update_option('wp_staff_diary_statuses', $statuses);

        wp_send_json_success(array(
            'message' => 'Status added successfully',
            'status_key' => $status_key,
            'status_label' => $status_label
        ));
    }

    /**
     * AJAX: Delete custom status
     */
    public function delete_status() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $status_key = sanitize_text_field($_POST['status_key']);

        // Prevent deletion of default statuses
        $default_statuses = array('pending', 'in-progress', 'completed', 'cancelled');
        if (in_array($status_key, $default_statuses)) {
            wp_send_json_error(array('message' => 'Cannot delete default statuses'));
        }

        // Check if any jobs are using this status (excluding completed and cancelled)
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_diary WHERE status = %s AND status NOT IN ('completed', 'cancelled')",
            $status_key
        ));

        if ($count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this status. There are $count active job(s) using it. Please change the status of these jobs first or complete/cancel them."
            ));
        }

        // Get current statuses
        $statuses = get_option('wp_staff_diary_statuses', array());

        // Remove the status
        if (isset($statuses[$status_key])) {
            unset($statuses[$status_key]);
            update_option('wp_staff_diary_statuses', $statuses);
            wp_send_json_success(array('message' => 'Status deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Status not found'));
        }
    }

    /**
     * AJAX: Add payment method
     */
    public function add_payment_method() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $method_label = sanitize_text_field($_POST['method_label']);

        if (empty($method_label)) {
            wp_send_json_error(array('message' => 'Payment method name is required'));
        }

        // Get current payment methods
        $payment_methods = get_option('wp_staff_diary_payment_methods', array(
            'cash' => 'Cash',
            'bank-transfer' => 'Bank Transfer',
            'card-payment' => 'Card Payment'
        ));

        // Create key from label
        $method_key = sanitize_title($method_label);

        // Check if method already exists
        if (isset($payment_methods[$method_key])) {
            wp_send_json_error(array('message' => 'Payment method already exists'));
        }

        // Add new payment method
        $payment_methods[$method_key] = $method_label;
        update_option('wp_staff_diary_payment_methods', $payment_methods);

        wp_send_json_success(array(
            'message' => 'Payment method added successfully',
            'method_key' => $method_key,
            'method_label' => $method_label
        ));
    }

    /**
     * AJAX: Delete payment method
     */
    public function delete_payment_method() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $method_key = sanitize_text_field($_POST['method_key']);

        // Check if any payments are using this method
        global $wpdb;
        $table_payments = $wpdb->prefix . 'staff_diary_payments';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_payments WHERE payment_method = %s",
            $method_key
        ));

        if ($count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this payment method. There are $count payment(s) using it."
            ));
        }

        // Get current payment methods
        $payment_methods = get_option('wp_staff_diary_payment_methods', array());

        // Remove the payment method
        if (isset($payment_methods[$method_key])) {
            unset($payment_methods[$method_key]);
            update_option('wp_staff_diary_payment_methods', $payment_methods);
            wp_send_json_success(array('message' => 'Payment method deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Payment method not found'));
        }
    }
}
