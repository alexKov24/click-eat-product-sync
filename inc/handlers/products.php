<?php

namespace Inc\Handlers\Product;

/**
 * 
 * Creates new products by sku, sets metadata category and sub category
 * @param mixed $products
 * @return void
 */
function setupProducts($products, $product_limit = -1)
{

    $logger = new \Inc\Logger\WpDatabaseLogger();

    $options = get_option('clickeat_settings');

    if (!isset($options['product_post_type']) || empty($options['product_post_type'])) {
        $logger->log('error', 'Cannot create product post. Product post type not set');
        throw new Exception('[ERROR] Cannot create product post. Product post type not set');
    }

    $product_post_type = $options['product_post_type'];

    $sync_img = $options['is_sync_img'];

    $products_num = 0;
    foreach ($products as $product) {
        if ($products_num++ >= $product_limit && $product_limit != -1) {
            break;
        }
        [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'sku' => $sku,
            'branches' => $branches,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'image' => $imageUrl,
            'price' => $price,
            'is_active' => $isActive,
            'sale_text' => $saleText,
            'hours' => $businessHours,
            'is_hidden' => $isHidden,
            'ordr' => $order
        ] = apply_filters('before_clickeat_product_handler', $product);


        $logger->log('Sync Begin', "Syncing product $name $id");
        $logger->log('log', "Syncing data " . print_r($product, true));


        // Check if product exists by clickeat_id
        $existing_product = get_posts([
            'post_type' => $product_post_type,
            'meta_key' => 'clickeat_id',
            'meta_value' => $id,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft']
        ]);

        if (empty($existing_product)) {
            $logger->log('log', "product not found by clickeat_id $id");
            // Create new product
            $post_data = [
                'post_title' => $name,
                'post_content' => $description,
                'post_status' => $isActive ? 'publish' : 'draft',
                'post_type' => $product_post_type
            ];

            $post_id = wp_insert_post($post_data);
            $logger->log('log', "product created  $post_id");
        } else {
            $post_id = $existing_product[0]->ID;
            $logger->log('log', "product found $post_id");

            // Update existing product
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $name,
                'post_content' => $description,
                'post_status' => $isActive ? 'publish' : 'draft'
            ]);
        }

        // Handle image
        if ($sync_img && $imageUrl) {
            $logger->log('log', "Syncing image for $post_id");
            $source_img_url = get_post_meta($post_id, 'source_img_url', true);
            if ($imageUrl != $source_img_url) {
                delete_post_thumbnail_and_file($post_id);
                $attach_id = upload_image_from_url($imageUrl);
                $logger->log('log', 'old image deleted. new image uploaded.');
                if (!is_wp_error($attach_id)) {
                    $logger->log('log', "setting thumbnail for $post_id");
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }

        $logger->log('log', "setting up product category $categoryId, subcategory $subcategoryId");
        setupProductCategory($post_id, $categoryId, $subcategoryId);


        // Update meta fields
        update_post_meta($post_id, 'category_id', $categoryId);
        update_post_meta($post_id, 'subcategory_id', $subcategoryId);
        update_post_meta($post_id, 'clickeat_id', $id);
        update_post_meta($post_id, 'sku', $sku);
        update_post_meta($post_id, 'price', $price);
        update_post_meta($post_id, 'sale_text', $saleText);
        update_post_meta($post_id, 'business_hours', $businessHours);
        update_post_meta($post_id, 'is_hidden', $isHidden);
        update_post_meta($post_id, 'order', $order);
        update_post_meta($post_id, 'source_img_url', $imageUrl);


        // update branches seperatley
        delete_post_meta($post_id, 'branch');

        foreach ($branches as $branch) {
            $logger->log('log', "adding branch $branch");
            add_post_meta($post_id, 'branch', $branch, false);
        }

        $saved_branches = get_post_meta($post_id, 'branch', false);
        $logger->log('log', "Saved branches: " . print_r($saved_branches, true));
    }
}


function setupProductCategory($post_id, $categoryId, $subcategoryId)
{
    // Set categories and subcategories
    if (!$categoryId)  return;


    $category_terms = get_terms([
        'parent' => 0,
        'taxonomy' => 'products_category',
        'hide_empty' => false,
        'meta_query' => [
            [
                'key' => 'clickeat-category_id',
                'value' => $categoryId,
                'compare' => '='
            ]
        ],
        'number' => 1
    ]);

    if (empty($category_terms)) return;

    $terms_to_set = [$category_terms[0]->term_id];

    // Add subcategory if exists
    if ($subcategoryId) {
        $subcategory_terms = get_terms([
            'taxonomy' => 'products_category',
            'meta_key' => 'clickeat-subcategory_id',
            'meta_value' => $subcategoryId,
            'hide_empty' => false,
            'parent' => $category_terms[0]->term_id
        ]);

        if (!empty($subcategory_terms)) {
            $terms_to_set[] = $subcategory_terms[0]->term_id;
        }
    }

    // Set both terms at once (overrides)
    wp_set_object_terms($post_id, $terms_to_set, 'products_category');
}
