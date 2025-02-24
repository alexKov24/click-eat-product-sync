<?php

require_once CLICKEAT_SYNC_PATH . 'inc/activation/post_types/product.php';
require_once CLICKEAT_SYNC_PATH . 'inc/activation/post_types/product_meta_fields.php';

require_once CLICKEAT_SYNC_PATH . 'inc/activation/post_types/branch.php';
require_once CLICKEAT_SYNC_PATH . 'inc/activation/post_types/branch_meta_fields.php';

require_once CLICKEAT_SYNC_PATH . 'inc/activation/post_types/term_meta_fields.php';

// Register post type and taxonomy on init
add_action('init', 'clickeat_register_post_type_and_taxonomy');

function clickeat_register_post_type_and_taxonomy()
{

    clickeat_register_branch_post_type();
    clickeat_register_product_post_type();
}
