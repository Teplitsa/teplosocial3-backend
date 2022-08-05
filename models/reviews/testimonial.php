<?php

namespace Teplosocial\models;

class Testimonial extends Cacheable
{
    public static string $post_type = 'substance';
    public static ?string $taxonomy = 'substance_type';
    public static ?string $taxonomy_term = 'testimonials';
    public static string $collection_name = 'testimonials';

    public static function filter_fields(\WP_Post $testimonial): array
    {
        ['ID' => $ID, 'post_title' => $post_title, 'post_excerpt' => $post_excerpt, 'post_content' => $post_content] = (array) $testimonial;

        $thumbnail = \get_the_post_thumbnail_url($ID, 'post-thumbnail');

        return [
            'externalId' => $ID,
            'title'      => $post_title,
            'excerpt'    => $post_excerpt,
            'content'    => $post_content,
            'thumbnail'  => $thumbnail,
        ];
    }
}
