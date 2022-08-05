<?php

use Teplosocial\models\CourseTag;

add_action('saved_' . CourseTag::$taxonomy, ['Teplosocial\models\CourseTag', 'update_item_cache'], 10, 1);

add_action('edited_' . CourseTag::$taxonomy, ['Teplosocial\models\CourseTag', 'update_item_cache'], 10, 1);

add_action('delete_' . CourseTag::$taxonomy, ['Teplosocial\models\CourseTag', 'delete_item_cache'], 10, 1);
