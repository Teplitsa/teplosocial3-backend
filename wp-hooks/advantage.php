<?php

use Teplosocial\models\Advantage;

add_action('save_post_' . Advantage::$post_type, ['Teplosocial\models\Advantage', 'update_item_cache'], 10, 1);
add_action('after_delete_post', ['Teplosocial\models\Advantage', 'delete_item_cache'], 10, 1);
