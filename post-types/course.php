<?php

use \Teplosocial\models\Course;
use \Teplosocial\models\Module;

function tps_course_setup(){

    register_post_type( Course::$post_type, array(
        'labels' => array(
            'name'               => 'Курсы',
            'singular_name'      => 'Курс',
            'menu_name'          => 'Курсы',
            'name_admin_bar'     => 'Добавить курс',
            'add_new'            => 'Добавить новый',
            'add_new_item'       => 'Добавить новый курс',
            'new_item'           => 'Новый курс',
            'edit_item'          => 'Редактировать курс',
            'view_item'          => 'Просмотр курса',
            'all_items'          => 'Все курсы',
            'search_items'       => 'Искать курсы',
            'not_found'          => 'Курсы не найдены',
            'not_found_in_trash' => 'В Корзине курсы не найдены'
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
        'rewrite'               => true,
        'hierarchical'          => false,
        'menu_position'       => 5,
        //'menu_icon'             => 'dashicons-businessman',
        'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes'),
        'taxonomies'            => array('post_tag'),
        'show_in_rest'          => true,
        'rest_base'             => Course::$post_type,
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    ));

    if ( function_exists('p2p_register_connection_type') ) {
        p2p_register_connection_type(array(
            'name' => Module::$connection_course_module,
            'from' => Course::$post_type,
            'to' => Module::$post_type,
            'cardinality' => 'many-to-many',
            'admin_dropdown' => 'any',
            'title' => array(
                'from' => __('Связанные модули', 'tps'),
                'to' => __('Связанные курсы', 'tps'),
            ),
            'from_labels' => array(
                'singular_name' => __('Курс', 'tps'),
                'search_items' => __('Поиск курсов', 'tps'),
                'not_found' => __('Курсы не найдены', 'tps'),
                'create' => __('Создать связь', 'tps'),
            ),
            'to_labels' => array(
                'singular_name' => __('Модуль', 'tps'),
                'search_items' => __('Поиск модулей', 'tps'),
                'not_found' => __('Модули не найдены', 'tps'),
                'create' => __('Создать связь', 'tps'),
            ),
            'admin_column' => false,
        ));
    }
    
}

add_action('init', 'tps_course_setup');

