<?php

function clickeat_add_cron_schedules($schedules)
{
    $schedules['clickeat_set_hour_twicedaily'] = array(
        'interval' => 12 * HOUR_IN_SECONDS,
        'display' => __('Twice Daily at Fixed Times', CLICKEAT_TEXT_DOMAIN)
    );

    $schedules['clickeat_set_hour_daily'] = array(
        'interval' => 24 * HOUR_IN_SECONDS,
        'display' => __('Daily at Fixed Times', CLICKEAT_TEXT_DOMAIN)
    );

    $schedules['clickeat_set_hour_hourly'] = array(
        'interval' => HOUR_IN_SECONDS,
        'display' => __('Hourly at Fixed Times', CLICKEAT_TEXT_DOMAIN)
    );

    return $schedules;
}
add_filter('cron_schedules', 'clickeat_add_cron_schedules');


function clickeat_get_all_schedules()
{
    $schedules = wp_get_schedules();
    $clickeat_schedules = array();

    // Filter schedules by clickeat prefix
    foreach ($schedules as $name => $schedule) {
        if (strpos($name, 'clickeat') === 0) {
            $clickeat_schedules[$name] = $schedule;
        }
    }

    return $clickeat_schedules;
}
