<?php

namespace Teplosocial\models;

class Mail
{
    public function setup_atvetka_email($email_name)
    {
        $tax = 'atv_notif_events';
        $source_file_name = $email_name . '.json';
        $source = file_get_contents(get_stylesheet_directory() . '/init/mail/' . $source_file_name);

        if (!$source) {
            \WP_CLI::error(sprintf(__('Failed to get the source file: %s.', 'itv-backend'), $source_file_name));
        }
    
        $email = json_decode($source, true);
    
        if (is_null($email)) {
            \WP_CLI::error(sprintf(__('Failed to decode the %s mail data.', 'itv-backend'), $email_name));
        }        

        $message_title = $email['subject'];
        $message_content = $email['message'];
        $message_content = wpautop( $message_content );	    

        ob_start();
        include(get_template_directory() . '/mail/message_template.php');
        $message_content = ob_get_clean();

        $posts_data[] = [
            'post_title' => $message_title,
            'post_content' => $message_content,
            'post_content_raw' => $message_content,
            'post_name' => $email_name,
            'tax_terms' => array(
                $tax => array($email_name),
            ),
        ];

        $terms = array(
            array('slug' => $email_name, 'name' => $email_name,),
        );
	
        \TstSetupUtils::setup_terms_data($terms, $tax);
        \TstSetupUtils::setup_posts_data($posts_data, \ATV_Email_Notification::POST_TYPE);
    }
}
