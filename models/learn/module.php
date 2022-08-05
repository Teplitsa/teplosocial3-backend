<?php 

namespace Teplosocial\models;

use \Teplosocial\models\Post;
use \Teplosocial\models\Block;

class Module extends Post
{
    public static $post_type = 'sfwd-courses';
    public static $connection_course_module = 'course-module';

    public const META_LD_MODULE_ID = 'course_id';

    public const USER_META_MODULE_COMPLETED = 'course_completed_'; // LD meta field
    public const USER_META_MODULE_STARTED = 'tps_module_started_';
    public const USER_META_MODULE_COMPLETED_BY_ADAPTEST = 'tps_module_completed_by_adaptest_';

    public static function get_by_block($block_id)
    {
        $module_id = intval(get_post_meta($block_id, self::META_LD_MODULE_ID, true));
        return $module_id ? static::get($module_id) : null;
    }

    public static function get_by_quiz($quiz_id)
    {
        return self::get_by_block($quiz_id);
    }

    public static function get_list_by_course($course_id)
    {
        return static::get_list([
            'connected_type'    => self::$connection_course_module,
            'connected_from'    => $course_id,            
        ]);
    }

    public static function get_points($module_id)
    {
        $points = 0;
        $ld_blocks = \learndash_get_course_lessons_list($module_id);
        // error_log("points blocks count: " . count($ld_blocks));
        foreach($ld_blocks as $ld_block) {
            $points += Block::get_points($ld_block['post']->ID);
        }
        return $points;        
    }

    public static function get_duration($module_id)
    {
        $duration = 0;        
        $ld_blocks = \learndash_get_course_lessons_list($module_id);
        // error_log("duration blocks count: " . count($ld_blocks));
        foreach($ld_blocks as $ld_block) {
            $duration += Block::get_duration($ld_block['post']->ID);
        }
        return $duration;        
    }

    public static function count_blocks($module_id)
    {
        $ld_blocks = \learndash_get_course_lessons_list($module_id);
        return count($ld_blocks);
    }

    public static function count_completed_blocks($module_id, $user_id)
    {
        $count = 0;

        if(!$user_id) {
            return $count;
        }

        $ld_blocks = \learndash_get_course_lessons_list($module_id, $user_id);
        foreach($ld_blocks as $ld_block) {
            if(Block::is_ld_block_completed($ld_block)) {
                $count++;
            }
        }
        return $count;        
    }

    public static function complete_by_user($module_id, $user_id)
    {
        $timestamp = current_time('timestamp', true);
        update_user_meta($user_id, self::USER_META_MODULE_COMPLETED . $module_id, $timestamp);
        return $timestamp;
    }

    public static function is_completed_by_user($module_id, $user_id)
    {
        return $user_id ? \boolval(get_user_meta($user_id, self::USER_META_MODULE_COMPLETED . $module_id, true)) : false;
    }

    public static function start_by_user($module_id, $user_id) {
        $start_timestamp = current_time('timestamp', true);
        update_user_meta($user_id, self::USER_META_MODULE_STARTED . $module_id, $start_timestamp);
        return $start_timestamp;
    }

    public static function is_started_by_user($module_id, $user_id)
    {
        return $user_id ? \boolval(get_user_meta($user_id, self::USER_META_MODULE_STARTED . $module_id, true)) : false;
    }

    public static function get_next_block($module_id, $block_id)
    {        
        $all_blocks = \learndash_get_course_lessons_list($module_id, null); // , ['orderby' => 'menu_order']
        $next_is_target = false;
        
        foreach($all_blocks as $block_item) {
            
            if($next_is_target) {
                return $block_item['post'];
            }
            
            if($block_item['post']->ID == $block_id) {
                $next_is_target = true;
            }
            
        }
        
        return null;
    }

