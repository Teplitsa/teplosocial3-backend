<?php 

namespace Teplosocial\models;

use \Teplosocial\models\Post;
use \Teplosocial\models\Quiz;
use \Teplosocial\models\Assignment;
use \Teplosocial\models\StudentLearning;
use \Teplosocial\Config;

class Block extends Post
{
    public static $post_type = 'sfwd-lessons';

    public const META_DURATION = 'tps_block_duration';
    public const META_VIDEO_TEXT_VERSION_LINK = 'tps_video_text_version_url';
    public const META_LD_BLOCK_ID = 'lesson_id';
    public const META_TASK_FIELDS = 'tps_task_fields';
    public const META_ASSIGNMENT = 'tps_main_assignment';
    public const META_LD_OPTIONS = '_sfwd-lessons';

    public const TYPE_TEXT = 'text';
    public const TYPE_VIDEO = 'video';
    public const TYPE_TEST = 'test';
    public const TYPE_TASK = 'task';

    public const TASK_FIELD_FILE = 'file';
    public const TASK_FIELD_URL = 'url';
    public const TASK_FIELD_TEXT = 'text';

    public static function get_by_quiz($quiz_id)
    {
        $block_id = intval(get_post_meta($quiz_id, self::META_LD_BLOCK_ID, true));
        return $block_id ? static::get($block_id) : null;
    }

    public static function get_points($block_id)
    {
        return Config::BLOCK_POINTS;
    }

    public static function get_duration($block_id)
    {
        return \intval(get_post_meta($block_id, self::META_DURATION, true));
    }

    public static function is_test_block($block_id)
    {
        return count(get_posts([
            'post_type' => Quiz::$post_type,
            'meta_query' => [
                [
                    'key' => self::META_LD_BLOCK_ID,
                    'value' => $block_id,
                ]
            ],
        ])) > 0;
    }

    public static function is_task_block($block)
    {
        return \lesson_hasassignments( $block );
    }

    public static function is_video_block_ld($block)
    {
        $ld_block_meta = get_post_meta($block->ID, self::META_LD_OPTIONS, true);
        return isset($ld_block_meta['sfwd-lessons_lesson_video_enabled']) && $ld_block_meta['sfwd-lessons_lesson_video_enabled'] === 'on';
    }

    public static function is_video_block_youtube($block)
    {
        $is_youtube_video = false;
        $youtube_url_regex_list = [
            "/:\/\/www\.youtube\.com\//",
            "/:\/\/youtu\.be\//",
        ];
        foreach($youtube_url_regex_list as $regex) {
            $is_youtube_video = \preg_match($regex, $block->post_content);
            if($is_youtube_video) {
                break;
            }
        }

        if($is_youtube_video) {
            $is_youtube_video = $is_youtube_video && \preg_match("/(<!-- wp:embed {.*?\"providerNameSlug\":\"youtube\".*? -->.*?<!-- \/wp:embed -->)/si", $block->post_content);            
        }

        return $is_youtube_video;
    }

    public static function is_video_block($block)
    {
        return self::is_video_block_ld($block) || self::is_video_block_youtube($block);
    }

    public static function is_ld_block_completed($ld_block)
    {
        return $ld_block['status'] === 'completed';
    }

    public static function get_type($block)
    {
        if(self::is_video_block($block)) {
            // preg_match( '/\[\s*ld_video\s*\]/', $block->post_content );
            return self::TYPE_VIDEO;
        }
        elseif(self::is_task_block($block)) {
            return self::TYPE_TASK;
        }
        elseif(self::is_test_block($block->ID)) {
            return self::TYPE_TEST;
        }
        else {
            return self::TYPE_TEXT;
        }
    }

    public static function get_block_assignment($block_id, $user_id)
    {
        $block_assignments = get_posts([
            'post_type' => Assignment::$post_type,
            'author' => $user_id,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => Assignment::META_BLOCK_ID,
                    'value' => $block_id,
                ],
                [
                    'key' => Assignment::META_DECLINE_ASSIGNMENT,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        return count($block_assignments) > 0 ? $block_assignments[0] : null;
    }

    public static function set_block_main_assignment($block_id, $assignment_id)
    {
        $assignment = get_post($assignment_id);
        update_post_meta($block_id, self::META_ASSIGNMENT . "-u" . $assignment->post_author, $assignment_id);
    }

    public static function get_block_main_assignment($block_id, $user_id)
    {
        return (int)get_post_meta($block_id, self::META_ASSIGNMENT . "-u" . $user_id, True);
    }

    public static function update_ld_options($block_id, $ld_options) {
        $block_options = get_post_meta($block_id, self::META_LD_OPTIONS, true);
        $block_options = array_merge(!empty($block_options) ? $block_options : [], $ld_options);
        update_post_meta($block_id, self::META_LD_OPTIONS, $block_options);
    }
}
