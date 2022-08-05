<?php

namespace Teplosocial\models;

class VisitorSession
{
    public static string $table_name = 'visitor_sessions';

    public static function touch($session_id, $user_id)
    {
        global $wpdb;
        if($session_id) {
            $res = $wpdb->update($wpdb->prefix . self::$table_name, [
                'user_id' => $user_id,
                'time_last_touch' => current_time('mysql', true),                
            ], [
                'id' => $session_id,
            ]);
        }
        else {
            $session_id = uniqid("tps:", true);
            $res = $wpdb->insert($wpdb->prefix . self::$table_name, [
                'id' => $session_id,
                'user_id' => $user_id,
            ]);
        }       

        return $session_id ? $session_id : "";
    }

    public static function get_session($session_id)
    {
        if( !$session_id ) {
            return null;
        }

        global $wpdb;
        $table_name = self::$table_name;
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$table_name} WHERE id = %s ", $session_id);
        $session = $wpdb->get_row($sql);
        return $session;
    }
}
