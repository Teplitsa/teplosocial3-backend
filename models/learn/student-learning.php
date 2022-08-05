<?php

namespace Teplosocial\models;

use \Teplosocial\models\Student;
use \Teplosocial\models\Track;
use \Teplosocial\models\Course;
use \Teplosocial\models\Module;
use \Teplosocial\models\Block;

class StudentLearning
{
    public static function complete_block_by_user($block_id, $user_id)
    {
        self::save_block_completed_time($user_id, $block_id);
        
        $course = Course::get_by_block($block_id);
        self::update_course_last_action_time($user_id, $course->ID);

        \learndash_process_mark_complete( $user_id, $block_id );
    }

    public static function complete_block_by_guest_module_passing_state($modulePassingState, $user_id)
    {
        // error_log("modulePassingState: " . print_r($modulePassingState, true));

        if(empty($modulePassingState)) {
            return;
        }

        foreach($modulePassingState as $module_id => $completed_blocks) {
            foreach($completed_blocks as $block_id) {
                error_log("complete block: " . $block_id . " by user: " . $user_id);
                self::complete_block($block_id, $user_id);
            }
        }
    }

    public static function complete_block($block_id, $user_id)
    {
        // error_log("complete block ID: " . $block_id);

        if (!$user_id) {
            throw new \Teplosocial\exceptions\AuthenticationRequiredException();
        }

        StudentLearning::complete_block_by_user($block_id, $user_id);

        $ret = [];
        $course = null;
        $track = null;
        $completedModule = null;
        $completedCourse = null;
        $completedTrack = null;
        $uncompleted_module_block = null;
        $uncompleted_course_module = null;
        $uncompleted_track_course = null;
        $next_track_block = null;

        $module = Module::get_by_block($block_id);
        if ($module) {
            // error_log("block module:" . $module->post_name);
            // error_log("block module ID:" . $module->ID);

            $course = Course::get_by_module($module->ID);

            $uncompleted_module_block = Module::get_next_uncompleted_block_by_user($module->ID, $user_id);
            // error_log("uncompleted_module_block:" . ($uncompleted_module_block ? $uncompleted_module_block->post_name : ""));

            if (!$uncompleted_module_block) {
                $completedModule = $module;
            }
        }

        if ($course) {
            // error_log("block course: " . $course->post_name);
            // error_log("block course ID: " . $course->ID);

            $track = Track::get_by_course($course->ID);

            if ($completedModule) {
                $uncompleted_course_module = Course::get_first_uncompleted_module($course->ID, $user_id);
                // error_log("uncompleted_course_module:" . ($uncompleted_course_module ? $uncompleted_course_module->post_name : ""));
                if (!$uncompleted_course_module) {
                    $completedCourse = $course;
                }
            }
        }

        if ($track) {
            // error_log("block track: " . $track->post_name);
            // error_log("block track ID: " . $track->ID);

            if ($completedCourse) {
                $uncompleted_track_course = Track::get_first_uncompleted_course($track->ID, $user_id);
                // error_log("uncompleted_track_course:" . ($uncompleted_track_course ? $uncompleted_track_course->post_name : ""));
                if (!$uncompleted_track_course) {
                    $completedTrack = $track;
                }
            }
        }

        if ($uncompleted_module_block) {
            $next_track_block = $uncompleted_module_block;
        } else {
            $next_block_module = $uncompleted_course_module;

            if (!$next_block_module && $uncompleted_track_course) {
                $next_block_module = Course::get_first_uncompleted_module($uncompleted_track_course->ID, $user_id);
            }

            if ($next_block_module) {
                $next_track_block = Module::get_next_uncompleted_block_by_user($next_block_module->ID, $user_id);
            }
        }

        Track::start_block_chain_by_user($user_id, $module, $course, $track);

        return [
            'completedModuleSlug' => $completedModule ? $completedModule->post_name : "",
            'completedCourseSlug' => $completedCourse ? $completedCourse->post_name : "",
            'completedTrackSlug' => $completedTrack ? $completedTrack->post_name : "",
            'nextTrackCourseSlug' => $uncompleted_track_course ? $uncompleted_track_course->post_name : "",
            'nextCourseModuleSlug' => $uncompleted_course_module ? $uncompleted_course_module->post_name : "",
            'nextModuleBlockSlug' => $uncompleted_module_block ? $uncompleted_module_block->post_name : "",
            'nextBlockSlug' => $next_track_block ? $next_track_block->post_name : "",
        ];
    }

