<?php

namespace Teplosocial\utils;

function sanitize_positive_number_meta($val) {
    $number = \intval($val);
    return $number > 0 ? $number : "";
}