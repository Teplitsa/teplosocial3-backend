<?php

namespace Teplosocial\models;

use \Teplosocial\models\{Module, Block};

class CourseCache extends Cacheable
{
    public static string $post_type = 'tps_course';
    public static ?string $taxonomy = null;
    public static ?string $taxonomy_term = null;
    public static string $collection_name = 'courses';

    public static function filter_fields(\WP_Post $course): array
    {
        ['ID' => $ID, 'post_name' => $post_name, 'post_title' => $post_title, 'post_excerpt' => $post_excerpt] = (array) $course;

        $thumbnail = \get_the_post_thumbnail_url($ID, Image::SIZE_CARD_DEFAULT_COVER);
        $small_thumbnail = \get_the_post_thumbnail_url($ID, Image::SIZE_CARD_SMALL_COVER);
        $duration = Course::get_duration($ID);
        $points = Course::get_points($ID);
        $teaser = \get_post_meta($ID, Course::META_TEASER, true);
        $track = Track::get_by_course($ID);
        $track_id = $track->ID ?? 0;
        $track_slug = $track->post_name ?? "";
        $track_title = $track->post_title ?? "";
        $tags = \get_terms([
            'taxonomy'   => 'post_tag',
            'hide_empty' => true,
            'object_ids' => $ID,
            'fields'     => 'ids'
        ]);

        return [
            'externalId'     => $ID,
            'slug'           => $post_name,
            'title'          => $post_title,
            'thumbnail'      => empty($thumbnail) ? null : $thumbnail,
            'smallThumbnail' => empty($small_thumbnail) ? null : $small_thumbnail,
            'duration'       => $duration,
            'points'         => $points,
            'teaser'         => empty($teaser) ? $post_excerpt : $teaser,
            'tags'           => $tags,
            'trackId'        => $track_id,
            'trackSlug'      => $track_slug,
            'trackTitle'     => $track_title,
        ];
    }
}

class Course extends Post
{
    public static $post_type = 'tps_course';
    public static $connection_track_course = 'track-courses';

    const META_DESCRIPTION = 'tps_course_description';
    const META_TEASER = 'tps_course_teaser';
    const USER_META_COURSE_STARTED = 'tps_course_started_';
    const USER_META_COURSE_COMPLETED = 'tps_course_completed_';
    const META_SUITABLE_FOR = 'tps_suitable_for';
    const META_LEARNING_RESULT = 'tps_learning_result';
    const META_ADAPTEST = 'tps_adaptest';

    public static function get_by_module($module_id)
    {
        $courses = static::get_list([
            'connected_direction' => 'to',
            'connected_type'    => Module::$connection_course_module,
            'connected_items'   => $module_id,
        ]);

        return !empty($courses) ? $courses[0] : null;
    }

    public static function get_by_block($block_id)
    {
        $module = Module::get_by_block($block_id);
        $course = static::get_by_module($module->ID);
        return $course;
    }

    public static function get_list_by_track($track_id)
    {
        return static::get_list([
            'connected_type'    => self::$connection_track_course,
            'connected_from'   => $track_id,
        ]);
    }

    public static function get_points($course_id)
    {
        $points = 0;
        $modules = Module::get_list_by_course($course_id);
        // error_log("points modules count: " . count($modules));
        foreach ($modules as $module) {
            $points += Module::get_points($module->ID);
        }
        return $points;
    }

    public static function get_duration($course_id)
    {
        $duration = 0;
        $modules = Module::get_list_by_course($course_id);
        // error_log("duration modules count: " . count($modules));
        foreach ($modules as $module) {
            $duration += Module::get_duration($module->ID);
        }
        return $duration;
    }

    public static function count_blocks($course_id)
    {
        $count = 0;
        $modules = Module::get_list_by_course($course_id);
        foreach ($modules as $module) {
            $count += Module::count_blocks($module->ID);
        }
        return $count;
    }

    public static function count_completed_blocks($course_id, $user_id)
    {
        $count = 0;

        if (!$user_id) {
            return $count;
        }

        $modules = Module::get_list_by_course($course_id);
        foreach ($modules as $module) {
            $count += Module::count_completed_blocks($module->ID, $user_id);
        }
        return $count;
    }

    public static function complete_by_user($course_id, $user_id)
    {
        $timestamp = current_time('timestamp', true);
        update_user_meta($user_id, self::USER_META_COURSE_COMPLETED . $course_id, $timestamp);
        return $timestamp;
    }

    public static function is_completed_by_user($course_id, $user_id)
    {
        return $user_id ? boolval(get_user_meta($user_id, self::USER_META_COURSE_COMPLETED . $course_id, true)) : false;
    }

    public static function start_by_user($course_id, $user_id)
    {
        $start_timestamp = current_time('timestamp', true);
        update_user_meta($user_id, self::USER_META_COURSE_STARTED . $course_id, $start_timestamp);
        return $start_timestamp;
    }

    public static function is_started_by_user($course_id, $user_id)
    {
        // error_log("is_started_by_user: $course_id, $user_id");
        return $user_id ? \boolval(get_user_meta($user_id, self::USER_META_COURSE_STARTED . $course_id, true)) : false;
    }

    public static function get_first_uncompleted_module($course_id, $user_id)
    {
        $modules = Module::get_list_by_course($course_id);
        $meta = \get_user_meta($user_id);

        // foreach($modules as $module) {
        //     error_log("course module:" . $module->post_name);
        //     error_log("course module_id:" . $module->ID);
        // }

        foreach ($modules as $module) {
            if (empty($meta[Module::USER_META_MODULE_COMPLETED . $module->ID])) {
                return $module;
            }
        }

        return null;
    }

    public static function is_block_available_for_guest($block) {
        // error_log("is_block_available_for_guest...");

        $block_id = $block->ID;
        // error_log("block_id: " . $block_id);

        if(Block::is_task_block($block)) {
            return false;
        }

        if(Block::is_test_block($block_id)) {
            return false;
        }        

        $module = Module::get_by_block($block_id);

        if(!$module) {
            // error_log("no module");
            return false;
        }

        $course = self::get_by_module($module->ID);        

        if(!$course) {
            // error_log("no course");
            return false;
        }

        $modules = Module::get_list_by_course($course->ID);
        if(!count($modules)) {
            // error_log("no course modules");
            return false;
        }

        $first_module = $modules[0];

        // error_log("first_module->ID: " . $first_module->ID);
        // error_log("module->ID: " . $module->ID);
        return $first_module->ID === $module->ID;
    }
}
