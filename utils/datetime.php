<?php

namespace Teplosocial\utils;

function get_yesterday_mysql_date() {
    return date("Y-m-d", strtotime("-1 days"));
}

function get_week_ago_mysql_date() {
    return date("Y-m-d", strtotime("-7 days"));
}

function seconds_to_hours_minutes($seconds) {
    $d = floor($seconds / (24 * 3600));
    $H = floor(($seconds % (24 * 3600)) / 3600);
    $i = floor(($seconds % 3600) / 60);

    return $d ? sprintf("%d д. %02d ч. %02d мин.", $d, $H, $i) : sprintf("%02d ч. %02d мин.", $H, $i);
}