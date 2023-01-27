<?php 

namespace Teplosocial\models;

class Track {
    
    public static $post_type = 'tps_track';

    public static function get($limit = 0) {
        return get_posts(array(
            'posts_per_page'   => $limit,
            'post_type'        => self::$post_type,
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'nopaging' => true,
        ));
    }
}
