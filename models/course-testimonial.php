<?php

namespace Teplosocial\models;

class CourseTestimonial
{
    const META_REVIEWER_NAME = 'tps_reviewer_name';
    const META_REVIEWER_POSITION = 'tps_reviewer_position';

    public static $post_type = 'course_testimonial';
    public static $connection_course_testimonial = 'course-testimonial';

    public static function get_list(int $course_id, int $limit = 0): ?array
    {
        $args = [
            'post_type'      => self::$post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'nopaging'       => true,
        ];

        if (!empty($course_id)) {
            $args = array_merge(
                $args,
                [
                    'connected_type' => self::$connection_course_testimonial,
                    'connected_from' => $course_id,
                ]
            );
        }

        $list = \get_posts($args);

        $list = array_map(fn ($item) => [
            'id'       => $item->ID,
            'name'     => ($name = \get_post_meta($item->ID, self::META_REVIEWER_NAME, true)) ? $name : '',
            'position' => ($position = \get_post_meta($item->ID, self::META_REVIEWER_POSITION, true)) ? $position : '',
            'text'     => $item->post_content,
            'avatar'   => ($avatar = \get_the_post_thumbnail_url($item->ID, Image::SIZE_AVATAR)) ? $avatar : ''
        ], $list);

        return empty($list) ? null : $list;
    }

    public static function admin_init(): void
    {
        $cmb = \new_cmb2_box([
            'id'            => 'course_testimonial_metabox',
            'title'         => \__('Об авторе отзыва', 'tps'),
            'object_types'  => [self::$post_type],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $cmb->add_field([
            'id'         => self::META_REVIEWER_NAME,
            'type'       => 'text_medium',
            'name'       => \__('Имя', 'tps'),
        ]);

        $cmb->add_field([
            'id'         => self::META_REVIEWER_POSITION,
            'type'       => 'text_medium',
            'name'       => \__('Должность', 'tps'),
        ]);
    }

    public static function admin_customize_title(string $title): string
    {
        $screen = \get_current_screen();

        if ($screen->post_type === self::$post_type) {
            $title = 'Заголовок отзыва';
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
}
