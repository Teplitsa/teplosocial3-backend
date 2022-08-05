<?php

use Teplosocial\models\{UserStats, CertificateStats, CourseStats, TrackStats};

function stats_api_add_routes(WP_REST_Server $server)
{

    register_rest_route('tps/v1', 'stats', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => fn (WP_REST_Request $request)  => [
            'user' => [
                'total' => UserStats::get_count()
            ],
            'certificate' => [
                'total' => CertificateStats::get_count()
            ],
            'course' => [
                'total' => CourseStats::get_count()
            ],
            'track' => [
                'total' => TrackStats::get_count()
            ],
        ],
        'permission_callback' => '__return_true',
    ]);
}

add_action('rest_api_init', 'stats_api_add_routes');
