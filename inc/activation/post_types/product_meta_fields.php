<?php


// Add meta box separately
add_action('add_meta_boxes', 'add_product_meta_boxes');
function add_product_meta_boxes()
{

    $options = get_option('clickeat_settings');

    if (!isset($options['product_post_type']) || empty($options['product_post_type'])) {
        error_log('Aborting product post type creation. Product post type not set');
        return;
    }

    $product_post_type = $options['product_post_type'];


    add_meta_box(
        'product_details_readonly',    // Meta box ID
        'Product Meta Details',            // Title
        'product_display_readonly_meta_fields', // Callback function
        $product_post_type,                    // Post type - notice I changed this to 'product'
        'normal',
        'high'
    );
}

function product_display_readonly_meta_fields($post)
{
    // Get ALL meta data for this post
    $all_meta = get_post_meta($post->ID);

?>
    <style>
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .meta-table .key {
            background: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }
    </style>

    <table class="meta-table">
        <?php
        foreach ($all_meta as $key => $values) {
            // Meta values are always stored in an array, even single values
            $value = $values[0];

            // Check if value is serialized (like arrays or objects)
            if (is_serialized($value)) {
                $value = maybe_unserialize($value);
                $value = print_r($value, true); // Convert array/object to string
            }

            echo '<tr>';
            echo '<td class="key">' . esc_html($key) . '</td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '</tr>';
        }
        ?>
    </table>
<?php
}
