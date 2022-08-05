<?php

namespace Teplosocial\API;

use Teplosocial\API\UserRestApi;
use Teplosocial\models\Student;

class StudentRestApi extends UserRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/user/update-profile', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function($request) {
                $current_user_id = \get_current_user_id();                
                // error_log("current user id:" . $current_user_id);
                if(!$current_user_id) {
                    return new \WP_Error(
                        'authentication_required',
                        __( 'Error', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $user = Student::get($current_user_id);
                if(!$user) {
                    return new \WP_Error(
                        'rest_tps_user_not_found',
                        __( 'User not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
                
                // error_log("user:" . $user->user_login);
                // error_log("user_id:" . $user->ID);

                try {
                    Student::update_profile($user, $request->get_params());
                    $user = Student::get($user->ID);
                }
                catch(\Exception $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'block_action_error',
                            'message' => __( 'Error', 'tps' ),
                        ),
                        500
                    );
                }

                // error_log("result user:" . print_r($user, true));
                return $user;
            },
            'permission_callback' => '__return_true',
        ] );        
    }

    public static function user_query($args) {
        // \error_log(print_r($args, true));
        
        unset($args['has_published_posts']);

        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
            'firstName' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return wp_specialchars_decode(Student::get_meta($user->ID, Student::META_FIRST_NAME));
                },
            ],
            'lastName' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return wp_specialchars_decode(Student::get_meta($user->ID, Student::META_LAST_NAME));
                },
            ],
            'description' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return wp_specialchars_decode(Student::get_meta($user->ID, Student::META_DESCRIPTION));
                },
            ],
            'city' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return Student::get_meta($user->ID, Student::META_CITY);
                },
            ],
            'socialLinks' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return Student::get_social_links($user->ID);
                },
            ],
            'points' => [
                'type'        => 'Int',
                'resolve'     => function( $user ) {
                    return Student::get_points($user->ID);
                },
            ],
            'avatar' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return Student::get_avatar_url($user);
                },
            ],
            'avatarFile' => [
                'type'        => 'Array',
                'resolve'     => function( $user ) {
                    return Student::get_avatar_file($user->ID);
                },
            ],
            'fileUploadNonce' => [
                'type'        => 'String',
                'resolve'     => function( $user ) {
                    return wp_create_nonce( 'wp_rest' );
                },
            ]
        ];

        self::register_user_fields($fields);
    }
}

add_action( 'rest_api_init', '\Teplosocial\API\StudentRestApi::add_routes' );
add_filter( 'rest_user_query', '\Teplosocial\API\StudentRestApi::user_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\StudentRestApi::register_fields', 11 );
add_action( 'rest_api_init', '\Teplosocial\API\StudentRestApi::fix_seo_integration', 11 );