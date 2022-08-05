<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\{Track, Course, Module, Block, Image, Assignment};

/** Add SVG to allowed file uploads */

function itv_svg_mime_type($mimes = array())
{
    $mimes['svg']  = 'image/svg';
    $mimes['svgz'] = 'image/svg';

    return $mimes;
}

\add_filter('upload_mimes', '\Teplosocial\hooks\itv_svg_mime_type');

// theme setup

function theme_setup()
{
    add_theme_support('post-thumbnails');

    Image::setup_sizes();
}
\add_action('after_setup_theme', 'Teplosocial\hooks\theme_setup');

function remove_empty_tags($content)
{
    $content = \preg_replace("/<p>\s*<\/p>/", "", $content);
    $content = \preg_replace("/<p>(&nbsp;)*<\/p>/", "", $content);
    return $content;
}
\add_filter('the_content', '\Teplosocial\hooks\remove_empty_tags');

function modify_the_link($post_url, $post)
{
    $path_list = [
        ['post_type' => Track::$post_type, 'from' => "\/\?" . Track::$post_type . "=", 'to' => '/tracks/'],
        ['post_type' => Course::$post_type, 'from' => "\/\?" . Course::$post_type . "=", 'to' => '/courses/'],
        ['post_type' => Course::$post_type, 'from' => "\/tps_course\/", 'to' => '/courses/'],
        ['post_type' => Module::$post_type, 'from' => "\/courses\/", 'to' => '/modules/'],
        ['post_type' => Block::$post_type, 'from' => "\/lessons\/", 'to' => '/blocks/'],
    ];
    
    foreach($path_list as $path_rewrite) {
        if($path_rewrite['post_type'] === $post->post_type) {
            $post_url = \preg_replace("/" . $path_rewrite['from'] . "/", $path_rewrite['to'], $post_url);
        }
    }

    if($post->post_type === Assignment::$post_type) {
        $file_link = get_post_meta($post->ID, Assignment::META_FILE_URL, true);
        if ($file_link) {
            $post_url = $file_link;
        }
    }

    return $post_url;
}
\add_filter('post_type_link', '\Teplosocial\hooks\modify_the_link', 100, 2);

function disable_deprecated_function_trigger_error($mustTrigger)
{
    return false;
}
\add_filter('deprecated_function_trigger_error', '\Teplosocial\hooks\disable_deprecated_function_trigger_error');

function debug_query(\WP_Query $query)
{
    if ($query->query_vars['post_type'] === 'sfwd-quiz') {
        // error_log("query arg: " . print_r($query->query_vars, true));
        foreach (debug_backtrace() as $k => $call) {
            // error_log($call['file'] . "    line: " . $call['line'] . "   func: " . $call['function']);
        }
    }
}
// add_action( 'pre_get_posts', '\Teplosocial\hooks\debug_query', 100 );

function tps_modify_feed_request($qv) {
    if (isset($qv['feed'])) {
        $qv['post_type'] = Course::$post_type;
        $qv['date_query'] = [
            [
                'after'     => '1 year ago',
                'inclusive' => true,
            ],
        ];
    }
    // error_log("qv: " . print_r($qv, "tps"));
    return $qv;
}
\add_filter('request', '\Teplosocial\hooks\tps_modify_feed_request');
