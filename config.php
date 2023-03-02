<?php

namespace Teplosocial;

use \Teplosocial\ConfigCommon;

class Config extends ConfigCommon {
    const MONGO_CONNECTION = 'mongodb://teplosocial-mongo:27017';
    // teplosocial2.ngo2.ru Mongo config //'mongodb://teplosocial-mongo';
    // localhost Mongo config: mongodb://teplosocial-mongo:27017
    
    const STATS_EXTRA_EMAILS = [
    ];

    const NEW_ASSIGNMENT_NOTIFY_EMAILS = [
        'ahaenor@gmail.com',
    ];
}
