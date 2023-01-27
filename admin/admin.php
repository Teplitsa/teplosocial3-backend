<?php

require get_template_directory() . '/admin/actions.php';
require get_template_directory() . '/admin/admin-assignments.php';
require get_template_directory() . '/admin/admin-statistics.php';
require get_template_directory() . '/admin/page-reviews.php';

/**
 *  Customize admin menu
 */
function tps_menu_setup() {

    add_submenu_page( 'learndash-lms', 'Курсы', 'Курсы', 'manage_options', 'edit.php?post_type=tps_course', null, 0 );
    add_submenu_page( 'learndash-lms', 'Треки','Треки', 'manage_options', 'edit.php?post_type=tps_track', null, 0 );

    global $menu;
    foreach($menu as $k => $v) {
        if($v[0] == 'LearnDash LMS') {
            $menu[$k][0] = __('Teploset', 'tps');
        }
    }

    add_submenu_page(
        'learndash-lms',
        __('Actions', 'tps'),
        __('Actions', 'tps'),
        'manage_options',
        'tps_actions',
        'tps_admin_actions_page_display',
    );

    add_submenu_page(
        'learndash-lms',
        'Статистика',
        'Статистика',
        'manage_options',
        'tps_statistics',
        'tps_admin_statistics_page_display',
    );

}
add_action('admin_menu', 'tps_menu_setup', 50);

function tps_admin_actions_page_display() {
	include( get_template_directory().'/admin/page-actions.php' );
}

// course reviews
function tps_feedback_page_register() {
    add_submenu_page(
        'learndash-lms',
        'Теплица.Курсы - Обратная связь',
        'Обратная связь',
        'manage_options',
        'tps-feedback',
        'TPS_Feedback_List::tps_feedback_page_content'
    );
}
add_action( 'admin_menu', 'tps_feedback_page_register', 1001 );

// link to user profile
function tps_add_login_as_student_link($actions, $user)
{
    $href = admin_url("?mock-login=" . $user->user_nicename);
    $actions['login_as_student'] = '<a href="' . $href . '" target="_blank">Войти как студент</a>';

    return ($actions) ;
}
add_filter ('user_row_actions', 'tps_add_login_as_student_link', 10, 2) ;

function tps_load_admin_scripts() {
    $url = get_template_directory_uri();
    wp_enqueue_style( 'tps-admin', $url . '/assets/css/admin.css', null );
}
add_action( 'admin_enqueue_scripts', 'tps_load_admin_scripts', 30 );