<?php
/**
 * Images Repository
 *
 * Handles all database operations for job images.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Images_Repository extends WP_Staff_Diary_Base_Repository {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('staff_diary_images');
    }

    /**
     * Add an image to a job
     *
     * @param int $diary_entry_id The diary entry ID
     * @param string $image_url Image URL
     * @param int $attachment_id WordPress attachment ID
     * @param string $caption Optional caption
     * @return int|false Image ID or false on failure
     */
    public function add_image($diary_entry_id, $image_url, $attachment_id = null, $caption = '') {
        $data = array(
            'diary_entry_id' => $diary_entry_id,
            'image_url' => $image_url,
            'attachment_id' => $attachment_id,
            'image_caption' => $caption,  // Database column is image_caption, not caption
            'uploaded_at' => current_time('mysql')
        );

        return $this->create($data);
    }

    /**
     * Get all images for a diary entry
     *
     * @param int $diary_entry_id The diary entry ID
     * @return array Array of image records
     */
    public function get_entry_images($diary_entry_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE diary_entry_id = %d ORDER BY id ASC",
            $diary_entry_id
        );
        return $this->wpdb->get_results($sql);
    }

    /**
     * Delete an image
     *
     * @param int $image_id The image ID
     * @return bool True on success, false on failure
     */
    public function delete_image($image_id) {
        // Get image before deleting to clean up attachment
        $image = $this->find_by_id($image_id);

        if ($image && $image->attachment_id) {
            wp_delete_attachment($image->attachment_id, true);
        }

        return $this->delete($image_id);
    }
}
