<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\Module;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;
use \Teplosocial\models\Certificate;

require_once get_template_directory().'/admin/admin-utility-functions.php';

class ModuleHooks {
    public static function handle_module_complete( $data ) {
        // error_log("handle_module_complete...");

        $user = $data['user'];
        $module = $data['course'];

        $user_id = $user->ID;
        $course = null;
        $track = null;
        $completedModule = $module;
        $completedCourse = null;
        $completedTrack = null;
        $uncompleted_module_block = null;
        $uncompleted_course_module = null;
        $uncompleted_track_course = null;
        $next_track_block = null;

        Module::complete_by_user($module->ID, $user_id);

        // error_log("module ID: " . $module->ID);

        // The Module is completed - update the user activity in the Modules special table:
        $result = tps_update_user_activity_modules($user_id, $module->ID);

        $course = Course::get_by_module($module->ID);
        if($course) {
            // error_log("block course:" . $course->post_name);
            $track = Track::get_by_course($course->ID);

            if($completedModule) {
                $uncompleted_course_module = Course::get_first_uncompleted_module($course->ID, $user_id);
                // error_log("uncompleted_course_module:" . ($uncompleted_course_module ? $uncompleted_course_module->post_name : ""));
                if(!$uncompleted_course_module) {

                    Course::complete_by_user($course->ID, $user_id);
                    $completedCourse = $course;

                    // The Course is completed - update the user activity in the Courses special table:
                    $result = tps_update_user_activity_courses($user_id, $course->ID);

                }
            }
        }

        if($track) {
            // error_log("block track:" . $track->post_name);

            if($completedCourse) {

                $uncompleted_track_course = Track::get_first_uncompleted_course($track->ID, $user_id);
                // error_log("uncompleted_track_course:" . ($uncompleted_track_course ? $uncompleted_track_course->post_name : ""));
                if( !$uncompleted_track_course ) {

                    Track::complete_by_user($track->ID, $user_id);
                    $completedTrack = $track;

                    // The Track is completed - update the user activity in the Tracks special table:
                    $result = tps_update_user_activity_tracks($user_id, $track->ID);

                }
            }
        }

        if($completedCourse) {
            Certificate::save_certificate($user_id, $completedCourse->post_title, Certificate::CERTIFICATE_TYPE_COURSE, ['course_id' => $completedCourse->ID]);
        }

        if($completedTrack) {
            Certificate::save_certificate($user_id, $completedTrack->post_title, Certificate::CERTIFICATE_TYPE_TRACK, ['track_id' => $completedTrack->ID]);            
        }
    }
}

add_action("learndash_course_completed", '\Teplosocial\hooks\ModuleHooks::handle_module_complete', 5, 1);

// TMP DBG:
if(isset($_GET['tst'])) {
    add_action('init', function(){

//        $res = tps_update_user_activity_modules(get_current_user_id(), 22112);
//        echo '<pre>HERE: '.print_r((int)$res, 1).'</pre>';

    });
}