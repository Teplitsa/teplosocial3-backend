<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Student};


if( !class_exists('WP_CLI') ) {
    return;
}

class Notificator
{
    public function notify_onboarding_faq($args, $assoc_args) {

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

            if(class_exists('WP_CLI')) {
                \WP_CLI::log("user: " . $user->user_email);
            }

            self::mail_onboarding_faq($user);
            add_user_meta($user->ID, Student::META_ONBOARDING_FAQ_SENT, true, true);

        }

        if(class_exists('WP_CLI')) {
            \WP_CLI::success(__('Onboarding faq sent.', 'tps'));
        }


    }

    public static function mail_onboarding_faq($user, $use_wp_mail = false) {

        $user_first_name = get_user_meta($user->ID, Student::META_FIRST_NAME, true);
        $user_first_name = $user_first_name ? : $user->user_firstname;

        $atvetka_mail_slug = 'onboarding_faq';

        if( !!$use_wp_mail ) { // Sometime, the normal Atvetka emails are not sent, so bypass it

            $email_template = get_posts(array(
                'post_type' => 'atv_notification',
                'numberposts' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'atv_notif_events',
                        'field' => 'slug',
                        'terms' => $atvetka_mail_slug,
                        'include_children' => false
                    )
                )
            ));
            $email_template = $email_template ? reset($email_template) : false;

            wp_mail(
                $user->user_email,
                $email_template->post_title,
                wpautop(str_replace('{user_first_name}', $user_first_name, $email_template->post_content))
                []
            );

        } else {

            $atvetka_data = [
                'mailto' => $user->user_email,
                'email_placeholders' => [
                    '{user_first_name}' => $user_first_name,
                ],
            ];
            do_action('atv_email_notification', $atvetka_mail_slug, $atvetka_data);

        }

    }
}

\WP_CLI::add_command('tps_notify', '\Teplosocial\cli\Notificator');
