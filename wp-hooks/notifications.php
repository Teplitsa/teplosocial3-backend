<?php

use Teplosocial\models\{CourseCache, TrackCache};

add_action('save_certificate', ['Teplosocial\models\Notifications', 'new_certificate_handler'], 10, 2);

add_action('transition_post_status', ['Teplosocial\models\Notifications', 'new_learning_object_handler'], 10, 3);
