<?php

namespace Teplosocial\models;

class CourseTag
{
    public static ?string $taxonomy = 'post_tag';
    public static string $collection_name = 'course_tags';

    public static function filter_fields(\WP_Term $tag): array
    {
        ['term_id' => $id, 'slug' => $slug, 'name' => $name, 'count' => $count] = (array) $tag;

        return [
            'externalId' => $id,
            'slug'       => $slug,
            'name'       => $name,
            'count'      => $count
        ];
    }
    public static function check_validity(array $item): ?array
    {
        return $item["count"] > 0 ? $item : null;
    }
    public static function update_item_cache(int $item_id): void
    {
        $mongo_client = MongoClient::getInstance();
        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{self::$collection_name};

        $term = self::get_item($item_id);

        try {
            if (is_null(self::check_validity($term))) {
                throw new \Exception(sprintf(\__('No valid %s found.', 'tps'), self::$taxonomy));
            }
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return;
        }

        $updateCacheResult = $collection->findOneAndUpdate(['externalId' => $item_id], ['$set' => $term]);

        // No item found to update.

        if (is_null($updateCacheResult)) {

            $updateCacheResult = $collection->insertOne($term);
        }
    }
    public static function delete_item_cache(int $item_id): void
    {
        $mongo_client = MongoClient::getInstance();

        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{self::$collection_name};

        $deleteCacheResult = $collection->findOneAndDelete(['externalId' => $item_id]);

        // No item found to delete.

        if (is_null($deleteCacheResult)) {

            trigger_error(sprintf(__('No %s found to delete.', 'tps'), self::$taxonomy), E_USER_WARNING);
        }
    }

    public static function get_item(int $term_id): ?array
    {
        $term = \get_term($term_id, self::$taxonomy);

        if (\is_wp_error($term) || is_null($term)) {
            return null;
        }

        return self::filter_fields($term);
    }

    public static function get_list(): ?array
    {
        $tags = \get_terms(array(
            'taxonomy'   => self::$taxonomy,
            'hide_empty' => true,
        ));

        if (\is_wp_error($tags)) {
            return null;
        }

        foreach ($tags as &$tag) {
            $tag = self::filter_fields($tag);
        }

        return $tags;
    }
}
