<?php

namespace Teplosocial\API;

class UserRestApi
{
    public static function register_user_fields($fields)
    {
        foreach ($fields as $field_name => $field)
        {
            register_rest_field('user', $field_name, [
                'type' => $field['type'],
                'get_callback' => function($response_data, $property_name, $request) use ($field) {
                    $user = get_user_by('ID', $response_data['id']);
                    return $field['resolve']($user, [], null);
                }, 
                'context' => ['view', 'edit', 'embed'],
                // 'schema' => null,
            ]);
        }
    }

    public static function fix_seo_integration($server)
    {
        global $wp_rest_additional_fields;
        // remove yoast fields for portfolio_action
        $wp_rest_additional_fields[ 'user' ][ 'yoast_head' ]['context'] = [ 'edit', 'embed' ];
    }
}
