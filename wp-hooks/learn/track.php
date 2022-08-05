<?php

namespace Teplosocial\hooks;

use Teplosocial\models\{TrackCache};

add_action('save_post_' . TrackCache::$post_type, ['Teplosocial\models\TrackCache', 'update_item_cache'], 10, 1);

add_action('after_delete_post', ['Teplosocial\models\TrackCache', 'delete_item_cache'], 10, 1);