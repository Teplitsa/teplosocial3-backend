<?php 

namespace Teplosocial\models;

use \Teplosocial\models\Quiz;
use \Teplosocial\models\Course;
use \Teplosocial\models\Module;
use \Teplosocial\models\StudentLearning;

class Adaptest extends Quiz
{
    const USER_META_ADAPTEST_COMPLETED = 'tps_adaptest_completed_';
    const USER_META_COMPLETED_ADAPTEST_QUIZ = 'tps_completed_adaptest_quiz_';
    const META_ADAPTEST_COURSE = 'tps_adaptest_course';
    const META_ADAPTEST_DURATION = 'tps_adaptest_duration';
    const META_ADAPTEST_QUESTIONS_MODULES = 'tps_adaptest_questions_modules';
    const REST_ADAPTEST_COURSE_SLUG = 'adaptestCourseSlug';

    public static function set_course_adaptest($course_id, $quiz_id) {
        global $wpdb;

        // error_log("quiz_id:" . $quiz_id);
        // error_log("course_id:" . $course_id);

        if(!$course_id) {
            $course_id_to_unlink_from_adaptest = self::get_course_id($quiz_id);
        }

        $wpdb->query('START TRANSACTION');
        update_post_meta($quiz_id, self::META_ADAPTEST_COURSE, $course_id);
        if($course_id_to_unlink_from_adaptest) {
            delete_post_meta($course_id_to_unlink_from_adaptest, Course::META_ADAPTEST);
        }
        else {
            update_post_meta($course_id, Course::META_ADAPTEST, $quiz_id);
        }
        $wpdb->query('COMMIT');
    }

    public static function is_quiz_adaptest($quiz_id) {
        // error_log("quiz_id:" . $quiz_id);
        /** @todo When all Adaptest quizzes will have a proper "adaptest" value for the "tps_course_type" setting, leave only "tps_course_type" setting value check here */
        if(self::get_course_id($quiz_id) > 0) {

            if(get_post_meta($quiz_id, 'tps_quiz_type', true) !== 'adaptest') {
                update_post_meta($quiz_id, 'tps_quiz_type', 'adaptest');
            }

            return true;

        } else {
            return false;
        }

    }

    public static function get_course_id($quiz_id) {
        // error_log("quiz_id:" . $quiz_id);
        return intval(get_post_meta($quiz_id, Adaptest::META_ADAPTEST_COURSE, true));
    }

    public static function get_by_course($course_id) {
        $quiz_id = intval(get_post_meta($course_id, Course::META_ADAPTEST, true));
        return $quiz_id ? get_post($quiz_id) : null;
    }

    public static function is_course_adaptest_completed_by_user($course_id, $user_id) {
        // \error_log("user_id: " . $user_id);
        // \error_log("course_id: " . $course_id);
        return $course_id ? \boolval(\get_user_meta($user_id, self::USER_META_ADAPTEST_COMPLETED . $course_id, true)) : false;
    }

    public static function complete_course_adaptest($course_id, $user_id) {
        $quiz = self::get_by_course($course_id);
        $timestamp = \current_time('timestamp', true);
        // \error_log("user_id: " . $user_id);
        // \error_log("quiz: " . $quiz->ID);
        // \error_log("course_id: " . $course_id);
        // \error_log("meta_key: " . self::USER_META_ADAPTEST_COMPLETED . $course_id);
        \update_user_meta($user_id, self::USER_META_ADAPTEST_COMPLETED . $course_id, $timestamp);
        \update_user_meta($user_id, self::USER_META_COMPLETED_ADAPTEST_QUIZ . $course_id, $quiz->ID);
    }

    public static function get_questions_modules($quiz_id) {
        $question_modules = get_post_meta($quiz_id, self::META_ADAPTEST_QUESTIONS_MODULES, true);
        return $question_modules ? $question_modules : [];
    }

    public static function save_question_module($quiz_id, $question_id, $module_id) {
        $questions_modules = self::get_questions_modules($quiz_id);
        // \error_log("quiz: " . $quiz_id);
        // \error_log("question_id: " . $question_id);
        // \error_log("module_id: " . $module_id);
        // \error_log("questions_modules: " . print_r($questions_modules, true));
        $questions_modules[$question_id] = $module_id;
        \update_post_meta($quiz_id, self::META_ADAPTEST_QUESTIONS_MODULES, $questions_modules);
    }

    public static function complete_question_module($quiz_id, $question_id, $user_id) {
        global $wpdb;

        $questions_modules = self::get_questions_modules($quiz_id);
        // \error_log("questions_modules:" . print_r($questions_modules, true));

        if(isset($questions_modules[$question_id])) {
            $module_id = intval($questions_modules[$question_id]);

            // \error_log("[ADAPTEST] question - module: " . $question_id . " - " . $module_id);
            // \error_log("[ADAPTEST] user: " . $user_id);
            if($module_id) {

                $ld_blocks = \learndash_get_course_lessons_list($module_id);

                $wpdb->query('START TRANSACTION');

                foreach($ld_blocks as $ld_block) {
                    // \error_log("[ADAPTEST] complete block: " . $ld_block['post']->ID);
                    StudentLearning::complete_block_by_user($ld_block['post']->ID, $user_id);
                }

                Module::complete_by_adaptest($module_id, $user_id);
                Module::complete_by_user($module_id, $user_id);

                $wpdb->query('COMMIT');
            }
        }
    }

    public static function get_question_module_id($quiz_id, $question_id)
    {
        global $wpdb;

        $questions_modules = self::get_questions_modules($quiz_id);
        // \error_log("questions_modules:" . print_r($questions_modules, true));

        return isset($questions_modules[$question_id]) ? intval($questions_modules[$question_id]) : 0;
    }

    public static function complete_module($module_id, $user_id)
    {
        global $wpdb;
        
        $ld_blocks = \learndash_get_course_lessons_list($module_id);

        $wpdb->query('START TRANSACTION');

        foreach($ld_blocks as $ld_block) {
            // \error_log("[ADAPTEST] complete block: " . $ld_block['post']->ID);
            StudentLearning::complete_block_by_user($ld_block['post']->ID, $user_id);
        }

        Module::complete_by_adaptest($module_id, $user_id);
        Module::complete_by_user($module_id, $user_id);

        $wpdb->query('COMMIT');
    }
}
