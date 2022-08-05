<?php

class Atvetka {

    private static $_instance = NULL;

    public static function instance()
    {
        if (Atvetka::$_instance == NULL) {
            Atvetka::$_instance = new Atvetka ();
        }
        return Atvetka::$_instance;
    }

    public function mail($mail_slug, $data)
    {
        $atvetka_data = $data;
        $common_data = array();

        $atvetka_data['email_placeholders'] = [];
        foreach(array_merge($data, $common_data) as $k => $v) {
            $atvetka_data['email_placeholders']["{{$k}}"] = $v;
        }

        // error_log(print_r($atvetka_data, true));
        do_action('atv_email_notification', $mail_slug, $atvetka_data);
    }

    public function load_single_mail($mail_name)
    {
        $dpath = get_theme_file_path() . '/init/mail/' . $mail_name;
        $source_file_name = $dpath . '/' . $mail_name . '.json';
        $source = file_get_contents($source_file_name);

        if (!$source) {
            throw new \Exception(sprintf(__('Failed to get the source file: %s.', 'tps'), $source_file_name));
        }
    
        $emails = json_decode($source, true);

        foreach($emails as $mail_data) {
            print($mail_data['term'] . "\n");
            $this->load_mail($mail_data, $dpath);
            break;
        }
    }

    public function load_mail($mail_data, $dpath)
    {
        $tax = 'atv_notif_events';

        $email_name = $mail_data['term'];
        $message_title = $mail_data['subject'];
        $message_content = file_get_contents($dpath . "/" . $email_name . ".html");

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