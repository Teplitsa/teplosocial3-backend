<?php
/**
 * Template Name: Account activation
 *
 **/

if(get_current_user_id() || empty($_GET['uid'])) {
    wp_redirect(home_url());
    exit();
}

$user = get_user_by('id', (int)$_GET['uid']);

if( !$user ) {

    wp_redirect("/account-activation-completed?status=error&message=" . urlencode(__('User account not found.', 'tst')));

} elseif(empty($_GET['code']) || get_user_meta($user->ID, 'activation_code', true) != $_GET['code']) {

    wp_redirect("/account-activation-completed?status=error&message=" . urlencode(__('Wrong data given.', 'tst')));

} else {
    
    update_user_meta($user->ID, 'activation_code', '');
    wp_redirect("/account-activation-completed?status=ok");
    
}        

exit();