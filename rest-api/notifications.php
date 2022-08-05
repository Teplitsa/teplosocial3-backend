<?php

use Teplosocial\models\{Auth, Notifications, NotificationStatusNames};

function notification_api_add_routes(WP_REST_Server $server)
{

    register_rest_route('tps/v1', 'notifications/(?P<notification_id>\d+)', [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => function (WP_REST_Request $request) {
            $user_id = $request->get_param('user_id');
            $notification_id = $request->get_param('notification_id');

            ["status" => $status_name] = $request->get_json_params();

            $status_names = NotificationStatusNames::get_values();

            if (!in_array($status_name, $status_names)) {
                return new WP_Error(
                    'no_valid_status_name',
                    \__('Status name is not valid.', 'tps'),
                    ['status' => 400]
                );
            }

            if (
                Notifications::status_will_update($notification_id) &&
                Notifications::add_new_status($notification_id, $status_name)
            ) {
                Notifications::update_item_cache($notification_id, $user_id, ['status' => $status_name]);

                $response = rest_ensure_response(null);
                $response->set_status(204);

                return $response;
            } else {
                return new WP_Error(
                    'new_notification_status_failed',
                    \__('Adding the new notification status is failed.', 'tps'),
                    ['status' => 500]
                );
            }
        },
        'permission_callback' => function (\WP_REST_Request $request): bool {
            try {
                ["userId" => $user_id] = $request->get_json_params();

                $auth = new Auth();

                ['is_valid' => $is_valid, 'user' => $user] = $auth->validate_token($auth->parse_token_from_request(), true);

                $request->set_param('user_id', $user['id']);

                return $is_valid && $user['id'] == $user_id;
            } catch (\Exception $error) {
                return false;
            }
        },
        'args' => [
            'notification_id' => [
                'required' => true,
                'validate_callback' => fn ($param) => is_numeric($param),
                'sanitize_callback' => fn ($param) => (int) $param,
            ],
        ]
    ]);
}

add_action('rest_api_init', 'notification_api_add_routes');
