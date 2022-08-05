<?php

namespace Teplosocial;

use \Teplosocial\ConfigCommon;

class Config extends ConfigCommon {
    const MONGO_CONNECTION = 'mongodb://localhost:27017';
    
    const STATS_EXTRA_EMAILS = [
        'kartsov@te-st.ru',
        'avdeeva.nataliya@gmail.com',
        'denis.cherniatev@gmail.com',
    ];

    const NEW_ASSIGNMENT_NOTIFY_EMAILS = [
        'filatova@te-st.ru',
        'denis.cherniatev@yandex.ru',
    ];
}
