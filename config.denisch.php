<?php

namespace Teplosocial;

use \Teplosocial\ConfigCommon;

class Config extends ConfigCommon {
    const MONGO_CONNECTION = 'mongodb://mongo.db:27017';
    
    const STATS_EXTRA_EMAILS = [
        'wantprog@mail.ru',
        // 'avdeeva.nataliya@gmail.com',
        // 'nv.ru@bk.ru',
    ];

    const NEW_ASSIGNMENT_NOTIFY_EMAILS = [
        'wantprog@mail.ru',
        // 'filatova@te-st.ru',
    ];
}
