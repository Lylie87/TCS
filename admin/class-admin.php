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

        wp_enqueue_style(
            $this->plugin_name . '-v2',
            WP_STAFF_DIARY_URL . 'assets/css/admin-v2-additions.css',
            array($this->plugin_name),
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

        // Submenu - Customers (only for staff and above)
        add_submenu_page(
            'wp-staff-diary',
            'Customers',
            'Customers',
            'read',
            'wp-staff-diary-customers',
            array($this, 'display_customers_page')
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
     * Display Customers page
     */
    public function display_customers_page() {
        require_once WP_STAFF_DIARY_PATH . 'admin/views/customers.php';
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

        // Prepare data for main entry
        $data = array(
            'user_id' => $user_id,
            'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
            'fitter_id' => !empty($_POST['fitter_id']) ? intval($_POST['fitter_id']) : null,
            'job_date' => sanitize_text_field($_POST['job_date']),
            'job_time' => !empty($_POST['job_time']) ? sanitize_text_field($_POST['job_time']) : null,
            'fitting_date' => !empty($_POST['fitting_date']) ? sanitize_text_field($_POST['fitting_date']) : null,
            'fitting_time_period' => !empty($_POST['fitting_time_period']) ? sanitize_text_field($_POST['fitting_time_period']) : null,
            'area' => !empty($_POST['area']) ? sanitize_text_field($_POST['area']) : null,
            'size' => !empty($_POST['size']) ? sanitize_text_field($_POST['size']) : null,
            'product_description' => !empty($_POST['product_description']) ? sanitize_textarea_field($_POST['product_description']) : null,
            'sq_mtr_qty' => !empty($_POST['sq_mtr_qty']) ? floatval($_POST['sq_mtr_qty']) : null,
            'price_per_sq_mtr' => !empty($_POST['price_per_sq_mtr']) ? floatval($_POST['price_per_sq_mtr']) : null,
            'notes' => !empty($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : null,
            'status' => sanitize_text_field($_POST['status'])
        );

        if ($entry_id > 0) {
            // Update existing entry (don't change order number)
            $result = $this->db->update_entry($entry_id, $data);

            if ($result !== false) {
                // Update job accessories if provided
                if (isset($_POST['accessories']) && is_array($_POST['accessories'])) {
                    // Delete existing accessories for this job
                    $this->db->delete_all_job_accessories($entry_id);

                    // Add new accessories
                    foreach ($_POST['accessories'] as $accessory) {
                        if (!empty($accessory['accessory_id'])) {
                            $this->db->add_job_accessory(
                                $entry_id,
                                intval($accessory['accessory_id']),
                                sanitize_text_field($accessory['accessory_name']),
                                floatval($accessory['quantity']),
                                floatval($accessory['price_per_unit'])
                            );
                        }
                    }
                }

                wp_send_json_success(array(
                    'entry_id' => $entry_id,
                    'message' => 'Entry updated successfully'
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to update entry'));
            }
        } else {
            // Create new entry - generate order number
            $order_number = $this->db->generate_order_number();
            $data['order_number'] = $order_number;

            $new_id = $this->db->create_entry($data);

            if ($new_id) {
                // Add job accessories if provided
                if (isset($_POST['accessories']) && is_array($_POST['accessories'])) {
                    foreach ($_POST['accessories'] as $accessory) {
                        if (!empty($accessory['accessory_id'])) {
                            $this->db->add_job_accessory(
                                $new_id,
                                intval($accessory['accessory_id']),
                                sanitize_text_field($accessory['accessory_name']),
                                floatval($accessory['quantity']),
                                floatval($accessory['price_per_unit'])
                            );
                        }
                    }
                }

                wp_send_json_success(array(
                    'entry_id' => $new_id,
                    'order_number' => $order_number,
                    'message' => 'Entry created successfully'
                ));
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
     * AJAX: Cancel diary entry (soft delete)
     */
    public function cancel_diary_entry() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();

        // Verify ownership or admin
        $entry = $this->db->get_entry($entry_id);
        if (!$entry || ($entry->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $result = $this->db->cancel_entry($entry_id);
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Entry cancelled successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to cancel entry'));
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
            // Get customer information if linked
            if ($entry->customer_id) {
                $customer = $this->db->get_customer($entry->customer_id);
                $entry->customer = $customer;
            }

            // Get images for this entry
            $images = $this->db->get_entry_images($entry_id);
            $entry->images = $images;

            // Get job accessories
            $accessories = $this->db->get_job_accessories($entry_id);
            $entry->accessories = $accessories;

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

            // Calculate financial totals
            $subtotal = $this->db->calculate_job_subtotal($entry_id);
            $entry->subtotal = $subtotal;

            // Calculate VAT
            $vat_enabled = get_option('wp_staff_diary_vat_enabled', '1');
            $vat_rate = get_option('wp_staff_diary_vat_rate', '20');

            $vat_amount = 0;
            $total = $subtotal;

            if ($vat_enabled == '1') {
                $vat_amount = $subtotal * ($vat_rate / 100);
                $total = $subtotal + $vat_amount;
            }

            $entry->vat_rate = $vat_rate;
            $entry->vat_amount = $vat_amount;
            $entry->total = $total;

            // Get total payments and calculate balance
            $total_payments = $this->db->get_entry_total_payments($entry_id);
            $entry->total_payments = $total_payments;
            $entry->balance = $total - $total_payments;

            // Format dates according to settings
            $date_format = get_option('wp_staff_diary_date_format', 'd/m/Y');

            if ($entry->job_date) {
                $entry->job_date_formatted = date($date_format, strtotime($entry->job_date));
            }

            if ($entry->fitting_date) {
                $entry->fitting_date_formatted = date($date_format, strtotime($entry->fitting_date));
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
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $status_label = sanitize_text_field($_POST['label']);

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
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

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
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $method_label = sanitize_text_field($_POST['label']);

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
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

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

    // ==================== FITTER AJAX HANDLERS ====================

    /**
     * AJAX: Add fitter
     */
    public function add_fitter() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $name = sanitize_text_field($_POST['name']);
        $color = sanitize_hex_color($_POST['color']);

        if (empty($name)) {
            wp_send_json_error('Fitter name is required');
        }

        if (empty($color)) {
            $color = '#3498db'; // Default color
        }

        // Get current fitters
        $fitters = get_option('wp_staff_diary_fitters', array());

        // Add new fitter
        $fitters[] = array(
            'name' => $name,
            'color' => $color
        );

        update_option('wp_staff_diary_fitters', $fitters);
        wp_send_json_success('Fitter added successfully');
    }

    /**
     * AJAX: Delete fitter
     */
    public function delete_fitter() {
        check_ajax_referer('wp_staff_diary_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $index = intval($_POST['index']);

        // Get current fitters
        $fitters = get_option('wp_staff_diary_fitters', array());

        // Check if index exists
        if (!isset($fitters[$index])) {
            wp_send_json_error('Fitter not found');
        }

        // Check if any jobs are assigned to this fitter
        global $wpdb;
        $table_diary = $wpdb->prefix . 'staff_diary_entries';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_diary WHERE fitter_id = %d",
            $index
        ));

        if ($count > 0) {
            wp_send_json_error("Cannot delete this fitter. There are $count job(s) assigned to them.");
        }

        // Remove the fitter
        unset($fitters[$index]);
        $fitters = array_values($fitters); // Re-index array
        update_option('wp_staff_diary_fitters', $fitters);
        wp_send_json_success('Fitter deleted successfully');
    }

    // ==================== ACCESSORY AJAX HANDLERS ====================

    /**
     * AJAX: Add accessory
     */
    public function add_accessory() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $accessory_name = sanitize_text_field($_POST['accessory_name']);
        $price = floatval($_POST['price']);

        if (empty($accessory_name)) {
            wp_send_json_error(array('message' => 'Accessory name is required'));
        }

        $data = array(
            'accessory_name' => $accessory_name,
            'price' => $price,
            'is_active' => 1,
            'display_order' => 0
        );

        $accessory_id = $this->db->create_accessory($data);

        if ($accessory_id) {
            $accessory = $this->db->get_accessory($accessory_id);
            wp_send_json_success(array(
                'message' => 'Accessory added successfully',
                'accessory' => $accessory
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to add accessory'));
        }
    }

    /**
     * AJAX: Update accessory
     */
    public function update_accessory() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $accessory_id = intval($_POST['accessory_id']);
        $accessory_name = sanitize_text_field($_POST['accessory_name']);
        $price = floatval($_POST['price']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($accessory_name)) {
            wp_send_json_error(array('message' => 'Accessory name is required'));
        }

        $data = array(
            'accessory_name' => $accessory_name,
            'price' => $price,
            'is_active' => $is_active
        );

        $result = $this->db->update_accessory($accessory_id, $data);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Accessory updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update accessory'));
        }
    }

    /**
     * AJAX: Delete accessory
     */
    public function delete_accessory() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $accessory_id = intval($_POST['accessory_id']);

        // Check if any jobs are using this accessory
        global $wpdb;
        $table_job_accessories = $wpdb->prefix . 'staff_diary_job_accessories';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_job_accessories WHERE accessory_id = %d",
            $accessory_id
        ));

        if ($count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this accessory. It's being used in $count job(s)."
            ));
        }

        $result = $this->db->delete_accessory($accessory_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Accessory deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete accessory'));
        }
    }

    // ==================== PDF GENERATION AJAX HANDLER ====================

    /**
     * AJAX: Generate PDF job sheet
     */
    public function generate_pdf() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $entry_id = intval($_POST['entry_id']);

        // Verify permissions
        $entry = $this->db->get_entry($entry_id);
        $user_id = get_current_user_id();

        if (!$entry || ($entry->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Create PDF generator
        $pdf_generator = new WP_Staff_Diary_PDF_Generator();

        if (!$pdf_generator->is_available()) {
            wp_send_json_error(array(
                'message' => 'PDF generation not available. TCPDF library not installed. Please see libs/README.md for installation instructions.'
            ));
        }

        // Generate and save PDF
        $result = $pdf_generator->generate_job_sheet($entry_id, 'F');

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'PDF generated successfully',
                'url' => $result['url']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Download PDF (direct output)
     */
    public function download_pdf() {
        if (!isset($_GET['entry_id']) || !isset($_GET['nonce'])) {
            wp_die('Invalid request');
        }

        if (!wp_verify_nonce($_GET['nonce'], 'wp_staff_diary_pdf_' . $_GET['entry_id'])) {
            wp_die('Invalid nonce');
        }

        $entry_id = intval($_GET['entry_id']);
        $entry = $this->db->get_entry($entry_id);
        $user_id = get_current_user_id();

        if (!$entry || ($entry->user_id != $user_id && !current_user_can('edit_users'))) {
            wp_die('Permission denied');
        }

        // Create PDF generator
        $pdf_generator = new WP_Staff_Diary_PDF_Generator();

        if (!$pdf_generator->is_available()) {
            wp_die('PDF generation not available. Please install TCPDF library.');
        }

        // Generate and output PDF (D = download)
        $pdf_generator->generate_job_sheet($entry_id, 'D');
    }

    // ==================== CUSTOMER AJAX HANDLERS ====================

    /**
     * AJAX: Search customers
     */
    public function search_customers() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $customers = $this->db->get_all_customers($search);

        wp_send_json_success(array('customers' => $customers));
    }

    /**
     * AJAX: Add customer
     */
    public function add_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_name = sanitize_text_field($_POST['customer_name']);
        $address_line_1 = isset($_POST['address_line_1']) ? sanitize_text_field($_POST['address_line_1']) : '';
        $address_line_2 = isset($_POST['address_line_2']) ? sanitize_text_field($_POST['address_line_2']) : '';
        $address_line_3 = isset($_POST['address_line_3']) ? sanitize_text_field($_POST['address_line_3']) : '';
        $postcode = isset($_POST['postcode']) ? sanitize_text_field($_POST['postcode']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($customer_name)) {
            wp_send_json_error(array('message' => 'Customer name is required'));
        }

        $data = array(
            'customer_name' => $customer_name,
            'address_line_1' => $address_line_1,
            'address_line_2' => $address_line_2,
            'address_line_3' => $address_line_3,
            'postcode' => $postcode,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'notes' => $notes
        );

        $customer_id = $this->db->create_customer($data);

        if ($customer_id) {
            $customer = $this->db->get_customer($customer_id);
            wp_send_json_success(array(
                'message' => 'Customer added successfully',
                'customer' => $customer
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to add customer'));
        }
    }

    /**
     * AJAX: Get customer
     */
    public function get_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_id = intval($_POST['customer_id']);
        $customer = $this->db->get_customer($customer_id);

        if ($customer) {
            // Get job count for this customer
            $job_count = $this->db->get_customer_jobs_count($customer_id);
            $customer->job_count = $job_count;

            wp_send_json_success(array('customer' => $customer));
        } else {
            wp_send_json_error(array('message' => 'Customer not found'));
        }
    }

    /**
     * AJAX: Update customer
     */
    public function update_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        $customer_id = intval($_POST['customer_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $address_line_1 = isset($_POST['address_line_1']) ? sanitize_text_field($_POST['address_line_1']) : '';
        $address_line_2 = isset($_POST['address_line_2']) ? sanitize_text_field($_POST['address_line_2']) : '';
        $address_line_3 = isset($_POST['address_line_3']) ? sanitize_text_field($_POST['address_line_3']) : '';
        $postcode = isset($_POST['postcode']) ? sanitize_text_field($_POST['postcode']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($customer_name)) {
            wp_send_json_error(array('message' => 'Customer name is required'));
        }

        $data = array(
            'customer_name' => $customer_name,
            'address_line_1' => $address_line_1,
            'address_line_2' => $address_line_2,
            'address_line_3' => $address_line_3,
            'postcode' => $postcode,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'notes' => $notes
        );

        $result = $this->db->update_customer($customer_id, $data);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Customer updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update customer'));
        }
    }

    /**
     * AJAX: Delete customer
     */
    public function delete_customer() {
        check_ajax_referer('wp_staff_diary_nonce', 'nonce');

        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $customer_id = intval($_POST['customer_id']);

        // Check if customer has any jobs
        $job_count = $this->db->get_customer_jobs_count($customer_id);

        if ($job_count > 0) {
            wp_send_json_error(array(
                'message' => "Cannot delete this customer. They have $job_count job(s) associated with them."
            ));
        }

        $result = $this->db->delete_customer($customer_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Customer deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete customer'));
        }
    }
}
