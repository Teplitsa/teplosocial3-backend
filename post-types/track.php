<?php

use \Teplosocial\models\Track;
use \Teplosocial\models\Course;

function tps_track_setup(){

    register_post_type( Track::$post_type, array(
        'labels' => array(
            'name'               => 'Треки',
            'singular_name'      => 'Трек',
            'menu_name'          => 'Треки',
            'name_admin_bar'     => 'Добавить трек',
            'add_new'            => 'Добавить новый',
            'add_new_item'       => 'Добавить новый трек',
            'new_item'           => 'Новый трек',
            'edit_item'          => 'Редактировать трек',
            'view_item'          => 'Просмотр трека',
            'all_items'          => 'Все треки',
            'search_items'       => 'Искать треки',
            'not_found'          => 'Треки не найдены',
            'not_found_in_trash' => 'В Корзине треки не найдены'
        ),
        'public'                => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_nav_menus'     => false,
        'show_in_menu'          => false,
        'show_in_admin_bar'     => true,
        'query_var'           => true,
        'capability_type'       => 'post',
        'has_archive'           => false,
        'rewrite'               => false,
        'hierarchical'          => false,
        'menu_position'       => 5,
        //'menu_icon'             => 'dashicons-businessman',
        'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
        'taxonomies'            => array('post_tag'),
        'show_in_rest'          => true,
        'rest_base'             => Track::$post_type,
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    ));

    if ( function_exists('p2p_register_connection_type') ) {
        p2p_register_connection_type(array(
            'name' => Course::$connection_track_course,
            'from' => 'tps_track',
            'to' => 'tps_course',
            'cardinality' => 'many-to-many',
            'admin_dropdown' => 'any',
            'title' => array(
                'from' => __('Связанные курсы', 'tps'),
                'to' => __('Связанные треки', 'tps'),
            ),
            'from_labels' => array(
                'singular_name' => __('Трек', 'tps'),
                'search_items' => __('Поиск треков', 'tps'),
                'not_found' => __('Треки не найдены', 'tps'),
                'create' => __('Создать связь', 'tps'),
            ),
            'to_labels' => array(
                'singular_name' => __('Курс', 'tps'),
                'search_items' => __('Поиск курсов', 'tps'),
                'not_found' => __('Курсы не найдены', 'tps'),
                'create' => __('Создать связь', 'tps'),
            ),
            'admin_column' => false,
        ));
    }
    
}

add_action('init', 'tps_track_setup');

