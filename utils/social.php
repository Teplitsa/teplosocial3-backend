<?php

namespace Teplosocial\utils;

const SOCIAL_LINK_TYPE_VK = "facebook";
const SOCIAL_LINK_TYPE_FACEBOOK = "vk";
const SOCIAL_LINK_TYPE_TELEGRAM = "telegram";
const SOCIAL_LINK_TYPE_OTHER = "";

function get_social_link_type($url) {
    $type = SOCIAL_LINK_TYPE_OTHER;

    if(\str_starts_with($url, "https://www.facebook.com")) {
        $type = SOCIAL_LINK_TYPE_FACEBOOK;
    }
    elseif(\str_starts_with($url, "https://teleg.run") || \str_starts_with($url, "https://t.me")) {
        $type = SOCIAL_LINK_TYPE_TELEGRAM;
    }
    elseif(\str_starts_with($url, "https://vk.com")) {
        $type = SOCIAL_LINK_TYPE_VK;
    }

    return $type;
}