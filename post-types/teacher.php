<?php

use \Teplosocial\models\{Course, Teacher};

function tps_teacher_setup()
{

    \register_post_type(Teacher::$post_type, [
        'labels' => [
            'name'               => 'Преподаватель',
            'singular_name'      => 'Преподаватели',
            'menu_name'          => 'Преподаватели',
            'name_admin_bar'     => 'Добавить преподавателя',
            'add_new'            => 'Добавить нового',
            'add_new_item'       => 'Добавить преподавателя',
            'new_item'           => 'Новый преподаватель',
            'edit_item'          => 'Редактировать преподавателя',
            'view_item'          => 'Просмотр преподавателя',
            'all_items'          => 'Все преподаватели',
            'search_items'       => 'Искать преподавателя',
            'parent_item_colon'  => 'Старший преподаватель:',
            'not_found'          => 'Преподаватели не найдены',
            'not_found_in_trash' => 'В корзине преподаватели не найдены',
            'featured_image'        => 'Фото преподавателя',
            'set_featured_image'    => 'Загрузить фото преподавателя',
            'remove_featured_image' => 'Удалить фото преподавателя',
            'use_featured_image'    => 'Использовать как фото преподавателя',
        ],
        'public'                => true,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'show_ui'               => true,
        'show_in_nav_menus'     => false,
        'show_in_menu'          => true,
        'show_in_admin_bar'     => false,
        'capability_type'       => 'post',
        'has_archive'           => false,
        'hierarchical'          => false,
        'menu_position'         => 10,
        'supports'              => ['title', 'editor', 'thumbnail'],
        'taxonomies'            => [],
        'show_in_rest'          => false,
    ]);

    if (function_exists('p2p_register_connection_type')) {
        \p2p_register_connection_type([
            'name' => Teacher::$connection_course_teacher,
            'from' => Course::$post_type,
            'to' => Teacher::$post_type,
            'cardinality' => 'many-to-many',
            'admin_dropdown' => 'any',
            'title' => [
                'from' => \__('Связанные преподаватели', 'tps'),
                'to'   => \__('Связанные курсы', 'tps'),
            ],
            'from_labels' => [
                'singular_name' => \__('Курс', 'tps'),
                'search_items'  => \__('Поиск курсов', 'tps'),
                'not_found'     => \__('Курсы не найдены', 'tps'),
                'create'        => \__('Создать связь', 'tps'),
            ],
            'to_labels' => [
                'singular_name' => \__('Преподаватель', 'tps'),
                'search_items'  => \__('Поиск преподавателей', 'tps'),
                'not_found'     => \__('Преподаватели не найдены', 'tps'),
                'create'        => \__('Создать связь', 'tps'),
            ],
            'admin_column' => false,
        ]);
    }
}

\add_action('init', 'tps_teacher_setup');
