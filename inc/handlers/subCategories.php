<?php

/**
 * Creates sub categoris by name and parent
 * @param mixed $sub_cats
 * @return void
 */
function setupSubCategories($sub_cats)
{
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
            'meta_key' => 'clickeat_id',
            'meta_value' => $categoryId,
            'hide_empty' => false,
            'parent' => 0 // ensure term has no parent
        ]);




        if (empty($parent_terms)) {
            continue; // Skip if parent category not found
        }

        $parent_term_id = $parent_terms[0]->term_id;

        // Check if subcategory exists
        //$term = get_term_by('name', $name, 'products_category');

        $terms = get_terms([
            'taxonomy' => 'products_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'id',
                    'value' => $id,
                    'compare' => '='
                ]
            ],
            'number' => 1 // Limit to just one result
        ]);

        $term = $terms[0];


        if (!$term) {
            // Create new term with parent
            $termData = wp_insert_term($name, 'products_category', [
                'parent' => $parent_term_id
            ]);

            if (!is_wp_error($termData)) {
                $term_id = $termData['term_id'];
            } else {
                continue;
            }
        } else {
            $term_id = $term->term_id;
        }


        if ($imageUrl) {
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
        update_term_meta($term_id, 'category_id', $categoryId);
        update_term_meta($term_id, 'clickeat_id', $id);
        update_term_meta($term_id, 'description', $description);
        update_term_meta($term_id, 'is_active', $isActive);
        update_term_meta($term_id, 'order', $order);
    }
}
