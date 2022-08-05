<?php

namespace Teplosocial\REST;

use \Teplosocial\models\{Module, Student, Track, StudentLearning};

function auth_api_add_routes($server)
{

    register_rest_route('tps/v1', '/auth/login', [
        'methods' => \WP_REST_Server::EDITABLE,
        'callback' => function ($request) {
            $login = $request->get_param('login');
            $password = $request->get_param('password');
            $remember = $request->get_param('remember');
            $modulePassingState = $request->get_param('modulePassingState');

            if (!$login || !$password) {
                return new \WP_Error(
                    'invalid_params',
                    __('Invalid params', 'tps'),
                    array('status' => 400)
                );
            }

            $auth = new \Teplosocial\models\Auth();
            $auth_result = $auth->login($login, $password, $remember);

            if (is_wp_error($auth_result)) {
                $error_code = $auth_result->get_error_code();
                $message = strip_tags($auth_result->get_error_message($error_code));

                if ($error_code === 'incorrect_password') {
                    $message = str_replace('Забыли пароль?', '<a href="/auth/forgot-password">Забыли пароль?</a>', $message);
                }

                return new \WP_REST_Response(
                    array(
                        'code'    => $error_code,
                        'message' => $message,
                    ),
                    403
                );
            }

            // error_log("auth_result:" . print_r($auth_result, true));
            StudentLearning::complete_block_by_guest_module_passing_state($modulePassingState, $auth_result['user']['id']);
            Student::save_user_ip_if_not_exist($auth_result['user']['id']);
            Student::refresh_last_login_time($auth_result['user']['id']);

            return $auth_result;
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('tps/v1', '/auth/register', [
        'methods' => \WP_REST_Server::EDITABLE,
        'callback' => function ($request) {
            $first_name = $request->get_param('first_name');
            $last_name = $request->get_param('last_name');
            $email = $request->get_param('email');
            $password = $request->get_param('password');
            $modulePassingState = $request->get_param('modulePassingState');

            if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
                return new \WP_Error(
                    'invalid_params',
                    __('Invalid params', 'tps'),
                    array('status' => 400)
                );
            }

            $auth = new \Teplosocial\models\Auth();
            $auth_result = $auth->register([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password,
            ]);

            if (is_wp_error($auth_result)) {
                $error_code = $auth_result->get_error_code();

                return new \WP_REST_Response(
                    array(
                        'code'       => $error_code,
                        'message'    => strip_tags($auth_result->get_error_message($error_code)),
                    ),
                    403
                );
            }

            // error_log("auth_result:" . print_r($auth_result, true));
            StudentLearning::complete_block_by_guest_module_passing_state($modulePassingState, $auth_result['user']['id']);
            Student::save_user_ip_if_not_exist($auth_result['user']['id']);
            Student::refresh_last_login_time($auth_result['user']['id']);

            return $auth_result;
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('tps/v1', '/auth/validate-token', [
        'methods' => \WP_REST_Server::EDITABLE,
        'callback' => function ($request) {
            $token = $request->get_param('token');
            $short_response = is_null($request->get_param('short_response')) || gettype($request->get_param('short_response')) !== 'boolean' ? false : $request->get_param('short_response');

            $auth = new \Teplosocial\models\Auth();

            try {
                $token = $auth->parse_token_from_request();
            } catch (\Teplosocial\models\NoAuthHeaderException $ex) {
                return new \WP_REST_Response(
                    array(
                        'code' => 'no_auth_header',
                        'message' => __('No auth header', 'tps'),
                    ),
                    400
                );
            }

            if (!$token) {
                return new \WP_REST_Response(
                    array(
                        'code' => 'bad_auth_header',
                        'message' => __('Bad auth header', 'tps'),
                    ),
                    400
                );
            }

            try {
                return $auth->validate_token($token, $short_response);
            } catch (\Teplosocial\models\InvalidAuthTokenException $ex) {
                return new \WP_REST_Response(
                    array(
                        'code' => 'invalid_auth_token',
                        'message' => __('Invalid auth token', 'tps'),
                    ),
                );
            } catch (\Teplosocial\models\UserNotFoundException $ex) {
                return new \WP_REST_Response(
                    array(
                        'code' => 'user_not_found',
                        'message' => __('User not found', 'tps'),
                    ),
                );
            }
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('tps/v1', '/auth/retrieve-password', [
        'methods' => \WP_REST_Server::EDITABLE,
        'callback' => function ($request) {
            $email = $request->get_param('email');

            if (empty($email)) {
                return new \WP_Error(
                    'invalid_params',
                    __('Invalid params', 'tps'),
                    array('status' => 400)
                );
            }

            $auth = new \Teplosocial\models\Auth();
            $auth_result = $auth->retrieve_password($email);

            if (is_wp_error($auth_result)) {
                $error_code = $auth_result->get_error_code();

                return new \WP_REST_Response(
                    array(
                        'code'       => $error_code,
                        'message'    => strip_tags($auth_result->get_error_message($error_code)),
                    ),
                    403
                );
            }

            return ['status' => 'ok'];
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('tps/v1', '/auth/change-password', [
        'methods' => \WP_REST_Server::EDITABLE,
        'callback' => function ($request) {
            $user_id = $request->get_param('user_id');
            $old_password = $request->get_param('old_password');
            $new_password = $request->get_param('new_password');
            $new_password_repeat = $request->get_param('new_password_repeat');


            if (empty($old_password) || empty($new_password) || empty($new_password_repeat)) {
                return new \WP_Error(
                    'invalid_params',
                    __('Invalid params', 'tps'),
                    array('status' => 400)
                );
            }

            $auth = new \Teplosocial\models\Auth();
            $auth_result = $auth->change_password($user_id, $old_password, $new_password, $new_password_repeat);

            if (is_wp_error($auth_result)) {
                $error_code = $auth_result->get_error_code();

                return new \WP_REST_Response(
                    array(
                        'code'       => $error_code,
                        'message'    => strip_tags($auth_result->get_error_message($error_code)),
                    ),
                    403
                );
            }

            return ['data' => ['status' => 201]];
        },
        'permission_callback' => function (\WP_REST_Request $request) {
            $validation_request = new \WP_REST_Request('POST', '/tps/v1/auth/validate-token');

            $validation_request->set_body_params([
                'short_response' => true
            ]);

            $validation_response = \rest_do_request($validation_request);

            if (!\is_wp_error($validation_response) && $validation_response->data['is_valid']) {
                $request->set_param('user_id', $validation_response->data['user']['id']);

                return true;
            }

            return false;
        }
    ]);
}
add_action('rest_api_init', 'Teplosocial\REST\auth_api_add_routes');
