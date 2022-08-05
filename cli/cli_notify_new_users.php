<?php

require_once(dirname(__FILE__) . '/../mail/atvetka.php');

try {
    include('cli_common.php');

    $notif_keys = [
        '3' => 'user_registered_d3',
        '7' => 'user_registered_d7',
        '10' => 'user_registered_d10',
        '14' => 'user_registered_d14',
    ];

    $args = array(
        'role'           => 'author',
        'fields'         => array( 'user_registered', 'user_email', 'display_name' ),
        'date_query' => [
            [ 'after'  => '15 days ago midnight', 'inclusive' => true ],
        ]
    );

    $user_query = new WP_User_Query( $args );
    $date_today = new DateTime('', new DateTimeZone('Europe/Moscow'));

    if ( ! empty( $user_query->results ) ) {
        foreach ( $user_query->results as $u ) {
            $date_registered = new DateTime(date('Y-m-d H:i:s', strtotime($u->user_registered)));
            $diff = $date_today->diff( $date_registered )->days;

            if(isset( $notif_keys[$diff]) ) {
                Atvetka::instance()->mail( $notif_keys[$diff], [
                    'mailto' => $u->user_email,
                    'username' => $u->display_name,
                ]);
            }
        }
    } else {
        echo 'Пользователей по заданным критериям не найдено.';
    }
}
catch (NotCLIRunException $ex) {
    echo $ex->getMessage() . "\n";
}
catch (CLIHostNotSetException $ex) {
    echo $ex->getMessage() . "\n";
}
catch (Exception $ex) {
    echo $ex;
}
