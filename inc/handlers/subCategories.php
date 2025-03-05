<?php

/**
 * Creates sub categoris by name and parent
 * @param mixed $sub_cats
 * @return void
 */
function setupSubCategories($sub_cats)
{

    $options = get_option('clickeat_settings');
    $sync_img = $options['is_sync_img'];


    foreach ($sub_cats as $sub_cat) {

        [
            'id' => $id,
            'category_id' => $categoryId,
            'name' => $name,
            'image' => $imageUrl,
            'description' => $description,
            'is_active' => $isActive,
            'ordr' => $order
        ] = $sub_cat;

        // First get the parent term by its external_id
        $parent_terms = get_terms([
            'taxonomy' => 'products_category',
            'meta_key' => 'clickeat-category_id',
            'meta_value' => $categoryId,
            'hide_empty' => false,
            'parent' => 0 // ensure term has no parent
        ]);

        if (empty($parent_terms)) {
            error_log("Parent category not found for subcategory: $name , id: $id");
            continue; // Skip if parent category not found
        }

        $parent_term_id = $parent_terms[0]->term_id;

        // Check if subcategory exists
        $terms = get_terms([
            'taxonomy' => 'products_category',
            'hide_empty' => false,
            'parent' => $parent_term_id,
            'meta_query' => [
                [
                    'key' => 'clickeat-subcategory_id',
                    'value' => $id,
                    'compare' => '='
                ]
            ],
            'number' => 1
        ]);


        if (isset($terms[0])) {

            $term_id = $terms[0]->term_id;
        } else {


            // Create new term with parent
            $termData = wp_insert_term($name, 'products_category', [
                'parent' => $parent_term_id
            ]);

            if (!is_wp_error($termData)) {
                $term_id = $termData['term_id'];
            } else {

                // in case of a duplicate sub category name for the same paretn
                if ($termData->get_error_code() == 'term_exists') {
                    $termData = wp_insert_term($name . '_' . $id, 'products_category', [
                        'parent' => $parent_term_id
                    ]);
                    $term_id = $termData['term_id'];
                } else {
                    continue;
                }
            }
        }


        if ($sync_img && $imageUrl) {
            $source_img_url = get_term_meta($term_id, 'image_url', true);
            $current_attach_id = get_term_meta($term_id, 'image_id', true);
            if ($imageUrl != $source_img_url || empty($current_attach_id)) {

                // delete local file
                if (!empty($current_attach_id)) {
                    wp_delete_attachment($current_attach_id, true);
                }

                // upload new file
                $attach_id = upload_image_from_url($imageUrl);
                if (!is_wp_error($attach_id)) {
                    update_term_meta($term_id, 'image_id', $attach_id);
                    update_term_meta($term_id, 'image_url', $imageUrl);
                }
            }
        }

        // Update term meta
        update_term_meta($term_id, 'clickeat-subcategory_id', $id);
        update_term_meta($term_id, 'clickeat-category_id', $categoryId);
        update_term_meta($term_id, 'description', $description);
        update_term_meta($term_id, 'is_active', $isActive);
        update_term_meta($term_id, 'order', $order);
    }
}
