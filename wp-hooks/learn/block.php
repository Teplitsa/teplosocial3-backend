<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\Block;
use \Teplosocial\models\StudentLearning;

class BlockHooks {
    public static function init_meta() {
        $cmb = new_cmb2_box( array(
            'id'            => 'block_metabox',
            'title'         => __( 'Teplosocial meta', 'tps' ),
            'object_types'  => array( Block::$post_type, ), // Post type
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true, // Show field names on the left
        ) );

        $cmb->add_field( array(
            'name'       => __( 'Duration (in minutes)', 'tps' ),
            'id'         => Block::META_DURATION,
            'type'       => 'text',
            'sanitization_cb' => '\Teplosocial\utils\sanitize_positive_number_meta',
        ) );        
        $cmb->add_field( array(
            'name'       => __( 'Link to video text version', 'tps' ),
            'id'         => Block::META_VIDEO_TEXT_VERSION_LINK,
            'type'       => 'text',
        ) );        
        $cmb->add_field( array(
            'name'       => __( 'Task fields (for task blocks only)', 'tps' ),
            'id'         => Block::META_TASK_FIELDS,
            'type'       => 'multicheck',
            'select_all_button' => false,
            'options' => array(
                Block::TASK_FIELD_FILE => __('File Upload', 'tps'),
                Block::TASK_FIELD_URL  => __('Link', 'tps'),
                Block::TASK_FIELD_TEXT => __('Text', 'tps'),
            ),
        ) );        
    }

    public static function render_conent_video($content) {
        $block = get_post();
        $lesson_settings = learndash_get_setting( $block );
        $ld_course_videos = \Learndash_Course_Video::get_instance();
        return $ld_course_videos->add_video_to_content( $content, $block, $lesson_settings );;
    }

    public static function save_block($post_id, $post, $update) {
    
        $block = Block::get($post_id);
        
        if( !$block ) {
            return;
        }
    
        if ( Block::$post_type != $block->post_type ) {
            return;
        }
        
        if( !Block::is_video_block($block) ) {
            return;
        }

        $new_content = $block->post_content;

        $video_text_version = trim(get_post_meta($block->ID, Block::META_VIDEO_TEXT_VERSION_LINK, true));
        $link_to_video_text_version = "";

        $new_content = \preg_replace("/<p>\s*<a class=\"tps-video-text-version\".*?<\/a>\s*<\/p>/", "", $new_content);
        $new_content = \preg_replace("/<a class=\"tps-video-text-version\".*?<\/a>/", "", $new_content);

        if($video_text_version) {
            $link_to_video_text_version = '<a class="tps-video-text-version" target="_blank" href="' . $video_text_version . '">Скачать текстовую расшифровку видео</a>';
        }

        if(Block::is_video_block_ld($block)) {
            if( !preg_match( '/\[\s*ld_video\s*\]/', $block->post_content ) ) {
                $new_content = $new_content . "\n<p>[ld_video]" . $link_to_video_text_version . "</p>";
            }
            else {
                $new_content = str_replace("[ld_video]", "[ld_video]" . $link_to_video_text_version, $new_content);
            }
        }
        elseif(Block::is_video_block_youtube($block)) {
            // error_log("youtube...");
            // error_log($link_to_video_text_version);
            // \error_log($new_content);
            $new_content = preg_replace("/(<!-- wp:embed {.*?\"providerNameSlug\":\"youtube\".*? -->.*?<!-- \/wp:embed -->)/si", '\1' . $link_to_video_text_version, $new_content);            
            // \error_log($new_content);
        }

        remove_action( 'save_post', '\Teplosocial\hooks\BlockHooks::save_block', 50 );
        wp_update_post( array( 'ID' => $post_id, 'post_content' => $new_content ) );
        add_action( 'save_post', '\Teplosocial\hooks\BlockHooks::save_block', 50, 3 );
    }

    public static function change_block_options($post_id, $post, $update)
    {
        // unlimitate number of assignments
        // error_log("post_data:" . print_r($_POST, true));
        if(!empty($_POST['learndash-lesson-display-content-settings'])) {
            $_POST['learndash-lesson-display-content-settings']['assignment_upload_limit_count'] = 0;

            Block::update_ld_options($post_id, [
                'sfwd-lessons_assignment_upload_limit_count' => 0,
            ]);
        }
    }
    
    public static function complete_block_on_test_complete($data, $user) {
        // error_log("complete_block_on_test_complete...");

        $block = $data['lesson'];
        $test = $data['quiz'];

        // error_log("user: " . $user->ID);
        // error_log("block: " . $block->post_name . "   " . $block->ID);
        
        $is_test_passed = (bool)$data['pass'];

        // error_log("is_test_passed: " . $is_test_passed);
    
        if(!$is_test_passed) {
            return;
        }
    
        StudentLearning::complete_block_by_user($block->ID, $user->ID);
    }

    public static function learndash_course_steps_post_status_keys($post_status_keys)
    {
        // error_log("post_status_keys:" . print_r($post_status_keys, true));
        return ["publish"];
    }

    public static function learndash_course_steps_post_statuses($post_status_keys)
    {
        // error_log("post_status_keys:" . print_r($post_status_keys, true));

        $ignore_statues = ['future', 'draft', 'pending', 'private', 'trash'];
    
        $res = [];
        foreach($post_status_keys as $key => $value) {
            if(!\in_array($key, $ignore_statues)) {
                $res[$key] = $value;
            }
        }

        // error_log("res:" . print_r($res, true));

        return $res;
    }
}

add_action( 'cmb2_admin_init', '\Teplosocial\hooks\BlockHooks::init_meta' );
add_filter( 'the_content', '\Teplosocial\hooks\BlockHooks::render_conent_video', 1 );
add_action( 'save_post', '\Teplosocial\hooks\BlockHooks::save_block', 50, 3 );
add_action( 'save_post', '\Teplosocial\hooks\BlockHooks::change_block_options', 50, 3 );
add_action( 'learndash_quiz_completed', '\Teplosocial\hooks\BlockHooks::complete_block_on_test_complete', 5, 2);
add_filter( 'learndash_course_steps_post_status_keys', '\Teplosocial\hooks\BlockHooks::learndash_course_steps_post_status_keys');
add_filter( 'learndash_course_steps_post_statuses', '\Teplosocial\hooks\BlockHooks::learndash_course_steps_post_statuses');
