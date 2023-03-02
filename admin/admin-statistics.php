<?php

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