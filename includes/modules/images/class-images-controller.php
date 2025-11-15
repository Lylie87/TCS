<?php
/**
 * Images Controller
 *
 * Handles HTTP/AJAX requests for image operations.
 *
 * @since      2.1.0
 * @package    WP_Staff_Diary
 */

class WP_Staff_Diary_Images_Controller extends WP_Staff_Diary_Base_Controller {

    /**
     * The images repository
     *
     * @var WP_Staff_Diary_Images_Repository
     */
    private $repository;

    /**
     * Constructor
     *
     * @param WP_Staff_Diary_Images_Repository $repository The images repository
     */
    public function __construct($repository) {
        $this->repository = $repository;
    }

    /**
     * Handle upload job image request
     */
    public function upload() {
        ob_start();

        // Debug logging
        error_log('=== IMAGE UPLOAD DEBUG ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));

        if (!$this->verify_request()) {
            error_log('Image upload: verify_request() failed');
            ob_end_clean();
            return;
        }

        error_log('Image upload: verify_request() passed');

        $diary_entry_id = intval($_POST['diary_entry_id']);
        error_log('Image upload: diary_entry_id = ' . $diary_entry_id);

        if (empty($diary_entry_id)) {
            error_log('Image upload: diary_entry_id is empty');
            ob_end_clean();
            $this->send_error('Missing required field: diary_entry_id');
            return;
        }

        // Handle file upload using WordPress functions
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        error_log('Image upload: About to call media_handle_upload');
        $attachment_id = media_handle_upload('image', 0);
        error_log('Image upload: media_handle_upload returned: ' . print_r($attachment_id, true));

        if (is_wp_error($attachment_id)) {
            error_log('Image upload: WP_Error - ' . $attachment_id->get_error_message());
            ob_end_clean();
            $this->send_error('Failed to upload image: ' . $attachment_id->get_error_message());
            return;
        }

        $image_url = wp_get_attachment_url($attachment_id);
        $caption = isset($_POST['caption']) ? sanitize_text_field($_POST['caption']) : '';

        error_log('Image upload: Saving to repository - entry_id=' . $diary_entry_id . ', url=' . $image_url);
        $image_id = $this->repository->add_image($diary_entry_id, $image_url, $attachment_id, $caption);
        error_log('Image upload: Repository returned image_id=' . $image_id);

        ob_end_clean();

        if ($image_id) {
            $image = $this->repository->find_by_id($image_id);
            error_log('Image upload: SUCCESS');
            $this->send_success(array(
                'message' => 'Image uploaded successfully',
                'image' => $image
            ));
        } else {
            // Clean up attachment if database insert failed
            error_log('Image upload: FAILED to save to database');
            wp_delete_attachment($attachment_id, true);
            $this->send_error('Failed to save image to database');
        }
    }

    /**
     * Handle delete image request
     */
    public function delete() {
        ob_start();

        if (!$this->verify_request()) {
            ob_end_clean();
            return;
        }

        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if (empty($image_id)) {
            ob_end_clean();
            $this->send_error('Missing required field: image_id');
            return;
        }

        $result = $this->repository->delete_image($image_id);

        ob_end_clean();

        if ($result) {
            $this->send_success(array('message' => 'Image deleted successfully'));
        } else {
            $this->send_error('Failed to delete image');
        }
    }
}
