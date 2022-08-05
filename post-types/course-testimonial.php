<?php

use \Teplosocial\models\{Course, CourseTestimonial};

function tps_course_testimonial_setup()
{

    \register_post_type(CourseTestimonial::$post_type, [
        'labels' => [
            'name'               => 'Отзыв на курс',
            'singular_name'      => 'Отзывы на курсы',
            'menu_name'          => 'Отзывы на курсы',
            'name_admin_bar'     => 'Добавить отзыв на курс',
            'add_new'            => 'Добавить новый',
            'add_new_item'       => 'Добавить отзыв на курс',
            'new_item'           => 'Новый отзыв на курс',
            'edit_item'          => 'Редактировать отзыв на курс',
            'view_item'          => 'Просмотр отзыва на курс',
            'all_items'          => 'Все отзывы на курсы',
            'search_items'       => 'Искать отзыв на курс',
            'parent_item_colon'  => 'Вышестоящий отзыв на курс:',
            'not_found'          => 'Отзывы на курсы не найдены',
            'not_found_in_trash' => 'В корзине отзывы на курсы не найдены',
            'featured_image'        => 'Фото автора',
            'set_featured_image'    => 'Загрузить фото автора',
            'remove_featured_image' => 'Удалить фото автора',
            'use_featured_image'    => 'Использовать как фото автора',
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
            'name' => CourseTestimonial::$connection_course_testimonial,
            'from' => Course::$post_type,
            'to' => CourseTestimonial::$post_type,
            'cardinality' => 'one-to-many',
            'admin_dropdown' => 'any',
            'title' => [
                'from' => \__('Связанные отзывы', 'tps'),
                'to'   => \__('Связанный курс', 'tps'),
            ],
            'from_labels' => [
                'singular_name' => \__('Курс', 'tps'),
                'search_items'  => \__('Поиск курсов', 'tps'),
                'not_found'     => \__('Курсы не найдены', 'tps'),
                'create'        => \__('Создать связь', 'tps'),
            ],
            'to_labels' => [
                'singular_name' => \__('Отзывы', 'tps'),
                'search_items'  => \__('Поиск отзывов', 'tps'),
                'not_found'     => \__('Отзывы не найдены', 'tps'),
                'create'        => \__('Создать связь', 'tps'),
            ],
            'admin_column' => false,
        ]);
    }
}

\add_action('init', 'tps_course_testimonial_setup');
