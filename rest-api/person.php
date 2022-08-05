<?php

namespace Teplosocial\API;

use \Teplosocial\models\Person;
use \Teplosocial\models\Image;

class PersonRestApi extends PostRestApi
{
    function post_query( $args, $request ) {
        $params = $request->get_params(); 
    
        if(!empty($params['filter'])) {
    
            if(isset($params['filter'][Person::$taxonomy])){
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => Person::$taxonomy,
                        'field' => 'slug',
                        'terms' => explode(',', $params['filter'][Person::$taxonomy])
                    )
                );
            }
    
        }
    
        return $args; 
    }
    
    public static function register_fields($server)
    {
        $fields = [
            'avatar' => [
                'type'        => 'String',
                'resolve'     => function( $post ) {
                    $image_id = get_post_thumbnail_id($post->ID);
                    return wp_get_attachment_image_url($image_id, Image::SIZE_AVATAR);
                },
            ],
        ];

        self::register_post_type_fields(Person::$post_type, $fields);
    }

    public static function fix_seo_integration($server) {
        self::fix_post_type_seo_integration($server, Person::$post_type);
    }
}

add_filter( "rest_" . Person::$post_type . "_query", '\Teplosocial\API\PersonRestApi::post_query', 10, 2 );
add_action( 'rest_api_init', '\Teplosocial\API\PersonRestApi::register_fields', 11 );