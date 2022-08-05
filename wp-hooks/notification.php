<?php

use Teplosocial\Config;
use Teplosocial\models\Student;

function tps_emails_set_html_content_type() {
    return 'text/html';
}

/** On assignment uploads. Sent to: admins. */
add_action('learndash_assignment_uploaded', 'tps_notify_admin_on_assignment_upload', 10, 2);
function tps_notify_admin_on_assignment_upload($assignment_id, $assignment_meta) {

    $block = get_post($assignment_meta['lesson_id']);
    $course = get_post($assignment_meta['course_id']);
    $course = \Teplosocial\models\Course::get_by_module($course->ID);
    $gamer = get_user_by('id', $assignment_meta['user_id']);

    $assignment = get_post($assignment_id);
    $upload_time = $assignment ? $assignment->post_date : "";
    $check_deadline_time = $upload_time ? date('Y-m-d H:i:s', strtotime($upload_time) + 24 * 3600) : "";

    $site_name = get_bloginfo('name');
    $email_text = sprintf(__("Hello from %s!<br><br>

New block assignment was uploaded by a gamer, and is waiting for approve.<br><br>

<ul>
<li><strong>Tile / block:</strong> %s / %s</li>
<li><strong>The gamer:</strong> %s</li>
<li><strong>The assignment uploaded:</strong> %s</li>
<li><strong>The assignment upload time:</strong> %s</li>
<li><strong>The assignment check deadline:</strong> %s</li>
</ul>
<br>
Yours,<br>
%s", 'tps'),
        $site_name,
        "",
        "",
        '<a href="'.admin_url('user-edit.php?user_id='.$gamer->ID).'">'.$gamer->display_name.'</a>',
        '<a href="'.$assignment_meta['file_link'].'" target="_blank">'.$assignment_meta['file_name'].'</a> (<a href="'.admin_url('post.php?action=edit&post='.$assignment_id).'">'.__('edit').'</a> | <a href="'.admin_url('edit.php?post_type=sfwd-assignment&tps_assignment_review=1').'">'.__('assignments list page', 'tps').'</a>)',
        $upload_time,
        $check_deadline_time,
        $site_name
    );

    add_filter('wp_mail_content_type', 'tps_emails_set_html_content_type');
    foreach([...Config::NEW_ASSIGNMENT_NOTIFY_EMAILS, get_bloginfo('admin_email')] as $admin_email) {
        $res = wp_mail(
            $admin_email,
            __('New block assignment uploaded', 'tps'),
            $email_text,
            array('From: '.$site_name.' <no_reply@kurs.te-st.ru>', 'Content-Type: text/html; charset=UTF-8')
        );
    }
    remove_filter('wp_mail_content_type', 'tps_emails_set_html_content_type');

    if( !$res ) {
        /** @todo Email wasn't sent - write to some log about it */
    }

}

/** On new assignment comments.
 * Sent to:
 * - admin, if comment added by gamer,
 * - gamer, if comment added to admin.
 */
add_action('comment_post', 'tps_notify_on_assignment_comment', 10, 3);
function tps_notify_on_assignment_comment($comment_id, $comment_approved, array $comment_data) {

    if(empty($comment_data['user_ID'])) { // Guest comment - is it even possible?..
        return;
    }

    $assignment_post = get_post($comment_data['comment_post_ID']);
    if($assignment_post->post_type !== 'sfwd-assignment') { // Comment not on assignment post
        return;
    }

    $assignment_module_id = (int)get_post_meta($comment_data['comment_post_ID'], 'course_id', true);
    // error_log("assignment_module_id: " . $assignment_module_id);
    $assignment_course = $assignment_module_id ? \Teplosocial\models\Course::get_by_module($assignment_module_id) : false;
    // error_log("assignment_course: " . print_r($assignment_course, true));
	
	$assignment_course_title = __('Unknown tile', 'tps');
	if($assignment_course) {
        $assignment_course_title = get_the_title($assignment_course);
	}
	
    $assignment_course_link = $assignment_course ?
        '<a href="'.get_permalink($assignment_course).'">'.$assignment_course_title.'</a>' : $assignment_course_title;

    $assignment_block = (int)get_post_meta($comment_data['comment_post_ID'], 'lesson_id', true);
    $assignment_block = $assignment_block ? get_post($assignment_block) : false;
    $assignment_block_link = $assignment_block ?
        '<a href="'.get_permalink($assignment_block).'">'.get_the_title($assignment_block).'</a>' :
        __('Unknown block', 'tps');

    $site_name = get_bloginfo('name');

    if(user_can($comment_data['user_ID'], 'administrator')) { // Comment by admin

        $assignment_uploading_gamer = get_user_by('id', $assignment_post->post_author);
        if( !$assignment_uploading_gamer || !$assignment_uploading_gamer->user_email ) {
            return; /** @todo Can't send an email to an unknown gamer - write to some log about it */
        }

        // error_log("post_author: " . $assignment_post->post_author);
        $user_first_name = get_user_meta($assignment_uploading_gamer->ID, Student::META_FIRST_NAME, true);
        // error_log("user_first_name: " . $user_first_name);

        $atvetka_data = [
            'mailto' => $assignment_uploading_gamer->user_email,
            'email_placeholders' => [
                '{user_first_name}' => $user_first_name,
                '{comment}' => $comment_data['comment_content'],
                '{course_link}' => $assignment_course_link,
                '{block_link}' => $assignment_block_link,
                '{assignment_link}' => '<a href="'.get_permalink($assignment_post).'">'.get_the_title($assignment_post).'</a>',
            ],
        ];
        $mail_slug = 'new_admin_comment_on_assignment';
        do_action('atv_email_notification', $mail_slug, $atvetka_data);

    } else { // Comment by gamer

        $comment_author = get_user_by('id', $comment_data['user_ID']);
        if( !$comment_author ) {
            return; /** @todo Comment by unknown gamer - write to some log about it */
        }

        $email_to = get_bloginfo('admin_email'); // Tps admin email
        $assignment_course_edit_link = $assignment_course ?
            '<a href="'.admin_url('post.php?action=edit&post='.$assignment_course->ID).'">('.__('edit').')</a>' : '';
        $assignment_block_edit_link = $assignment_course ?
            '<a href="'.admin_url('post.php?action=edit&post='.$assignment_block->ID).'">('.__('edit').')</a>' : '';

        $email_title = __('New gamer comment for an assignment', 'tps');
        $email_text = sprintf(__("Hello from %s!<br><br>

There is a new gamer's comment for an assignment.<br><br>

<ul>
<li><strong>Tile/block:</strong> %s %s / %s %s</li>
<li><strong>Assignment:</strong> %s %s</li>
<li><strong>The comment author:</strong> %s</li>
<li><strong>The comment added:</strong> %s %s</li>
</ul>
<br>
Yours,<br>
%s", 'tps'),
            $site_name,
            $assignment_course_link,
            $assignment_course_edit_link,
            $assignment_block_link,
            $assignment_block_edit_link,
            '<a href="'.get_permalink($assignment_post).'">'.get_the_title($assignment_post).'</a>',
            '(<a href="'.admin_url('post.php?action=edit&post='.$assignment_post->ID).'">'.__('edit').'</a>)',
            $comment_author->display_name,
            $comment_data['comment_content'],
            '(<a href="'.admin_url('comment.php?action=editcomment&c='.$comment_id).'">'.__('edit').'</a>)',
            $site_name
        );

        add_filter('wp_mail_content_type', 'tps_emails_set_html_content_type');
        $res = wp_mail(
            $email_to,
            $email_title,
            $email_text,
            array('From: '.$site_name.' <no_reply@kurs.te-st.ru>', 'Content-Type: text/html; charset=UTF-8')
        );
        remove_filter('wp_mail_content_type', 'tps_emails_set_html_content_type');
    
        if( !$res ) {
            /** @todo Email wasn't sent - write to some log about it */
        }
    }
}

add_action('learndash_assignment_approved', 'tps_notify_on_assignment_approved');
function tps_notify_on_assignment_approved($assignment_id) {

    $assignment_post = get_post($assignment_id);
    if($assignment_post->post_type !== 'sfwd-assignment') { // Comment not on assignment post
        return;
    }

    $assignment_module_id = (int)get_post_meta($assignment_id, 'course_id', true);
    $assignment_course = $assignment_module_id ? \Teplosocial\models\Course::get_by_module($assignment_module_id) : false;
	
	$assignment_course_title = __('Unknown tile', 'tps');
	if($assignment_course) {
        $assignment_course_title = get_the_title($assignment_course);
	}
	
    $assignment_course_link = $assignment_course ?
        '<a href="'.get_permalink($assignment_course).'">'.$assignment_course_title.'</a>' : $assignment_course_title;

    $assignment_block = (int)get_post_meta($assignment_id, 'lesson_id', true);
    $assignment_block = $assignment_block ? get_post($assignment_block) : false;

    $assignment_block_link = $assignment_block ?
        '<a href="'.get_permalink($assignment_block).'">'.get_the_title($assignment_block).'</a>' :
        __('Unknown block', 'tps');

    $site_name = get_bloginfo('name');

    $assignment_uploading_gamer = get_user_by('id', $assignment_post->post_author);
    if( !$assignment_uploading_gamer || !$assignment_uploading_gamer->user_email ) {
        return; /** @todo Can't send an email to an unknown gamer - write to some log about it */
    }

    $user_first_name = get_user_meta($assignment_uploading_gamer->ID, Student::META_FIRST_NAME, true);

    $atvetka_data = [
        'mailto' => $assignment_uploading_gamer->user_email,
        'email_placeholders' => [
            '{user_first_name}' => $user_first_name,
            '{comment}' => $comment_data['comment_content'],
            '{course_link}' => $assignment_course_link,
            '{block_link}' => $assignment_block_link,
            '{assignment_link}' => '<a href="'.get_permalink($assignment_post).'">'.get_the_title($assignment_post).'</a>',
        ],
    ];
    $mail_slug = 'your_assignment_approved';
    do_action('atv_email_notification', $mail_slug, $atvetka_data);
}

function tps_change_mail_from($from_email) {
    return "no_reply@kurs.te-st.ru";
}
add_filter('wp_mail_from', 'tps_change_mail_from');

function tps_change_mail_from_name($from_name) {
    return get_bloginfo('name');
}
add_filter('wp_mail_from_name', 'tps_change_mail_from_name');
