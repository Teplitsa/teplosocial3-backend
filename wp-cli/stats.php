<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Stats as StatsModel, UserStats, TrackStats, ModuleStats, CertificateStats, QuizStats, VisitorSessionStats};
use Teplosocial\Config;

if( !class_exists('WP_CLI') ) {
    return;
}

class Stats {

    public function mail_weekly_stats($args, $assoc_args) {

        global $wpdb;

        $stats = [];

//        $date_week_ago = \Teplosocial\utils\get_week_ago_mysql_date();
//        $date_last_day_to_display = \Teplosocial\utils\get_yesterday_mysql_date();
        // TODO Only for the first stats script call (at 02.12.2022)! After that, return to prev. interval settings (weekly), but call the script every FRIDAY (instead of overy Monday):
        $date_week_ago = date('Y-m-d', strtotime('2022-10-01 00:00:00')); // Interval start date, date('Y-m-d') format
        $date_last_day_to_display = date('Y-m-d', strtotime('2022-12-01 23:59:59')); // Interval end date, date('Y-m-d') format
        // TODO END


        $date_last_day = \date('Y-m-d');

        \WP_CLI::log('date_week_ago: '.$date_week_ago);
        \WP_CLI::log('date_last_day: '.$date_last_day);

        $stats['registered_users_count'] = UserStats::get_registered_count($date_week_ago, $date_last_day_to_display);
//        $stats['total_registered_users_count'] = UserStats::get_registered_count(); // TODO TMP DBG
        $stats['total_registered_users_count'] = UserStats::get_registered_count($date_week_ago, $date_last_day_to_display);

        \WP_CLI::log('registered_users_count: '.$stats['registered_users_count']);
        \WP_CLI::log('total_registered_users_count: '.$stats['total_users_count']);

        $stats['completed_modules_count'] = ModuleStats::get_completed_count($date_week_ago, $date_last_day_to_display);
//        $stats['total_completed_modules_count'] = ModuleStats::get_completed_count(); // TODO TMP DBG
        $stats['total_completed_modules_count'] = ModuleStats::get_completed_count($date_week_ago, $date_last_day_to_display);

        \WP_CLI::log('completed_modules_count: '.$stats['completed_modules_count']);
        \WP_CLI::log('total_completed_modules_count: '.$stats['total_completed_modules_count']);

        $stats['completed_tracks_count'] = TrackStats::get_completed_count($date_week_ago, $date_last_day_to_display);
        $stats['total_completed_tracks_count'] = TrackStats::get_completed_count($date_week_ago, $date_last_day_to_display);

        \WP_CLI::log('completed_tracks_count: '.$stats['completed_tracks_count']);
        \WP_CLI::log('total_completed_tracks_count: '.$stats['total_completed_tracks_count']);

        $stats['certificates_count'] = CertificateStats::get_count_on_kursi($date_week_ago, $date_last_day_to_display);
        $stats['total_certificates_count'] = CertificateStats::get_count_on_kursi($date_week_ago, $date_last_day_to_display);

        \WP_CLI::log('certificates_count: '.$stats['certificates_count']);
        \WP_CLI::log('total_certificates_count: '.$stats['total_certificates_count']);

        $stats['completed_adaptests_count'] = QuizStats::get_completed_adaptests_count($date_week_ago, $date_last_day_to_display);
        $stats['total_completed_adaptests_count'] = QuizStats::get_completed_adaptests_count(
            $date_week_ago,
            $date_last_day_to_display
        );

        \WP_CLI::log('completed_adaptests_count: '.$stats['completed_adaptests_count']);
        \WP_CLI::log('total_completed_adaptests_count: '.$stats['total_completed_adaptests_count']);

        $stats['avarage_session_duration'] = \Teplosocial\utils\seconds_to_hours_minutes(VisitorSessionStats::get_avarage_duration($date_week_ago, $date_last_day));
        $stats['total_avarage_session_duration'] = \Teplosocial\utils\seconds_to_hours_minutes(VisitorSessionStats::get_avarage_duration($date_week_ago, $date_last_day_to_display));

        \WP_CLI::log('avarage_session_duration: '.$stats['avarage_session_duration']);
        \WP_CLI::log('total_avarage_session_duration: '.$stats['total_avarage_session_duration']);

        $stats_titles = [ // The stats order in the regular email is determined by this array elements order
            'registered_users_count' => 'Новые регистрации',
            'completed_modules_count' => 'Завершённые модули',
            'completed_adaptests_count' => 'Пройденные адаптационные тесты',
            'avarage_session_duration' => 'Средняя сессия на сайте',
            'completed_tracks_count' => 'Завершённые треки',
            'certificates_count' => 'Полученные сертификаты',
        ];

        $this->send_stats_email([
            'stats_html' => $this->compose_stats_html($stats, $stats_titles, Config::STATS_GOALS),
            'from_date' => date("d.m.Y", strtotime($date_week_ago)),
            'to_date' => date("d.m.Y", strtotime($date_last_day_to_display)),
            'week_number' => $this->get_week_number(),
            'week_completed_modules' => $stats['completed_modules_count'],
        ]);

        \WP_CLI::success(__('Weekly stats sent.', 'tps'));

    }

    private function compose_stats_html($stats, $stats_titles, $stats_goals) {

        ob_start();

        echo '<table class="log-stats-table"><col width="50%"><col width="25%"><col width="25%">';

        $i = 0;
        foreach($stats_titles as $key => $title) {

            $i++;
            echo '<tr class="'.($i % 2 == 0 ? "alternate" : '').'">';
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

    private function send_stats_email($stats_data) {

        // $subject = "Теплица.Курсы - статистика за {{week_number}}-ю неделю"; // TODO TMP DBG
        $subject = "Теплица.Курсы - статистика за период с 1-го октября по 1 декабря (включительно)";
        $message = <<<MAIL
            Статистика Теплица.Курсы за {{week_number}}-ю неделю с {{from_date}} по {{to_date}}.

            {{stats_html}}
        MAIL;

        $subject = \Teplosocial\utils\fill_template($subject, $stats_data);

        $message = nl2br($message);
        $message = \Teplosocial\utils\fill_template($message, $stats_data);

        $to = 'ahaenor@gmail.com'; // get_bloginfo('admin_email'); // TODO TMP DBG
        $from = $to;

        $headers  = 'MIME-Version: 1.0'."\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8'."\r\n";
        $headers .= 'From: '.wp_specialchars_decode(get_option('blogname'), ENT_QUOTES).' <'.$from.'>'."\r\n";
//        if(count(Config::STATS_EXTRA_EMAILS) > 0) { // TODO TMP DBG
//            $headers .= 'Cc: '.implode(', ', Config::STATS_EXTRA_EMAILS)."\r\n";
//        }
        
        wp_mail($to, $subject, $message, $headers);

    }

    private function get_week_number() {

        $now_datetime = new \DateTime();
        $start_datetime = \DateTime::createFromFormat('Y-m-d H:i:s', '2021-10-01 00:00:00');
        $datetime_diff = $now_datetime->diff($start_datetime);
        
        $days_number = $datetime_diff->format('%a');
        $week_number = floor($days_number / 7);
        
        return $week_number;

    }

}

\WP_CLI::add_command('tps_stats', '\Teplosocial\cli\Stats');