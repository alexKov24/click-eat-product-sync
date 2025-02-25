<?php
// Add menu item
add_action('admin_menu', 'clickeat_add_sync_page');

function clickeat_add_sync_page()
{

    add_submenu_page(
        'clickeat-sync',           // Parent slug
        'Manual Sync',       // Page title
        'Manual Sync',                // Menu title
        'manage_options',          // Capability
        'manual-sync',       // Menu slug
        'clickeat_manual_sync_page'   // Function to display the page
    );
}

function clickeat_manual_sync_page()
{


    if (isset($_POST['sync_now_no_ajax'])) {
        do_action('clickeat_sync_event', $_POST['max_sync_products']);
    }

    $options = get_option('clickeat_settings');
    $api_url = isset($options['api_url']) ? $options['api_url'] : '';
    $sync_img = $options['is_sync_img'];
?>
    <div class="wrap">
        <h2>Sync Using Ajax</h2>
        <p>Use the button below to manually sync products from ClickEat.</p>
        <p>You can set the limit on products fetched using limit.
            Since products are fetched in batches, the number should be devisible by batch size (5)</p>
        <p><?php echo $api_url; ?></p>
        <p>img sync set to <?php echo $sync_img; ?></p>

        <div id="sync-progress" style="display: none;">
            <div class="progress-bar-wrapper" style="width: 100%; background: #f0f0f0; padding: 2px; margin: 10px 0;">
                <div id="sync-progress-bar" style="width: 0%; height: 20px; background: #0073aa; transition: width 0.3s;"></div>
            </div>
            <p id="sync-status">Starting sync...</p>
        </div>

        <form method="post" action="" id="sync-form">
            <input type="number" min="5" name="batch_size" value="10" placeholder="Batch Size" style="width: 200px;">
            <input type="number" name="max_sync_products" value="10" placeholder="Max products to sync" style="width: 200px;">
            <input type="submit" name="sync_now" class="button button-primary" value="Sync Now">
        </form>

        <br>

        <h2>Sync Using Cron Hook</h2>
        <p>Used to test the fetching function after changes. Cron uses the same function</p>
        <form method="post" action="" id="sync-form-test">
            <input type="number" name="max_sync_products" value="10" max="10" placeholder="Max products to sync" style="width: 200px;">
            <input type="submit" name="sync_now_no_ajax" class="button button-primary" value="Sync Now">
        </form>
    </div>
<?php
}
