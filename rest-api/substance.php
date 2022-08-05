<?php

use \Teplosocial\models\Substance;

function tps_rest_filter_substance_query( $args, $request ) { 
    $params = $request->get_params(); 

    if(!empty($params['filter'])) {

        if(isset($params['filter'][Substance::$taxonomy])){
            $args['tax_query'] = array(
                array(
                    'taxonomy' => Substance::$taxonomy,
                    'field' => 'slug',
                    'terms' => explode(',', $params['filter'][Substance::$taxonomy])
                )
            );
        }

    }

    return $args; 
}   
add_filter( "rest_" . Substance::$post_type . "_query", 'tps_rest_filter_substance_query', 10, 2 ); 