<?php

namespace Teplosocial\API;

use \Teplosocial\API\PostRestApi;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;

class TrackRestApi extends PostRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/tracks/(?P<slug>[- _0-9a-zA-Z]+)/start', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $action = $request->get_param('action');
                $track = Track::get($slug);

                if(!$track) {
                    return new \WP_Error(
                        'rest_tps_track_not_found',
                        __( 'Course not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
                
                // error_log("track:" . $track->post_name);
                // error_log("track_id:" . $track->ID);

                try {
                    return Track::start_track($track, $_POST);
                }
                catch(\Teplosocial\exceptions\AuthenticationRequiredException $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'authentication_required',
                            'message' => __( 'Error', 'tps' ),
                        ),
                        500
                    );
                }
                catch(\Exception $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'start_track_error',
                            'message' => __( 'Error', 'tps' ),
                        ),
                        500
                    );
                }
            },
            'permission_callback' => '__return_true',
        ] );
    }    

    public static function post_query($args, $request)
    {
        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
            'duration' => [
                'type'        => 'Int',
                'resolve'     => function( $track ) {
                    return Track::get_duration($track->ID);
                },
            ],
            'points' => [
                'type'        => 'Int',
                'resolve'     => function( $track ) {
                    return Track::get_points($track->ID);
                },
            ],
            'numberOfBlocks' => [
                'type'        => 'Int',
                'resolve'     => function( $track ) {
                    return Track::count_blocks($track->ID);
                },
            ],
            'numberOfCompletedBlocks' => [
                'type'        => 'Int',
                'resolve'     => function( $track ) {
                    $user_id = \get_current_user_id();
                    return Track::count_completed_blocks($track->ID, $user_id);
                },
            ],
            'isStarted' => [
                'type'        => 'Bool',
                'description' => __( 'Currently logged in student started the track', 'tps' ),
                'resolve'     => function( $track, $args, $context ) {
                    $user_id = \get_current_user_id();
                    return Track::is_started_by_user($track->ID, $user_id);
                },
            ],
            'isCompleted' => [
                'type'        => 'Bool',
                'description' => __( 'Currently logged in student completed the track', 'tps' ),
                'resolve'     => function( $track, $args, $context ) {
                    $user_id = \get_current_user_id();
                    return Track::is_completed_by_user($track->ID, $user_id);
                },
            ],
        ];

        self::register_post_type_fields(Track::$post_type, $fields);
    }

    public static function fix_seo_integration($server) {
        self::fix_post_type_seo_integration($server, Track::$post_type);
    }

    public static function customize_collection_params($params) {
        $params['orderby']['enum'][] = 'menu_order';
        return $params;
    }
}

add_action( 'rest_api_init', '\Teplosocial\API\TrackRestApi::add_routes' );
add_filter( 'rest_' . Track::$post_type . '_query', '\Teplosocial\API\TrackRestApi::post_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\TrackRestApi::register_fields', 11 );
add_action( 'rest_api_init', '\Teplosocial\API\TrackRestApi::fix_seo_integration', 11 );
add_filter( 'rest_' . Track::$post_type . '_collection_params', '\Teplosocial\API\CourseRestApi::customize_collection_params', 10, 1 );