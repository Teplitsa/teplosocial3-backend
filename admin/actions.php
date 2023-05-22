<?php
/**
 * Export course reviews to CSV
 */

use \Teplosocial\models\Student;
use \Teplosocial\models\Course;
use \Teplosocial\models\StudentLearning;

//Hook our function , wi_create_backup(), into the action wi_create_daily_backup
add_action( 'wi_create_daily_backup', 'wi_create_backup' );
function wi_create_backup(){
    //Run code to create backup.
}

if ( isset($_GET['action'] ) && $_GET['action'] == 'download_csv' )  {
    add_action( 'admin_init', 'csv_export' );
}
function csv_export() {
    if( !current_user_can( 'manage_options' ) ){ return false; }

    if( !is_admin() ){ return false; }

    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_csv' ) ) {
        die( 'Security check error' );
    }

    ob_start();
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'feedback-' . $domain . '-' . time() . '.csv';

    $header_row = array(
        'User ID',
        'Автор',
        'Курс',
        'Оценка',
        'Комментарий',
        'Дата',
        'Город',
        'Почта'
    );
    $data_rows = array();
    global $wpdb;
    $table_name = \Teplosocial\models\CourseReview::$table_name;
    $sql = "SELECT * FROM {$wpdb->prefix}{$table_name}";
    $results = $wpdb->get_results( $sql, 'ARRAY_A' );
    foreach ( $results as $result ) {
        $user_info = get_userdata($result['user_id']);
        $row = array(
            $result['user_id'],
            $user_info->display_name,
            get_the_title($result['course_id']),
            $result['mark'],
            $result['mark_comment'],
            $result['mark_time'],
            get_user_meta( $result['user_id'], \Teplosocial\models\Student::META_CITY, true),
            $user_info->user_email
        );
        $data_rows[] = $row;
    }
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );

    ob_end_flush();

    die();
}

// course certs export
if ( isset($_GET['action'] ) && $_GET['action'] == 'download_cert_csv' )  {
    add_action( 'admin_init', 'tps_course_cert_csv_export' );
}
function tps_course_cert_csv_export() {
    if( !current_user_can( 'manage_options' ) ){ return false; }

    if( !is_admin() ){ return false; }

    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_cert_csv' ) ) {
        die( 'Security check error' );
    }

    ob_start();
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'cerificates-' . $domain . '-' . date('Y-m-d') . '.csv';

    $header_row = array(
        '№',
        'Имя пользователя',
        'Email пользователя',
        'Название курса',
        'Дата',
        'ID пользователя',
    );
    $data_rows = array();
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}certificates WHERE course_type IN ('tiles_group', %s) ORDER BY ID DESC ";
    $results = $wpdb->get_results( $wpdb->prepare( $sql, \Teplosocial\models\Certificate::CERTIFICATE_TYPE_COURSE), 'ARRAY_A' );
    foreach ( $results as $result ) {
        $user_info = get_userdata($result['user_id']);
        $row = array(
            $result['ID'],
            $result['user_name'],
            $user_info->user_email,
            $result['course_name'],
            $result['moment'],
            $result['user_id'],
        );
        $data_rows[] = $row;
    }
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );

    ob_end_flush();

    die();
}

// track certs export
if ( isset($_GET['action'] ) && $_GET['action'] == 'download_track_cert_csv' )  {
    add_action( 'admin_init', 'tps_track_cert_csv_export' );
}
function tps_track_cert_csv_export() {
    if( !current_user_can( 'manage_options' ) ){ return false; }

    if( !is_admin() ){ return false; }

    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_track_cert_csv' ) ) {
        die( 'Security check error' );
    }

    ob_start();
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'cerificates-track-' . $domain . '-' . date('Y-m-d') . '.csv';

    $header_row = array(
        '№',
        'Имя пользователя',
        'Email пользователя',
        'Название направления',
        'Дата',
        'ID пользователя',
    );
    $data_rows = array();
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}certificates WHERE course_type IN (%s) ORDER BY ID DESC ";
    $results = $wpdb->get_results( $wpdb->prepare( $sql, \Teplosocial\models\Certificate::CERTIFICATE_TYPE_TRACK), 'ARRAY_A' );
    foreach ( $results as $result ) {
        $user_info = get_userdata($result['user_id']);
        $row = array(
            $result['ID'],
            $result['user_name'],
            $user_info->user_email,
            $result['course_name'],
            $result['moment'],
            $result['user_id'],
        );
        $data_rows[] = $row;
    }
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );

    ob_end_flush();

    die();
}

