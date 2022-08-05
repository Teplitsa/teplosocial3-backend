<?php

namespace Teplosocial\models;

class MongoCache {
    const STORAGE_NAME = 'tps_cache';

    public function update_page($post_data) {
        $mongo_client = MongoClient::getInstance();
        $collection = $mongo_client->{self::STORAGE_NAME}->pages;

        $slug = $post_data['slug'];
        $update_result = $collection->findOneAndUpdate(['slug' => $slug], ['$set' => $post_data]);

        if(!$update_result) {
            $collection->insertOne(['slug' => $slug], ['$set' => $post_data]);
        }
    }    
}
