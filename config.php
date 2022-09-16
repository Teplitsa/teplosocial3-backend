<?php

namespace Teplosocial;

use \Teplosocial\ConfigCommon;

class Config extends ConfigCommon {
    const MONGO_CONNECTION = 'mongodb://localhost:27017'; // teplosocial2.ngo2.ru Mongo config //'mongodb://teplosocial-mongo';
    
    const STATS_EXTRA_EMAILS = [
    ];

    const NEW_ASSIGNMENT_NOTIFY_EMAILS = [
        'maryshally90@gmail.com', //'filatova@te-st.ru',
    ];
}
