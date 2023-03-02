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

    public function setup_user_activities_tables($args, $assoc_args) {

        global $wpdb;

        $wpdb->show_errors();

        $result = $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tps_users_activity_modules` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL, 
            `module_post_id` bigint(20) NOT NULL, 
            `module_start_date` DATETIME, 
            `module_end_date` DATETIME,
            PRIMARY KEY (`ID`), 
            INDEX `user_id_index` (`user_id`), 
            INDEX `module_post_id_index` (`module_post_id`),
            UNIQUE KEY `user_module_unique` (`user_id`,`module_post_id`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4");

        if($result) {
            \WP_CLI::success(__('Table created: users activity (Modules).', 'tps'));
        } else {
            \WP_CLI::error(__('Table NOT created - users activity (Modules).', 'tps'));
        }

        $result = $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tps_users_activity_courses` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL, 
            `course_post_id` bigint(20) NOT NULL, 
            `course_start_date` DATETIME, 
            `course_end_date` DATETIME,
            PRIMARY KEY (`ID`), 
            INDEX `user_id_index` (`user_id`), 
            INDEX `course_post_id_index` (`course_post_id`),
            UNIQUE KEY `user_course_unique` (`user_id`,`course_post_id`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4");

        if($result) {
            \WP_CLI::success(__('Table created: users activity (Courses).', 'tps'));
        } else {
            \WP_CLI::error(__('Table NOT created - users activity (Courses).', 'tps'));
        }

        $result = $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tps_users_activity_tracks` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL, 
            `track_post_id` bigint(20) NOT NULL, 
            `track_start_date` DATETIME, 
            `track_end_date` DATETIME,
            PRIMARY KEY (`ID`), 
            INDEX `user_id_index` (`user_id`), 
            INDEX `track_post_id_index` (`track_post_id`),
            UNIQUE KEY `user_track_unique` (`user_id`,`track_post_id`)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4");

        if($result) {
            \WP_CLI::success(__('Table created: users activity (Tracks).', 'tps'));
        } else {
            \WP_CLI::error(__('Table NOT created - users activity (Tracks).', 'tps'));
        }

        $wpdb->hide_errors();

        \WP_CLI::confirm(__('Fill the users activity tables with the data? Warning: this might take a while.', 'tps'));

//       User meta keys & values:
//        * Module started: tps_module_started_{module_post_ID} => completion timestamp
//        * Module completed: course_completed_{module_post_ID} => completion timestamp,
//          tps_module_completed_by_adaptest_{module_post_ID} => completion timestamp
//        * Course started: tps_course_started_{course_post_ID} => completion timestamp
//        * Course completed: tps_course_completed_{course_post_ID} => completion timestamp
//        * Track started: tps_track_started_{track_post_ID} => completion timestamp
//        * Track completed: tps_track_completed_{track_post_ID} => completion timestamp

        self::_insert_users_activity_modules_data();
        self::_insert_users_activity_courses_data();
        self::_insert_users_activity_tracks_data();

    }

    // Users activity - Modules data insetion/update:
    protected static function _insert_users_activity_modules_data() {

        global $wpdb;

        $new_data = [];

        $results = $wpdb->get_results("SELECT 
            user_id, meta_key, meta_value as `timestamp` 
            FROM `{$wpdb->prefix}usermeta` 
            WHERE `meta_key` LIKE 'tps_module_started_%'");

        foreach($results as $line) {

            $module_id = absint(str_replace('tps_module_started_', '', $line->meta_key));
            if( !$module_id ) {
                continue;
            }

            $new_data[$line->user_id][$module_id]['date_start'] = date('Y-m-d H:i:s', $line->timestamp);

        }

        $results = $wpdb->get_results("SELECT 
            user_id, meta_key, meta_value as `timestamp` 
            FROM `{$wpdb->prefix}usermeta` 
            WHERE `meta_key` LIKE 'course_completed_%'");

        foreach($results as $line) {

            $module_id = absint(str_replace('course_completed_', '', $line->meta_key));
            if( !$module_id ) {
                continue;
            }

            $new_data[$line->user_id][$module_id]['date_end'] = date('Y-m-d H:i:s', $line->timestamp);

        }

        $rows_inserted = 0;
        $rows_updated = 0;
        $rows_total = 0;
        foreach($new_data as $user_id => $user_data) {
            foreach($user_data as $module_id => $module_data) {

                $existing_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tps_users_activity_modules WHERE user_id = %d AND module_post_id = %d",
                    $user_id, $module_id
                ));

                $rows_total++;

                if($existing_data) { // Data are already in the table, update them if needed

                    $update = [];

                    if($existing_data->module_start_date !== $module_data['date_start']) {
                        $update['module_start_date'] = $module_data['date_start'];
                    }
                    if($existing_data->module_end_date !== $module_data['date_end']) {
                        $update['module_end_date'] = $module_data['date_end'];
                    }

                    if($update) {

                        $res = $wpdb->update($wpdb->prefix.'tps_users_activity_modules', $update, ['ID' => $existing_data->ID]);

                        if($res) {
                            $rows_updated++;
                        }

                    }

                } else { // Insert the data anew

                    $res = $wpdb->insert(
                        $wpdb->prefix.'tps_users_activity_modules', [
                        'user_id' => $user_id,
                        'module_post_id' => $module_id,
                        'module_start_date' => empty($module_data['date_start']) ? NULL : $module_data['date_start'],
                        'module_end_date' => empty($module_data['date_end']) ? NULL : $module_data['date_end'],
                    ],
                        ['%d', '%d', '%s', '%s']
                    );

                    if($res) {
                        $rows_inserted++;
                    }

                }

            }
        }

        \WP_CLI::success("Modules users activity table filled. Rows inserted: {$rows_inserted}/{$rows_total}, rows updated: {$rows_updated}/{$rows_total}.");

    }

    // Users activity - Courses data insetion/update:
    protected static function _insert_users_activity_courses_data() {

        global $wpdb;

        $new_data = [];

        $results = $wpdb->get_results("SELECT 
            user_id, meta_key, meta_value as `timestamp` 
            FROM `{$wpdb->prefix}usermeta` 
            WHERE `meta_key` LIKE 'tps_course_started_%'");

        foreach($results as $line) {

            $course_id = absint(str_replace('tps_course_started_', '', $line->meta_key));
            if( !$course_id ) {
                continue;
            }

            $new_data[$line->user_id][$course_id]['date_start'] = date('Y-m-d H:i:s', $line->timestamp);

        }

        $results = $wpdb->get_results("SELECT 
            user_id, meta_key, meta_value as `timestamp` 
            FROM `{$wpdb->prefix}usermeta` 
            WHERE `meta_key` LIKE 'tps_course_completed_%'");

        foreach($results as $line) {

            $course_id = absint(str_replace('tps_course_completed_', '', $line->meta_key));
            if( !$course_id ) {
                continue;
            }

            $new_data[$line->user_id][$course_id]['date_end'] = date('Y-m-d H:i:s', $line->timestamp);

        }

        $rows_inserted = 0;
        $rows_updated = 0;
        $rows_total = 0;
        foreach($new_data as $user_id => $user_data) {
            foreach($user_data as $course_id => $course_data) {

                $existing_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tps_users_activity_courses WHERE user_id = %d AND course_post_id = %d",
                    $user_id, $course_id
                ));

                $rows_total++;

                if($existing_data) { // Data are already in the table, update them if needed

                    $update = [];

                    if($existing_data->course_start_date !== $course_data['date_start']) {
                        $update['course_start_date'] = $course_data['date_start'];
                    }
                    if($existing_data->course_end_date !== $course_data['date_end']) {
                        $update['course_end_date'] = $course_data['date_end'];
                    }

                    if($update) {

                        $res = $wpdb->update($wpdb->prefix.'tps_users_activity_courses', $update, ['ID' => $existing_data->ID]);

                        if($res) {
                            $rows_updated++;
                        }

                    }

                } else { // Insert the data anew

                    $res = $wpdb->insert(
                        $wpdb->prefix.'tps_users_activity_courses', [
                        'user_id' => $user_id,
                        'course_post_id' => $course_id,
                        'course_start_date' => empty($course_data['date_start']) ? NULL : $course_data['date_start'],
                        'course_end_date' => empty($course_data['date_end']) ? NULL : $course_data['date_end'],
                    ],
                        ['%d', '%d', '%s', '%s']
                    );

                    if($res) {
                        $rows_inserted++;
                    }

                }

            }
        }

        \WP_CLI::success("Courses users activity table filled. Rows inserted: {$rows_inserted}/{$rows_total}, rows updated: {$rows_updated}/{$rows_total}.");

    }

    // Users activity - Tracks data insetion/update:
    protected static function _insert_users_activity_tracks_data() {

        global $wpdb;

        $new_data = [];

        $results = $wpdb->get_results("SELECT 
            user_id, meta_key, meta_value as `timestamp` 
            FROM `{$wpdb->prefix}usermeta` 
            WHERE `meta_key` LIKE 'tps_track_started_%'");

        foreach($results as $line) {

            $track_id = absint(str_replace('tps_track_started_', '', $line->meta_key));
            if( !$track_id ) {
                continue;
            }

            $new_data[$line->user_id][$track_id]['date_start'] = date('Y-m-d H:i:s', $line->timestamp);

        }

        $results = $wpdb->get_results("SELECT 
            user_id, meta_key, meta_value as `timestamp` 
            FROM `{$wpdb->prefix}usermeta` 
            WHERE `meta_key` LIKE 'tps_track_completed_%'");

        foreach($results as $line) {

            $track_id = absint(str_replace('tps_track_completed_', '', $line->meta_key));
            if( !$track_id ) {
                continue;
            }

            $new_data[$line->user_id][$track_id]['date_end'] = date('Y-m-d H:i:s', $line->timestamp);

        }

        $rows_inserted = 0;
        $rows_updated = 0;
        $rows_total = 0;
        foreach($new_data as $user_id => $user_data) {
            foreach($user_data as $track_id => $track_data) {

                $existing_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tps_users_activity_tracks WHERE user_id = %d AND track_post_id = %d",
                    $user_id, $track_id
                ));

                $rows_total++;

                if($existing_data) { // Data are already in the table, update them if needed

                    $update = [];

                    if($existing_data->track_start_date !== $track_data['date_start']) {
                        $update['track_start_date'] = $track_data['date_start'];
                    }
                    if($existing_data->track_end_date !== $track_data['date_end']) {
                        $update['track_end_date'] = $track_data['date_end'];
                    }

                    if($update) {

                        $res = $wpdb->update($wpdb->prefix.'tps_users_activity_tracks', $update, ['ID' => $existing_data->ID]);

                        if($res) {
                            $rows_updated++;
                        }

                    }

                } else { // Insert the data anew

                    $res = $wpdb->insert(
                        $wpdb->prefix.'tps_users_activity_tracks', [
                        'user_id' => $user_id,
                        'track_post_id' => $track_id,
                        'track_start_date' => empty($track_data['date_start']) ? NULL : $track_data['date_start'],
                        'track_end_date' => empty($track_data['date_end']) ? NULL : $track_data['date_end'],
                    ],
                        ['%d', '%d', '%s', '%s']
                    );

                    if($res) {
                        $rows_inserted++;
                    }

                }

            }
        }

        \WP_CLI::success("Tracks users activity table filled. Rows inserted: {$rows_inserted}/{$rows_total}, rows updated: {$rows_updated}/{$rows_total}.");

    }

}

\WP_CLI::add_command('tps_setup_db', '\Teplosocial\cli\SetupDb');
