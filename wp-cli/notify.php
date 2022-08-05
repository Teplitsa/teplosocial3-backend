<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Student};


if (!class_exists('WP_CLI')) {
    return;
}

class Notificator
{
    public function notify_onboarding_faq($args, $assoc_args)
    {
        $users = get_users([
            'date_query' => [
                'relation' => 'AND',
                ['before' => '24 hours ago', 'inclusive' => true],
                ['after' => '48 hours ago', 'inclusive' => false],
            ],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => Student::META_ONBOARDING_FAQ_SENT,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);
        
        foreach($users as $user) {
            \WP_CLI::log("user: " . $user->user_email);
            self::mail_onboarding_faq($user);
            add_user_meta($user->ID, Student::META_ONBOARDING_FAQ_SENT, true, true);
        }

        \WP_CLI::success(__('Onboarding faq sent.', 'tps'));
    }

    private static function mail_onboarding_faq($user) {
        $user_email = $user->user_email;
        $user_first_name = get_user_meta($user->ID, Student::META_FIRST_NAME, true);

        $atvetka_data = [
            'mailto' => $user->user_email,
            'email_placeholders' => [
                '{user_first_name}' => $user_first_name,
            ],
        ];
        $mail_slug = 'onboarding_faq';
        do_action('atv_email_notification', $mail_slug, $atvetka_data);
    }
}

\WP_CLI::add_command('tps_notify', '\Teplosocial\cli\Notificator');
