<?php

namespace Teplosocial\API;

use \Teplosocial\API\PostRestApi;
use \Teplosocial\models\Course;
use \Teplosocial\models\Module;

class ModuleRestApi extends PostRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/modules/by-course/(?P<slug>[- _0-9a-zA-Z]+)', [
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $course = Course::get($slug);
    
                if(!$course) {
                    return new \WP_Error(
                        'rest_tps_course_not_found',
                        __( 'Course not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
    
                $request->set_param('course_id', $course->ID);
                $request->set_route('/wp/v2/' . Module::$post_type);
    
                return rest_do_request( $request );
            },
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( 'tps/v1', '/modules/by-course/(?P<slug>[- _0-9a-zA-Z]+)/completed-by-adaptest', [
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $course = Course::get($slug);
    
                if(!$course) {
                    return new \WP_Error(
                        'rest_tps_course_not_found',
                        __( 'Course not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
    
                $request->set_param('course_id', $course->ID);
                $request->set_route('/wp/v2/' . Module::$post_type);
    
                ['data' => $modules] = (array) \rest_do_request($request);

                $filtered_modules = [];

                $user_id = \get_current_user_id();
                // \error_log("user_id: " . $user_id);
                
                if($user_id) {
                    foreach($modules as $module) {
                        // \error_log("is_completed_by_adaptest: " . $module['id']);
                        if(Module::is_completed_by_adaptest($module['id'], $user_id)) {
                            $filtered_modules[] = $module;
                        }
                    }
                }

                return $filtered_modules;
            },
            'permission_callback' => '__return_true',
        ] );
    }    

    public static function post_query($args, $request)
    {
        // filter by course
        $course_id = $request->get_param('course_id');
        // error_log("course_id: " . $course_id);
    
        if($course_id) {
            $args = array_merge($args, [
                'connected_type'    => Module::$connection_course_module,
                'connected_items'   => $course_id,
                'orderby'          => [
                    'menu_order' => 'ASC',
                ],
            ]);
        }
    
        // error_log("result args: " . print_r($args, true) );
    
        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
            'duration' => [
                'type'        => 'Int',
                'resolve'     => function( $module ) {
                    return Module::get_duration($module->ID);
                },
            ],
            'points' => [
                'type'        => 'Int',
                'resolve'     => function( $module ) {
                    return Module::get_points($module->ID);
                },
            ],
            'numberOfBlocks' => [
                'type'        => 'Int',
                'resolve'     => function( $module ) {
                    return Module::count_blocks($module->ID);
                },
            ],
            'numberOfCompletedBlocks' => [
                'type'        => 'Int',
                'resolve'     => function( $module ) {
                    $user_id = \get_current_user_id();
                    return Module::count_completed_blocks($module->ID, $user_id);
                },
            ],
            'isStarted' => [
                'type'        => 'Bool',
                'description' => __( 'Currently logged in student started the module', 'tps' ),
                'resolve'     => function( $module, $args, $context ) {
                    $user_id = \get_current_user_id();
                    return Module::is_started_by_user($module->ID, $user_id);
                },
            ],
            'isCompleted' => [
                'type'        => 'Bool',
                'description' => __( 'Currently logged in student completed the module', 'tps' ),
                'resolve'     => function( $module, $args, $context ) {
                    $user_id = \get_current_user_id();
                    return Module::is_completed_by_user($module->ID, $user_id);
                },
            ],
            'courseSlug' => [
                'type'        => 'String',
                'resolve'     => function( $module ) {
                    $course = Course::get_by_module($module->ID);
                    return $course ? $course->post_name : "";
                },
            ],
            'isCompletedByAdaptest' => [
                'type'        => 'Bool',
                'resolve'     => function( $module ) {
                    $user_id = \get_current_user_id();
                    return $module ? Module::is_completed_by_adaptest($module->ID, $user_id) : false;
                },
            ],
        ];

        self::register_post_type_fields(Module::$post_type, $fields);
    }

    public static function fix_seo_integration($server) {
        self::fix_post_type_seo_integration($server, Module::$post_type);
    }
}

add_action( 'rest_api_init', '\Teplosocial\API\ModuleRestApi::add_routes' );
add_filter( 'rest_' . Module::$post_type . '_query', '\Teplosocial\API\ModuleRestApi::post_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\ModuleRestApi::register_fields', 11 );
add_action( 'rest_api_init', '\Teplosocial\API\ModuleRestApi::fix_seo_integration', 11 );
