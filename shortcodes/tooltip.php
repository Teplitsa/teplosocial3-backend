<?php

namespace Teplosocial\shortcodes;

class Tooltip
{
    const SHORTCODE_NAME = "tooltip";

    public static function register_shortcode($atts, string $content): string
    {
        $shortcode_name = Tooltip::SHORTCODE_NAME;
        $anchor = $atts['anchor'] ?? '';

        return <<<HTML
        <{$shortcode_name}>
            <{$shortcode_name}-anchor>{$anchor}</{$shortcode_name}-anchor>
            <{$shortcode_name}-content>{$content}</{$shortcode_name}-content>
        </{$shortcode_name}>
        HTML;
    }
}

\add_shortcode(Tooltip::SHORTCODE_NAME, '\Teplosocial\shortcodes\Tooltip::register_shortcode');
