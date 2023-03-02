<?php

// Users activity DB tables filling:
//do_action(
//    'learndash_course_completed',
//    array(
//        'user'             => $current_user,
//        'course'           => get_post( $course_id ),
//        'progress'         => array( $course_id => $course_progress ),
//        'course_completed' => $course_completed_time,
//    )
//);

add_action('learndash_course_completed', function($completed_module_data){ // Triggers on Module completion (VIA FRONTEND ONLY!)

    if(empty($completed_module_data) || !is_array($completed_module_data) || !is_a($completed_module_data, 'WP_Post')) {
        return;
    }

    $current_user_id = get_current_user_id();
    if( !$current_user_id ) { // If somehow it's a non-logged in user, stop right now
        return;
    }

    global $wpdb;

    // Modules activity table update:
//    $existing_module_activity_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tps_users_activity_modules WHERE user_id=%d AND module_post_id=%d", $current_user_id, $completed_module_data['course']->ID));
    $existing_module_activity_data = tps_get_user_activity_modules(['user_id' => $current_user_id,]);

    $tmp = ['Completed module data' => $completed_module_data, 'Existing activity data' => $existing_module_activity_data,];
//    delete_transient('tps_dbg');
    set_transient('tps_dbg', $tmp);
    error_log('<pre>'.print_r($tmp, 1).'</pre>');
//    die('<pre>HERE: '.print_r($tmp, 1).'</pre>');
    // Modules activity table update - END


    /** @todo Use the following for the Courses & Tracks activity logging: */
//    $course = Course::get_by_module($module->ID);
//    if($course) {
//        // error_log("block course:" . $course->post_name);
//        $track = Track::get_by_course($course->ID);
//
//        if($completedModule) {
//            $uncompleted_course_module = Course::get_first_uncompleted_module($course->ID, $user_id);
//            // error_log("uncompleted_course_module:" . ($uncompleted_course_module ? $uncompleted_course_module->post_name : ""));
//            if(!$uncompleted_course_module) {
//                Course::complete_by_user($course->ID, $user_id);
//                $completedCourse = $course;
//            }
//        }
//    }
//
//    if($track) {
//        // error_log("block track:" . $track->post_name);
//
//        if($completedCourse) {
//            $uncompleted_track_course = Track::get_first_uncompleted_course($track->ID, $user_id);
//            // error_log("uncompleted_track_course:" . ($uncompleted_track_course ? $uncompleted_track_course->post_name : ""));
//            if(!$uncompleted_track_course) {
//                Track::complete_by_user($track->ID, $user_id);
//                $completedTrack = $track;
//            }
//        }
//    }

}, 100);

add_action('init', function(){

    if(isset($_GET['tst'])) {

//        global $wpdb;
//        echo '<pre>'.print_r($wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tps_users_activity_modules WHERE user_id=%d AND module_post_id=%d", get_current_user_id(), 17813)), 1).'</pre>';

//        echo '<pre>HERE: '.print_r(get_transient('tps_dbg'), 1).'</pre>';

    }

});