<?php

add_action('added_user_meta', ['Teplosocial\models\UserProgress', 'update_item_cache'], 10, 3);
add_action('updated_user_meta', ['Teplosocial\models\UserProgress', 'update_item_cache'], 10, 3);
add_action('deleted_user_meta', ['Teplosocial\models\UserProgress', 'delete_item_cache'], 10, 3);
add_action( 'learndash_lesson_completed',  ['Teplosocial\models\UserProgress', 'complete_lesson'], 10, 1);