// completed courses with students
if ( isset($_GET['action'] ) && $_GET['action'] == 'download_completed_courses_students' )  {
    add_action( 'admin_init', 'tps_download_completed_courses_students' );
}
function tps_download_completed_courses_students() {
    if( !current_user_can( 'manage_options' ) ){ return false; }

    if( !is_admin() ){ return false; }

    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_completed_courses_students' ) ) {
        die( 'Security check error' );
    }

    ob_start();
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'completed-courses-students-' . $domain . '-' . date('Y-m-d') . '.csv';

    $header_row = array(
        'Название курса',
        'Пользователь',
        'Email пользователя',
        'Дата',
        'ID пользователя',
        'Имя',
    );
    $data_rows = array();

    global $wpdb;
    $courses = Course::get_list();
    $course_completed_sql = "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = %s ORDER BY umeta_id DESC ";

    foreach ( $courses as $course ) {
        // error_log("course: " . print_r($course->ID, true));

        $results = $wpdb->get_results( $wpdb->prepare( $course_completed_sql, "tps_course_completed_" . $course->ID ), 'ARRAY_A');

        foreach($results as $result) {
            // error_log("result: " . print_r($result, true));

            $user = get_user_by('id', $result['user_id']);
            $user_first_name = wp_specialchars_decode(Student::get_meta($user->ID, Student::META_FIRST_NAME));
    
            // error_log("user: " . print_r($user, true));
    
            $row = array(
                $course->post_title,
                $user->display_name,
                $user->user_email,
                date('Y-m-d', intval($result['meta_value'])),
                $user->ID,
                $user_first_name,
            );
            $data_rows[] = $row;
        }
    }

    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );

    ob_end_flush();

    die();
}

// almost completed courses with students
if ( isset($_GET['action'] ) && $_GET['action'] == 'download_almost_completed_courses_students' )  {
    add_action( 'admin_init', 'tps_download_almost_completed_courses_students' );
}
function tps_download_almost_completed_courses_students() {
    if( !current_user_can( 'manage_options' ) ){ return false; }

    if( !is_admin() ){ return false; }

    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_almost_completed_courses_students' ) ) {
        die( 'Security check error' );
    }

    ob_start();
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'almost-completed-courses-students-' . $domain . '-' . date('Y-m-d') . '.csv';

    $header_row = array(
        'Название курса',
        'Пользователь',
        'Email пользователя',
        'Дата',
        'ID пользователя',
        'Имя',
    );
    $data_rows = array();

    global $wpdb;
    $courses = Course::get_list();
    $table_uncompleted_courses = Student::$table_uncompleted_courses;

    $course_completed_sql = "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = %s ORDER BY umeta_id DESC ";
    $course_almost_completed_sql = "SELECT * FROM {$wpdb->prefix}{$table_uncompleted_courses} WHERE user_id = %s and course_id = %s";

    foreach ( $courses as $course ) {
        error_log("course: " . print_r($course->ID, true));

        $results = $wpdb->get_results( $wpdb->prepare( $course_completed_sql, "tps_course_started_" . $course->ID ), 'ARRAY_A');

        foreach($results as $result) {
            // error_log("result: " . print_r($result, true));
            error_log("user_id: " . $result['user_id']);            

            $is_course_completed = !empty($all_user_meta[Course::USER_META_COURSE_COMPLETED . $course->ID]);

            if($is_course_completed) {
                error_log("course completed: " . $course->ID);
                continue;
            }

            $course_almost_completed_results = $wpdb->get_row( $wpdb->prepare( $course_almost_completed_sql, $result['user_id'], $course->ID ), 'ARRAY_A');
            $is_only_task_uncompleted = boolval($course_almost_completed_results['task_only']);

            if(!$is_only_task_uncompleted) {
                error_log("course not only task uncompleted: " . $course->ID);
                continue;
            }
    
            $user = get_user_by('id', $result['user_id']);
            $user_first_name = wp_specialchars_decode(Student::get_meta($user->ID, Student::META_FIRST_NAME));

            // error_log("user: " . print_r($user, true));
    
            $row = array(
                $course->post_title,
                $user->display_name,
                $user->user_email,
                date('Y-m-d', intval($result['meta_value'])),
                $user->ID,
                $user_first_name,
            );
            $data_rows[] = $row;
        }
    }

    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );

    ob_end_flush();

    die();
}

