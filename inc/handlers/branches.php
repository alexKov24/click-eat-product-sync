<?php

/**
 * Creates sub categoris by name and parent
 * @param mixed $branches
 * @return void
 * @throws Exception if post type is not set
 */
function setupBranches($branches)
{

    $options = get_option('clickeat_settings');

    if (!isset($options['branch_post_type']) || empty($options['branch_post_type'])) {
        throw new Exception('[ERROR] Cannot create branch post. Branch post type not set');
    }

    $branch_post_type = $options['branch_post_type'];


    foreach ($branches as $branch) {
        [
            'id' => $id,
            'name' => $name,
            'is_active' => $isActive,
            'logo' => $logoUrl,
            'delivery_hours' => $deliveryHours,
            'work_hours' => $workHours,
        ] = $branch;

        $existing_branches = get_posts([
            'post_type' => $branch_post_type,
            'meta_key' => 'id',
            'meta_value' => $id,
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft']
        ]);

        if (empty($existing_branches)) {

            $post_data = [
                'post_title' => $name,
                'post_status' => $isActive ? 'publish' : 'draft',
                'post_type' => $branch_post_type
            ];

            $post_id = wp_insert_post($post_data);
        } else {
            $post_id = $existing_branches[0]->ID;

            wp_update_post([
                'ID' => $post_id,
                'post_title' => $name,
                'post_status' => $isActive ? 'publish' : 'draft'
            ]);
        }


        if ($logoUrl) {
            $original_logo_url = get_post_meta($post_id, 'logo', true);
            if ($logoUrl != $original_logo_url) {
                delete_post_thumbnail_and_file($post_id);
                $attach_id = upload_image_from_url($logoUrl);
                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }


        // Update term meta
        update_post_meta($post_id, 'id', $id);
        update_post_meta($post_id, 'name', $name);
        update_post_meta($post_id, 'is_active', $isActive);
        update_post_meta($post_id, 'logo', $logoUrl);
        update_post_meta($post_id, 'delivery_hours', $deliveryHours);
        update_post_meta($post_id, 'work_hours', $workHours);
    }
}
