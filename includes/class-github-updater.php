<?php
/**
 * GitHub Plugin Updater
 * Checks GitHub for new releases and enables WordPress auto-updates
 */

class WP_Staff_Diary_GitHub_Updater {
    private $plugin_file;
    private $github_user;
    private $github_repo;
    private $plugin_slug;
    private $version;

    public function __construct($plugin_file, $github_user, $github_repo, $version = null) {
        $this->plugin_file = $plugin_file;
        $this->github_user = $github_user;
        $this->github_repo = $github_repo;
        $this->plugin_slug = plugin_basename($plugin_file);

        // Get current version - use provided version or constant to avoid early get_plugin_data() call
        $this->version = $version ? $version : (defined('WP_STAFF_DIARY_VERSION') ? WP_STAFF_DIARY_VERSION : '1.0.0');

        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);

        // Add admin action to force clear update cache
        add_action('admin_init', array($this, 'maybe_clear_update_cache'));
    }

    /**
     * Clear update cache if requested
     */
    public function maybe_clear_update_cache() {
        if (isset($_GET['force_update_check']) && $_GET['force_update_check'] === 'wp_staff_diary') {
            delete_site_transient('update_plugins');
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
    }

    /**
     * Check GitHub for updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();

        // Debug logging
        error_log('WP Staff Diary Update Check:');
        error_log('- Current version: ' . $this->version);
        error_log('- Remote version: ' . ($remote_version ? $remote_version : 'Not found'));
        error_log('- Plugin slug: ' . $this->plugin_slug);

        if ($remote_version && version_compare($this->version, $remote_version, '<')) {
            $download_url = $this->get_download_url($remote_version);

            error_log('- Update available! Download URL: ' . $download_url);

            $plugin_data = array(
                'slug' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->github_user}/{$this->github_repo}",
                'package' => $download_url,
                'tested' => get_bloginfo('version'),
            );

            $transient->response[$this->plugin_slug] = (object) $plugin_data;
        } else {
            error_log('- No update needed or remote version not found');
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        $api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";

        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->tag_name)) {
            // Remove 'v' prefix if present (e.g., v2.0.12 -> 2.0.12)
            return ltrim($data->tag_name, 'v');
        }

        return false;
    }

    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        // Get the release asset (wp-staff-diary.zip) from the release
        $api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/tags/v{$version}";

        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body);

        // Look for wp-staff-diary.zip in release assets
        if (isset($release->assets) && is_array($release->assets)) {
            foreach ($release->assets as $asset) {
                if ($asset->name === 'wp-staff-diary.zip') {
                    return $asset->browser_download_url;
                }
            }
        }

        // Fallback to repository archive if asset not found
        return "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/v{$version}.zip";
    }

    /**
     * Provide plugin information for WordPress update screen
     */
    public function plugin_info($false, $action, $args) {
        if ($action !== 'plugin_information') {
            return $false;
        }

        if ($args->slug !== $this->plugin_slug) {
            return $false;
        }

        $remote_version = $this->get_remote_version();

        if (!$remote_version) {
            return $false;
        }

        $info = new stdClass();
        $info->name = 'Staff Daily Job Planner';
        $info->slug = $this->plugin_slug;
        $info->version = $remote_version;
        $info->author = '<a href="https://www.express-websites.co.uk">Alex Lyle</a>';
        $info->homepage = "https://github.com/{$this->github_user}/{$this->github_repo}";
        $info->download_link = $this->get_download_url($remote_version);
        $info->sections = array(
            'description' => 'A daily job planning and management system for staff members with image uploads and detailed job tracking.',
            'changelog' => $this->get_changelog(),
        );

        return $info;
    }

    /**
     * Get changelog from GitHub
     */
    private function get_changelog() {
        $api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases";

        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ));

        if (is_wp_error($response)) {
            return 'See GitHub repository for changelog.';
        }

        $body = wp_remote_retrieve_body($response);
        $releases = json_decode($body);

        if (!is_array($releases) || empty($releases)) {
            return 'See GitHub repository for changelog.';
        }

        $changelog = '<ul>';
        foreach (array_slice($releases, 0, 5) as $release) {
            $changelog .= '<li><strong>Version ' . ltrim($release->tag_name, 'v') . '</strong> - ' . date('Y-m-d', strtotime($release->published_at)) . '<br>';
            $changelog .= nl2br(esc_html($release->body)) . '</li>';
        }
        $changelog .= '</ul>';

        return $changelog;
    }
}
