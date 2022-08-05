<?php

namespace Teplosocial\API;

class PostRestApi
{
    public static function register_post_type_fields($post_type, $fields)
    {
        foreach ($fields as $field_name => $field)
        {    
            register_rest_field( $post_type, $field_name, [ 
                'get_callback' => function($response_data, $property_name, $request) use ($field) {
                    $post = get_post($response_data['id']);
                    return $field['resolve']($post, [], null);
                }, 
                'context' => [ 'view', 'edit', 'embed' ],
                'schema' => null,
            ] );
        }
    }

    public static function fix_post_type_seo_integration($server, $post_type)
    {
        global $wp_rest_additional_fields;
        // remove yoast fields for portfolio_action
        $wp_rest_additional_fields[ $post_type ][ 'yoast_head' ]['context'] = [ 'edit', 'embed' ];
    }
}
