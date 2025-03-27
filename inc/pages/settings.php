<?php

add_action('admin_menu', 'clickeat_add_menu');

function clickeat_add_menu()
{
    add_menu_page(
        'ClickEat Sync',
        'ClickEat Sync',
        'manage_options',
        'clickeat-sync',
        'clickeat_sync_page',
        'dashicons-update',
        30
    );
}

// Register settings
add_action('admin_init', 'clickeat_register_settings');
function clickeat_register_settings()
{
    register_setting(
        'clickeat_settings',       // Option group
        'clickeat_settings',       // Option name
        'clickeat_sanitize_settings' // Sanitization callback
    );

    // API Section
    add_settings_section(
        'api_settings',
        'API Settings',
        null,
        'clickeat-settings'
    );

    add_settings_field(
        'api_url',
        'API URL',
        'clickeat_api_url_field',
        'clickeat-settings',
        'api_settings'
    );

    // Cron Section
    add_settings_section(
        'cron_settings',
        'Sync Schedule',
        null,
        'clickeat-settings'
    );

    add_settings_field(
        'cron_enabled',
        'Auto Sync',
        'clickeat_cron_enabled_field',
        'clickeat-settings',
        'cron_settings'
    );

    add_settings_field(
        'is_sync_img',
        'Sync Images',
        'clickeat_is_sync_img_field',
        'clickeat-settings',
        'cron_settings'
    );

    add_settings_field(
        'cron_interval',
        'Sync Interval',
        'clickeat_cron_interval_field',
        'clickeat-settings',
        'cron_settings'
    );

    add_settings_field(
        'cron_offset',
        'Start Time (Hours)',
        'clickeat_cron_offset_field',
        'clickeat-settings',
        'cron_settings'
    );

    // Post section
    add_settings_section(
        'product_settings',
        'Product Settings',
        null,
        'clickeat-settings'
    );

    add_settings_field(
        'product_post_type',
        'Product Post Type',
        'clickeat_custom_product_post_type_field',
        'clickeat-settings',
        'product_settings'
    );

    add_settings_field(
        'branch_post_type',
        'Branch Post Type',
        'clickeat_custom_branch_post_type_field',
        'clickeat-settings',
        'product_settings'
    );


    // Log Section
    add_settings_section(
        'log_settings',
        'Log Settings',
        null,
        'clickeat-settings'
    );

    add_settings_field(
        'log_enabled',
        'Log Enabled',
        'clickeat_log_enabled_field',
        'clickeat-settings',
        'log_settings'
    );
}

// Settings page display
function clickeat_sync_page()
{
?>
    <div class="wrap">
        <h2>ClickEat Sync Settings</h2>

        <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 10px 20px; background: white;">
            <?php echo show_system_status(); ?>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('clickeat_settings');
            do_settings_sections('clickeat-settings');
            submit_button();
            ?>
        </form>

        <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 10px 20px; background: white;">
            <h3>Sync Status</h3>
            <?php echo show_cron_status() ?>
        </div>

    </div>
<?php
}




function clickeat_log_enabled_field()
{
    $options = get_option('clickeat_settings');
    $checked = isset($options['log_enabled']) ? checked($options['log_enabled'], 1, false) : '';
    echo "<input type='checkbox' name='clickeat_settings[log_enabled]' value='1' {$checked}> Enable Log";
}

function clickeat_custom_product_post_type_field()
{
    $options = get_option('clickeat_settings');
    // Just get the value directly, don't use selected() here
    $value = isset($options['product_post_type']) ? $options['product_post_type'] : '';

    $post_types = get_post_types(['public' => true], 'names');
    $post_type_options = [
        '' => 'Select post type',
        'product' => 'Default (product)'
    ];

    foreach ($post_types as $post_type) {
        $post_type_options[$post_type] = $post_type;
    }

    // The name should match your settings array
    echo '<select name="clickeat_settings[product_post_type]">';

    foreach ($post_type_options as $option_value => $option_label) {
        echo '<option value="' . esc_attr($option_value) . '"' .
            selected($option_value, $value, false) . '>' .
            esc_html($option_label) . '</option>';
    }
    echo '</select>';
}

