<?php
// Add to your existing admin_menu action
add_action('admin_menu', 'clickeat_add_delete_products_page');
function clickeat_add_delete_products_page()
{
    add_submenu_page(
        'clickeat-sync',
        'Reset Products',
        'Reset Products',
        'manage_options',
        'clickeat-reset-products',
        'clickeat_reset_products_page'
    );
}
function clickeat_reset_products_page()
{
?>
    <div class="wrap">
        <h2>Reset Products Data</h2>

        <div class="card" style="max-width: 100%; margin: 20px 0; padding: 10px 20px; background: white;">
            <h3 style="color: #d63638;">Warning: Destructive Action</h3>
            <p>This will permanently delete:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>All products of type <?= get_option('clickeat_settings')['product_post_type']; ?></li>
                <li>All product images</li>
                <li>All product categories and subcategories</li>
            </ul>
            <p><strong>This action cannot be undone!</strong></p>
        </div>

        <?php
        if (isset($_POST['reset_products']) && check_admin_referer('clickeat_reset_products')) {
            $results = delete_all_products_data();

            echo '<div class="notice notice-success"><p>';
            echo "Deleted: <br>";
            echo "- {$results['products']} products<br>";
            echo "- {$results['images']} images<br>";
            echo "- {$results['terms']} categories/subcategories";
            echo '</p></div>';
        }
        ?>

        <form method="post" onsubmit="return confirm('Are you sure you want to delete all products? This cannot be undone!');">
            <?php wp_nonce_field('clickeat_reset_products'); ?>
            <p><input type="submit" name="reset_products" class="button button-primary"
                    value="Delete All Products Data" style="background: #d63638; border-color: #d63638;"></p>
        </form>
    </div>
<?php
}

function delete_all_products_data()
{

    $options = get_option('clickeat_settings');

    if (!isset($options['product_post_type']) || empty($options['product_post_type'])) {
        error_log('Aborting product post type creation. Product post type not set');
        return;
    }

    $product_post_type = $options['product_post_type'];

    $results = [
        'products' => 0,
        'images' => 0,
        'terms' => 0
    ];

    // Delete all products and count them
    $products = get_posts([
        'post_type' => $product_post_type,
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);

    foreach ($products as $product) {
        // Get and delete the product thumbnail
        $thumbnail_id = get_post_thumbnail_id($product->ID);
        if ($thumbnail_id) {
            wp_delete_attachment($thumbnail_id, true);
            $results['images']++;
        }

        // Delete the product
        wp_delete_post($product->ID, true);
        $results['products']++;
    }

    // Delete all terms in the taxonomy
    $terms = get_terms([
        'taxonomy' => 'products_category',
        'hide_empty' => false
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'products_category');
            $results['terms']++;
        }
    }

    return $results;
}
