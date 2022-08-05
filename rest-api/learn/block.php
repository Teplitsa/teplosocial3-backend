<?php

namespace Teplosocial\API;

use \Teplosocial\API\PostRestApi;
use \Teplosocial\models\Block;
use \Teplosocial\models\Module;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;
use \Teplosocial\models\StudentLearning;

class BlockRestApi extends PostRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/blocks/sibling-list-by-block/(?P<slug>[- _0-9a-zA-Z]+)', [
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

        register_rest_route( 'tps/v1', '/blocks/(?P<slug>[- _0-9a-zA-Z]+)/complete', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function($request) {
                $slug = $request->get_param('slug');
                $action = $request->get_param('action');
                $block = Block::get($slug);

                if(!$block) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Block not found', 'tps' ),
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
                        500
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
        // error_log("result BlockRestApi args: " . print_r($args, true) );
        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
            'duration' => [
                'type'        => 'Int',
                'resolve'     => function( $block ) {
                    return Block::get_duration($block->ID);
                },
            ],
            'points' => [
                'type'        => 'Int',
                'resolve'     => function( $block ) {
                    return Block::get_points($block);
                },
            ],
            'contentType' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    return Block::get_type($block);
                },
            ],
            'isFinalInModule' => [
                'type'        => 'Bool',
                'resolve'     => function( $block ) {
                    return Module::is_final_block($block->ID);
                },
            ],
            'courseSlug' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    $course = Course::get_by_block($block->ID);
                    return $course ? $course->post_name : "";
                },
            ],
            'trackSlug' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    $course = Course::get_by_block($block->ID);
                    $track = Track::get_by_course($course->ID);
                    return $track ? $track->post_name : "";
                },
            ],
            'moduleSlug' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    // error_log("block_slug:" . $block->post_name);
                    $module = Module::get_by_block($block->ID);
                    return $module ? $module->post_name : "";
                },
            ],
            'nextBlockSlug' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    // error_log("block_slug:" . $block->post_name);
                    $user_id = \get_current_user_id();
                    $module = Module::get_by_block($block->ID);
                    if($user_id) {
                        $nextBlock = $module ? Module::get_next_block($module->ID, $block->ID) : null;
                    }
                    else {
                        $nextBlock = $module ? Module::get_next_block_for_guest($module->ID, $block->ID) : null;
                    }
                    return $nextBlock ? $nextBlock->post_name : "";
                },
            ],
            'nextUncompletedBlockSlug' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    $user_id = \get_current_user_id();
                    $module = Module::get_by_block($block->ID);
                    $nextBlock = ($module && $user_id) ? Module::get_next_uncompleted_block_by_user($module->ID, $user_id, $block->ID) : null;
                    return $nextBlock ? $nextBlock->post_name : "";
                },
            ],
            'isStarted' => [
                'type'        => 'Bool',
                'description' => __( 'Block is always started.', 'tps' ),
                'resolve'     => function( $block, $args, $context ) {
                    return true;
                },
            ],
            'isCompleted' => [
                'type'        => 'Bool',
                'description' => __( 'Currently logged in student completed the block', 'tps' ),
                'resolve'     => function( $block, $args, $context ) {
                    $user_id = \get_current_user_id();
                    return Module::is_block_completed_by_user($block->ID, $user_id);
                },
            ],
            'taskAvailableFields' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    $taskAvailableFields = get_post_meta($block->ID, Block::META_TASK_FIELDS, true);
                    return $taskAvailableFields ? $taskAvailableFields : [Block::TASK_FIELD_FILE];
                },
            ],
            'uploadAssignmentNonce' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    $user_id = get_current_user_id();
                    $block_id = $block->ID;
                    return \wp_create_nonce( 'uploadfile_' . $user_id . '_' . $block_id );
                },
            ],
            'uploadedTask' => [
                'type'        => 'String',
                'resolve'     => function( $block ) {
                    $user_id = \get_current_user_id();
                    $block_id = $block->ID;
                    // \error_log("uploadAssignmentNonce user_id: " . $user_id);
                    return Block::get_block_assignment($block_id, $user_id);
                },
            ],
            'isCompletedByAdaptest' => [
                'type'        => 'Bool',
                'resolve'     => function( $block, $args, $context ) {
                    $user_id = \get_current_user_id();
                    $module = Module::get_by_block($block->ID);
                    return $module ? Module::is_completed_by_adaptest($module->ID, $user_id) : false;
                },
            ],
            'isAvailableForGuest' => [
                'type'        => 'Bool',
                'resolve'     => function( $block, $args, $context ) {
                    return Course::is_block_available_for_guest($block);
                },
            ],            
        ];

        self::register_post_type_fields(Block::$post_type, $fields);
    }

    public static function fix_seo_integration($server) {
        self::fix_post_type_seo_integration($server, Block::$post_type);
    }
}

add_action( 'rest_api_init', '\Teplosocial\API\BlockRestApi::add_routes' );
add_filter( 'rest_' . Block::$post_type . '_query', '\Teplosocial\API\BlockRestApi::post_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\BlockRestApi::register_fields', 11 );
add_action( 'rest_api_init', '\Teplosocial\API\BlockRestApi::fix_seo_integration', 11 );
