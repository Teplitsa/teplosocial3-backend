<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\Module;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;
use \Teplosocial\models\Certificate;

require_once get_template_directory().'/admin/admin-utility-functions.php';

class ModuleHooks {
    public static function handle_module_complete( $data ) {

        $user = $data['user'];
        $module = $data['course'];

        $user_id = $user->ID;
        $course = null;
        $track = null;
        $completed_module = $module;
        $completed_course = null;
        $completed_track = null;
        $uncompleted_module_block = null;
        $uncompleted_course_module = null;
        $uncompleted_track_course = null;
        $next_track_block = null;

        Module::complete_by_user($module->ID, $user_id);

        // error_log("module ID: " . $module->ID);

        // The Module is completed - update the user activity in the Modules special table:
//        $result = tps_update_user_activity_modules($user_id, $module->ID);

        $course = Course::get_by_module($module->ID);
        if($course) {
            // error_log("block course:" . $course->post_name);
            $track = Track::get_by_course($course->ID);

            if($completed_module) {

                $uncompleted_course_module = Course::get_first_uncompleted_module($course->ID, $user_id);

                // Intentional changes after 20.09.2023: now the "Final" ("итоговое задание") Module is not accounted for
                // in completion of the course/track, and it isn't required to complete when user course sertificates are given:
                if(
                    stripos($uncompleted_course_module->post_title, 'Итоговое') !== false
                    && count(Course::get_all_uncompleted_modules($course->ID, $user_id)) === 1
                ) { // Just one uncompleted Module remains - the "Final task" ("Итоговое задание"). Giving sertificate to user:
                    $uncompleted_course_module = null;
                }
                // Intentional changes - END

                if( !$uncompleted_course_module ) {

                    Course::complete_by_user($course->ID, $user_id);
                    $completed_course = $course;

                    // The Course is completed - update the user activity in the Courses special table:
//                    $result = tps_update_user_activity_courses($user_id, $course->ID);

                }

            }

        }

        if($track) {
            // error_log("block track:" . $track->post_name);

            if($completed_course) {

                $uncompleted_track_course = Track::get_first_uncompleted_course($track->ID, $user_id);
                if( !$uncompleted_track_course ) {

                    Track::complete_by_user($track->ID, $user_id);
                    $completed_track = $track;

                    // The Track is completed - update the user activity in the Tracks special table:
//                    $result = tps_update_user_activity_tracks($user_id, $track->ID);

                }

            }

        }

        if($completed_course) {
            Certificate::save_certificate($user_id, $completed_course->post_title, Certificate::CERTIFICATE_TYPE_COURSE, ['course_id' => $completed_course->ID]);
        }

        if($completed_track) {
            Certificate::save_certificate($user_id, $completed_track->post_title, Certificate::CERTIFICATE_TYPE_TRACK, ['track_id' => $completed_track->ID]);
        }
    }
}

add_action("learndash_course_completed", '\Teplosocial\hooks\ModuleHooks::handle_module_complete', 5, 1);

// TMP DBG:
if(isset($_GET['tst-1'])) {
    add_action('init', function(){

//        $res = tps_update_user_activity_modules(get_current_user_id(), 22112);
//        echo '<pre>HERE: '.print_r((int)$res, 1).'</pre>';

//        $user_id = 5702; // Алексей Курьянов
//        $completed_course = get_post(18008); // Как сделать себе сайт бесплатно
//        $sert_id = Certificate::save_certificate($user_id, $completed_course->post_title, Certificate::CERTIFICATE_TYPE_COURSE, ['course_id' => $completed_course->ID]);
//
//        echo '<pre>'.print_r('Trying to give the sert for the course "'.$completed_course->post_title.'" to user #'.$user_id.'. The new sert ID: '.$sert_id, 1).'</pre>';

    });
}