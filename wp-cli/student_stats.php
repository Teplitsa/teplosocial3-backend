<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Student, Course, Module, Block, StudentLearning};


if (!class_exists('WP_CLI')) {
    return;
}

class StudentStats
{
    public function calculate($args, $assoc_args)
    {
        global $wpdb;
        $user_id_list = get_users([
            'fields' => 'ID',
        ]);
        // $user_id_list = [2];

        foreach($user_id_list as $user_id) {
            $stats = Student::calc_stats($user_id);
            // print_r($stats);
            Student::save_stats($user_id, $stats);
        }

        \WP_CLI::success(__('User stats updated.', 'tps'));        
    }

    public function task_not_done($args, $assoc_args)
    {
        global $wpdb;
        $user_id_list = get_users([
            'fields' => 'ID',
        ]);
        // $user_id_list = [4198];

        $stats = [];

        foreach($user_id_list as $user_id) {
            $all_user_meta = \get_user_meta($user_id);

            \WP_CLI::log("user_id: " . $user_id);

            foreach($all_user_meta as $key => $value) {
                $course_id = 0;
                $is_course_started = false;
                $is_course_completed = false;

                if(!\str_starts_with($key, Course::USER_META_COURSE_STARTED)) {
                    continue;
                }

                $course_id = intval(\str_replace(Course::USER_META_COURSE_STARTED, "", $key));
                $is_course_started = true;

                $is_course_completed = !empty($all_user_meta[Course::USER_META_COURSE_COMPLETED . $course_id]);

                \WP_CLI::log("course: " . $course_id);
                \WP_CLI::log("is_course_started: " . $is_course_started);
                \WP_CLI::log("is_course_completed: " . $is_course_completed);

                if($is_course_started && !$is_course_completed) {
                    \WP_CLI::log("must check");

                    $course_completion_status = StudentLearning::get_course_completion_status($user_id, $course_id);
                    $is_only_task_uncompleted = $course_completion_status['is_only_task_uncompleted'];
                    $is_test_or_task_uncompleted = $course_completion_status['is_test_or_task_uncompleted'];

                    \WP_CLI::log("\tis_only_task_uncompleted: " . $is_only_task_uncompleted);
                    \WP_CLI::log("\tis_test_or_task_uncompleted: " . $is_test_or_task_uncompleted);

                    if($is_only_task_uncompleted) {
                        $stats[$user_id][$course_id]['task_only'] = true;
                    }

                    if($is_test_or_task_uncompleted) {
                        $stats[$user_id][$course_id]['task_or_test'] = true;
                    }
                }
            }
        }

        \WP_CLI::log("\n\nresult: " . print_r($stats, true));

        global $wpdb;
        $wpdb->show_errors();
        $table = "{$wpdb->prefix}" . Student::$table_uncompleted_courses;
        $wpdb->query("TRUNCATE TABLE `{$table}`");
        foreach($stats as $user_id => $user_courses) {
            foreach($user_courses as $course_id => $data) {
                $wpdb->insert($table, array_merge($data, ['user_id' => $user_id, 'course_id' => $course_id]));
            }
        }
        $wpdb->hide_errors();

        \WP_CLI::success(__('User stats updated.', 'tps'));        
    }
}

\WP_CLI::add_command('tps_student_stats', '\Teplosocial\cli\StudentStats');
