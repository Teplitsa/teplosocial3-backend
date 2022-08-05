<?php

namespace Teplosocial\models;

class Advantage extends Cacheable
{
    public static string $post_type = 'substance';
    public static ?string $taxonomy = 'substance_type';
    public static ?string $taxonomy_term = 'advantages';
    public static string $collection_name = 'advantages';

    public static function filter_fields(\WP_Post $advantage): array
    {
        ['ID' => $ID, 'post_title' => $post_title, 'post_excerpt' => $post_excerpt] = (array) $advantage;

        $thumbnail = \get_the_post_thumbnail_url($ID, Image::SIZE_ADVANTAGE);

        return [
            'externalId' => $ID,
            'title'      => $post_title,
            'excerpt'    => $post_excerpt,
            'thumbnail'  => $thumbnail,
        ];
    }
}
