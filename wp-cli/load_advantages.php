<?php

use Teplosocial\models\Substance;
use function Teplosocial\utils\upload_image;

if (!class_exists('\WP_CLI')) {
    return;
}

function load_advantages(): void
{
    $source_file_name = 'advantages.json';

    $source = file_get_contents(get_stylesheet_directory() . '/init/home/' . $source_file_name);

    if (!$source) {
        \WP_CLI::error(sprintf(__('Failed to get the source file: %s.', 'tps'), $source_file_name));
    }

    $advantages = json_decode($source, true);

    if (is_null($advantages)) {
        \WP_CLI::error(sprintf(__('Failed to decode the %s data.', 'tps'), Substance::$post_type));
    }

    $inserted_item_count = 0;

    if (!term_exists(Substance::$TYPE_ADVANTAGES, Substance::$taxonomy)) {
        wp_insert_term(__('Преимущества', 'tps'), Substance::$taxonomy, ['slug' => Substance::$TYPE_ADVANTAGES]);
    }

    foreach ($advantages as ['title' => $title, 'excerpt' => $excerpt, 'thumbnail' => $thumbnail]) {
        $advantage_data = [
            'post_type'    => Substance::$post_type,
            'post_title'   => $title,
            'post_excerpt' => $excerpt,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_author'  => 'admin'
        ];

        $advantage_id = wp_insert_post($advantage_data, true);

        if (is_wp_error($advantage_id)) {
            \WP_CLI::error($advantage_id->get_error_message());
        }

        if ($thumbnail) {

            $thumbnail_id = upload_image(['url' => $thumbnail, 'attached_to' => $advantage_id, 'desc' => $title]);

            if (is_null($thumbnail_id)) {
                \WP_CLI::error(sprintf(__('Failed to upload an image from url: %s.', 'tps'), $thumbnail));
            }

            if (!set_post_thumbnail($advantage_id, $thumbnail_id)) {
                \WP_CLI::error(sprintf(__('Failed to set a thumbnail to %s with the ID #%d.', 'tps'), Substance::$post_type, $advantage_id));
            }
        }

        if (!wp_set_object_terms($advantage_id, Substance::$TYPE_ADVANTAGES, Substance::$taxonomy)) {
            \WP_CLI::error(sprintf(__('Failed to assign a term to the %s with the ID #%d.', 'tps'), Substance::$post_type, $advantage_id));
        }

        $inserted_item_count++;
    }


    \WP_CLI::success(sprintf(__('%d %s(s) has been successfully loaded.', 'tps'), $inserted_item_count, Substance::$post_type));
}

\WP_CLI::add_command('load_advantages', 'load_advantages');
