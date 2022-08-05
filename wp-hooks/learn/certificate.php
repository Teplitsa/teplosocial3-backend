<?php

add_action('save_certificate', ['Teplosocial\models\Certificate', 'update_item_cache'], 10, 4);
