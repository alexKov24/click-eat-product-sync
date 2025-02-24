<?php

function clickeat_register_branch_post_type()
{
    $options = get_option('clickeat_settings');

    if (!isset($options['branch_post_type']) || empty($options['branch_post_type'])) {
        error_log('Aborting branch post type creation. Branch post type not set');
        return;
    }

    $branch_post_type = $options['branch_post_type'];



    // Register custom post type
    register_post_type($branch_post_type, [
        'labels' => [
            'name' => $branch_post_type . 's',
            'singular_name' => $branch_post_type
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_menu' => true
    ]);
}
