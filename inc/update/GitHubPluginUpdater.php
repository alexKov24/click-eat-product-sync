<?php

/**
 * GitHub Plugin Updater
 * 
 * Enables automatic updates for your WordPress plugin from a GitHub repository.
 */

if (!class_exists('GitHubPluginUpdater')) {

    class GitHubPluginUpdater
    {
        // Basic plugin info
        private $plugin_file;      // The main plugin file (e.g., 'my-plugin/my-plugin.php')
        private $plugin_slug;      // The plugin directory name
        private $plugin_path;      // Full path to the main plugin file

        // GitHub details
        private $github_username;  // Your GitHub username
        private $github_repo;      // Your GitHub repository name

        // Cache settings
        private $cache_key;        // Transient name for caching
        private $cache_allowed;    // Whether to use caching
        private $cache_expiry;     // How long to cache (in seconds)

        /**
         * Initialize the updater
         * 
         * @param string $plugin_file The main plugin file (relative to plugins directory)
         * @param string $github_username Your GitHub username
         * @param string $github_repo Your GitHub repository name
         * @param bool $enable_cache Whether to cache API requests (default: true)
         * @param int $cache_expiry How long to cache GitHub data in seconds (default: 6 hours)
         */
        public function __construct($plugin_file, $github_username, $github_repo, $enable_cache = true, $cache_expiry = 21600)
        {
            // Set the plugin details
            $this->plugin_file = $plugin_file;
            $this->plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
            $this->plugin_slug = dirname($plugin_file);

            // Set GitHub details
            $this->github_username = $github_username;
            $this->github_repo = $github_repo;

            // Set cache settings
            $this->cache_allowed = $enable_cache;
            $this->cache_expiry = $cache_expiry;
            $this->cache_key = 'github_updater_' . $this->plugin_slug;

            // Initialize hooks
            $this->init();
        }

        /**
         * Set up the required WordPress hooks
         */
        private function init()
        {
            // For testing/development
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // Useful for testing with self-signed certificates
                add_filter('https_ssl_verify', '__return_false');
                add_filter('https_local_ssl_verify', '__return_false');
            }

            // Hook into the update system
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);

            // Provide plugin information for the details popup
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);

            // Clear cache when plugin is updated
            add_action('upgrader_process_complete', [$this, 'clear_cache'], 10, 2);
        }

        /**
         * Get the currently installed version of the plugin
         */
        private function get_current_version()
        {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }

            $plugin_data = get_plugin_data($this->plugin_path);
            return $plugin_data['Version'];
        }

        /**
         * Get the latest release information from GitHub
         */
        private function get_github_release()
        {
            // Check cache first
            if ($this->cache_allowed) {
                $cached_data = get_transient($this->cache_key);
                if ($cached_data !== false) {
                    return $cached_data;
                }
            }

            // Make API request to GitHub
            $api_url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest";
            $response = wp_remote_get($api_url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
                ],
                'timeout' => 10,
            ]);

            // Handle errors
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return false;
            }

            // Parse the response
            $release_data = json_decode(wp_remote_retrieve_body($response), true);

            // Make sure we got valid data
            if (empty($release_data) || !isset($release_data['tag_name'])) {
                return false;
            }

            // Cache the result
            if ($this->cache_allowed) {
                set_transient($this->cache_key, $release_data, $this->cache_expiry);
            }

            return $release_data;
        }

        /**
         * Compare versions and check if an update is available
         * 
         * @param object $transient The update_plugins transient
         * @return object Modified transient if update is available
         */
        public function check_for_update($transient)
        {
            // Skip if not a proper update check
            if (empty($transient->checked)) {
                return $transient;
            }

            // Get the installed version
            $current_version = $this->get_current_version();

            // Get the latest release from GitHub
            $release_data = $this->get_github_release();
            if (!$release_data) {
                return $transient;
            }

            // Format the version (remove 'v' prefix if present)
            $latest_version = ltrim($release_data['tag_name'], 'v');

            // Check if a new version is available
            if (version_compare($current_version, $latest_version, '<')) {
                // Build the update object
                $update = new stdClass();
                $update->slug = $this->plugin_slug;
                $update->plugin = $this->plugin_file;
                $update->new_version = $latest_version;
                $update->url = $release_data['html_url'];
                $update->package = $release_data['zipball_url'];

                // Add to the update response
                $transient->response[$this->plugin_file] = $update;
            }

            return $transient;
        }


        /**
         * Provide plugin information for the details popup
         * 
         * @param mixed $result
         * @param string $action
         * @param object $args
         * @return object Plugin info
         */
        public function plugin_info($result, $action, $args)
        {
            // Only handle requests for our plugin
            if ('plugin_information' !== $action || !isset($args->slug) || $this->plugin_slug !== $args->slug) {
                return $result;
            }

            // Get plugin data
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_data = get_plugin_data($this->plugin_path);

            // Get the latest release from GitHub
            $release_data = $this->get_github_release();
            if (!$release_data) {
                return $result;
            }

            // Create plugin info object
            $info = new stdClass();
            $info->name = $plugin_data['Name'];
            $info->slug = $this->plugin_slug;
            $info->version = ltrim($release_data['tag_name'], 'v');
            $info->author = $plugin_data['Author'];
            $info->author_profile = $plugin_data['AuthorURI'];
            $info->requires = isset($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '';
            $info->tested = isset($plugin_data['TestedUpTo']) ? $plugin_data['TestedUpTo'] : '';
            $info->last_updated = date('Y-m-d', strtotime($release_data['published_at']));
            $info->homepage = $plugin_data['PluginURI'] ?: $release_data['html_url'];
            $info->download_link = $release_data['zipball_url'];

            // Add sections
            $info->sections = [
                'description' => $plugin_data['Description'],
                'changelog' => $release_data['body'],
            ];

            return $info;
        }

        /**
         * Clear the cache when plugin is updated
         * 
         * @param WP_Upgrader $upgrader
         * @param array $options
         */
        public function clear_cache($upgrader, $options)
        {
            if ($this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type']) {
                // Check if our plugin was updated
                if (isset($options['plugins']) && is_array($options['plugins'])) {
                    if (in_array($this->plugin_file, $options['plugins'])) {
                        delete_transient($this->cache_key);
                    }
                }
            }
        }
    }
}
