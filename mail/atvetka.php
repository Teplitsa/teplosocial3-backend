<?php
class Atvetka {

    private static $_instance = NULL;

    public static function instance() {
        if (Atvetka::$_instance == NULL) {
            Atvetka::$_instance = new Atvetka ();
        }
        return Atvetka::$_instance;
    }

    public function mail($mail_slug, $data) {
        $atvetka_data = $data;
        $common_data = array();

        $atvetka_data['email_placeholders'] = [];
        foreach(array_merge($data, $common_data) as $k => $v) {
            $atvetka_data['email_placeholders']["{{$k}}"] = $v;
        }

        // error_log(print_r($atvetka_data, true));
        do_action('atv_email_notification', $mail_slug, $atvetka_data);
    }
}