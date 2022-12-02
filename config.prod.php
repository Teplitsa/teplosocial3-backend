<?php

namespace Teplosocial;

use \Teplosocial\ConfigCommon;

class Config extends ConfigCommon {
    const MONGO_CONNECTION = 'mongodb://localhost:27017';
    
    const STATS_EXTRA_EMAILS = [
        'kartsov@te-st.ru',
        'avdeeva.nataliya@gmail.com',
        'sidorenko.a@te-st.ru',
//        'denis.cherniatev@gmail.com',
    ];

    const NEW_ASSIGNMENT_NOTIFY_EMAILS = [
        'maryshally90@gmail.com',
//        'filatova@te-st.ru',
    ];
}
