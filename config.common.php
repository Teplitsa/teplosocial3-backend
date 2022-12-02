<?php

namespace Teplosocial;

class ConfigCommon {
    const AUTH_SECRET_KEY = '';
    const AUTH_EXPIRE_DAYS = 30;
    const AUTH_TOKEN_COOKIE_NAME = 'tps-token';

    const BLOCK_POINTS = 10;
    const COURSE_POINTS = 50;
    const TRACK_POINTS = 50;

    const STATS_GOALS = [
        'registered_users_count' => 300,
        'completed_modules_count' => 700,
//        'completed_adaptests_count' => 350,
//        'avarage_session_duration' => "8:00 мин.",
//        'completed_tracks_count' => 100,
//        'certificates_count' => 300,
    ];
}
