<?php

namespace Teplosocial\cli;

use Teplosocial\models\{CourseReview, VisitorSession};


if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Setup db tables and fields
 */

class SetupDb
{
    public function setup_course_reviews($args, $assoc_args)
    {
        global $wpdb;

        $table_name = CourseReview::$table_name;

        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$table_name}` (
            `mark_id` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL,
            `course_id` bigint(20) NOT NULL,
            `mark` smallint(6) NOT NULL,
            `mark_comment` text NOT NULL,
            `mark_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`mark_id`),
            KEY `user_course_marks` (`user_id`, `course_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
          
        \WP_CLI::success(__('Course reviews table created.', 'tps'));        
    }

    public function setup_visitor_sessions($args, $assoc_args)
    {
        global $wpdb;

        $table_name = VisitorSession::$table_name;

        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$table_name}` (
            `id` VARCHAR(32) NOT NULL, 
            `time_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            `time_last_touch` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            `user_id` INT NOT NULL , 
            PRIMARY KEY (`id`), 
            INDEX (`time_start`), 
            INDEX (`time_last_touch`)
            ) ENGINE = MyISAM DEFAULT CHARSET=utf8mb4");
          
        \WP_CLI::success(__('Visitor sessions table created.', 'tps'));        
    }

    public function setup_stats_uncompleted_courses($args, $assoc_args)
    {
        global $wpdb;

        $table_name = 'stats_uncompleted_course';

        $wpdb->show_errors();
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$table_name}` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL, 
            `course_id` bigint(20) NOT NULL, 
            `task_only` smallint NOT NULL, 
            `task_or_test` smallint NOT NULL,
            PRIMARY KEY (`id`) 
            ) ENGINE = MyISAM DEFAULT CHARSET=utf8mb4");
        $wpdb->hide_errors();
          
        \WP_CLI::success(__('Table created.', 'tps'));        
    }
}

\WP_CLI::add_command('tps_setup_db', '\Teplosocial\cli\SetupDb');