function clickeat_custom_branch_post_type_field()
{
    $options = get_option('clickeat_settings');
    // Just get the value directly, don't use selected() here
    $value = isset($options['branch_post_type']) ? $options['branch_post_type'] : '';

    $post_types = get_post_types(['public' => true], 'names');
    $post_type_options = [
        '' => 'Select post type',
        'branch' => 'Default (branch)'
    ];

    foreach ($post_types as $post_type) {
        $post_type_options[$post_type] = $post_type;
    }

    // The name should match your settings array
    echo '<select name="clickeat_settings[branch_post_type]">';

    foreach ($post_type_options as $option_value => $option_label) {
        echo '<option value="' . esc_attr($option_value) . '"' .
            selected($option_value, $value, false) . '>' .
            esc_html($option_label) . '</option>';
    }
    echo '</select>';
}

// Field renderers
function clickeat_api_url_field()
{
    $options = get_option('clickeat_settings');
    $value = isset($options['api_url']) ? $options['api_url'] : '';
    echo "<input type='text' name='clickeat_settings[api_url]' value='" . esc_attr($value) . "' class='regular-text'>";
}

function clickeat_is_sync_img_field()
{
    $options = get_option('clickeat_settings');
    $checked = isset($options['is_sync_img']) ? checked($options['is_sync_img'], 1, false) : '';
    echo "<input type='checkbox' name='clickeat_settings[is_sync_img]' value='1' {$checked}> Sync Images";
}

function clickeat_cron_enabled_field()
{
    $options = get_option('clickeat_settings');
    $checked = isset($options['cron_enabled']) ? checked($options['cron_enabled'], 1, false) : '';
    echo "<input type='checkbox' name='clickeat_settings[cron_enabled]' value='1' {$checked}> Enable automatic sync";
}

function clickeat_cron_interval_field()
{
    $options = get_option('clickeat_settings');
    $current = isset($options['cron_interval']) ? $options['cron_interval'] : 'clickeat_set_hour_daily';

    $intervals = clickeat_get_all_schedules();

    echo "<select name='clickeat_settings[cron_interval]'>";
    foreach ($intervals as $value => $schedule) {
        $selected = selected($current, $value, false);
        echo "<option value='{$value}' {$selected}>{$schedule['display']}</option>";
    }
    echo "</select>";
}

// Sanitization callback
function clickeat_sanitize_settings($input)
{
    $sanitized = [];

    // Sanitize API URL
    $sanitized['api_url'] = esc_url_raw($input['api_url']);

    $sanitized['product_post_type'] = sanitize_text_field($input['product_post_type']);
    $sanitized['branch_post_type'] = sanitize_text_field($input['branch_post_type']);

    // Sanitize cron settings
    $sanitized['is_sync_img'] = isset($input['is_sync_img']) ? 1 : 0;
    $sanitized['cron_enabled'] = isset($input['cron_enabled']) ? 1 : 0;
    $sanitized['cron_interval'] = sanitize_text_field($input['cron_interval']);
    $sanitized['cron_offset'] = intval($input['cron_offset']);

    $sanitized['log_enabled'] = isset($input['log_enabled']) ? 1 : 0;

    // Update cron schedule if settings changed
    $old_options = get_option('clickeat_settings');
    if (
        $sanitized['cron_enabled'] !== ($old_options['cron_enabled'] ?? 0) ||
        $sanitized['cron_interval'] !== ($old_options['cron_interval'] ?? 'clickeat_set_hour_daily') ||
        $sanitized['cron_offset'] !== ($old_options['cron_offset'] ?? 0)
    ) {

        // Clear existing schedule
        wp_clear_scheduled_hook('clickeat_sync_event');

        // Set new schedule if enabled
        if ($sanitized['cron_enabled']) {
            // Calculate next run time based on offset
            $now = current_time('timestamp');
            $current_hour = intval(date('G', $now));
            $target_hour = $sanitized['cron_offset'];

            // Calculate when the next run should be
            if ($current_hour >= $target_hour) {
                // Start tomorrow at target hour
                $start_time = strtotime("tomorrow {$target_hour}:00", $now);
            } else {
                // Start today at target hour
                $start_time = strtotime("today {$target_hour}:00", $now);
            }

            wp_schedule_event($start_time, $sanitized['cron_interval'], 'clickeat_sync_event');
        }
    }

    return $sanitized;
}

