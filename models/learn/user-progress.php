<?php

namespace Teplosocial\models;

use \Teplosocial\models\StudentLearning;

class UserProgress
{
    public static string $collection_name = 'user_progress';
    const USER_META_LD_COURSE_PROGRESS = '_sfwd-course_progress';
    const USER_META_DUMMY = 'tps_lesson_completed_';

    public static function complete_lesson(array $lesson_data): void
    {
        $meta_key = self::USER_META_DUMMY;

        self::reset_item_cache($lesson_data['user']->ID, $meta_key);
    }

    private static function reset_item_cache(int $user_id, string $meta_key): void
    {
        $meta_key_started_course = Course::USER_META_COURSE_STARTED;
        $meta_key_completed_course = Course::USER_META_COURSE_COMPLETED;
        $meta_key_started_module = Module::USER_META_MODULE_STARTED;
        $meta_key_completed_module = Module::USER_META_MODULE_COMPLETED;
        $meta_key_started_track = Track::USER_META_TRACK_STARTED;
        $meta_key_completed_track = Track::USER_META_TRACK_COMPLETED;
        $meta_key_dummy = self::USER_META_DUMMY;

        $mongo_client = MongoClient::getInstance();
        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{static::$collection_name};

        foreach ([$meta_key_started_course, $meta_key_completed_course, $meta_key_started_module, $meta_key_completed_module, $meta_key_started_track, $meta_key_completed_track, $meta_key_dummy] as $key) {
            if (strpos($meta_key, $key) !== false) {
                $item = self::get_item($user_id);

                if (empty($item['startedCourseIds']) && empty($item['completedCourseIds'])) {
                    $deleteCacheResult = $collection->findOneAndDelete(['userId' => $user_id]);

                    if (is_null($deleteCacheResult)) {

                        trigger_error(\__('No user progress item found to delete.', 'tps'), E_USER_WARNING);
                    }
                } else {
                    $updateCacheResult = $collection->findOneAndUpdate(['userId' => $user_id], ['$set' => $item]);

                    // No item found to update.

                    if (is_null($updateCacheResult)) {

                        $updateCacheResult = $collection->insertOne($item);
                    }
                }
            }
        }
    }

    public static function update_item_cache(int $meta_id, int $user_id, string $meta_key): void
    {
        self::reset_item_cache($user_id, $meta_key);
    }

    public static function delete_item_cache(array $meta_ids, int $user_id, string $meta_key): void
    {
        self::reset_item_cache($user_id, $meta_key);
    }

    public static function get_study_course_id(int $user_id): ?string
    {
        global $wpdb;

        $meta_key = Course::USER_META_COURSE_STARTED;
        $meta_key_completed = Course::USER_META_COURSE_COMPLETED;

        $started_course_id_list = $wpdb->get_col(
            <<<SQL
                SELECT
                    REPLACE(meta_key,'{$meta_key}','') AS course_id
                FROM
                    {$wpdb->usermeta}
                WHERE
                    user_id = $user_id
                AND
                    meta_key RLIKE '^{$meta_key}[0-9]+$'
                ORDER BY
                    umeta_id
                DESC
            SQL
        );

        $completed_course_id_list = $wpdb->get_col(
            <<<SQL
                SELECT
                    REPLACE(meta_key,'{$meta_key_completed}','') AS course_id
                FROM
                    {$wpdb->usermeta}
                WHERE
                    user_id = $user_id
                AND
                    meta_key RLIKE '^{$meta_key_completed}[0-9]+$'
                ORDER BY
                    umeta_id
                DESC
            SQL
        );

        $not_completed_course_id_list = array_diff($started_course_id_list, $completed_course_id_list);

        return !empty($not_completed_course_id_list) ? $not_completed_course_id_list[0] : null;
    }

