<?php

/**
 * 
 * Creates new products by sku, sets metadata category and sub category
 * @param mixed $products
 * @return void
 */
function setupProducts($products, $product_limit = -1)
{

    $options = get_option('clickeat_settings');

    if (!isset($options['product_post_type']) || empty($options['product_post_type'])) {
        throw new Exception('[ERROR] Cannot create product post. Product post type not set');
    }

    $product_post_type = $options['product_post_type'];


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
            'ordr' => $order,
            'tags' => $tags
        ] = $product;

        // Check if product exists by SKU
        $existing_product = get_posts([
            'post_type' => $product_post_type,
            'meta_key' => 'sku',
            'meta_value' => $sku,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft']
        ]);

        if (empty($existing_product)) {
            // Create new product
            $post_data = [
                'post_title' => $name,
                'post_content' => $description,
                'post_status' => $isActive ? 'publish' : 'draft',
                'post_type' => $product_post_type
            ];

            $post_id = wp_insert_post($post_data);
        } else {
            $post_id = $existing_product[0]->ID;

            // Update existing product
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $name,
                'post_content' => $description,
                'post_status' => $isActive ? 'publish' : 'draft'
            ]);
        }

        // Handle image
        if ($imageUrl) {
            $source_img_url = get_post_meta($post_id, 'source_img_url', true);
            if ($imageUrl != $source_img_url) {
                delete_post_thumbnail_and_file($post_id);
                $attach_id = upload_image_from_url($imageUrl);
                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }


        // Set categories and subcategories
        if ($categoryId) {
            $category_terms = get_terms([
                'taxonomy' => 'products_category',
                'meta_key' => 'clickeat_id',
                'meta_value' => $categoryId,
                'hide_empty' => false
            ]);

            if (!empty($category_terms)) {
                $terms_to_set = [$category_terms[0]->term_id];

                // Add subcategory if exists
                if ($subcategoryId) {
                    $subcategory_terms = get_terms([
                        'taxonomy' => 'products_category',
                        'meta_key' => 'clickeat_id',
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
        }

        // Update meta fields
        update_post_meta($post_id, 'category_id', $categoryId);
        update_post_meta($post_id, 'subcategory_id', $subcategoryId);
        update_post_meta($post_id, 'clickeat_id', $id);
        update_post_meta($post_id, 'sku', $sku);
        update_post_meta($post_id, 'branches', $branches);
        update_post_meta($post_id, 'price', $price);
        update_post_meta($post_id, 'sale_text', $saleText);
        update_post_meta($post_id, 'business_hours', $businessHours);
        update_post_meta($post_id, 'is_hidden', $isHidden);
        update_post_meta($post_id, 'order', $order);
        update_post_meta($post_id, 'tags', $tags);
        update_post_meta($post_id, 'source_img_url', $imageUrl);
    }
}
