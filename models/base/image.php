<?php

namespace Teplosocial\models;

class Image
{
    const SIZE_AVATAR = 'avatar';
    const SIZE_ADVANTAGE = 'advantage';
    const SIZE_CARD_SMALL_COVER = 'card_small_cover';
    const SIZE_CARD_DEFAULT_COVER = 'card_default_cover';
    const SIZE_COURSE_TEACHER_AVATAR = 'course_teacher_avatar';

    public static function setup_sizes()
    {
        \add_image_size(self::SIZE_AVATAR, 180, 180, array('center', 'center'));
        \add_image_size(self::SIZE_ADVANTAGE, 38, 38, array('center', 'center'));
        \add_image_size(self::SIZE_CARD_DEFAULT_COVER, 746, 390, array('center', 'center'));
        \add_image_size(self::SIZE_CARD_SMALL_COVER, 180, 180, array('center', 'center')); // originally 90x90
        \add_image_size(self::SIZE_COURSE_TEACHER_AVATAR, 360, 360, array('center', 'center')); // originally 180x180
    }
}
