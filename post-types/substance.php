<?php

use \Teplosocial\models\Substance;

function tps_substance_setup(){
    
    register_taxonomy(Substance::$taxonomy, array(Substance::$post_type), array(
        'labels' => array(
            'name'                       => 'Типы',
            'singular_name'              => 'Тип',
            'menu_name'                  => 'Типы',
            'not_found'                  => __('Not found', 'tps'),
        ),
        'hierarchical'          => true,
        'show_ui'               => true,
        'show_in_nav_menus'     => true,
        'show_tagcloud'         => false,
        'show_admin_column'     => true,
        'query_var'             => true,
        'show_in_rest'          => true,
        'rest_base'             => Substance::$taxonomy,
        'rest_controller_class' => 'WP_REST_Terms_Controller',        
    ));
    
    register_post_type(Substance::$post_type, array(
        'labels' => array(
            'name'               => 'Контент',
            'singular_name'      => 'Контент',
            'menu_name'          => 'Контент',
            'name_admin_bar'     => 'Добавить контент',
            'add_new'            => 'Добавить новый',
            'add_new_item'       => 'Добавить контент',
            'new_item'           => 'Новый контент',
            'edit_item'          => 'Редактировать контент',
            'view_item'          => 'Просмотр контента',
            'all_items'          => 'Весь контент',
            'search_items'       => 'Искать контент',
            'parent_item_colon'  => 'Родительский контент:',
            'not_found'          => 'Контент не найдены',
            'not_found_in_trash' => 'В Корзине контент не найден'
        ),
        'public'                => true,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_nav_menus'     => false,
        'show_in_menu'          => true,
        'show_in_admin_bar'     => false,
        //'query_var'           => true,
        'capability_type'       => 'post',
        'has_archive'           => false,
        'hierarchical'          => false,
        'menu_position'         => 10,
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),  
        'taxonomies'            => array(Substance::$taxonomy),
		'show_in_rest'          => true,
		'rest_base'             => Substance::$post_type,
		'rest_controller_class' => 'WP_REST_Posts_Controller',
    ));
    
}
add_action('init', 'tps_substance_setup');
