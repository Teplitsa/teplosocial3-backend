<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Stats as StatsModel, UserStats, TrackStats, ModuleStats, CertificateStats, QuizStats, VisitorSessionStats};
use Teplosocial\Config;

if( !class_exists('WP_CLI') ) {
    return;
}

class Stats {

    public function mail_weekly_stats($args, $assoc_args) {

//        global $wpdb;

        $stats = [];

        $assoc_args = wp_parse_args(
            $assoc_args,
            [
                'date-from' => \Teplosocial\utils\get_week_ago_mysql_date(),
                'date-to' => date('Y-m-d'),
                'date-total-period-start' => get_option('tps_stats_date_total_period_start'),
                'emails' => Config::STATS_EXTRA_EMAILS,
                'print-results' => 'cli',
                'return-results' => false,
		    ]
	    );

//        $date_total_period_start = date('Y-m-d', strtotime('2022-10-01 00:00:00')); // Interval start date, date('Y-m-d') format
        $date_total_period_start = $assoc_args['date-total-period-start']; // Interval start date, date('Y-m-d') format

        // Use in normal work mode (w/o custom date settings):
//        $date_from = \Teplosocial\utils\get_week_ago_mysql_date();
//        $date_last_day_to_display = \Teplosocial\utils\get_yesterday_mysql_date();
//        $date_to = \date('Y-m-d');
        $date_from = $assoc_args['date-from'];
        $date_last_day_to_display = date( 'Y-m-d', strtotime('-1 day', strtotime($assoc_args['date-to'])) );
        $date_to = $assoc_args['date-to'];
        // Use in normal work mode - END

        // Use when custom dates settings are needed:
//        $date_from = date('Y-m-d', strtotime('2022-12-02 00:00:00')); // Interval start date, date('Y-m-d') format
//        $date_last_day_to_display = date('Y-m-d', strtotime('2022-12-09 23:59:59')); // Interval end date, date('Y-m-d') format
//        $date_to = \date('Y-m-d', strtotime('2022-12-09'));
        // Custom dates settings - END

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('date_from: '.$date_from);
            \WP_CLI::log('date_to: '.$date_to);
        }

