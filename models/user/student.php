<?php 

namespace Teplosocial\models;

use \Teplosocial\Config;
use \Teplosocial\models\Image;
use \Teplosocial\models\Track;
use \Teplosocial\models\Module;
use \Teplosocial\models\Block;

class Student
{
    static $table_uncompleted_courses = 'stats_uncompleted_course';
    const META_AVATAR = 'tps_user_avatar';
    const META_FIRST_NAME = 'first_name';
    const META_LAST_NAME = 'last_name';
    const META_DESCRIPTION = 'description';
    const META_CITY = 'profile_city';    
    const META_SOCIAL_LINKS = 'tps_social_links';
    const META_REGISTRATION_IP = 'tps_registration_ip';
    const META_STATS_CITY = 'tps_stats_city';
    const META_STATS_POINTS = 'tps_stats_points';
    const META_STATS_COMPLETED_MODULES = 'tps_stats_completed_modules';
    const META_STATS_COMPLETED_COURSES = 'tps_stats_completed_courses';
    const META_STATS_ALMOSTS_COMPLETED_COURSES_NO_TASK = 'tps_almost_completed_courses_no_task';
    const META_STATS_STARTED_COURSES = 'tps_started_courses';
    const META_STATS_COMPLETED_TRACKS = 'tps_stats_completed_tracks';
    const META_LAST_LOGIN_TIME = 'tps_last_login_time';
    const META_ONBOARDING_FAQ_SENT = 'tps_onboarding_faq_sent';
    const META_BLOCKS_COMPLETED_TIME = 'tps_blocks_completed_time';
    const META_COURSES_ACTION_TIME = 'tps_courses_action_time';

    public static function get($slug_or_id)
    {
        if(is_int($slug_or_id)) {
            return \get_user_by('ID', $slug_or_id);
        }
        elseif($slug_or_id) {
            return \get_user_by('slug', $slug_or_id);
        }
        else {
            return null;
        }
    }

    public static function set_avatar_id($user_id, $attachment_id)
    {
        update_user_meta( $user_id, self::META_AVATAR, $attachment_id );
    }
    
    public static function get_avatar_id($user_id)
    {
        return \get_user_meta( $user_id, self::META_AVATAR, true );
    }

    public static function get_gravatar_url($user)
    {
        return "https://www.gravatar.com/avatar/".md5(strtolower(trim($user->user_email)))."?s=2400&d=" . urlencode(get_template_directory_uri() . '/assets/img/default-avatar.png');
    }

    public static function get_avatar_url($user)
    {
        $avatar_id = self::get_avatar_id($user->ID);
        $avatar_url = "";

        if($avatar_id) {
            $avatar = wp_get_attachment_image_src( $avatar_id, Image::SIZE_AVATAR);

            if(!empty($avatar)) {
                $avatar_url = $avatar[0];
            }
        }

        return $avatar_url ? $avatar_url : self::get_gravatar_url($user);
    }

    public static function get_avatar_file($user_id)
    {
        $file_id = \intval(\get_user_meta($user_id, self::META_AVATAR, true));
        
        if($file_id) {
            return [
                'url' => wp_get_attachment_url($file_id),
                'id' => $file_id,
            ];
        }
        
        return null;
    }

    public static function get_points($user_id)
    {
        $block_points = 0;
        $course_points = 0;
        $track_points = 0;

        $all_user_meta = \get_user_meta($user_id);
        foreach($all_user_meta as $key => $value) {
            // error_log(print_r($meta_item, true));
            if(\str_starts_with($key, Module::USER_META_MODULE_STARTED)) {
                $module_id = intval(\str_replace(Module::USER_META_MODULE_STARTED, '', $key));

                // error_log("user points module_id: " . $module_id);

                $ld_blocks = \learndash_get_course_lessons_list($module_id, $user_id); // , ['orderby' => 'menu_order']
                // error_log("user points module blocks number: " . count($ld_blocks));
                $block_points += array_reduce($ld_blocks, function($accum_points, $ld_block) {
                    return $accum_points + (Block::is_ld_block_completed($ld_block) ? Config::BLOCK_POINTS : 0);

                }, 0);
            }

            if(\str_starts_with($key, Course::USER_META_COURSE_COMPLETED)) {
                $course_points += Config::COURSE_POINTS;
            }

            if(\str_starts_with($key, Track::USER_META_TRACK_COMPLETED)) {
                $track_points += Config::TRACK_POINTS;
            }
        }

        // error_log("block_points:" . $block_points);
        // error_log("course_points:" . $course_points);
        // error_log("track_points:" . $track_points);

        $points = $block_points + $course_points + $track_points;

        return $points;
    }

    public static function get_meta($user_id, $meta_name)
    {
        return \get_user_meta( $user_id, $meta_name, true );
    }

