<?php
/*
Plugin Name: ClickEat Products Sync
Plugin URI: https://webchad.tech
Description: Syncs products, categories and subcategories from ClickEat API
Version: 0.0.4
Author: Alex Kovalev
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    set_time_limit(300); // Set to 5 minutes
});

define('CLICKEAT_SYNC_PATH', plugin_dir_path(__FILE__));
define('CLICKEAT_SYNC_URL', plugin_dir_url(__FILE__));
define('CLICKEAT_SYNC_MAIN_FILE', __FILE__);


require_once CLICKEAT_SYNC_PATH . 'inc/handlers/branches.php';
require_once CLICKEAT_SYNC_PATH . 'inc/handlers/products.php';
require_once CLICKEAT_SYNC_PATH . 'inc/handlers/categories.php';
require_once CLICKEAT_SYNC_PATH . 'inc/handlers/subCategories.php';


require_once CLICKEAT_SYNC_PATH . 'inc/activation/handleActivation.php';
require_once CLICKEAT_SYNC_PATH . 'inc/logger/Logger.php';
require_once CLICKEAT_SYNC_PATH . 'inc/pages/settings.php'; // must be first page to load
require_once CLICKEAT_SYNC_PATH . 'inc/pages/manual-sync.php';
require_once CLICKEAT_SYNC_PATH . 'inc/pages/log.php';
require_once CLICKEAT_SYNC_PATH . 'inc/pages/cleanup.php';
require_once CLICKEAT_SYNC_PATH . 'inc/pages/delte-products.php';
require_once CLICKEAT_SYNC_PATH . 'inc/img-helpers.php';
require_once CLICKEAT_SYNC_PATH . 'inc/ajax.php';


// Add this function to your plugin
function clickeat_enqueue_admin_scripts($hook)
{
    // Only add to manual sync page
    if ($hook != 'clickeat-sync_page_manual-sync') {
        return;
    }

    wp_enqueue_script(
        'clickeat-admin-js',
        plugins_url('admin.js', __FILE__),
        ['jquery'],
        '1.0.0',
        true
    );

    // Localize the script with our data
    wp_localize_script(
        'clickeat-admin-js',
        'clickeatAdmin',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clickeat_sync_action')
        ]
    );
}
add_action('admin_enqueue_scripts', 'clickeat_enqueue_admin_scripts');




add_action('clickeat_sync_event', 'clickeat_product_sync_fetch_products_clickeat');
/**
 * 
 * Fetch products from ClickEat API
 * @param mixed $product_limit
 * @return void
 */
function clickeat_product_sync_fetch_products_clickeat($product_limit = -1)
{

    $options = get_option('clickeat_settings');
    if (!isset($options['api_url'])) {
        error_log('API URL not set');
        return;
    }

    $response = wp_remote_get($options['api_url']);

    if (is_wp_error($response)) {
        error_log('Error fetching products: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    error_log("decoded json " . print_r($json, true));

    if (!empty($json)) {
        update_items_and_categories($json, $product_limit);
    }
}



function update_items_and_categories($json, $product_limit)
{

    $categories = $json['categories'];
    $subcategories = $json['subcategories'];
    $branches = $json['branches'];
    $products = $json['products'];

    try {
        setupCategories($categories);
        setupSubCategories($subcategories);
        setupBranches($branches);
        setupProducts($products, $product_limit);
    } catch (Exception $e) {
        error_log('Error in setupCategories: ' . $e->getMessage());
        return false;
    }
}




/**
 * Initialize the GitHub updater
 */
function my_plugin_init_updater()
{
    // Include the updater class
    require_once CLICKEAT_SYNC_PATH . 'inc/update/GitHubPluginUpdater.php';

    // Initialize the updater
    new GitHubPluginUpdater(
        plugin_basename(CLICKEAT_SYNC_MAIN_FILE),  // The plugin file relative to plugins directory
        'alexKov24',           // Your GitHub username
        'click-eat-product-sync'                // Your GitHub repository name
    );
}
add_action('init', 'my_plugin_init_updater');
