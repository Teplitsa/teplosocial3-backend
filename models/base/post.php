<?php

namespace Teplosocial\models;

class Post
{
    public static function get($slug_or_id)
    {
        if(is_int($slug_or_id)) {
            return get_post($slug_or_id);
        }
        elseif($slug_or_id) {
            $args = array(
                'name'        => $slug_or_id,
                'post_type'   => static::$post_type,
                'post_status' => 'publish',
                'numberposts' => 1,
            );

            $posts = get_posts($args);
            return $posts ? $posts[0] : 0;
        }
        else {
            return null;
        }
    }

    public static function get_list($args = [])
    {
        $args_with_defaults = [
            'post_type'        => static::$post_type,
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'nopaging'         => true,
            'orderby'          => [
                'menu_order' => 'ASC',
                // 'date'      => 'ASC',
            ],
        ];

        if(isset($args['posts_per_page']) && $args['posts_per_page'] > -1) {
            unset($args_with_defaults['nopaging']);
        }

        // error_log("get list params:" . print_r($args_with_defaults, true));

//        $args_with_defaults += $args;
        $args_with_defaults = array_merge($args_with_defaults, $args);

        // error_log("get list params:" . print_r($args_with_defaults, true));.

        return get_posts($args_with_defaults);
    }
}