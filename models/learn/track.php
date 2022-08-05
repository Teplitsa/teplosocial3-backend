<?php

namespace Teplosocial\models;

class TrackCache extends Cacheable
{
    public static string $post_type = 'tps_track';
    public static ?string $taxonomy = null;
    public static ?string $taxonomy_term = null;
    public static string $collection_name = 'tracks';

    public static function filter_fields(\WP_Post $course): array
    {
        ['ID' => $ID, 'post_name' => $post_name, 'post_title' => $post_title, 'post_excerpt' => $post_excerpt] = (array) $course;

        $thumbnail = \get_the_post_thumbnail_url($ID, Image::SIZE_CARD_DEFAULT_COVER);
        $duration = Track::get_duration($ID);
        $tags = \get_terms([
            'taxonomy'   => 'post_tag',
            'hide_empty' => true,
            'object_ids' => $ID,
            'fields'     => 'ids'
        ]);

        return [
            'externalId' => $ID,
            'slug'       => $post_name,
            'title'      => $post_title,
            'teaser'     => $post_excerpt,
            'thumbnail'  => empty($thumbnail) ? null : $thumbnail,
            'duration'   => $duration,
            'tags'       => $tags
        ];
    }
}

class Track extends Post
{
    public static $post_type = 'tps_track';
    public static $connection_track_course = 'track-courses';

    const USER_META_TRACK_STARTED = 'tps_track_started_';
    const USER_META_TRACK_COMPLETED = 'tps_track_completed_';

    public static function get_by_course($course_id)
    {
        $courses = static::get_list([
            'connected_direction' => 'to',
            'connected_type'    => Course::$connection_track_course,
            'connected_items'   => $course_id,
        ]);

        return !empty($courses) ? $courses[0] : null;
    }

    public static function get_points($track_id)
    {
        $points = 0;
        $courses = Course::get_list_by_track($track_id);
        // error_log("points courses count: " . count($courses));
        foreach ($courses as $course) {
            $points += Course::get_points($course->ID);
        }
        return $points;
    }

    public static function get_duration($track_id)
    {
        $duration = 0;
        $courses = Course::get_list_by_track($track_id);
        // error_log("duration courses count: " . count($courses));
        foreach ($courses as $course) {
            $duration += Course::get_duration($course->ID);
        }
        return $duration;
    }

    public static function count_blocks($track_id)
    {
        $count = 0;
        $courses = Course::get_list_by_track($track_id);
        foreach ($courses as $course) {
            $count += Course::count_blocks($course->ID);
        }
        return $count;
    }

    public static function count_completed_blocks($track_id, $user_id)
    {
        $count = 0;

        if (!$user_id) {
            return $count;
        }

        $courses = Course::get_list_by_track($track_id);
        foreach ($courses as $course) {
            $count += Course::count_completed_blocks($course->ID, $user_id);
        }
        return $count;
    }

    public static function complete_by_user($track_id, $user_id)
    {
        $timestamp = \current_time('timestamp', true);
        \update_user_meta($user_id, self::USER_META_TRACK_COMPLETED . $track_id, $timestamp);
        return $timestamp;
    }

    public static function is_completed_by_user($track_id, $user_id)
    {
        // error_log("is_completed_by_user: " . $track_id . " --- " . $user_id);
        return $user_id ? boolval(get_user_meta($user_id, self::USER_META_TRACK_COMPLETED . $track_id, true)) : false;
    }

    public static function start_by_user($track_id, $user_id)
    {
        $start_timestamp = current_time('timestamp', true);
        update_user_meta($user_id, self::USER_META_TRACK_STARTED . $track_id, $start_timestamp);
        return $start_timestamp;
    }

    public static function is_started_by_user($course_id, $user_id)
    {
        return $user_id ? \boolval(get_user_meta($user_id, self::USER_META_TRACK_STARTED . $course_id, true)) : false;
    }

    public static function get_first_uncompleted_course($track_id, $user_id)
    {
        $courses = Course::get_list_by_track($track_id);
        $meta = \get_user_meta($user_id);

        // foreach($courses as $course) {
        //     error_log("track course:" . $course->post_name);
        //     error_log("track course_id:" . $course->ID);
        // }

        foreach ($courses as $course) {
            if (empty($meta[Course::USER_META_COURSE_COMPLETED . $course->ID])) {
                return $course;
            }
        }

        return null;
    }

    public static function start_block_chain_by_user($user_id, $module, $course, $track)
    {
        if($module && !Module::is_started_by_user($module->ID, $user_id)) {
            Module::start_by_user($module->ID, $user_id);
        }

        if($course && !Course::is_started_by_user($course->ID, $user_id)) {
            Course::start_by_user($course->ID, $user_id);
        }

        if($track && !self::is_started_by_user($track->ID, $user_id)) {
            self::start_by_user($track->ID, $user_id);
        }
    }

    public static function start_course($course, $params = [])
    {
        // error_log("start_course: " . $course->post_name);
        $user_id = \get_current_user_id();

        if (!$user_id) {
            throw new \Teplosocial\exceptions\AuthenticationRequiredException();
        }

        // error_log("get_first_uncompleted_in_course FOR course_id:" . $course->ID);
        $start_course = null;
        $start_track = null;
        $start_module = Course::get_first_uncompleted_module($course->ID, $user_id);
        $start_block = $start_module ? Module::get_next_uncompleted_block_by_user($start_module->ID, $user_id) : null;

        if ($start_module) {
            // error_log("course module:" . $start_module->post_name);
            if (!Module::is_started_by_user($start_module->ID, $user_id)) {
                // error_log("start module");
                Module::start_by_user($start_module->ID, $user_id);
            }
            $start_course = Course::get_by_module($start_module->ID);
        }

        if ($start_course) {
            // error_log("course:" . $start_course->post_name);
            if (!Course::is_started_by_user($start_course->ID, $user_id)) {
                // error_log("start course");
                Course::start_by_user($start_course->ID, $user_id);
            }
            $start_track = self::get_by_course($start_course->ID);
        }

        if ($start_track) {
            // error_log("course track:" . $start_track->post_name);
            if (!self::is_started_by_user($start_track->ID, $user_id)) {
                // error_log("start track");
                self::start_by_user($start_track->ID, $user_id);
            }
        }

        // error_log("start_module:" . $start_module->post_name);
        // error_log("start_module_id:" . $start_module->ID);
        // error_log("start_block:" . $start_block->post_name);
        // error_log("start_block_id:" . $start_block->ID);

        return [
            "startBlockSlug" => $start_block ? $start_block->post_name : "",
            "startModuleSlug" => $start_module ? $start_module->post_name : "",
            "startCourseSlug" => $start_course ? $start_course->post_name : "",
            "startTrackSlug" => $start_track ? $start_track->post_name : "",
        ];
    }

    public static function start_track($track, $params = [])
    {
        // error_log("start_track: " . $track->post_name);
        $user_id = \get_current_user_id();

        if (!$user_id) {
            throw new \Teplosocial\exceptions\AuthenticationRequiredException();
        }

        $start_course = self::get_first_uncompleted_course($track->ID, $user_id);

        if ($start_course) {
            return self::start_course($start_course);
        } else {
            return [
                "startBlockSlug" => "",
                "startModuleSlug" => "",
                "startCourseSlug" => "",
                "startTrackSlug" => "",
            ];
        }
    }

}