    public static function get_completed_course_id(int $user_id): ?string
    {
        global $wpdb;

        $meta_key = Course::USER_META_COURSE_COMPLETED;

        $course_id = $wpdb->get_var(
            <<<SQL
                SELECT
                    REPLACE(meta_key,'{$meta_key}','') AS course_id
                FROM
                    {$wpdb->usermeta}
                WHERE
                    user_id = $user_id
                AND
                    meta_key RLIKE '^{$meta_key}[0-9]+$'
                ORDER BY
                    umeta_id
                DESC
            SQL
        );

        return $course_id;
    }

    public static function get_item(int $user_id): ?array
    {
        $progress = self::get_list($user_id);

        return empty($progress) ? null : array_shift($progress);
    }

    public static function get_list(int $user_id = 0): ?array
    {
        global $wpdb;

        $meta_key_started = Course::USER_META_COURSE_STARTED;
        $meta_key_completed = Course::USER_META_COURSE_COMPLETED;
        $meta_key_started_track = Track::USER_META_TRACK_STARTED;
        $meta_key_completed_track = Track::USER_META_TRACK_COMPLETED;

        $where_clause = $user_id > 0 ? "AND user_id = {$user_id}" : "";

        $progress = $wpdb->get_results(
            <<<SQL
                SELECT
                    started_courses.user_id AS userId,
                    started_courses.course_ids AS startedCourseIds,
                    completed_courses.course_ids AS completedCourseIds,
                    started_tracks.track_ids AS startedTrackIds,
                    completed_tracks.track_ids AS completedTrackIds
                FROM
                (SELECT
                    user_id,
                    GROUP_CONCAT(REPLACE(meta_key,'{$meta_key_started}','')) AS course_ids
                FROM
                    {$wpdb->usermeta}
                WHERE
                    meta_key RLIKE '^{$meta_key_started}[0-9]+$'
                    {$where_clause}
                GROUP BY
                    user_id
                ) AS started_courses
                LEFT JOIN
                    (SELECT
                        user_id,
                        GROUP_CONCAT(REPLACE(meta_key,'{$meta_key_completed}',''))  AS course_ids
                    FROM
                        {$wpdb->usermeta}
                    WHERE
                        meta_key RLIKE '^{$meta_key_completed}[0-9]+$'
                        {$where_clause}
                    GROUP BY
                        user_id
                    ) AS completed_courses
                ON
                    started_courses.user_id = completed_courses.user_id
                LEFT JOIN
                    (SELECT
                        user_id,
                        GROUP_CONCAT(REPLACE(meta_key,'{$meta_key_started_track}',''))  AS track_ids
                    FROM
                        {$wpdb->usermeta}
                    WHERE
                        meta_key RLIKE '^{$meta_key_started_track}[0-9]+$'
                        {$where_clause}
                    GROUP BY
                        user_id
                    ) AS started_tracks
                ON
                    started_courses.user_id = started_tracks.user_id
                LEFT JOIN
                    (SELECT
                        user_id,
                        GROUP_CONCAT(REPLACE(meta_key,'{$meta_key_completed_track}',''))  AS track_ids
                    FROM
                        {$wpdb->usermeta}
                    WHERE
                        meta_key RLIKE '^{$meta_key_completed_track}[0-9]+$'
                        {$where_clause}
                    GROUP BY
                        user_id
                    ) AS completed_tracks
                ON
                    started_courses.user_id = completed_tracks.user_id
            SQL,
            \ARRAY_A
        );

        if (!empty($progress)) {
            foreach ($progress as &$item) {
                $item['userId'] = (int) $item['userId'];

                $item['startedCourseIds'] = empty($item['startedCourseIds']) ? null : array_map(fn ($course_id) => (int) $course_id, explode(",", $item['startedCourseIds']));

                $item['completedCourseIds'] = empty($item['completedCourseIds']) ? null : array_map(fn ($course_id) => (int) $course_id, explode(",", $item['completedCourseIds']));

                $item['startedTrackIds'] = empty($item['startedTrackIds']) ? null : array_map(fn ($track_id) => (int) $track_id, explode(",", $item['startedTrackIds']));

                $item['completedTrackIds'] = empty($item['completedTrackIds']) ? null : array_map(fn ($track_id) => (int) $track_id, explode(",", $item['completedTrackIds']));

                $in_progress_course_ids = array_diff($item['startedCourseIds'] ?? [], $item['completedCourseIds'] ?? []);

                $in_progress_track_ids = array_diff($item['startedTrackIds'] ?? [], $item['completedTrackIds'] ?? []);

                if (empty($item['startedCourseIds'])) {
                    $item['progressPerCourse'] = null;
                    $item['recentStudiedCourseId'] = null;
                } else {
                    // Using $dummy is a guarantee of integer-based array as a result in any case
                    $item['progressPerCourse'] = array_map(function ($course_id, $dummy) use ($item) {
                        $completed_blocks = Course::count_completed_blocks($course_id, $item['userId']);

                        if ($completed_blocks === 0) {
                            $percentage = "0%";
                        } else {
                            $block_count = Course::count_blocks($course_id);
                            $percentage = number_format((float) ($completed_blocks / $block_count * 100), 2, ".", "") . "%";
                        }

                        return [$course_id, $percentage];
                    }, $in_progress_course_ids, []);

                    $courses_last_action_time = StudentLearning::get_courses_action_time($item['userId']);
                    // error_log("courses_last_action_time: " . print_r($courses_last_action_time, true));
                    $item['lastActionTimePerCourse'] = array_map(function ($course_id, $dummy) use ($courses_last_action_time) {
                        // error_log("course_id: " . print_r($course_id, true));
                        $lastActionTime = $courses_last_action_time[$course_id] ?? 0;
                        return [$course_id, $lastActionTime];
                    }, $in_progress_course_ids, []);
                    // error_log("lastActionTimePerCourse: " . print_r($item['lastActionTimePerCourse'], true));

                    $item['recentStudiedCourseId'] = null;
                    if(empty($item['completedCourseIds'])) {
                        $item['recentStudiedCourseId'] = end($item['startedCourseIds']);
                    }
                    else {
                        foreach(array_reverse($item['startedCourseIds']) as $startedCourseIds) {
                            if(!\in_array($startedCourseIds, $item['completedCourseIds'])) {
                                $item['recentStudiedCourseId'] = $startedCourseIds;
                                break;
                            }
                        }
                    }
                }

                if (!empty($item['completedCourseIds'])) {
                    $item['recentCompletedCourseId'] = end($item['completedCourseIds']);
                } else {
                    $item['recentCompletedCourseId'] = null;
                }

                if (empty($item['startedTrackIds'])) {
                    $item['progressPerTrack'] = null;
                } else {
                    // Using $dummy is a guarantee of integer-based array as a result in any case
                    $item['progressPerTrack'] = array_map(function ($track_id, $dummy) use ($item) {
                        $completed_blocks = Track::count_completed_blocks($track_id, $item['userId']);

                        if ($completed_blocks === 0) {
                            $percentage = "0%";
                        } else {
                            $block_count = Track::count_blocks($track_id);
                            $percentage = round($completed_blocks / $block_count * 100) . "%";

                            $percentage = number_format((float) ($completed_blocks / $block_count * 100), 2, ".", "") . "%";
                        }

                        return [$track_id, $percentage];
                    }, $in_progress_track_ids, []);
                }

                if (!empty($item['startedCourseIds'])) {
                    $module = Course::get_first_uncompleted_module(end($item['startedCourseIds']), $item['userId']);

                    $next_block = $module ? Module::get_next_uncompleted_block_by_user($module->ID, $item['userId']) : null;

                    if (!empty($next_block)) {
                        $item['nextBlockSlug'] = $next_block->post_name;
                        $item['nextBlockTitle'] = $next_block->post_title;
                    }
                }
            }
        }

        return $progress;
    }
}
