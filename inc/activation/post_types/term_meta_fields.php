<?php
// For adding fields to the term edit form
function add_term_fields($term)
{
    // Get ALL meta data for this term
    $all_meta = get_term_meta($term->term_id);
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
    <div class="form-field term-meta-wrap">
        <h3>Term Meta Data</h3>
        <table class="meta-table">
            <?php
            if (empty($all_meta)) {
                echo '<tr><td colspan="2">No meta data found for this term.</td></tr>';
            } else {
                foreach ($all_meta as $key => $values) {
                    $value = $values[0];
                    if (is_serialized($value)) {
                        $value = maybe_unserialize($value);
                        $value = print_r($value, true);
                    }
                    echo '<tr>';
                    echo '<td class="key">' . esc_html($key) . '</td>';
                    echo '<td>' . esc_html($value) . '</td>';
                    echo '</tr>';
                }
            }
            ?>
        </table>
    </div>
<?php
}
add_action('products_category_edit_form_fields', 'add_term_fields');