    public static function get_social_links($user_id)
    {
        $social_links = \get_user_meta( $user_id, self::META_SOCIAL_LINKS, true );

        if(!$social_links) {
            $social_links = [];
        }

        foreach($social_links as $i => $link) {
            $social_links[$i]['type'] = \Teplosocial\utils\get_social_link_type($link['url']);
        }

        // error_log("social_links:" . print_r($social_links, true));
        return $social_links;
    }

    public static function update_profile($user, $new_data)
    {
        // error_log("new profile data:" . print_r($new_data, true));

        $params2update = [
            self::META_FIRST_NAME => 'first_name',
            self::META_LAST_NAME => 'last_name',
            self::META_DESCRIPTION => 'description',
            self::META_CITY => 'profile_city',
            self::META_SOCIAL_LINKS => 'social_links',
            self::META_AVATAR => 'user_avatar',
        ];

        foreach($params2update as $meta_key => $input_key) {
            if(!isset($new_data[$input_key])) {
                continue;
            }

            $meta_value = $new_data[$input_key];

            if($meta_key !== self::META_SOCIAL_LINKS) {
                $meta_value = trim($meta_value);
            }

            \update_user_meta($user->ID, $meta_key, $meta_value);
        }
    }

    public static function get_stats_city($user_id)
    {
        $stats_city = '';
        $profile_city = \get_user_meta($user_id, self::META_CITY, true);
        
        if(trim($profile_city)) {
            // printf("profile city: %s\n", $profile_city);
            $stats_city = trim($profile_city);
        }
        else {            
            $ip = \get_user_meta( $user_id, self::META_REGISTRATION_IP, true);
            // printf("ip: %s\n", $ip);
            if($ip) {
                $stats_city = \TstIPGeo::instance()->get_city_by_ip($ip);
            }
        }

        return $stats_city;
    }

    public static function refresh_last_login_time($user_id)
    {
        $last_login_time = current_time('mysql');
        update_user_meta( $user_id, self::META_LAST_LOGIN_TIME,  $last_login_time);
        return $last_login_time;
    }

    public static function calc_stats($user_id)
    {
        $completed_modules_number = 0;
        $completed_courses_number = 0;
        $completed_tracks_number = 0;
        $almost_completed_courses_number_no_task = 0;
        $started_courses_number = 0;

        $all_user_meta = \get_user_meta($user_id);
        $min_timestamp = \strtotime("2021-10-01 00:00:00");
        // echo "user_id=" . $user_id . "\n";
        // echo "min_timestamp=" . $min_timestamp . "\n";
        foreach($all_user_meta as $key => $value) {
            if(\str_starts_with($key, Course::USER_META_COURSE_COMPLETED)) {
                $completed_courses_number += 1;
            }
    
            if(\str_starts_with($key, Module::USER_META_MODULE_COMPLETED)) {
                // echo "value=" . $value . "\n";
                // echo "module_timestamp=" . \intval($value[0]) . "\n";

                if($value && \intval($value[0]) >= $min_timestamp) {
                    $completed_modules_number += 1;
                }
            }

            if(\str_starts_with($key, Track::USER_META_TRACK_COMPLETED)) {
                $completed_tracks_number += 1;
            }

            if(\str_starts_with($key, Course::USER_META_COURSE_STARTED)) {
                $started_courses_number += 1;
            }
        }

        $almost_completed_courses_number_no_task = self::calculate_almost_completed_courses_number_no_task($user_id);

        return [
            self::META_STATS_CITY => self::get_stats_city($user_id),
            self::META_STATS_POINTS => self::get_points($user_id),
            self::META_STATS_COMPLETED_MODULES => $completed_modules_number,
            self::META_STATS_COMPLETED_COURSES => $completed_courses_number,
            self::META_STATS_ALMOSTS_COMPLETED_COURSES_NO_TASK => $almost_completed_courses_number_no_task,
            self::META_STATS_STARTED_COURSES => $started_courses_number,
            self::META_STATS_COMPLETED_TRACKS => $completed_tracks_number,
        ];
    }

    public static function save_stats($user_id, $stats)
    {
        foreach($stats as $meta_key => $meta_value) {
            \update_user_meta($user_id, $meta_key, $meta_value);
        }
    }

    public static function save_user_ip_if_not_exist($user_id)
    {
        if(!$user_id) {            
            return;
        }

        $reg_ip = \get_user_meta($user_id, self::META_REGISTRATION_IP, true);
        
        if(!$reg_ip) {
            $ip = \TstIPGeo::get_client_ip();
            \update_user_meta($user_id, self::META_REGISTRATION_IP, $ip);
        }            
    }

    public static function calculate_almost_completed_courses_number_no_task($user_id)
    {
        global $wpdb;

        $table = "{$wpdb->prefix}" . self::$table_uncompleted_courses;
        $sql = "SELECT COUNT(id) FROM {$table} WHERE user_id = %s AND task_only = 1";
        return $wpdb->get_var($wpdb->prepare($sql, $user_id));
    }
}
