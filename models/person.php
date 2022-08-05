<?php 

namespace Teplosocial\models;

class Person
{

    public static $post_type = 'person';
    public static $taxonomy = 'person_type';

    public static function get_posts($type)
    {
        return get_posts(array(
            'posts_per_page' => -1,
            'post_type' => self::$post_type,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'suppress_filters' => true,
            'nopaging' => true,
            'tax_query' => array(
                array(
                    'taxonomy' => self::$taxonomy,
                    'field' => 'slug',
                    'terms' => $type
                )
            )
        ));
    }

}
