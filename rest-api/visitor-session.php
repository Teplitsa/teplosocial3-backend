<?php

namespace Teplosocial\API;

use Teplosocial\models\{VisitorSession};

class VisitorSessionRestApi
{
    public static function add_routes(\WP_REST_Server $server)
    {
        register_rest_route('tps/v1', 'visitor-session/touch', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => function(\WP_REST_Request $request) {
                $visitor_session_id = @$_GET['tps-vsid'];
                $user_id = get_current_user_id();
                $visitor_session_id = VisitorSession::touch($visitor_session_id, $user_id);
                return $visitor_session_id;
            },
            'permission_callback' => '__return_true',
        ]);
    }
}

add_action('rest_api_init', '\Teplosocial\API\VisitorSessionRestApi::add_routes');
