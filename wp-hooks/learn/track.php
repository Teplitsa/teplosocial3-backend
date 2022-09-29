<?php

namespace Teplosocial\hooks;

use Teplosocial\models\{Track, TrackCache};

class TrackHooks {

    public static function init_metabox() {

//        $track_post_id = get_the_ID();
//        $value = get_post_meta($track_post_id, 'tps_track_description_common', true);
        // TODO Add meta name to the Track model as a class const

        $cmb = new_cmb2_box([
            'id'            => 'tps_track_settings_metabox',
            'title'         => 'Настройки трека',
            'object_types'  => [Track::$post_type],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $cmb->add_field([
            'id' => 'tps_track_description_lead', // TODO Add meta name to the Track model as a class const
            'name' => 'Текст, который отображается на лэндинге трека перед блоками с заголовками',
            'desc'    => '',
            'type'    => 'wysiwyg',
            'default' => '',
            'options' => [
//                'wpautop' => true, // use wpautop?
                'media_buttons' => false, // show insert/upload button(s)
//                'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
                'textarea_rows' => 3, // rows="..."
//                'tabindex' => '',
//                'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the `<style>` tags, can use "scoped".
//                'editor_class' => '', // add extra class(es) to the editor textarea
//                'teeny' => false, // output the minimal editor config used in Press This
//                'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
//                'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
//                'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
            ],
        ]);

        $cmb->add_field([
            'id' => 'tps_track_description_common', // TODO Add meta name to the Track model as a class const
            'name' => 'Что такое трек?',
            'desc'    => 'Обычно этот текст един для всех треков, но при необходимости вы можете переопределить его для этого трека.',
            'type'    => 'wysiwyg',
            'default' => 'Трек — это подборка курсов, которая поможет изучить тему системно.',
            'options' => [
//                'wpautop' => true, // use wpautop?
                'media_buttons' => false, // show insert/upload button(s)
//                'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
                'textarea_rows' => 3, // rows="..."
//                'tabindex' => '',
//                'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the `<style>` tags, can use "scoped".
//                'editor_class' => '', // add extra class(es) to the editor textarea
//                'teeny' => false, // output the minimal editor config used in Press This
//                'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
//                'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
//                'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
            ],
        ]);

        $cmb->add_field([
            'id' => 'tps_track_description', // TODO Add meta name to the Track model as a class const
            'name' => 'О чём этот трек?',
            'desc'    => 'Текст для лэндинга трека (в одноимённом разделе лэндинга).',
            'type'    => 'wysiwyg',
            'default' => '',
            'options' => [
//                'wpautop' => true, // use wpautop?
                'media_buttons' => false, // show insert/upload button(s)
//                'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
                'textarea_rows' => 3, // rows="..."
//                'tabindex' => '',
//                'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the `<style>` tags, can use "scoped".
//                'editor_class' => '', // add extra class(es) to the editor textarea
//                'teeny' => false, // output the minimal editor config used in Press This
//                'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
//                'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
//                'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
            ],
        ]);

    }

    public static function save_metabox($quiz_post_id, $quiz_post, $update) { }

}

add_action('cmb2_admin_init', '\Teplosocial\hooks\TrackHooks::init_metabox');
add_action('save_post', '\Teplosocial\hooks\TrackHooks::save_metabox', 50, 3);

add_action('save_post_'.TrackCache::$post_type, ['Teplosocial\models\TrackCache', 'update_item_cache'], 10, 1);
add_action('after_delete_post', ['Teplosocial\models\TrackCache', 'delete_item_cache'], 10, 1);