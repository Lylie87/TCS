<?php
/**
 * Base Repository
 *
 * Provides common database operations for all repositories.
 * Handles standard CRUD operations and query building.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

abstract class WP_Staff_Diary_Base_Repository implements WP_Staff_Diary_Repository_Interface {

    /**
     * The table name (without prefix)
     *
     * @var string
     */
    protected $table_name;

    /**
     * The full table name (with prefix)
     *
     * @var string
     */
    protected $table;

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    protected $wpdb;

    /**
     * Constructor
     *
     * @param string $table_name The table name without prefix
     */
    public function __construct($table_name) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $table_name;
        $this->table = $wpdb->prefix . $table_name;
    }

    /**
     * Find a record by ID
     *
     * @param int $id The record ID
     * @return object|null The record or null if not found
     */
    public function find_by_id($id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        );
        return $this->wpdb->get_row($sql);
    }

    /**
     * Get all records
     *
     * @param array $args Optional query arguments
     * @return array Array of records
     */
    public function get_all($args = array()) {
        $defaults = array(
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM {$this->table}";
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= " LIMIT {$args['limit']} OFFSET {$args['offset']}";

        return $this->wpdb->get_results($sql);
    }

    /**
     * Create a new record
     *
     * @param array $data The data to insert
     * @return int|false The new record ID or false on failure
     */
    public function create($data) {
        $result = $this->wpdb->insert(
            $this->table,
            $data
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update an existing record
     *
     * @param int $id The record ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $result = $this->wpdb->update(
            $this->table,
            $data,
            array('id' => $id)
        );

        return $result !== false;
    }

    /**
     * Delete a record
     *
     * @param int $id The record ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        $result = $this->wpdb->delete(
            $this->table,
            array('id' => $id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Get count of records
     *
     * @param array $where Optional WHERE conditions
     * @return int The count
     */
    public function count($where = array()) {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if (!empty($where)) {
            $conditions = array();
            $values = array();

            foreach ($where as $field => $value) {
                $conditions[] = "$field = %s";
                $values[] = $value;
            }

            $sql .= " WHERE " . implode(' AND ', $conditions);
            $sql = $this->wpdb->prepare($sql, $values);
        }

        return (int) $this->wpdb->get_var($sql);
    }
}
