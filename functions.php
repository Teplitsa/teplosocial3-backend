<?php

require_once(get_theme_file_path() . '/vendor/autoload.php');
require_once(get_theme_file_path() . '/config.common.php');
require_once(get_theme_file_path() . '/config.php');

load_theme_textdomain('tps', get_theme_file_path() . '/lang');
error_reporting(E_ALL & ~E_NOTICE);
set_error_handler(function ($severity, $message, $file, $line) {

    if(error_reporting() & $severity) {

        error_log($message);

        foreach(debug_backtrace() as $line) {

            if(isset($line['file']) && strpos($line['file'], '/wp-content/themes/teplosocial-backend/') !== false) {
                error_log("{$line['line']} in {$line['file']}");
            }

        }

        error_log("\n");

    }

});

// Admin area additions
if (is_admin()) {
    require get_template_directory() . '/admin/admin.php';
}

// utils
require_once(get_theme_file_path() . '/exceptions.php');
require_once(get_theme_file_path() . '/utils/encode.php');
require_once(get_theme_file_path() . '/utils/sanitize.php');
require_once(get_theme_file_path() . '/utils/format.php');
require_once(get_theme_file_path() . '/utils/upload_image.php');
require_once(get_theme_file_path() . '/utils/social.php');
require_once(get_theme_file_path() . '/utils/atvetka.php');
require_once(get_theme_file_path() . '/utils/ipgeo.php');
require_once(get_theme_file_path() . '/utils/setup_utils.php');
require_once(get_theme_file_path() . '/utils/datetime.php');

// system models
require_once(get_theme_file_path() . '/models/db/mongo.php');
require_once(get_theme_file_path() . '/models/cache/mongo.php');
require_once(get_theme_file_path() . '/models/cache/cacheable.php');
require_once(get_theme_file_path() . '/models/auth.php');

// models
require_once(get_theme_file_path() . '/models/base/post.php');
require_once(get_theme_file_path() . '/models/base/image.php');
require_once(get_theme_file_path() . '/models/learn/track.php');
require_once(get_theme_file_path() . '/models/learn/course.php');
require_once(get_theme_file_path() . '/models/learn/course_tag.php');
require_once(get_theme_file_path() . '/models/learn/module.php');
require_once(get_theme_file_path() . '/models/learn/block.php');
require_once(get_theme_file_path() . '/models/learn/quiz.php');
require_once(get_theme_file_path() . '/models/learn/adaptest.php');
require_once(get_theme_file_path() . '/models/learn/certificate.php');
require_once(get_theme_file_path() . '/models/learn/assignment.php');
require_once(get_theme_file_path() . '/models/learn/user-progress.php');
require_once(get_theme_file_path() . '/models/learn/adaptest.php');
require_once(get_theme_file_path() . '/models/user/student.php');
require_once(get_theme_file_path() . '/models/learn/student-learning.php');
require_once(get_theme_file_path() . '/models/substance.php');
require_once(get_theme_file_path() . '/models/person.php');
require_once(get_theme_file_path() . '/models/stats/stats.php');
require_once(get_theme_file_path() . '/models/advantage/advantage.php');
require_once(get_theme_file_path() . '/models/reviews/testimonial.php');
require_once(get_theme_file_path() . '/models/reviews/course_review.php');
require_once(get_theme_file_path() . '/models/visitor-session.php');
require_once(get_theme_file_path() . '/models/teacher.php');
require_once(get_theme_file_path() . '/models/course-testimonial.php');
require_once(get_theme_file_path() . '/models/notifications/notifications.php');

// post-types
require_once(get_theme_file_path() . '/post-types/track.php');
require_once(get_theme_file_path() . '/post-types/course.php');
require_once(get_theme_file_path() . '/post-types/substance.php');
require_once(get_theme_file_path() . '/post-types/person.php');
require_once(get_theme_file_path() . '/post-types/teacher.php');
require_once(get_theme_file_path() . '/post-types/course-testimonial.php');

// rest-api
require_once(get_theme_file_path() . '/rest-api/base/post.php');
require_once(get_theme_file_path() . '/rest-api/base/user.php');
require_once(get_theme_file_path() . '/rest-api/auth.php');
require_once(get_theme_file_path() . '/rest-api/gutenberg.php');
require_once(get_theme_file_path() . '/rest-api/substance.php');
require_once(get_theme_file_path() . '/rest-api/learn/track.php');
require_once(get_theme_file_path() . '/rest-api/learn/course.php');
require_once(get_theme_file_path() . '/rest-api/learn/module.php');
require_once(get_theme_file_path() . '/rest-api/learn/block.php');
require_once(get_theme_file_path() . '/rest-api/learn/quiz.php');
require_once(get_theme_file_path() . '/rest-api/learn/certificate.php');
require_once(get_theme_file_path() . '/rest-api/learn/assignment.php');
require_once(get_theme_file_path() . '/rest-api/user/student.php');
require_once(get_theme_file_path() . '/rest-api/stats/stats.php');
require_once(get_theme_file_path() . '/rest-api/person.php');
require_once(get_theme_file_path() . '/rest-api/reviews/course_review.php');
require_once(get_theme_file_path() . '/rest-api/visitor-session.php');
require_once(get_theme_file_path() . '/rest-api/notifications.php');

// wp-cli
require_once(get_theme_file_path() . '/wp-cli/load_advantages.php');
require_once(get_theme_file_path() . '/wp-cli/load_atvetka.php');
require_once(get_theme_file_path() . '/wp-cli/cache.php');
require_once(get_theme_file_path() . '/wp-cli/debug.php');
require_once(get_theme_file_path() . '/wp-cli/setup_db.php');
require_once(get_theme_file_path() . '/wp-cli/setup_blocks.php');
require_once(get_theme_file_path() . '/wp-cli/student_stats.php');
require_once(get_theme_file_path() . '/wp-cli/stats.php');
require_once(get_theme_file_path() . '/wp-cli/certificate.php');
require_once(get_theme_file_path() . '/wp-cli/notify.php');
require_once(get_theme_file_path() . '/wp-cli/notifications.php');

// register hooks
require_once(get_theme_file_path() . '/wp-hooks/general.php');
require_once(get_theme_file_path() . '/wp-hooks/auth.php');
require_once(get_theme_file_path() . '/wp-hooks/page.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/course.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/track.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/module.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/block.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/assignment.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/user-progress.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/certificate.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/adaptest.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/quiz-types.php');
require_once(get_theme_file_path() . '/wp-hooks/learn/course_tag.php');
require_once(get_theme_file_path() . '/wp-hooks/notification.php');
require_once(get_theme_file_path() . '/wp-hooks/stats.php');
require_once(get_theme_file_path() . '/wp-hooks/advantage.php');
require_once(get_theme_file_path() . '/wp-hooks/testimonial.php');
require_once(get_theme_file_path() . '/wp-hooks/course-testimonial.php');
require_once(get_theme_file_path() . '/wp-hooks/teacher.php');
require_once(get_theme_file_path() . '/wp-hooks/notifications.php');

// register shortcodes
require_once(get_theme_file_path() . '/shortcodes/tooltip.php');