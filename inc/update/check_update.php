<?php

// Plugin update checker
function clickeat_sync_update_checker()
{
    // Plugin data
    $plugin_slug = basename(CLICKEAT_SYNC_PATH);
    $plugin_file = plugin_basename(CLICKEAT_SYNC_MAIN_FILE);
    $github_username = 'alexKov24';
    $github_repo = 'clickeat_sync';

    error_log('Plugin slug: ' . $plugin_slug);
    error_log('Plugin file: ' . $plugin_file);

    // Set up the filters needed for the update system
    add_filter('pre_set_site_transient_update_plugins', function ($transient) use ($plugin_file, $github_username, $github_repo, $plugin_slug) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get the installed version
        $plugin_data = get_plugin_data(CLICKEAT_SYNC_PATH);
        $current_version = $plugin_data['Version'];

        // Check GitHub for the latest release
        $response = wp_remote_get("https://api.github.com/repos/{$github_username}/{$github_repo}/releases/latest");

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return $transient;
        }

        $release_data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($release_data['tag_name'])) {
            return $transient;
        }

        // Format the version (remove 'v' prefix if present)
        $latest_version = ltrim($release_data['tag_name'], 'v');

        // Compare versions
        if (version_compare($current_version, $latest_version, '<')) {
            $transient->response[$plugin_file] = (object) [
                'slug' => $plugin_slug,
                'new_version' => $latest_version,
                'url' => $release_data['html_url'],
                'package' => $release_data['zipball_url'],
            ];
        }





        // Inside your update checker
        error_log('Plugin slug: ' . $plugin_slug);
        error_log('Plugin file: ' . $plugin_file);
        error_log('Current version: ' . $current_version);
        error_log('Latest version: ' . $latest_version);
        error_log('Version compare result: ' . (version_compare($current_version, $latest_version, '<') ? 'Update needed' : 'No update needed'));








        return $transient;
    });

    // Provide plugin information for the details popup
    add_filter('plugins_api', function ($result, $action, $args) use ($plugin_slug, $github_username, $github_repo) {
        if ('plugin_information' !== $action || $plugin_slug !== $args->slug) {
            return $result;
        }

        $response = wp_remote_get("https://api.github.com/repos/{$github_username}/{$github_repo}/releases/latest");

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return $result;
        }

        $release_data = json_decode(wp_remote_retrieve_body($response), true);
        $plugin_data = get_plugin_data(CLICKEAT_SYNC_PATH);

        $plugin_info = (object) [
            'name' => $plugin_data['Name'],
            'slug' => $plugin_slug,
            'version' => ltrim($release_data['tag_name'], 'v'),
            'author' => $plugin_data['Author'],
            'author_profile' => $plugin_data['AuthorURI'],
            'requires' => $plugin_data['RequiresWP'],
            'tested' => $plugin_data['TestedUpTo'],
            'last_updated' => date('Y-m-d', strtotime($release_data['published_at'])),
            'homepage' => $plugin_data['PluginURI'],
            'sections' => [
                'description' => $plugin_data['Description'],
                'changelog' => $release_data['body'],
            ],
            'download_link' => $release_data['zipball_url'],
        ];

        return $plugin_info;
    }, 10, 3);
}

add_action('init', 'clickeat_sync_update_checker');
