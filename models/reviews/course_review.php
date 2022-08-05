<?php

namespace Teplosocial\models;

class CourseReview
{
    public static string $table_name = 'course_reviews';

    public static function add_review($course_id, $user_id, array $params = array())
    {
        $params['mark'] = empty($params['mark']) || (int)$params['mark'] <= 0 ? false : (int)$params['mark'];
        $params['comment'] = empty($params['comment']) ? false : trim(esc_html($params['comment']));
        $params['course_id'] = $course_id;
        $params['user_id'] = $user_id;

        // error_log("params:" . print_r($params, true));

        if( !$params['mark'] || !$params['course_id'] || !$params['user_id'] ) {
            return new \WP_Error(-1, esc_html__("Error while adding mark: insufficient data given.", 'tps'));
        }

        global $wpdb;
        $res = $wpdb->insert($wpdb->prefix . self::$table_name, array(
            'user_id' => $params['user_id'],
            'course_id' => $params['course_id'],
            'mark' => $params['mark'],
            'mark_comment' => $params['comment'],
            'mark_time' => current_time('mysql'),
        ));

        $mark_id = $res ? $wpdb->insert_id : 0;

        return $res ? $mark_id : new \WP_Error(2, esc_html__("Error while adding Expert's mark: DB insertion error.", 'tps'));

    }

    public static function get_review($course_id, $user_id)
    {
        if( !$course_id || !$user_id ) {
            return null;
        }

        global $wpdb;
        $table_name = self::$table_name;
        // error_log("table_name:" . $table_name);
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$table_name} WHERE course_id = %s AND user_id = %s", $course_id, $user_id);
        $review = $wpdb->get_row($sql);
        // error_log("review:" . print_r($review, true));
        return $review;
    }
}
