<?php

// For Datepickers:
add_action('admin_enqueue_scripts', function(){

    if(isset($_GET['page']) && $_GET['page'] === 'tps_statistics') {

        wp_enqueue_script('jquery-ui-datepicker');

        $wp_scripts = wp_scripts();
        wp_enqueue_style(
            'jquery-ui-theme-smoothness',
            sprintf(
                'https://ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/base/jquery-ui.css',
                $wp_scripts->registered['jquery-ui-core']->ver
            )
        );

    }

}, 1);

function tps_admin_stats_footer_scripts() {

    if(isset($_GET['page']) && $_GET['page'] === 'tps_statistics') {?>
        <script type="text/javascript">

            jQuery(function($){

                $('.tps-admin-datepicker').each(function(){

                    let $this = $(this);

                    $this.datepicker({
                        dateFormat: 'dd.mm.yy',
                        changeMonth: true,
                        changeYear: true,
                        minDate: $this.data('min-date'),
                        maxDate: $this.data('max-date'),
                        defaultDate: $this.data('default-date'),
                        altFormat: 'yy-mm-dd',
                        altField: $this.data('formatted-value-field'),
                    });

                });
            });

        </script>
    <?php }?>

    <?php
}
add_action('admin_footer', 'tps_admin_stats_footer_scripts');
// For Datepickers - END

// Statistics page handling:
function tps_admin_statistics_page_display() {

    if(isset($_GET['tps_stats_submit'])) {

        $_GET['tps_stats_date_total_period_start'] = empty($_GET['tps_stats_date_total_period_start']) ?
            date('Y-m-d', strtotime('2022-10-01 00:00:00')) : $_GET['tps_stats_date_total_period_start'];

        if( // The total stats period start is in the DB as an option
            $_GET['tps_stats_date_total_period_start']
            && $_GET['tps_stats_date_total_period_start'] !== get_option('tps_stats_date_total_period_start')
        ) {
            update_option('tps_stats_date_total_period_start', $_GET['tps_stats_date_total_period_start']);
        }

        $_GET['tps_stats_date_from'] = empty($_GET['tps_stats_date_from']) ?
            date('Y-m-d', strtotime('-7 days')) : $_GET['tps_stats_date_from'];

        $_GET['tps_stats_date_to'] = empty($_GET['tps_stats_date_to']) ?
            date('Y-m-d') : $_GET['tps_stats_date_to'];

        $_GET['tps_stats_results_emails'] = empty($_GET['tps_stats_results_emails']) ?
            Teplosocial\Config::STATS_EXTRA_EMAILS :
            array_map(function($element){ return trim($element); }, explode(',', $_GET['tps_stats_results_emails']));

        if(isset($_GET['tst'])) {
            echo '<pre>'.print_r($_GET, 1).'</pre>';
        }

        /** @todo When Teplosocial\models\StatsReport::get_weekly_report_stats() is ready, use it inslead of Teplosocial\cli\Stats::mail_weekly_stats() */

        $stats = new Teplosocial\cli\Stats;
        $result_html = $stats->mail_weekly_stats([], [
            'date-from' => $_GET['tps_stats_date_from'],
            'date-to' => $_GET['tps_stats_date_to'],
            'date-total-period-start' => $_GET['tps_stats_date_total_period_start'],
            'emails' => $_GET['tps_stats_results_emails'],
            'print-results' => false,
            'return-results' => true,
        ]);

        echo '<h4>Статистика Теплица.Курсы за '.$stats->get_week_number().'-ю неделю (с '.$_GET['date_from'].' по '.$_GET['date_to'].'</h4>'
            .$result_html
            .'<p><a href="'.admin_url('admin.php?page=tps_statistics').'">Назад на страницу получения статистики</a></p>';

    } else {
        include( get_template_directory().'/admin/page-statistics.php' );
    }

}
// Statistics page handling - END

// Users activity: Users Modules display:
function tps_admin_users_activity_modules_page_display() {

    require_once 'admin-lists/tps-class-admin-users-activity-modules-list-table.php';

    $modules_list = new Tps_Admin_Users_Activity_Modules_List_Table();?>

    <h1 class="wp-heading-inline">Активность студентов - модули</h1>

    <div id="poststuff">
    <div>

        <div id="post-body-content" class="<?php if($modules_list->get_items_count() === 0) {?>empty-list<?php }?>">
            <div class="meta-box-sortables ui-sortable">
                <form method="post">

                    <?php $modules_list->prepare_items();
                    $modules_list->display();

                    if($modules_list->has_items()) {
                        $modules_list->bulk_edit_fields();
                    }?>

                </form>
            </div>
        </div>

    </div>

    <?php
}
// Users activity: Users Modules display - END

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
    $existing_module_activity_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tps_users_activity_modules WHERE user_id=%d AND module_post_id=%d", $current_user_id, $completed_module_data['course']->ID));

    $tmp = ['Completed module data' => $completed_module_data, 'Existing activity data' => $existing_module_activity_data,];
//    delete_transient('tps_dbg');
    set_transient('tps_dbg', $tmp);
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

        echo '<pre>HERE: '.print_r(get_transient('tps_dbg'), 1).'</pre>';

    }

});