function clickeat_cron_offset_field()
{
    $options = get_option('clickeat_settings');
    $offset = isset($options['cron_offset']) ? intval($options['cron_offset']) : 0;
?>
    <select name="clickeat_settings[cron_offset]">
        <?php
        for ($i = 0; $i < 24; $i++) {
            $formatted = sprintf("%02d:00", $i);
            echo "<option value='{$i}' " . selected($offset, $i, false) . ">{$formatted}</option>";
        }
        ?>
    </select>
    <p class="description">Select when you want the sync to start (in 24-hour format)</p>
<?php
}

function show_cron_status()
{

    $next_scheduled = wp_next_scheduled('clickeat_sync_event');
    $options = get_option('clickeat_settings');
    $is_enabled = isset($options['cron_enabled']) && $options['cron_enabled'];

    echo '<p><strong>Auto Sync:</strong> ' . ($is_enabled ? 'Enabled' : 'Disabled') . '</p>';

    if ($next_scheduled) {
        $time_diff = human_time_diff($next_scheduled, current_time('timestamp'));
        echo '<p><strong>Next Run:</strong> ' . date('F j, Y, g:i a', $next_scheduled) . ' (in ' . $time_diff . ')</p>';
    } else {
        echo '<p><strong>Next Run:</strong> Not scheduled</p>';
    }

    if ($is_enabled) {
        $offset = isset($options['cron_offset']) ? sprintf("%02d:00", $options['cron_offset']) : "00:00";
        echo '<p><strong>Current Interval:</strong> ' .
            ucfirst($options['cron_interval'] ?? 'hourly') . '</p>';
        echo '<p><strong>Start Time:</strong> ' . $offset . '</p>';
    }
}


function show_system_status()
{

    $settings = get_option('clickeat_settings');

?>
    <h3>System Status</h3>
    <?php
    // Check if WordPress cron is enabled
    $cron_enabled = !(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON);
    $status_color = $cron_enabled ? '#00a32a' : '#d63638';
    ?>
    <p>
        <strong>WordPress Cron System:</strong>
        <span style="color: <?php echo $status_color; ?>">
            <?php echo $cron_enabled ? 'Enabled' : 'Disabled'; ?>
        </span>
        <?php
        if (!$cron_enabled) {
            echo ' (DISABLE_WP_CRON is set to true in wp-config.php)';
        }
        ?>
    </p>

    <?php
    // Check if Post Types Set
    $clickeat_product_post_defined = (isset($settings['product_post_type']) && $settings['product_post_type']);
    $status_color = $clickeat_product_post_defined ? '#00a32a' : '#d63638';
    ?>
    <p>
        <strong>Product Post Type:</strong>
        <span style="color: <?php echo $status_color; ?>">
            <?php echo $clickeat_product_post_defined ? $settings['product_post_type'] : 'Undefined'; ?>
        </span>
        <?php
        if (!$clickeat_product_post_defined) {
            echo ' (Define Post Type in settings, or select default)';
        }
        ?>
    </p>

    <?php
    // Check if Post Types Set
    $clickeat_branch_post_defined = ((isset($settings['branch_post_type']) && $settings['branch_post_type']));
    $status_color = $clickeat_branch_post_defined ? '#00a32a' : '#d63638';
    ?>
    <p>
        <strong>Branch Post Type:</strong>
        <span style="color: <?php echo $status_color; ?>">
            <?php echo $clickeat_branch_post_defined ? $settings['branch_post_type'] : 'Undefined'; ?>
        </span>
        <?php
        if (!$clickeat_branch_post_defined) {
            echo ' (Define Post Type in settings, or select default)';
        }
        ?>
    </p>
<?php
}
