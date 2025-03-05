<?php

namespace Inc\Logger;

class WpDatabaseLogger
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'clickeat_product_sync_logs';
        $this->maybe_create_table();
    }

    private function maybe_create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            log_level varchar(20) NOT NULL,
            log_message text NOT NULL,
            source varchar(255),
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function log($level, $message, $source = '')
    {

        $options = get_option('clickeat_settings');
        $log_enabled = isset($options['log_enabled']) ? $options['log_enabled'] : false;

        if(!$log_enabled) return;

        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'log_level' => $level,
                'log_message' => $message,
                'source' => $source
            ]
        );
    }

    public function get_logs($limit = 100, $offset = 0, $level = null)
    {
        global $wpdb;

        $where = '';
        if ($level) {
            $where = $wpdb->prepare("WHERE log_level = %s", $level);
        }

        $sql = "SELECT * FROM {$this->table_name} 
                {$where}
                ORDER BY log_time DESC
                LIMIT %d OFFSET %d";

        return $wpdb->get_results(
            $wpdb->prepare($sql, $limit, $offset)
        );
    }

    public function clear_logs($days_old = 30)
    {
        global $wpdb;

        $date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE log_time < %s",
                $date
            )
        );
    }

    public function count_logs($level = null)
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table_name}";

        if ($level) {
            $sql .= $wpdb->prepare(" WHERE log_level = %s", $level);
        }

        return (int) $wpdb->get_var($sql);
    }

    public function info($message, $source = '')
    {
        $this->log('info', $message, $source);
    }

    public function warning($message, $source = '')
    {
        $this->log('warning', $message, $source);
    }

    public function error($message, $source = '')
    {
        $this->log('error', $message, $source);
    }

    public function debug($message, $source = '')
    {
        $this->log('debug', $message, $source);
    }
}