    public static function get_next_block_for_guest($module_id, $block_id=0)
    {        
        $all_blocks = \learndash_get_course_lessons_list($module_id, null); // , ['orderby' => 'menu_order']
        $next_is_target = false;
        
        foreach($all_blocks as $block_item) {
            if(!$block_id || $next_is_target) {
                if(!Block::is_test_block($block_item['post']->ID) && !Block::is_task_block($block_item['post']->ID)) {
                    return $block_item['post'];
                }
            }
            
            if($block_item['post']->ID == $block_id) {
                $next_is_target = true;
            }            
        }
        
        return null;
    }

    public static function get_next_uncompleted_block_by_user($module_id, $user_id, $block_id = null)
    {        
        $next_block = null;

        if($block_id) {
            $next_block = self::get_next_block($module_id, $block_id);
        }
        else {
            $all_blocks = \learndash_get_course_lessons_list($module_id, null); // , ['orderby' => 'menu_order']
            // error_log("count blocks:" . count($all_blocks));
            if(count($all_blocks)) {
                $first_block = array_shift($all_blocks);
                $next_block = $first_block ? $first_block['post'] : null;
            }
        }

        $ld_blocks = \learndash_get_course_lessons_list($module_id, $user_id); // , ['orderby' => 'menu_order']
        // error_log("count ld_blocks:" . count($ld_blocks));
        // error_log("first found next_block:" . ($next_block ? $next_block->post_name : ""));
        
        while(true) {
            if(!$next_block) {
                break;
            }

            $found_ld_block = null;
            foreach($ld_blocks as $ld_block) {
                // error_log("ld_block:" . $ld_block['post']->post_name . " status:" . $ld_block['status']);
                if($ld_block['post']->ID === $next_block->ID) {
                    $found_ld_block = $ld_block;
                    break;
                }
            }
    
            // error_log("found_ld_block:" . $found_ld_block['post']->post_name . " status:" . $found_ld_block['status']);
            if($found_ld_block && Block::is_ld_block_completed($found_ld_block)) {
                $next_block = self::get_next_block($module_id, $next_block->ID);
            }
            else {
                break;
            }
        }

        // error_log("final next_block:" . ($next_block ? $next_block->post_name : ""));
        return $next_block;
    }
    
    public static function is_final_block($block_id)
    {
        $module = static::get_by_block($block_id);
        // error_log("block_id:" . $block_id . " module_id:" . $module->ID);
        // error_log("next block:" . print_r(static::get_next_block($module->ID, $block_id), true));
        return $module ? !(bool)static::get_next_block($module->ID, $block_id) : false;
    }

    public static function is_block_completed_by_user($block_id, $user_id)
    {
        $is_completed = false;

        $module = static::get_by_block($block_id);
        if($module) {
            $ld_blocks = \learndash_get_course_lessons_list($module->ID, $user_id);
            $found_ld_block = null;
            foreach($ld_blocks as $ld_block) {
                // error_log("ld_block:" . $ld_block['post']->post_name . " status:" . $ld_block['status']);
                if($ld_block['post']->ID === $block_id) {
                    $found_ld_block = $ld_block;
                    break;
                }
            }

            // error_log("found_ld_block:" . $found_ld_block['post']->post_name . " status:" . $found_ld_block['status']);
            $is_completed = $found_ld_block && Block::is_ld_block_completed($found_ld_block);
        }

        return $is_completed;
    }

    public static function complete_by_adaptest($module_id, $user_id)
    {
        return \update_user_meta($user_id, self::USER_META_MODULE_COMPLETED_BY_ADAPTEST . $module_id, \current_time('timestamp'));
    }

    public static function is_completed_by_adaptest($module_id, $user_id)
    {
        // \error_log("module_id: " . $module_id);
        // \error_log("user_id: " . $user_id);
        return $user_id ? \boolval(\get_user_meta($user_id, self::USER_META_MODULE_COMPLETED_BY_ADAPTEST . $module_id, true)) : false;
    }
}
