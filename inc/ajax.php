<?php


// Add AJAX handlers
add_action('wp_ajax_start_sync', 'handle_start_sync');
add_action('wp_ajax_process_items', 'handle_process_items');

function handle_start_sync()
{
    check_ajax_referer('clickeat_sync_action', 'nonce');

    $options = get_option('clickeat_settings');

    if (!isset($options['api_url'])) {
        wp_send_json_error('API URL not set');
        return;
    }

    error_log('Starting sync... on ' . $options['api_url']);

    $response = wp_remote_get($options['api_url'], [
        'timeout' => 60,
        'sslverify' => false
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        wp_send_json_error('Failed to fetch data: ' . $error_message);
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    if (empty($json)) {
        wp_send_json_error('Invalid data received');
        return;
    }

    wp_send_json_success($json);
}

function handle_process_items()
{
    check_ajax_referer('clickeat_sync_action', 'nonce');

    $type = $_POST['type'];
    $items = $_POST['items'];

    if ($type === 'products') {
        error_log('Processing ' . count($items) . ' products');
    }

    try {
        switch ($type) {
            case 'branches':
                setupBranches($items);
                break;
            case 'categories':
                setupCategories($items);
                break;
            case 'subcategories':
                setupSubCategories($items);
                break;
            case 'products':
                \Inc\Handlers\Product\setupProducts($items);
                break;
        }
        wp_send_json_success();
    } catch (Exception $e) {
        error_log('Error processing ' . $type . ': ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}
