<?php

use Teplosocial\models\Testimonial;

add_action('save_post_' . Testimonial::$post_type, ['Teplosocial\models\Testimonial', 'update_item_cache'], 10, 1);
add_action('after_delete_post', ['Teplosocial\models\Testimonial', 'delete_item_cache'], 10, 1);
