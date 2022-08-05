<?php

namespace Teplosocial\API;

use \Teplosocial\API\PostRestApi;
use \Teplosocial\models\Track;
use \Teplosocial\models\Module;
use \Teplosocial\models\Block;
use \Teplosocial\models\Quiz;
use \Teplosocial\models\Adaptest;
use \Teplosocial\models\StudentLearning;

class QuizRestApi extends PostRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/quiz/(?P<slug>[- _0-9a-zA-Z]+)', [
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $quiz = Quiz::get($slug);
    
                if(!$quiz) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Quiz not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $module = Module::get_by_quiz($quiz->ID);
                if(!$module) {
                    return new \WP_Error(
                        'rest_tps_module_not_found',
                        __( 'Module not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
    
                $block = Block::get_by_quiz($block_id);
                if(!$block) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Block not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $request->set_param('course', $module->ID);
                $request->set_param('lesson', $block->ID);
                $request->set_route('/ldlms/v1/' . Quiz::$post_type);
    
                return rest_do_request( $request );
            },
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'tps/v1', '/quiz/get-by-block/(?P<slug>[- _0-9a-zA-Z]+)', [
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $block = Block::get($slug);
    
                if(!$block) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Block not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $module = Module::get_by_block($block->ID);
                if(!$module) {
                    return new \WP_Error(
                        'rest_tps_module_not_found',
                        __( 'Module not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $request->set_param('course', $module->ID);
                $request->set_param('lesson', $block->ID);
                // $request->set_param('tps_request_id', 'get_quiz_by_block');
                $request->set_route('/ldlms/v1/' . Quiz::$post_type);

                return rest_do_request( $request );
            },
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'tps/v1', '/quiz/(?P<slug>[- _0-9a-zA-Z]+)/questions', [
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $block = Block::get($slug);
    
                if(!$block) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Block not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $module_id = intval(get_post_meta($block->ID, 'course_id', true));
                // error_log("block module_id:" . $module_id);
                $module = Module::get($module_id);

                if(!$module) {
                    return new \WP_Error(
                        'rest_tps_module_not_found',
                        __( 'Module not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
    
                $request->set_param('course', $module->ID);
                $request->set_route('/ldlms/v1/' . Block::$post_type);
    
                return rest_do_request( $request );
            },
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'tps/v1', '/quiz/(?P<slug>[- _0-9a-zA-Z]+)/complete', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $action = $request->get_param('action');
                $block = Quiz::get($slug);

                if(!$block) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Quiz not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
                
                // error_log("block:" . $block->post_name);
                // error_log("block_id:" . $block->ID);

                $user_id = \get_current_user_id();
                if(!$user_id) {
                    return new \WP_Error(
                        'authentication_required',
                        __( 'Authentication required', 'tps' ),
                        array( 'status' => 403 )
                    );
                }

                try {
                    return StudentLearning::complete_block($block->ID, $user_id);
                }
                catch(\Teplosocial\exceptions\AuthenticationRequiredException $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'authentication_required',
                            'message' => __( 'Error', 'tps' ),
                        ),
                        403
                    );
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
            },
            'permission_callback' => '__return_true',
        ] );        
    }    

    public static function post_query($args, $request)
    {
        // $request_id = $request->get_param('tps_request_id');
        $block_id = $request->get_param('lesson');
        if($block_id) {
            $args['meta_query'][] = [
                'key'     => Block::META_LD_BLOCK_ID,
                'value'   => $block_id,
            ];
        }

        $module_id = $request->get_param('course');
        if($module_id) {
            $args['meta_query'][] = [
                'key'     => Module::META_LD_MODULE_ID,
                'value'   => $module_id,
            ];
        }

        // error_log("quiz args: " . print_r($args, true));
        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
            'nonce' => [
                'type'        => 'String',
                'resolve'     => function( $quiz ) {
                    $user_id = get_current_user_id();
                    $quiz_pro_id = Quiz::get_quiz_pro_id($quiz->ID);
                    // error_log("current_time: " . current_time('mysql'));
                    // error_log("quiz nonce action: " . 'sfwd-quiz-nonce-' . $quiz->ID . '-' . $quiz_pro_id . '-' . $user_id);
                    // error_log("nonce: " . wp_create_nonce( 'sfwd-quiz-nonce-' . $quiz->ID . '-' . $quiz_pro_id . '-' . $user_id ));
                    // error_log("wp_nonce_tick:" . wp_nonce_tick());
                    // error_log("wp_get_session_token:" . wp_get_session_token());                    
                    return wp_create_nonce( 'sfwd-quiz-nonce-' . $quiz->ID . '-' . $quiz_pro_id . '-' . $user_id );
                },
            ],
            'quizProId' => [
                'type'        => 'Int',
                'resolve'     => function( $quiz ) {
                    return Quiz::get_quiz_pro_id($quiz->ID);
                },
            ],
            'questions' => [
                'type'        => 'Array',
                'resolve'     => function( $quiz ) {
                    // error_log("quiz_slug:" . $quiz->post_name);
                    return Quiz::get_questions($quiz->ID);
                },
            ],
            'isAdaptest' => [
                'type'        => 'Boolean',
                'resolve'     => function( $quiz ) {
                    return Adaptest::is_quiz_adaptest($quiz->ID);
                },
            ],
            'duration' => [
                'type'        => 'Int',
                'resolve'     => function( $quiz ) {
                    return intval(get_post_meta($quiz->ID, Adaptest::META_ADAPTEST_DURATION, true));
                },
            ],
            'courseSlug' => [
                'type'        => 'String',
                'resolve'     => function( $quiz ) {
                    $course_id = Adaptest::get_course_id($quiz->ID);
                    if(!$course_id) {
                        return "";
                    }

                    $course = get_post($course_id);
                    return $course ? $course->post_name : "";
                },
            ],
            // 'trackSlug' => [
            //     'type'        => 'String',
            //     'resolve'     => function( $quiz ) {
            //         $course_id = Adaptest::get_course_id($quiz->ID);
            //         if(!$course_id) {
            //             return "";
            //         }

            //         $track = Track::get_by_course($course_id);
            //         return $track ? $track->post_name : "";
            //     },
            // ],
        ];

        self::register_post_type_fields(Quiz::$post_type, $fields);
    }

    public static function fix_seo_integration($server) {
        self::fix_post_type_seo_integration($server, Quiz::$post_type);
    }
}

add_action( 'rest_api_init', '\Teplosocial\API\QuizRestApi::add_routes' );
add_filter( 'rest_' . Quiz::$post_type . '_query', '\Teplosocial\API\QuizRestApi::post_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\QuizRestApi::register_fields', 11 );
add_action( 'rest_api_init', '\Teplosocial\API\QuizRestApi::fix_seo_integration', 11 );
