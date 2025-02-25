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

        $term = get_term_by('name', $name, 'products_category');

        if (!$term) {

            $termData = wp_insert_term($name, 'products_category');

            if (!is_wp_error($termData)) {
                $term_id = $termData['term_id'];
            } else {
                continue;
            }
        } else {
            $term_id = $term->term_id;
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

        update_term_meta($term_id, 'clickeat_id', $id);
        update_term_meta($term_id, 'description', $description);
        update_term_meta($term_id, 'is_active', $isActive);
        update_term_meta($term_id, 'order', $order);
        update_term_meta($term_id, 'business_hours', $businessHours);
    }
}
