<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Student};


if (!class_exists('WP_CLI')) {
    return;
}

class Debug
{
    public function user_points($args, $assoc_args)
    {
        $user = \wp_get_current_user();
        if(!$user->ID) {
            \WP_CLI::error(sprintf(__('User not defined.', 'tps')));
            return;
        }

        \WP_CLI::success(sprintf(__('user %s; points %d', 'tps'), $user->user_login, Student::get_points($user->ID)));
    }

    public function get_blocks($args, $assoc_args)
    {
        $blocks = get_posts([
            'post_name__in' => ["blok-s-video"],
            // 'date_query' => [],        
            "post_type" => "sfwd-lessons",
        ]);
        \WP_CLI::log("blocks found: " . count($blocks));

        for($i = 0; $i < (min(5, count($blocks))); $i++) {
            \WP_CLI::log($blocks[$i]->post_name);
        }
    }
}

\WP_CLI::add_command('tps_debug', '\Teplosocial\cli\Debug');
