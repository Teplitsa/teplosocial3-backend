<?php

namespace Teplosocial\models;

use Teplosocial\models\VisitorSession;

class Stats
{
    public static string $collection_name = 'stats';
}

class UserStats extends Stats
{
    public static string $child_collection_name = 'user';

    public static function get_count(): int
    {
        global $wpdb;

        $count = $wpdb->get_var(
            // $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->users}
                SQL
            // )
        );

        return $count;
    }

    public static function get_registered_count($mysql_date_from = '2021-12-01', $mysql_date_to = ''): int
    {
        global $wpdb;

        if(!$mysql_date_to) {
            $mysql_date_to = date('Y-m-d');
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->users}
                WHERE
                    user_registered BETWEEN %s AND %s
                SQL,
                $mysql_date_from,
                $mysql_date_to
            )
        );

        return $count;
    }

    public static function update_cache(int $user_id = 0): void
    {
        $user_stats_count = self::get_count();

        if (!$user_stats_count) {

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Stats::$collection_name};

        $user_collection_name = self::$child_collection_name;

        $collection->updateOne([], [
            '$set' => [
                "{$user_collection_name}" => [
                    'total' => $user_stats_count,
                ]
            ],
        ]);
    }
}

class ModuleStats extends Stats
{
    public static function get_completed_count($mysql_date_from = "2021-10-01", $mysql_date_to = ""): int
    {
        global $wpdb;

        if(!$mysql_date_to) {
            $mysql_date_to = date("Y-m-d");
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->usermeta}
                WHERE 
                    meta_key LIKE %s
                    AND meta_value >= %s
                    AND meta_value <= %s
                SQL,
                Module::USER_META_MODULE_COMPLETED . "%",
                strtotime($mysql_date_from . " 00:00:00"),
                strtotime($mysql_date_to . " 23:59:59")
            )
        );

        return $count;
    }
}

class CourseStats extends Stats
{
    public static string $child_collection_name = 'course';

    public static function get_count(): int
    {
        global $wpdb;

        $where_post_type = "'" . Course::$post_type . "'";

        $count = $wpdb->get_var(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->posts}
                WHERE 
                    post_type = {$where_post_type}
                AND
                    post_status = 'publish'
                SQL
        );

        return $count;
    }

    public static function update_cache(string $new_status, string $old_status, \WP_Post $post): void
    {
        if ($post->post_type !== Course::$post_type || $new_status !== 'publish' && $old_status !== 'publish') {

            return;
        }

        $course_stats_count = self::get_count();

        if (!$course_stats_count) {

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Stats::$collection_name};

        $course_collection_name = self::$child_collection_name;

        $collection->updateOne([], [
            '$set' => [
                "{$course_collection_name}" => [
                    'total' => $course_stats_count,
                ],
            ],
        ]);
    }
}

class TrackStats extends Stats
{
    public static string $post_type = 'tps_track';
    public static string $child_collection_name = 'track';

    public static function get_count(): int
    {
        global $wpdb;

        $where_post_type = "'" . self::$post_type . "'";

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->posts}
                WHERE 
                    post_type = {$where_post_type}
                AND
                    post_status = 'publish'
                SQL
            )
        );

        return $count;
    }

    public static function update_cache(string $new_status, string $old_status, \WP_Post $post): void
    {
        if ($post->post_type !== Track::$post_type || $new_status !== 'publish' && $old_status !== 'publish') {

            return;
        }

        $track_stats_count = self::get_count();

        if (!$track_stats_count) {

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Stats::$collection_name};

        $track_collection_name = self::$child_collection_name;

        $collection->updateOne([], [
            '$set' => [
                "{$track_collection_name}" => [
                    'total' => $track_stats_count,
                ],
            ],
        ]);
    }

    public static function get_completed_count($mysql_date_from = "2021-10-01", $mysql_date_to = ""): int
    {
        global $wpdb;

        if(!$mysql_date_to) {
            $mysql_date_to = date("Y-m-d");
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->usermeta}
                WHERE 
                    meta_key LIKE %s
                    AND meta_value >= %s
                    AND meta_value <= %s
                SQL,
                Track::USER_META_TRACK_COMPLETED . "%",
                strtotime($mysql_date_from . " 00:00:00"),
                strtotime($mysql_date_to . " 23:59:59")
            )
        );

        return $count;
    }
}

class CertificateStats extends Stats
{
    public static string $child_collection_name = 'certificate';

    public static function get_count(): int
    {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->prefix}certificates
                SQL
            )
        );

        return $count;
    }

    public static function update_cache(int $certificate_id = 0): void
    {
        $certificate_stats_count = self::get_count();

        if (!$certificate_stats_count) {

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Stats::$collection_name};

        $certificate_collection_name = self::$child_collection_name;

        $collection->updateOne([], [
            '$set' => [
                "{$certificate_collection_name}" => [
                    'total' => $certificate_stats_count,
                ]
            ],
        ]);
    }

    public static function get_count_on_kursi($mysql_date_from = "2021-10-01", $mysql_date_to = ""): int
    {
        global $wpdb;

        if(!$mysql_date_to) {
            $mysql_date_to = date("Y-m-d");
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->prefix}certificates
                WHERE
                    course_type = 'course'
                    AND moment BETWEEN %s AND %s
                SQL,
                $mysql_date_from,
                $mysql_date_to
            )
        );

        return $count;
    }
}

class QuizStats extends Stats
{
    public static function get_completed_adaptests_count($mysql_date_from = "2021-10-01", $mysql_date_to = ""): int
    {
        global $wpdb;

        if(!$mysql_date_to) {
            $mysql_date_to = date("Y-m-d");
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    {$wpdb->usermeta}
                WHERE 
                    meta_key LIKE %s
                    AND meta_value >= %s
                    AND meta_value <= %s
                SQL,
                Adaptest::USER_META_ADAPTEST_COMPLETED . "%",
                strtotime($mysql_date_from . " 00:00:00"),
                strtotime($mysql_date_to . " 23:59:59")
            )
        );

        return $count;
    }
}

class VisitorSessionStats extends Stats
{
    public static function get_avarage_duration($mysql_date_from = "2021-10-01", $mysql_date_to = ""): int
    {
        global $wpdb;

        if(!$mysql_date_to) {
            $mysql_date_to = date("Y-m-d");
        }

        $table_name = VisitorSession::$table_name;

        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                <<<SQL
                SELECT *
                FROM
                    {$wpdb->prefix}{$table_name}
                WHERE
                    time_start BETWEEN CAST(%s AS DATE) AND CAST(%s AS DATE)
                SQL,
                $mysql_date_from,
                $mysql_date_to
            )
        );

        $durations = [];
        foreach($sessions as $session) {
            $durations[] = \strtotime($session->time_last_touch) - \strtotime($session->time_start);
        }

        $avg_session_duration = empty($durations) ? 0 : array_sum($durations) / count($durations);

        return $avg_session_duration;
    }
}
