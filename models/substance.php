<?php 

namespace Teplosocial\models;

class Substance {
    
    public static $post_type = 'substance';
    public static $taxonomy = 'substance_type';
    public static $news_post_type = 'news';
    
    public static $TYPE_ADVANTAGES = 'advantages';
    public static $TYPE_TESTIMONIALS = 'testimonials';
    public static $TYPE_PRESS_ABOUT_US = 'press-about-us';
    public static $TYPE_PARTNERS = 'partners';
    public static $TYPE_PROJECTS = 'projects';
    
    public static $SLUG_ABOUT_TEPLITSA = 'about-teplitsa';
    
    
    public static function get_posts($type, $limit = 0) {
        return get_posts(array(
            'posts_per_page'   => $limit,
            'post_type'        => self::$post_type,
            'post_status'      => 'publish',
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

    public static function get_news($limit = 0) {
        return get_posts(array(
            'posts_per_page'   => $limit,
            'post_type'        => self::$news_post_type,
            'post_status'      => 'publish',
            'suppress_filters' => true,
            'nopaging' => true,
        ));
    }
    
}