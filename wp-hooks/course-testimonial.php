<?php

\add_action('cmb2_admin_init', ['Teplosocial\models\CourseTestimonial', 'admin_init']);

\add_filter('enter_title_here', ['Teplosocial\models\CourseTestimonial', 'admin_customize_title']);

\add_action('admin_head', ['Teplosocial\models\CourseTestimonial', 'admin_customize_media_button']);

\add_filter('user_can_richedit', ['Teplosocial\models\CourseTestimonial', 'admin_customize_editor']);
