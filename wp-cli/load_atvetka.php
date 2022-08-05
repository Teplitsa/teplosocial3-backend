<?php

namespace Teplosocial\cli;

if (!class_exists('WP_CLI')) {
    return;
}

/**
 * Setup atvetka emails
 */

class LoadAtvetka
{
    public function load_assignment_notif($args, $assoc_args)
    {
        $dpath = get_theme_file_path() . '/init/mail/assignment_notif';
        $source_file_name = $dpath . '/assignment_notif.json';
        $source = file_get_contents($source_file_name);

        if (!$source) {
            \WP_CLI::error(sprintf(__('Failed to get the source file: %s.', 'tps'), $source_file_name));
        }
    
        $emails = json_decode($source, true);

        foreach($emails as $mail_data) {
            print($mail_data['term'] . "\n");
            \Atvetka::instance()->load_mail($mail_data, $dpath);
        }

        \WP_CLI::success(__('Assignment notifications updated.', 'tps'));        
    }

    function setup_mail($args, $assoc_args)
    {
        $mail_name = !empty($args) ? $args[0] : "";

        if(!$mail_name) {
            \WP_CLI::error(__('Empty mail name.', 'tps'));
            return;
        }

        \WP_CLI::line(sprintf(__('Setup email: %s', 'tps'), $mail_name));
        
        \Atvetka::instance()->load_single_mail($mail_name);

        \WP_CLI::success(__('Email setup successfully completed.', 'tps'));
    }
}

\WP_CLI::add_command('tps_load_atvetka', '\Teplosocial\cli\LoadAtvetka');
