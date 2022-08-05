<?php

namespace Teplosocial\utils;

function remove_start_underscore($a) {
    $a_no_underscore = [];
    foreach($a as $ak => $av) {
        $a_no_underscore[preg_replace("/^_/", "", $ak)] = $av;
    }
    return $a_no_underscore;
}

function fill_template($template, $data) {
    return preg_replace_callback ( '/\{\{(\w+)\}\}/i', function($matches) use($data) {
        return isset($matches[1]) && isset($data[$matches[1]]) ? $data[$matches[1]] : '';
    }, $template );
}
