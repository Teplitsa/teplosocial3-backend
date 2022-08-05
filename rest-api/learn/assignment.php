<?php

namespace Teplosocial\API;

use \Teplosocial\API\PostRestApi;
use \Teplosocial\models\Assignment;
use \Teplosocial\models\Block;
use \Teplosocial\models\Module;

class AssignmentRestApi extends PostRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/assignments/submit', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function($request) {
                // error_log("submit assignment: " . print_r($_POST, true));

                $block_id = $request->get_param('post');
                $block_id = intval($block_id);
                // error_log("block_id: " . $block_id);
                $block = Block::get($block_id);

                if(!$block) {
                    return new \WP_Error(
                        'rest_tps_block_not_found',
                        __( 'Block not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
                // error_log("block found: " . $block->post_name);

                $module_id = $request->get_param('course_id');
                $module_id = intval($module_id);
                // error_log("module_id: " . $module_id);
                $module = Module::get($module_id);

                if(!$module) {
                    return new \WP_Error(
                        'rest_tps_module_not_found',
                        __( 'Module not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }
                // error_log("module found: " . $module->post_name);

                Assignment::upload($block_id);
                
                return ['status' => 'ok'];
            },
            'permission_callback' => '__return_true',
        ] );
    }    

    public static function post_query($args, $request)
    {
        // error_log("result AssignmentRestApi args: " . print_r($args, true) );
        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
        ];

        self::register_post_type_fields(Assignment::$post_type, $fields);
    }

    public static function fix_seo_integration($server) {
        self::fix_post_type_seo_integration($server, Assignment::$post_type);
    }
}

add_action( 'rest_api_init', '\Teplosocial\API\AssignmentRestApi::add_routes' );
add_filter( 'rest_' . Assignment::$post_type . '_query', '\Teplosocial\API\AssignmentRestApi::post_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\AssignmentRestApi::register_fields', 11 );
add_action( 'rest_api_init', '\Teplosocial\API\AssignmentRestApi::fix_seo_integration', 11 );
