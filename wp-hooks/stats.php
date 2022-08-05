<?php

add_action('user_register', ['Teplosocial\models\UserStats', 'update_cache'], 10, 1);
add_action('deleted_user', ['Teplosocial\models\UserStats', 'update_cache'], 10, 1);

add_action('transition_post_status', ['Teplosocial\models\CourseStats', 'update_cache'], 10, 3);

add_action('transition_post_status', ['Teplosocial\models\TrackStats', 'update_cache'], 10, 3);

add_action('save_certificate', ['Teplosocial\models\CertificateStats', 'update_cache'], 10, 1);
