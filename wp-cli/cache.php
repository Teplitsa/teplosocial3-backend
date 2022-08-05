<?php

namespace Teplosocial\cli;

use Teplosocial\models\{MongoClient, MongoCache, Stats, UserStats, CourseStats, TrackStats, CertificateStats, Advantage, Testimonial, CourseCache, TrackCache, UserProgress, Certificate, CourseTag};


if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Manage mongodb cache
 */

class Cache
{
    public function delete_tags_on_main($args, $assoc_args)
    {
        $mongo_client = MongoClient::getInstance();
        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->tags_on_main;
        $updateCacheResult = $collection->deletemany([]);
    }

    public function update_stats(): void
    {
        $user_stats_count = UserStats::get_count();

        if (!$user_stats_count) {

            \WP_CLI::warning(__('No users found.', 'tps'));

            return;
        }

        $course_stats_count = CourseStats::get_count();

        if (!$course_stats_count) {

            \WP_CLI::warning(__('No courses found.', 'tps'));

            return;
        }

        $track_stats_count = TrackStats::get_count();

        if (!$track_stats_count) {

            \WP_CLI::warning(__('No tracks found.', 'tps'));

            return;
        }

        $certificate_stats_count = CertificateStats::get_count();

        if (!$certificate_stats_count) {

            \WP_CLI::warning(__('No certificates found.', 'tps'));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Stats::$collection_name};

        $collection->drop();

        $user_collection_name = UserStats::$child_collection_name;
        $course_collection_name = CourseStats::$child_collection_name;
        $track_collection_name = TrackStats::$child_collection_name;
        $certificate_collection_name = CertificateStats::$child_collection_name;

        $updateCacheResult = $collection->insertOne([
            "{$user_collection_name}" => [
                'total' => $user_stats_count
            ],
            "{$course_collection_name}" => [
                'total' => $course_stats_count
            ],
            "{$track_collection_name}" => [
                'total' => $track_stats_count
            ],
            "{$certificate_collection_name}" => [
                'total' => $certificate_stats_count
            ],
        ]);

        if (!$updateCacheResult->getInsertedCount()) {
            \WP_CLI::error(__('Failted to update stats.', 'tps'));
        }

        \WP_CLI::success(__('Stats successfully updated.', 'tps'));
    }

    public function update_advantage_list(): void
    {
        $advantage_list = Advantage::get_list([
            'tax_query' => [
                [
                    'taxonomy' => Advantage::$taxonomy,
                    'field'    => 'slug',
                    'terms'    => Advantage::$taxonomy_term,
                ],
            ],
        ]);

        if (!$advantage_list) {

            \WP_CLI::warning(sprintf(__('No %s found.', 'tps'), Advantage::$post_type));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Advantage::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($advantage_list);

        \WP_CLI::success(sprintf(__('%d %s(s) successfully updated.', 'tps'), $updateCacheResult->getInsertedCount(), Advantage::$post_type));
    }

    public function update_testimonial_list(): void
    {
        $testimonial_list = Testimonial::get_list([
            'tax_query' => [
                [
                    'taxonomy' => Testimonial::$taxonomy,
                    'field'    => 'slug',
                    'terms'    => Testimonial::$taxonomy_term,
                ],
            ],
        ]);

        if (!$testimonial_list) {

            \WP_CLI::warning(sprintf(__('No %s found.', 'tps'), Testimonial::$post_type));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Testimonial::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($testimonial_list);

        \WP_CLI::success(sprintf(__('%d %s(s) successfully updated.', 'tps'), $updateCacheResult->getInsertedCount(), Testimonial::$post_type));
    }

    public function update_user_progress_list(): void
    {
        $progress = UserProgress::get_list();

        if (!$progress) {

            \WP_CLI::warning(__('No user progress data found.', 'tps'));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{UserProgress::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($progress);

        \WP_CLI::success(sprintf(__('%d user progress items successfully updated.', 'tps'), $updateCacheResult->getInsertedCount()));
    }

    public function update_certificate_list(): void
    {
        $certificate_list = Certificate::get_list([]);

        if (!$certificate_list) {

            \WP_CLI::warning(__('No certificate found.', 'tps'));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{Certificate::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($certificate_list);

        \WP_CLI::success(sprintf(__('%d certificates successfully updated.', 'tps'), $updateCacheResult->getInsertedCount()));
    }

    public function update_course_list(): void
    {
        $course_list = CourseCache::get_list();

        if (!$course_list) {

            \WP_CLI::warning(__('No courses found.', 'tps'));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{CourseCache::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($course_list);

        $collection->createIndex(["title" => "text", "teaser" => "text"], ["weights" => ["title" => 10, "teaser" => 5], "default_language" => "russian"]);

        \WP_CLI::success(sprintf(__('%d course(s) successfully updated.', 'tps'), $updateCacheResult->getInsertedCount()));
    }

    public function update_track_list(): void
    {
        $track_list = TrackCache::get_list();

        if (!$track_list) {

            \WP_CLI::warning(__('No tracks found.', 'tps'));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{TrackCache::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($track_list);

        $collection->createIndex(["title" => "text", "teaser" => "text"], ["weights" => ["title" => 10, "teaser" => 5], "default_language" => "russian"]);

        \WP_CLI::success(sprintf(__('%d track(s) successfully updated.', 'tps'), $updateCacheResult->getInsertedCount()));
    }

    public function update_tag_list(): void
    {
        $tag_list = CourseTag::get_list();

        if (!$tag_list) {

            \WP_CLI::warning(__('No tags found.', 'tps'));

            return;
        }

        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{CourseTag::$collection_name};

        $collection->drop();

        $updateCacheResult = $collection->insertMany($tag_list);

        \WP_CLI::success(sprintf(__('%d tag(s) successfully updated.', 'tps'), $updateCacheResult->getInsertedCount()));
    }
}

\WP_CLI::add_command('tps_cache', '\Teplosocial\cli\Cache');
