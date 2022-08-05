<?php

namespace Teplosocial\hooks;

function tps_logout_without_confirmation($action, $result) {
    // allow logout without confirmation
    if ($action == "log-out" && !isset($_GET['itv-logout'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '/login';
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to) . '&itv-logout=1');
        wp_redirect($location);
        exit;
    }
}
add_action('check_admin_referer', '\Teplosocial\hooks\tps_logout_without_confirmation', 10, 2);

function tps_do_mock_login() {

    if( !is_super_admin() || empty($_GET['mock-login']) ) { // Mock login function is only for site admins
        return;
    }

    if(is_email($_GET['mock-login'])) {
        $mockUser = get_user_by('email', trim($_GET['mock-login']));
    } else {
        $mockUser = get_user_by('slug', trim($_GET['mock-login']));
    }

    error_log("mockUser:" . print_r($mockUser, true));
    if( !$mockUser || !is_a($mockUser, 'WP_User') ) { //  || $mockUser->ID === get_current_user_id()
        wp_redirect('/');
        return;
    }

    $auth = new \Teplosocial\models\Auth();
    $token = $auth->generate_token($mockUser);
    setcookie(\Teplosocial\Config::AUTH_TOKEN_COOKIE_NAME, $token, time() + 30 * 24 * 3600, '/');
    wp_redirect('/?after-mock-login=1');
}
add_action('init', '\Teplosocial\hooks\tps_do_mock_login', 1);

function tps_block_admin_console_for_not_admin() {
    // error_log("is_admin: " . is_admin());
    // error_log("current_user_can administrator: " . current_user_can('administrator'));
    // error_log("DOING_AJAX: " . (defined('DOING_AJAX') && DOING_AJAX));

    if(is_admin() && !current_user_can('administrator') && !(defined('DOING_AJAX') && DOING_AJAX)) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', '\Teplosocial\hooks\tps_block_admin_console_for_not_admin');
