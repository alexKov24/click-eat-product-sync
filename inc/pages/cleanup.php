<?php
// Add submenus for cleanup tools
add_action('admin_menu', 'clickeat_add_cleanup_menus');

function clickeat_add_cleanup_menus()
{
    // Add Media Cleanup submenu
    add_submenu_page(
        'clickeat-sync',
        'Cleanup Media',
        'Cleanup Media',
        'manage_options',
        'clickeat-cleanup-media',
        'clickeat_cleanup_media_page'
    );

    // Add Product Image Cleanup submenu
    add_submenu_page(
        'clickeat-sync',
        'Cleanup Product Images',
        'Cleanup Product Images',
        'manage_options',
        'clickeat-cleanup-product-images',
        'clickeat_cleanup_product_images_page'
    );
}

// Media Cleanup Page
function clickeat_cleanup_media_page()
{
?>
    <div class="wrap">
        <h2>Cleanup Media Files</h2>
        <p>This tool will delete media attachments where the actual file is missing from the uploads directory.</p>

        <?php
        if (isset($_POST['cleanup_media']) && check_admin_referer('clickeat_cleanup_media')) {
            $deleted = delete_attachments_with_missing_files();
            echo '<div class="notice notice-success"><p>' .
                esc_html($deleted) . ' attachments with missing files were deleted.</p></div>';
        }
        ?>

        <form method="post">
            <?php wp_nonce_field('clickeat_cleanup_media'); ?>
            <p><input type="submit" name="cleanup_media" class="button button-primary"
                    value="Delete Missing Media"></p>
        </form>
    </div>
<?php
}

// Product Image Cleanup Page
function clickeat_cleanup_product_images_page()
{
?>
    <div class="wrap">
        <h2>Cleanup Product Image Metadata</h2>
        <p>This tool will clear image URL metadata for products that don't have a thumbnail image.</p>

        <?php
        if (isset($_POST['cleanup_product_images']) && check_admin_referer('clickeat_cleanup_product_images')) {
            $cleaned = delete_orphaned_product_image_meta();
            echo '<div class="notice notice-success"><p>' .
                esc_html($cleaned) . ' products had their image metadata cleaned.</p></div>';
        }
        ?>

        <form method="post">
            <?php wp_nonce_field('clickeat_cleanup_product_images'); ?>
            <p><input type="submit" name="cleanup_product_images" class="button button-primary"
                    value="Cleanup Product Images"></p>
        </form>
    </div>
<?php
}

// Function to delete attachments with missing files
function delete_attachments_with_missing_files()
{
    $args = array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_status' => 'any'
    );
    $attachments = get_posts($args);
    $deleted_count = 0;

    foreach ($attachments as $attachment) {
        $file_path = get_attached_file($attachment->ID);

        if (!file_exists($file_path)) {
            error_log("Deleting attachment ID {$attachment->ID} - File missing: {$file_path}");

            if (wp_delete_attachment($attachment->ID, true)) {
                $deleted_count++;
            } else {
                error_log("Failed to delete attachment ID {$attachment->ID}");
            }
        }
    }
    return $deleted_count;
}

// Function to delete orphaned product image meta
function delete_orphaned_product_image_meta()
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'any'
    );
    $products = get_posts($args);
    $cleaned_count = 0;

    foreach ($products as $product) {
        if (!has_post_thumbnail($product->ID)) {
            // Clear the original image URL meta
            delete_post_meta($product->ID, 'source_img_url');
            $cleaned_count++;
            error_log("Cleaned image metadata for product ID {$product->ID}");
        }
    }
    return $cleaned_count;
}
