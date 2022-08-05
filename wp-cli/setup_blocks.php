<?php

namespace Teplosocial\cli;

use Teplosocial\models\Block;


if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Setup db tables and fields
 */

class SetupBlocks
{
    public function update_assignments_count_option($args, $assoc_args)
    {
        global $wpdb;
        $block_id_list = get_posts([
            'post_type' => Block::$post_type,
            'post_status' => 'any',
            'suppress_filters' => true,
            'posts_per_page' => -1,
            'nopaging' => true,
            'fields' => 'ids',
        ]);

        foreach($block_id_list as $post_id) {
            Block::update_ld_options($post_id, [
                'sfwd-lessons_assignment_upload_limit_count' => 0,
            ]);
        }

        \WP_CLI::success(__('Block assignments count updated.', 'tps'));        
    }
}

\WP_CLI::add_command('tps_setup_blocks', '\Teplosocial\cli\SetupBlocks');