        $stats['registered_users_count'] = UserStats::get_registered_count($date_from, $date_last_day_to_display);
        $stats['total_registered_users_count'] = UserStats::get_registered_count(
            $date_total_period_start,
            $date_last_day_to_display
        );

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('registered_users_count: '.$stats['registered_users_count']);
            \WP_CLI::log('total_registered_users_count: '.$stats['total_registered_users_count']);
        }

        $stats['completed_modules_count'] = ModuleStats::get_completed_count($date_from, $date_last_day_to_display);
        $stats['total_completed_modules_count'] = ModuleStats::get_completed_count(
            $date_total_period_start,
            $date_last_day_to_display
        );

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('completed_modules_count: '.$stats['completed_modules_count']);
            \WP_CLI::log('total_completed_modules_count: '.$stats['total_completed_modules_count']);
        }

        $stats['completed_tracks_count'] = TrackStats::get_completed_count($date_from, $date_last_day_to_display);
        $stats['total_completed_tracks_count'] = TrackStats::get_completed_count(
            $date_total_period_start,
            $date_last_day_to_display
        );

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('completed_tracks_count: '.$stats['completed_tracks_count']);
            \WP_CLI::log('total_completed_tracks_count: '.$stats['total_completed_tracks_count']);
        }

        $stats['certificates_count'] = CertificateStats::get_count_on_kursi($date_from, $date_last_day_to_display);
        $stats['total_certificates_count'] = CertificateStats::get_count_on_kursi(
            $date_total_period_start,
            $date_last_day_to_display
        );

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('certificates_count: '.$stats['certificates_count']);
            \WP_CLI::log('total_certificates_count: '.$stats['total_certificates_count']);
        }

        $stats['completed_adaptests_count'] = QuizStats::get_completed_adaptests_count($date_from, $date_last_day_to_display);
        $stats['total_completed_adaptests_count'] = QuizStats::get_completed_adaptests_count(
            $date_total_period_start,
            $date_last_day_to_display
        );

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('completed_adaptests_count: '.$stats['completed_adaptests_count']);
            \WP_CLI::log('total_completed_adaptests_count: '.$stats['total_completed_adaptests_count']);
        }

        $stats['avarage_session_duration'] =
            \Teplosocial\utils\seconds_to_hours_minutes(VisitorSessionStats::get_avarage_duration(
                $date_from,
                $date_to
            ));
        $stats['total_avarage_session_duration'] =
            \Teplosocial\utils\seconds_to_hours_minutes(VisitorSessionStats::get_avarage_duration(
                $date_total_period_start,
                $date_last_day_to_display
            ));

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::log('avarage_session_duration: '.$stats['avarage_session_duration']);
            \WP_CLI::log('total_avarage_session_duration: '.$stats['total_avarage_session_duration']);
        }

        $stats_titles = [ // The stats order in the regular email is determined by this array elements order
            'registered_users_count' => 'Новые регистрации',
            'completed_modules_count' => 'Завершённые модули',
            'completed_adaptests_count' => 'Пройденные адаптационные тесты',
            'avarage_session_duration' => 'Средняя сессия на сайте',
            'completed_tracks_count' => 'Завершённые треки',
            'certificates_count' => 'Полученные сертификаты',
        ];

        $results_html = $this->compose_stats_html($stats, $stats_titles, Config::STATS_GOALS);

        if(count($assoc_args['emails'])) {
            $this->send_stats_email([
                'stats_html' => $results_html,
                'from_date' => date('d.m.Y', strtotime($date_from)),
                'to_date' => date('d.m.Y', strtotime($date_last_day_to_display)),
                'week_number' => $this->get_week_number(),
                'week_completed_modules' => $stats['completed_modules_count'],
            ], $assoc_args['emails']);
        }

        if($assoc_args['return-results']) { // Used when the function is called by the admin Statistics page
            return $results_html;
        }

        if($assoc_args['print-results'] === 'cli') {
            \WP_CLI::success(__('Weekly stats sent.', 'tps'));
        }

        return true;

    }

    private function compose_stats_html($stats, $stats_titles, $stats_goals) {

        ob_start();

        echo '<table class="log-stats-table"><col width="50%"><col width="25%"><col width="25%">';

        $i = 0;
        foreach($stats_titles as $key => $title) {

            $i++;
            echo '<tr class="'.($i % 2 == 0 ? 'alternate' : '').'">';
            echo '<td style="padding-right:50px;">'.$title.'&nbsp;</td>';

            echo '<td><b>'.($key != 'avarage_session_duration' ? '+' : '').$stats[$key].'</b>&nbsp;</td>';
            echo '<td>('
                .(isset($stats_goals[$key]) ? $stats['total_'.$key].' / '.$stats_goals[$key] : $stats['total_'.$key])
                .')&nbsp;</td>';

            echo '</tr>';

            if($key == 'completed_modules_count') { // Insert an HR after the last of the key stats
                echo '<tr><td colspan="3"><hr></td></tr>';
            }

        }

        echo '</table>';

        return ob_get_clean();

    }

    private function send_stats_email($stats_data, array $emails = []) {

        $subject = "Теплица.Курсы - статистика за {{week_number}}-ю неделю";
        $message = <<<MAIL
            Статистика Теплица.Курсы за {{week_number}}-ю неделю с {{from_date}} по {{to_date}}.

            {{stats_html}}
        MAIL;

        $subject = \Teplosocial\utils\fill_template($subject, $stats_data);

        $message = nl2br($message);
        $message = \Teplosocial\utils\fill_template($message, $stats_data);

        $emails = count($emails) ? $emails : Config::STATS_EXTRA_EMAILS;

        $to = get_bloginfo('admin_email');
        $from = $to;

        $headers  = 'MIME-Version: 1.0'."\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8'."\r\n";
        $headers .= 'From: '.wp_specialchars_decode(get_option('blogname'), ENT_QUOTES).' <'.$from.'>'."\r\n";
        if(count($emails)) {
            $headers .= 'Cc: '.implode(', ', $emails)."\r\n";
        }

        if(isset($_GET['tst'])) {
            echo '<pre>To: '.print_r($to, 1).'</pre>';
            echo '<pre>Subject: '.print_r($subject, 1).'</pre>';
            echo '<pre>Message: '.print_r($message, 1).'</pre>';
            echo '<pre>Headers: '.print_r($headers, 1).'</pre>';
        }
        
        $res = wp_mail($to, $subject, $message, $headers);
//        $res = wp_mail('ahaenor@gmail.com', 'Test email', 'Test email text');

        if(isset($_GET['tst'])) {
            echo '<pre>Results email sent: '.print_r((int)$res, 1).'</pre>';
        }

    }

    public function get_week_number() {

        $now_datetime = new \DateTime();
        $start_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', '2022-10-01 00:00:00');
        $datetime_diff = $now_datetime->diff($start_datetime);
        
        $days_number = $datetime_diff->format('%a');
        $week_number = floor($days_number / 7);
        
        return $week_number;

    }

}

\WP_CLI::add_command('tps_stats', '\Teplosocial\cli\Stats');