<?php 

namespace Teplosocial\models;

use \Teplosocial\Config;

class Assignment extends Post
{
    public static $post_type = 'sfwd-assignment';
    
    public const META_DECLINE_ASSIGNMENT = 'tps_assignment_declined';
    public const META_APPROVAL_STATUS = 'approval_status';
    public const META_URL = 'tps_assignment_url';
    public const META_TEXT = 'tps_assignment_text';
    public const META_FILE_NAME = 'file_name';
    public const META_FILE_URL = 'file_link';
    public const META_BLOCK_ID = 'lesson_id';

    public const EMPTY_ASSIGNMENT_FILE_NAME = 'tps-no-assignment.html';

    public static function get_assignment_disp_name_from_text_data($url, $text)
    {
        $ret = $url;
        
        if($text) {
            $ret = wp_trim_words( $text, 10 );
        }
        
        return $ret;
    }

    public static function upload($block_id)
    {
        if(!empty($_FILES['uploadfiles']['name'][0])) {
            \learndash_assignment_process_init();
        }
        else {
            $fname              = self::EMPTY_ASSIGNMENT_FILE_NAME;
            $url_link_arr       = wp_upload_dir();
            $dir_link           = $url_link_arr['basedir'];
            $file_path          = $dir_link . '/assignments/';

            if(!file_exists( $file_path . $fname )) {
                file_put_contents( $file_path . $fname, '<!doctype html><html lang="ru"><head><meta charset="utf-8"></head><body><h1>Задание без файла. Проверьте текст и ссылку.</h1></body>' );
            }
        
            \learndash_upload_assignment_init( $block_id, $fname );
        }
        // error_log("learndash_assignment_process_init OK");
    }

    public static function get_assignment_block_id($assignment_id)
    {
        return (int)get_post_meta($assignment_id, self::META_BLOCK_ID, true);
    }
    
    
    public static function is_assignment_approved($assignment_id)
    {
        return (bool)get_post_meta($assignment_id, self::META_APPROVAL_STATUS, true);
    }
    
    
    public static function is_block_assignment_declined($block_id)
    {
        return (bool)get_post_meta($block_id, self::META_DECLINE_ASSIGNMENT, true);
    }
    
    
    public static function decline_assignment($assignment_id, $block_id = null)
    {
        global $wpdb;
        
        if(!$block_id) {
            $block_id = self::get_assignment_block_id($assignment_id);
        }
        self::set_decline_block_assignment_flag($assignment_id);
    }
    
    
    public static function set_decline_block_assignment_flag($block_id)
    {
        update_post_meta($block_id, self::META_DECLINE_ASSIGNMENT, true);
    }
    

    public static function delete_decline_block_assignment_flag($block_id) {
        delete_post_meta($block_id, self::META_DECLINE_ASSIGNMENT);
    }

    public static function copy_assignment_content($from_assignment_id, $to_assignment_id) {
        global $wpdb;

        // $sql = "UPDATE `{$wpdb->prefix}postmeta` AS pmto
        //     JOIN `{$wpdb->prefix}postmeta` AS pmfrom
        //     ON pmfrom.meta_key = pmto.meta_key
        //     AND pmfrom.post_id = %s
        //     SET pmto.meta_value = pmfrom.meta_value
        //     WHERE pmto.post_id = %s AND pmto.meta_key IN ('file_name', 'file_link', 'disp_name', 'tps_assignment_url', 'tps_assignment_text')";
        // $sql = $wpdb->prepare($sql, $from_assignment_id, $to_assignment_id);
        // //echo $sql . "\n\n\n<br /><br /><br />";
        // $wpdb->query($sql);

        $sql = "DELETE FROM `{$wpdb->prefix}posts` WHERE ID = %s";
        $sql = $wpdb->prepare($sql, $to_assignment_id);
        //echo $sql . "\n\n\n<br /><br /><br />";
        $wpdb->query($sql);
        
        $sql = "DELETE FROM `{$wpdb->prefix}postmeta` WHERE post_id = %s";
        $sql = $wpdb->prepare($sql, $to_assignment_id);
        //echo $sql . "\n\n\n<br /><br /><br />";
        $wpdb->query($sql);
        
        $sql = "UPDATE `{$wpdb->prefix}posts` SET ID = %s WHERE ID = %s";
        $sql = $wpdb->prepare($sql, $to_assignment_id, $from_assignment_id);
        //echo $sql . "\n\n\n<br /><br /><br />";
        $wpdb->query($sql);
        
        $sql = "UPDATE `{$wpdb->prefix}postmeta` SET post_id = %s WHERE post_id = %s";
        $sql = $wpdb->prepare($sql, $to_assignment_id, $from_assignment_id);
        //echo $sql . "\n\n\n<br /><br /><br />";
        $wpdb->query($sql);
        
    }    
}
