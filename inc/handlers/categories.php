<?php

/**
 * Creates categories by name and sets metadata
 * @param mixed $cats parsed json object of categories
 * @return void
 */
function setupCategories($cats)
{
    $options = get_option('clickeat_settings');
    $sync_img = $options['is_sync_img'];

    foreach ($cats as $cat) {
        [
            'id' => $id,
            'name' => $name,
            'image' => $imageUrl,
            'description' => $description,
            'is_active' => $isActive,
            'ordr' => $order,
            'hours' => $businessHours
        ] = $cat;


        error_log("Category: $id");

        $terms = get_terms([
            'taxonomy' => 'products_category',
            'hide_empty' => false,
            'parent' => 0,
            'meta_query' => [
                [
                    'key' => 'clickeat-category_id',
                    'value' => $id,
                    'compare' => '='
                ]
            ],
            'number' => 1
        ]);

        if (isset($terms[0])) {

            $term_id = $terms[0]->term_id;
        } else {
            $termData = wp_insert_term($name, 'products_category');

            if (!is_wp_error($termData)) {
                $term_id = $termData['term_id'];
            } else {

                // in case of a duplicate category
                if ($termData->get_error_code() == 'term_exists') {
                    $termData = wp_insert_term($name . '_' . $id, 'products_category');
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

        update_term_meta($term_id, 'clickeat-category_id', $id);
        update_term_meta($term_id, 'description', $description);
        update_term_meta($term_id, 'is_active', $isActive);
        update_term_meta($term_id, 'order', $order);
        update_term_meta($term_id, 'business_hours', $businessHours);


        error_log("Category created: $term_id");
    }
}
