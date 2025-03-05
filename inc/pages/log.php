<?php



// Add Log Viewer submenu
add_submenu_page(
    'clickeat-sync',
    'Log Viewer',
    'Log Viewer',
    'manage_options',
    'clickeat-log-viewer',
    'clickeat_log_viewer_page'
);


// Log Viewer Page
function clickeat_log_viewer_page()
{
    // Initialize the logger
    $logger = new \Inc\Logger\WpDatabaseLogger();

    // Handle clearing logs
    if (isset($_POST['clear_logs']) && check_admin_referer('clickeat_log_actions')) {
        $days = isset($_POST['days_to_keep']) ? intval($_POST['days_to_keep']) : 30;
        $logger->clear_logs($days);
        echo '<div class="notice notice-success"><p>Logs older than ' . esc_html($days) . ' days have been cleared.</p></div>';
    }

    // Get filtering parameters
    $level = isset($_GET['log_level']) ? sanitize_text_field($_GET['log_level']) : null;
    $page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
    $per_page = 50; // Logs per page
    $offset = ($page - 1) * $per_page;

    // Get logs
    $logs = $logger->get_logs($per_page, $offset, $level);

    // Get total log count for pagination (you'll need to add this method to your Logger class)
    $total_logs = $logger->count_logs($level);
    $total_pages = ceil($total_logs / $per_page);

?>
    <div class="wrap">
        <h1>Log Viewer</h1>
        <!-- Refresh Button -->
        <a href="<?php echo esc_url(add_query_arg(array_filter(['page' => 'clickeat-log-viewer', 'log_level' => $level, 'log_page' => $page]))); ?>" class="button">
            Refresh Logs
        </a>

        <!-- Filter Form -->
        <form method="get">
            <input type="hidden" name="page" value="clickeat-log-viewer">
            <select name="log_level">
                <option value="">All Levels</option>
                <option value="info" <?php selected($level, 'info'); ?>>Info</option>
                <option value="warning" <?php selected($level, 'warning'); ?>>Warning</option>
                <option value="error" <?php selected($level, 'error'); ?>>Error</option>
                <option value="debug" <?php selected($level, 'debug'); ?>>Debug</option>
            </select>
            <input type="submit" class="button" value="Filter">
            <?php if ($level): ?>
                <a href="?page=clickeat-log-viewer" class="button">Clear Filter</a>
            <?php endif; ?>
        </form>

        <!-- Actions Form -->
        <form method="post">
            <?php wp_nonce_field('clickeat_log_actions'); ?>
            <p>
                <input type="submit" name="clear_logs" class="button button-secondary"
                    value="Clear Old Logs" onclick="return confirm('Are you sure you want to delete old logs?');">
                <select name="days_to_keep">
                    <option value="7">Older than 7 days</option>
                    <option value="30" selected>Older than 30 days</option>
                    <option value="90">Older than 90 days</option>
                </select>
            </p>
        </form>

        <!-- Log Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Level</th>
                    <th>Source</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4">No logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->log_time); ?></td>
                            <td>
                                <span class="log-level log-level-<?php echo esc_attr(strtolower($log->log_level)); ?>">
                                    <?php echo esc_html($log->log_level); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log->source); ?></td>
                            <td><?php echo esc_html($log->log_message); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo esc_html($total_logs); ?> logs</span>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('log_page', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $page,
                            'add_args' => array_filter(['log_level' => $level])
                        ]);
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Add some basic styling for log levels -->
        <style>
            .log-level {
                padding: 2px 8px;
                border-radius: 3px;
                font-weight: bold;
            }

            .log-level-info {
                background-color: #e0f7fa;
                color: #0277bd;
            }

            .log-level-warning {
                background-color: #fff8e1;
                color: #ff8f00;
            }

            .log-level-error {
                background-color: #ffebee;
                color: #c62828;
            }

            .log-level-debug {
                background-color: #f3e5f5;
                color: #7b1fa2;
            }
        </style>
    </div>
<?php
}
