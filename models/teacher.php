<?php

namespace Teplosocial\models;

class Teacher
{
    public static $post_type = 'teacher';
    public static $connection_course_teacher = 'course-teacher';

    const META_AUTHORITY = 'tps_teacher_authority';

    public static function get_list(int $course_id, int $limit = 0): ?array
    {
        $args = [
            'post_type'      => self::$post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'nopaging'       => true,
            'meta_key'       => self::META_AUTHORITY,
            'orderby'        => 'meta_value_num',
        ];

        if (!empty($course_id)) {
            $args = array_merge(
                $args,
                [
                    'connected_type' => self::$connection_course_teacher,
                    'connected_from' => $course_id,
                ]
            );
        }

        $list = \get_posts($args);

        $list = array_map(fn ($item) => [
            'id'     => $item->ID,
            'name'   => $item->post_title,
            'resume' => $item->post_content,
            'avatar' => ($avatar = \get_the_post_thumbnail_url($item->ID, Image::SIZE_COURSE_TEACHER_AVATAR)) ? $avatar : ''
        ], $list);

        return empty($list) ? null : $list;
    }

    public static function admin_customize_title(string $title): string
    {
        $screen = \get_current_screen();

        if ($screen->post_type === self::$post_type) {
            $title = 'Имя преподавателя';
        }

        return $title;
    }

    public static function admin_customize_media_button(): void
    {
        $screen = \get_current_screen();

        if ($screen->post_type === self::$post_type) {
            \remove_action('media_buttons', 'media_buttons');
        }
    }

    public static function admin_customize_editor(bool $default): bool
    {
        if (get_post_type() === self::$post_type) {
            return false;
        }

        return $default;
    }

    public static function admin_init_meta(): void
    {
        $cmb = \new_cmb2_box([
            'id'            => 'teacher_metabox',
            'title'         => \__('Характеристики', 'tps'),
            'object_types'  => [self::$post_type],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $cmb->add_field([
            'name' => \__('Авторитетность (вес)', 'tps'),
            'id'   => self::META_AUTHORITY,
            'type' => 'text',
            'default' => 0,
            'attributes' => [
                'type' => 'number',
            ],
        ]);
    }
}
