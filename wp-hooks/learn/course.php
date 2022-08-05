<?php

namespace Teplosocial\hooks;

use Teplosocial\models\{Course, CourseCache};

class CourseHooks
{
    public static function init_meta()
    {
        $cmb = \new_cmb2_box(array(
            'id'            => 'course_metabox',
            'title'         => __('Teplosocial meta', 'tps'),
            'object_types'  => array(Course::$post_type,),
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ));

        $cmb->add_field(array(
            'name'       => __('Краткое описание (карточка курса)', 'tps'),
            'id'         => Course::META_TEASER,
            'type'       => 'wysiwyg',
            'options'    => [
                'wpautop'       => true,
                'media_buttons' => false,
                'textarea_rows' => 10,
            ],
        ));

        $cmb->add_field(array(
            'name'       => __('Полное описание (лендинг курса)', 'tps'),
            'id'         => Course::META_DESCRIPTION,
            'type'       => 'wysiwyg',
            'options'    => [
                'wpautop'       => true,
                'media_buttons' => false,
                'textarea_rows' => 10,
            ],
        ));

        $cmb->add_field(array(
            'name'       => __('Кому подойдет', 'tps'),
            'id'         => Course::META_SUITABLE_FOR,
            'type'       => 'textarea_small',
        ));

        $cmb->add_field(array(
            'name'       => __('Чему вы научитесь', 'tps'),
            'id'         => Course::META_LEARNING_RESULT,
            'type'       => 'textarea_small',
        ));
    }
}

\add_action('cmb2_admin_init', '\Teplosocial\hooks\CourseHooks::init_meta');

\add_action('cmb2_save_post_fields', ['Teplosocial\models\CourseCache', 'update_item_cache'], 10, 1);

\add_action('save_post_' . CourseCache::$post_type, ['Teplosocial\models\CourseCache', 'update_item_cache'], 10, 1);

\add_action('after_delete_post', ['Teplosocial\models\CourseCache', 'delete_item_cache'], 10, 1);
