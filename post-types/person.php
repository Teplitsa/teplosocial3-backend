<?php

use \Teplosocial\models\Person;

function tps_person_setup(){
    
    register_taxonomy(Person::$taxonomy, array(Person::$post_type), array(
        'labels' => array(
            'name'                       => 'Группы сотрудников',
            'singular_name'              => 'Группа сотрудников',
            'menu_name'                  => 'Группы',
            'edit_item'                  => 'Редактировать группу',
            'add_new_item'               => 'Добавить новую группу',
            'new_item_name'              => 'Название новой группы',
            'not_found'                  => __('Not found', 'tps'),
        ),
        'hierarchical'          => true,
        'show_ui'               => true,
        'show_in_nav_menus'     => true,
        'show_tagcloud'         => false,
        'show_admin_column'     => true,
        'query_var'             => true,
        'show_in_rest'          => true,
        'rest_base'             => Person::$taxonomy,
        'rest_controller_class' => 'WP_REST_Terms_Controller',        
    ));

    register_post_type('person', array(
        'labels' => array(
            'name'               => 'Сотрудники',
            'singular_name'      => 'Сотрудник',
            'menu_name'          => 'Команда',
            'name_admin_bar'     => 'Добавить сотрудника',
            'add_new'            => 'Добавить нового',
            'add_new_item'       => 'Добавить нового сотрудника',
            'new_item'           => 'Новый сотрудник',
            'edit_item'          => 'Редактировать сотрудника',
            'view_item'          => 'Просмотр сотрудника',
            'all_items'          => 'Все сотрудники',
            'search_items'       => 'Искать сотрудников',
            'not_found'          => 'Сотрудник не найден',
            'not_found_in_trash' => 'В Корзине сотрудники не найдены'
        ),
        'public'                => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_nav_menus'     => false,
        'show_in_menu'          => true,
        'show_in_admin_bar'     => true,
        //'query_var'           => true,
        'capability_type'       => 'post',
        'has_archive'           => false,
        'rewrite'               => false,
        'hierarchical'          => false,
        //'menu_position'       => 5,
        'menu_icon'             => 'dashicons-businessman',
        'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes',),
        'taxonomies'            => array('person_type'),
        'show_in_rest'          => true,
        'rest_base'             => Person::$post_type,
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    ));
    
}
add_action('init', 'tps_person_setup');
