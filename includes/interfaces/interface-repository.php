<?php
/**
 * Repository Interface
 *
 * Repositories handle all database operations for a specific entity.
 * They abstract the data layer from the business logic.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

interface WP_Staff_Diary_Repository_Interface {

    /**
     * Find a record by ID
     *
     * @param int $id The record ID
     * @return object|null The record or null if not found
     */
    public function find_by_id($id);

    /**
     * Get all records
     *
     * @param array $args Optional query arguments
     * @return array Array of records
     */
    public function get_all($args = array());

    /**
     * Create a new record
     *
     * @param array $data The data to insert
     * @return int|false The new record ID or false on failure
     */
    public function create($data);

    /**
     * Update an existing record
     *
     * @param int $id The record ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function update($id, $data);

    /**
     * Delete a record
     *
     * @param int $id The record ID
     * @return bool True on success, false on failure
     */
    public function delete($id);
}
