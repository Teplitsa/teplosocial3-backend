<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\MongoCache;

function tps_update_post_in_mongo( $post_ID, \WP_Post $post, $is_update ) {
    $request = new \WP_REST_Request( $_SERVER['REQUEST_METHOD'], "/" );
    $rc = new \WP_REST_Posts_Controller($post->post_type);

    $data = $rc->prepare_item_for_response( $post, $request );

    $cache = new MongoCache();
    $cache->update_page($data->data);
}
add_action( 'save_post_page', '\Teplosocial\hooks\tps_update_post_in_mongo', 10, 3 );