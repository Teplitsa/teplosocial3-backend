<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\Assignment;
use \Teplosocial\models\Block;

class AssignmentHooks {
    public static function init_meta() {
        $assignment_details_cmb = new_cmb2_box( array(
            'id'            => 'tps_assignment_extra_metabox',
            'title'         => __('Extra data for approval', 'tps'),
            'object_types'  => array(Assignment::$post_type),
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ));
    
        $assignment_details_cmb->add_field(array(
            'name'    => __('Submitted URL', 'tps'),
            'id'      => Assignment::META_URL,
            'type' => 'text',
            'default' => '',
        ));
        
        $assignment_details_cmb->add_field(array(
            'name'    => __('Submitted text', 'tps'),
            'id'      => Assignment::META_TEXT,
            'type' => 'textarea',
            'default' => '',
        ));
    }

    public static function save_assignment_extra_data($assignment_post_id, $assignment_meta = false) {
        // error_log("save_assignment_extra_data...");
    
        $assignment_url = isset($_POST['assignment_url']) ? trim($_POST['assignment_url']) : '';
        $assignment_text = isset($_POST['assignment_text']) ? trim($_POST['assignment_text']) : '';
        $block_id = Assignment::get_assignment_block_id($assignment_post_id);
        
        $assignment_post = get_post($assignment_post_id);
        $exist_disp_name = $assignment_post ? $assignment_post->post_title : "";

        // error_log("exist_disp_name: " . $exist_disp_name);
        // error_log("Assignment::EMPTY_ASSIGNMENT_FILE_NAME: " . Assignment::EMPTY_ASSIGNMENT_FILE_NAME);

        // BEGIN assignments moderation
        $block_main_assignment_id = Block::get_block_main_assignment($block_id, $assignment_post->post_author);
        if($block_main_assignment_id && Assignment::is_block_assignment_declined($block_id)) {
            
            Assignment::copy_assignment_content($assignment_post_id, $block_main_assignment_id);
            Assignment::delete_decline_block_assignment_flag($block_id);
            $assignment_post_id = $block_main_assignment_id;
            
        }
        if(!$block_main_assignment_id) {
            Block::set_block_main_assignment($block_id, $assignment_post_id);
        }
        // END assignments moderations

        if(!$exist_disp_name || $exist_disp_name === Assignment::EMPTY_ASSIGNMENT_FILE_NAME) {
            $new_disp_name = Assignment::get_assignment_disp_name_from_text_data($assignment_url, $assignment_text);
            update_post_meta($assignment_post_id, Assignment::META_FILE_NAME, $new_disp_name);
            update_post_meta($assignment_post_id, Assignment::META_FILE_URL, get_post_permalink($assignment_post_id));
            wp_update_post( array('ID' => $assignment_post_id, 'post_title' => $new_disp_name) );
        }
        
        // error_log("assignment_url: " . $assignment_url);
        if($assignment_url) {
            update_post_meta($assignment_post_id, Assignment::META_URL, $assignment_url);
        }
    
        // error_log("assignment_text: " . $assignment_text);
        if($assignment_text) {
            update_post_meta($assignment_post_id, Assignment::META_TEXT, $assignment_text);
        }
    }    
}

add_action( 'cmb2_admin_init', '\Teplosocial\hooks\AssignmentHooks::init_meta' );
add_action( 'learndash_assignment_uploaded', '\Teplosocial\hooks\AssignmentHooks::save_assignment_extra_data', 15, 2 );
