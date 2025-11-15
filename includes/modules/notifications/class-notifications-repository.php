<?php
/**
 * Notifications Repository
 *
 * Handles database operations for notification logs.
 *
 * @since      2.2.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Notifications_Repository extends WP_Staff_Diary_Base_Repository {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('staff_diary_notification_logs');
    }

    /**
     * Log a notification
     *
     * @param array $data Notification data
     * @return int|false Log ID or false on failure
     */
    public function log_notification($data) {
        return $this->create($data);
    }

    /**
     * Get notification logs for a job
     *
     * @param int $entry_id Job entry ID
     * @return array
     */
    public function get_job_notifications($entry_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE diary_entry_id = %d ORDER BY sent_at DESC",
            $entry_id
        );
        return $this->wpdb->get_results($sql);
    }

    /**
     * Get recent notification logs
     *
     * @param int $limit Number of logs to retrieve
     * @return array
     */
    public function get_recent_logs($limit = 50) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} ORDER BY sent_at DESC LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($sql);
    }

    /**
     * Count notifications by type
     *
     * @param string $notification_type Notification type
     * @param string $status Status (sent, failed)
     * @return int
     */
    public function count_notifications($notification_type = null, $status = null) {
        $where = array();
        $params = array();

        if ($notification_type) {
            $where[] = "notification_type = %s";
            $params[] = $notification_type;
        }

        if ($status) {
            $where[] = "status = %s";
            $params[] = $status;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM {$this->table} $where_clause";

        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, $params);
        }

        return (int) $this->wpdb->get_var($sql);
    }
}
