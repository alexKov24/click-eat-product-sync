<?php

function clickeat_register_product_post_type()
{
    $options = get_option('clickeat_settings');

    if (!isset($options['product_post_type']) || empty($options['product_post_type'])) {
        error_log('Aborting product post type creation. Product post type not set');
        return;
    }

    $product_post_type = $options['product_post_type'];



    // Register custom post type
    register_post_type($product_post_type, [
        'labels' => [
            'name' => $product_post_type . 's',
            'singular_name' => $product_post_type
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_menu' => true
    ]);

    // Register taxonomy
    register_taxonomy('products_category', $product_post_type, [
        'hierarchical' => true,
        'labels' => [
            'name' => $product_post_type . ' Categories',
            'singular_name' => $product_post_type . ' Category'
        ],
        'show_ui' => true,
        'show_in_menu' => true,
        'show_admin_column' => true,
        'query_var' => true,
    ]);
}