    private static function get_blocks_completed_time($user_id)
    {
        $blocks_completed_time = \get_user_meta($user_id, Student::META_BLOCKS_COMPLETED_TIME, true);

        if(!$blocks_completed_time) {
            $blocks_completed_time = [];
        }

        return $blocks_completed_time;
    }

    private static function get_block_completed_time($user_id, $block_id)
    {
        $blocks_completed_time = self::get_blocks_completed_time($user_id);

        return $blocks_completed_time[$block_id] ?? 0;
    }

    public static function save_block_completed_time($user_id, $block_id)
    {
        $blocks_completed_time = self::get_blocks_completed_time($user_id);

        $blocks_completed_time[$block_id] = \current_time('timestamp');

        \update_user_meta($user_id, Student::META_BLOCKS_COMPLETED_TIME, $blocks_completed_time);
    }

    public static function get_courses_action_time($user_id)
    {
        $courses_action_time = \get_user_meta($user_id, Student::META_COURSES_ACTION_TIME, true);

        if(!$courses_action_time) {
            $courses_action_time = [];
        }

        return $courses_action_time;
    }

    public static function update_course_last_action_time($user_id, $course_id)
    {
        // error_log("course_id:" . $course_id);
        $courses_action_time = self::get_courses_action_time($user_id);
        // error_log("courses_action_time111:" . print_r($courses_action_time, true));

        $courses_action_time[$course_id] = \current_time('timestamp');
        // error_log("courses_action_time222:" . print_r($courses_action_time, true));

        \update_user_meta($user_id, Student::META_COURSES_ACTION_TIME, $courses_action_time);
    }

    public static function get_course_completion_status($user_id, $course_id)
    {
        $modules = Module::get_list_by_course($course_id);

        $uncompleted_blocks_count = 0;
        $uncompleted_task_count = 0;
        $uncompleted_info_blocks_count = 0;
        $uncompleted_test_count = 0;

        $is_only_task_uncompleted = false;
        $is_test_or_task_uncompleted = false;

        foreach($modules as $module) {
            if(Module::is_completed_by_user($module->ID, $user_id)) {
                continue;
            }

            error_log("\tcheck module: " . $module->ID);

            $ld_blocks = \learndash_get_course_lessons_list($module->ID, $user_id);

            foreach($ld_blocks as $ld_block) {
                if(!Block::is_ld_block_completed($ld_block)) {
                    $uncompleted_blocks_count += 1;

                    if(Block::is_task_block($ld_block['post'])) {
                        $uncompleted_task_count += 1;
                    }
                    elseif(Block::is_test_block($ld_block['post']->ID)) {
                        $uncompleted_test_count += 1;
                    }
                    else {
                        $uncompleted_info_blocks_count += 1;
                    }
                }
            }
        }

        error_log("\tuncompleted_blocks_count: " . $uncompleted_blocks_count);
        error_log("\tuncompleted_task_count: " . $uncompleted_task_count);
        error_log("\tuncompleted_test_count: " . $uncompleted_test_count);
        error_log("\tuncompleted_info_blocks_count: " . $uncompleted_info_blocks_count);

        if($uncompleted_info_blocks_count === 0 && $uncompleted_test_count === 0 && $uncompleted_task_count > 0) {
            $is_only_task_uncompleted = true;
        }

        if($uncompleted_info_blocks_count === 0 && ($uncompleted_test_count > 0 || $uncompleted_task_count > 0)) {
            $is_test_or_task_uncompleted = true;
        }

        return [
            'is_only_task_uncompleted' => $is_only_task_uncompleted,
            'is_test_or_task_uncompleted' => $is_test_or_task_uncompleted,
        ];
    }
}
