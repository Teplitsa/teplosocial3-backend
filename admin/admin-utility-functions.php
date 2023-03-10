<?php

use \Teplosocial\models\Module;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;

if( !function_exists('tps_get_user_activity_title_by_log_key') ) {
    function tps_get_user_activity_title_by_log_key($key) {
        switch($key) {
            case 'tps_module_started': return 'Начало модуля';
            case 'course_completed': return 'Завершение модуля';
            case 'tps_module_completed_by_adaptest': return 'Завершение модуля адаптестом';
            case 'tps_course_started': return 'Начало курса';
            case 'tps_course_completed': return 'Завершение курса';
            case 'tps_track_started': return 'Начало трека';
            case 'tps_track_completed': return 'Завершение трека';
            default:
                return false;
        }
    }
}

if( !function_exists('tps_get_user_activity_content_type_by_log_key') ) {
    function tps_get_user_activity_content_type_by_log_key($key) {
        switch($key) {
            case 'tps_module_started':
            case 'course_completed':
            case 'tps_module_completed_by_adaptest':
                return 'Модуль';
            case 'tps_course_started':
            case 'tps_course_completed':
                return 'Курс';
            case 'tps_track_started':
            case 'tps_track_completed':
                return 'Трек';
            default:
                return false;
        }
    }
}

function tps_get_user_activity_log(array $options, $query_count = false) {

//    if(empty($activity_data['id']) && empty($activity_data['user_id']) && empty($activity_data['module_post_id'])) {
//        return false;
//    }

    global $wpdb;

    $select_fields = $query_count ?
        "COUNT(*)" :
        "{$wpdb->prefix}usermeta.*, {$wpdb->prefix}users.display_name, {$wpdb->prefix}users.user_email";
    $join = $query_count ?
        "{$wpdb->prefix}usermeta" :
        "{$wpdb->prefix}usermeta JOIN {$wpdb->prefix}users ON {$wpdb->prefix}usermeta.user_id = {$wpdb->prefix}users.ID";

    $line_where = '%d ';
    $fields_where = [1,];

    $order_by = empty($options['order_by']) ? 'ID' : $options['order_by'];
    switch($order_by) {
        case 'date': $order_by = $wpdb->prefix.'usermeta.meta_value'; break;
        case 'event_type': $order_by = $wpdb->prefix.'usermeta.meta_key'; break;
//        case '': break;
//        case '': break;
        case 'id':
        case 'ID':
        default:
            $order_by = $wpdb->prefix.'usermeta.umeta_id';
    }
    $order = empty($options['order']) ? 'DESC' : $options['order'];

    if( !empty($options['id']) ) {

        $line_where .= ' AND ID = %d';
        $fields_where[] = absint($options['id']);

    } else {

        if( !empty($options['user_id']) ) {

            $line_where .= ' AND user_id = %d';
            $fields_where[] = absint($options['user_id']);

        }

        $event_types = [];
        if( !empty($options['event_type']) ) {

            switch($options['event_type']) {
                case 'module_started': $event_types[] = 'tps_module_started_'; break;
                case 'module_completed': $event_types[] = 'course_completed_'; break;
                case 'module_completed_by_adaptest': $event_types[] = 'tps_module_completed_by_adaptest_'; break;
                case 'module_all': array_push($event_types, 'tps_module_started_', 'course_completed_', 'tps_module_completed_by_adaptest_'); break;

                case 'course_started': $event_types[] = 'tps_course_started_'; break;
                case 'course_completed': $event_types[] = 'tps_course_completed_'; break;
                case 'course_all': array_push($event_types, 'tps_course_started_', 'tps_course_completed_'); break;

                case 'track_started': $event_types[] = 'tps_track_started_'; break;
                case 'track_completed': $event_types[] = 'tps_track_completed_'; break;
                case 'track_all': array_push($event_types, 'tps_track_started_', 'tps_track_completed_'); break;
                default:
            }

        }

        if(empty($event_types)) { // All available event types, if not set explicitly
            $event_types = ['tps_module_started_', 'course_completed_', 'tps_module_completed_by_adaptest_', 'tps_course_started_', 'tps_course_completed_', 'tps_track_started_', 'tps_track_completed_',];
        }

        $line_where .= ' AND (0';
        foreach($event_types as $event_type) {

            $line_where .= " OR {$wpdb->prefix}usermeta.meta_key LIKE %s";
            $fields_where[] = $event_type.'%';

        }
        $line_where .= ')';

        if( !empty($options['content_unit']) ) {

            $content_unit = explode('_', $options['content_unit']);

            if(count($content_unit) === 2 && absint($content_unit[1])) {

                $content_unit_event_types = [];
                switch($content_unit[0]) {
                    case 'module':
                        $content_unit_event_types = ['tps_module_started_', 'course_completed_', 'tps_module_completed_by_adaptest_',];
                        break;
                    case 'course': $content_unit_event_types = ['tps_course_started_', 'tps_course_completed_',]; break;
                    case 'track': $content_unit_event_types = ['tps_track_started_', 'tps_track_completed_',]; break;
                    default:
                }

                foreach($content_unit_event_types as &$event_type) {
                    $event_type = $event_type.absint($content_unit[1]);
                }

                if($content_unit_event_types) {

                    $line_where .= ' AND (0';
                    foreach($content_unit_event_types as $meta_key) {

                        $line_where .= " OR {$wpdb->prefix}usermeta.meta_key = %s";
                        $fields_where[] = $meta_key;

                    }
                    $line_where .= ')';

                }

            }

        }

        if( !empty($options['date']) ) {

            $dates_interval = [];

            if(is_string($options['date'])) {
                $dates_interval = [
                    strtotime( date('Y-m-d 00:00:00', strtotime($options['date'])) ),
                    strtotime( date('Y-m-d 23:59:59', strtotime($options['date'])) )
                ];
            } else if(is_array($options['date'])) {
                $dates_interval = [
                    strtotime( date('Y-m-d 00:00:00', strtotime($options['date'][0])) ),
                    strtotime( date('Y-m-d 23:59:59', strtotime($options['date'][1])) )
                ];
            }

            if($dates_interval) {
                $line_where .= $wpdb->prepare(
                    " AND {$wpdb->prefix}usermeta.meta_value >= %s 
                    AND {$wpdb->prefix}usermeta.meta_value <= %s",
                    $dates_interval[0], $dates_interval[1]
                );
            }

        }

    }

    $options['per_page'] = empty($options['per_page']) || $options['per_page'] === -1 ?
        false : absint($options['per_page']);
    $options['page_number'] = empty($options['page_number']) ? 1 : absint($options['page_number']);

    $limit = $query_count || !$options['per_page'] ?
        '' : 'LIMIT '.( (($options['page_number']-1)*$options['per_page']).', '.$options['per_page'] );

    $sql = $wpdb->prepare(
        "SELECT $select_fields FROM $join WHERE $line_where ".($query_count ? '' : " ORDER BY $order_by $order")." $limit",
        $fields_where
    );

    if(isset($_GET['tst'])) {
        echo '<pre>'.print_r($sql, 1).'</pre>';
    }

    return $query_count ? $wpdb->get_var($sql) : $wpdb->get_results($sql, ARRAY_A);

}

// WARNING: currently not in use in the admin area (mb, will be used later):
//function tps_get_user_activity_modules(array $activity_data, $query_count = false) {
//
////    if(empty($activity_data['id']) && empty($activity_data['user_id']) && empty($activity_data['module_post_id'])) {
////        return false;
////    }t
//
//    global $wpdb;
//
//    $select_fields = $query_count ?
//        "COUNT(*)" :
//        "{$wpdb->prefix}tps_users_activity_modules.*, {$wpdb->prefix}users.display_name, {$wpdb->prefix}users.user_email";
//    $join = $query_count ?
//        "{$wpdb->prefix}tps_users_activity_modules" :
//        "{$wpdb->prefix}tps_users_activity_modules JOIN {$wpdb->prefix}users ON {$wpdb->prefix}tps_users_activity_modules.user_id = {$wpdb->prefix}users.ID";
//
//    $line_where = '%d ';
//    $fields_where = [1,];
//
//    $order_by = empty($activity_data['order_by']) ? 'ID' : $activity_data['order_by'];
//    $order = empty($activity_data['order']) ? 'DESC' : $activity_data['order'];
//
//    if( !empty($activity_data['id']) ) {
//
//        $line_where .= ' AND ID = %d';
//        $fields_where[] = absint($activity_data['id']);
//
//    } else {
//
//        if( !empty($activity_data['user_id']) ) {
//
//            $line_where .= ' AND user_id = %d';
//            $fields_where[] = absint($activity_data['user_id']);
//
//        }
//
//        if( !empty($activity_data['module_post_id']) ) {
//
//            $line_where .= ' AND module_post_id = %d';
//            $fields_where[] = absint($activity_data['module_post_id']);
//
//        }
//
//        if( !empty($activity_data['course_post_id']) && absint($activity_data['course_post_id']) ) {
//
//            $modules_ids = Module::get_list([
//                'fields' => 'ids',
//                'connected_type' => Module::$connection_course_module,
//                'connected_from' => absint($activity_data['course_post_id']),
//            ]);
//
//            $line_where .= ' AND module_post_id IN ('.implode(',', $modules_ids).')';
//
//        }
//
//        if( !empty($activity_data['track_post_id']) && absint($activity_data['track_post_id']) ) {
//
//            $modules_ids = Module::get_list([ // Find all Modules of found Courses
//                'fields' => 'ids',
//                'connected_type' => Module::$connection_course_module,
//                'connected_from' => Course::get_list([ // Find all Courses of the given Track
//                    'fields' => 'ids',
//                    'connected_type' => Course::$connection_track_course,
//                    'connected_from' => absint($activity_data['track_post_id']),
//                ]),
//            ]);
//
//            $line_where .= ' AND module_post_id IN ('.implode(',', $modules_ids).')';
//
//        }
//
//        if( !empty($activity_data['module_start_date']) ) {
//
//            $dates_interval = [];
//
//            if(is_string($activity_data['module_start_date'])) {
//                $dates_interval = [
//                    date('Y-m-d 00:00:00', strtotime($activity_data['module_start_date'])),
//                    date('Y-m-d 23:59:59', strtotime($activity_data['module_start_date']))
//                ];
//            } else if(is_array($activity_data['module_start_date'])) {
//                $dates_interval = [
//                    date('Y-m-d 00:00:00', strtotime($activity_data['module_start_date'][0])),
//                    date('Y-m-d 23:59:59', strtotime($activity_data['module_start_date'][1]))
//                ];
//            }
//
//            if($dates_interval) {
//                $line_where .= $wpdb->prepare(
//                    " AND {$wpdb->prefix}tps_users_activity_modules.module_start_date >= %s
//                    AND {$wpdb->prefix}tps_users_activity_modules.module_start_date <= %s",
//                    $dates_interval[0], $dates_interval[1]
//                );
//            }
//
//        }
//
//        if( !empty($activity_data['module_end_date']) ) {
//
//            $dates_interval = [];
//
//            if(is_string($activity_data['module_end_date'])) {
//                $dates_interval = [
//                    date('Y-m-d 00:00:00', strtotime($activity_data['module_end_date'])),
//                    date('Y-m-d 23:59:59', strtotime($activity_data['module_end_date']))
//                ];
//            } else if(is_array($activity_data['module_end_date'])) {
//                $dates_interval = [
//                    date('Y-m-d 00:00:00', strtotime($activity_data['module_end_date'][0])),
//                    date('Y-m-d 23:59:59', strtotime($activity_data['module_end_date'][1]))
//                ];
//            }
//
//            if($dates_interval) {
//                $line_where .= $wpdb->prepare(
//                    " AND {$wpdb->prefix}tps_users_activity_modules.module_end_date >= %s
//                    AND {$wpdb->prefix}tps_users_activity_modules.module_end_date <= %s",
//                    $dates_interval[0], $dates_interval[1]
//                );
//            }
//
//        }
//
//    }
//
//    $activity_data['per_page'] = empty($activity_data['per_page']) || $activity_data['per_page'] === -1 ?
//        false : absint($activity_data['per_page']);
//    $activity_data['page_number'] = empty($activity_data['page_number']) ? 1 : absint($activity_data['page_number']);
//
//    $limit = $query_count || !$activity_data['per_page'] ?
//        '' : 'LIMIT '.( (($activity_data['page_number']-1)*$activity_data['per_page']).', '.$activity_data['per_page'] );
//
//    $sql = $wpdb->prepare(
//        "SELECT $select_fields FROM $join WHERE $line_where ".($query_count ? '' : " ORDER BY $order_by $order")." $limit",
//        $fields_where
//    );
//
//    if(isset($_GET['tst'])) {
//        echo '<pre>'.print_r($sql, 1).'</pre>';
//    }
//
//    return $query_count ? $wpdb->get_var($sql) : $wpdb->get_results($sql, ARRAY_A);
//
//}
//
//function tps_update_user_activity_modules($user_id, $module_post_id, array $activity_data = []) {
//
//    $user_id = absint($user_id);
//    $module_post_id = absint($module_post_id);
//
//    if( !$user_id || !$module_post_id ) {
//        return false;
//    }
//
//    global $wpdb;
//
//    $fields = [];
//
//    if( !empty($activity_data['module_start_date']) ) {
//        $fields['module_start_date'] = trim($activity_data['module_start_date']);
//    } else if(Module::is_started_by_user($module_post_id, $user_id)) { // module_start_date isn't given, get it from usermeta
//
//        $module_started_timestamp = get_user_meta($user_id, Module::USER_META_MODULE_STARTED.$module_post_id, true);
//        $fields['module_start_date'] = date('Y-m-d H:i:s', $module_started_timestamp);
//
//    }
//
//    if( !empty($activity_data['module_end_date']) ) {
//        $fields['module_end_date'] = trim($activity_data['module_end_date']);
//    } else if(Module::is_completed_by_user($module_post_id, $user_id)) { // module_end_date isn't given, get it from usermeta
//
//        $module_completed_timestamp = get_user_meta($user_id, Module::USER_META_MODULE_COMPLETED.$module_post_id, true);
//        $fields['module_end_date'] = date('Y-m-d H:i:s', $module_completed_timestamp);
//
//    }
//
//    $user_activity_id = $wpdb->get_var($wpdb->prepare(
//        "SELECT ID FROM {$wpdb->prefix}tps_users_activity_modules
//        WHERE user_id=%d AND module_post_id=%d",
//        [$user_id, $module_post_id]
//    ));
//
//    if($user_activity_id) { // User activity row already exists - update it
//
//        $result = $wpdb->update("{$wpdb->prefix}tps_users_activity_modules", $fields, ['ID' => $user_activity_id]);
//
//    } else { // Add new user activity row
//
//        $fields = array_merge($fields, ['user_id' => $user_id, 'module_post_id' => $module_post_id,]);
//
//        $result = $wpdb->insert("{$wpdb->prefix}tps_users_activity_modules", $fields);
//
//    }
//
//    return $result > 0;
//
//}
//
//function tps_delete_user_activity_modules(array $activity_data) {
//
//    if(empty($activity_data['id']) && empty($activity_data['user_id']) && empty($activity_data['module_post_id'])) {
//        return false;
//    }
//
//    global $wpdb;
//
//    $fields_where = [];
//    if( !empty($activity_data['id']) ) {
//        $fields_where['ID'] = absint($activity_data['id']);
//    } else {
//
//        if( !empty($activity_data['user_id']) ) {
//            $fields_where['user_id'] = absint($activity_data['user_id']);
//        }
//        if( !empty($activity_data['module_post_id']) ) {
//            $fields_where['module_post_id'] = absint($activity_data['module_post_id']);
//        }
//
//    }
//
//    return $wpdb->delete("{$wpdb->prefix}tps_users_activity_modules", $fields_where);
//
//}
//
//// Users activity DB tables - Courses:
//function tps_update_user_activity_courses($user_id, $course_post_id, array $activity_data = []) {
//
//    $user_id = absint($user_id);
//    $course_post_id = absint($course_post_id);
//
//    if( !$user_id || !$course_post_id ) {
//        return false;
//    }
//
//    global $wpdb;
//
//    $fields = [];
//
//    if( !empty($activity_data['course_start_date']) ) {
//        $fields['course_start_date'] = trim($activity_data['course_start_date']);
//    } else if(Course::is_started_by_user($course_post_id, $user_id)) { // course_start_date isn't given, get it from usermeta
//
//        $course_started_timestamp = get_user_meta($user_id, Course::USER_META_COURSE_STARTED.$course_post_id, true);
//        $fields['course_start_date'] = date('Y-m-d H:i:s', $course_started_timestamp);
//
//    }
//
//    if( !empty($activity_data['course_end_date']) ) {
//        $fields['course_end_date'] = trim($activity_data['course_end_date']);
//    } else if(Course::is_completed_by_user($course_post_id, $user_id)) { // course_end_date isn't given, get it from usermeta
//
//        $course_completed_timestamp = get_user_meta($user_id, Course::USER_META_COURSE_COMPLETED.$course_post_id, true);
//        $fields['course_end_date'] = date('Y-m-d H:i:s', $course_completed_timestamp);
//
//    }
//
//    $user_activity_id = $wpdb->get_var($wpdb->prepare(
//        "SELECT ID FROM {$wpdb->prefix}tps_users_activity_courses
//        WHERE user_id=%d AND course_post_id=%d",
//        [$user_id, $course_post_id]
//    ));
//
//    if($user_activity_id) { // User activity row already exists - update it
//
//        $result = $wpdb->update("{$wpdb->prefix}tps_users_activity_courses", $fields, ['ID' => $user_activity_id]);
//
//    } else { // Add new user activity row
//
//        $fields = array_merge($fields, ['user_id' => $user_id, 'course_post_id' => $course_post_id,]);
//
//        $result = $wpdb->insert("{$wpdb->prefix}tps_users_activity_courses", $fields);
//
//    }
//
//    return $result > 0;
//
//}
//// Users activity DB tables - Courses - END
//
//// Users activity DB tables - Tracks:
//function tps_update_user_activity_tracks($user_id, $track_post_id, array $activity_data = []) {
//
//    $user_id = absint($user_id);
//    $track_post_id = absint($track_post_id);
//
//    if( !$user_id || !$track_post_id ) {
//        return false;
//    }
//
//    global $wpdb;
//
//    $fields = [];
//
//    if( !empty($activity_data['track_start_date']) ) {
//        $fields['track_start_date'] = trim($activity_data['track_start_date']);
//    } else if(Track::is_started_by_user($track_post_id, $user_id)) { // track_start_date isn't given, get it from usermeta
//
//        $track_started_timestamp = get_user_meta($user_id, Track::USER_META_TRACK_STARTED.$track_post_id, true);
//        $fields['track_start_date'] = date('Y-m-d H:i:s', $track_started_timestamp);
//
//    }
//
//    if( !empty($activity_data['track_end_date']) ) {
//        $fields['track_end_date'] = trim($activity_data['track_end_date']);
//    } else if(Track::is_completed_by_user($track_post_id, $user_id)) { // track_end_date isn't given, get it from usermeta
//
//        $track_completed_timestamp = get_user_meta($user_id, Track::USER_META_TRACK_COMPLETED.$track_post_id, true);
//        $fields['track_end_date'] = date('Y-m-d H:i:s', $track_completed_timestamp);
//
//    }
//
//    $user_activity_id = $wpdb->get_var($wpdb->prepare(
//        "SELECT ID FROM {$wpdb->prefix}tps_users_activity_tracks
//        WHERE user_id=%d AND track_post_id=%d",
//        [$user_id, $track_post_id]
//    ));
//
//    if($user_activity_id) { // User activity row already exists - update it
//
//        $result = $wpdb->update("{$wpdb->prefix}tps_users_activity_tracks", $fields, ['ID' => $user_activity_id]);
//
//    } else { // Add new user activity row
//
//        $fields = array_merge($fields, ['user_id' => $user_id, 'track_post_id' => $track_post_id,]);
//
//        $result = $wpdb->insert("{$wpdb->prefix}tps_users_activity_tracks", $fields);
//
//    }
//
//    return $result > 0;
//
//}
// WARNING: currently not in use in the admin area (mb, will be used later) - END

/** Service function to prepare a singular object data value for export as a CSV cell. */
function tps_export_data_prepare($text) {
    return str_replace(['"'], [''], $text);
}

function tps_generate_csv($filename, array $rows, array $headings = [], $column_sep = "\t", $line_sep = "\n") {

    // 1. Use tab as column separator:
    $fputcsv = count($headings) ? '"'.implode('"'.$column_sep.'"', $headings).'"'.$line_sep : '';

    // 2. Loop over the * to export:
    if($rows) {
        foreach($rows as $row_array) {

            array_walk($row_array, function(&$item){ $item = tps_export_data_prepare($item); });

            $fputcsv .= '"'.implode('"'.$column_sep.'"', $row_array).'"'.$line_sep;

        }
    }

    // 3. Output CSV-specific headers:
    header('Content-type: application/vnd.ms-excel');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Pragma: no-cache');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');

    echo chr(255)
        .chr(254)
        .mb_convert_encoding( // 4. PHP array, converted to string - encode it into UTF-16
            $fputcsv,
            apply_filters('leyka_export_content_charset', 'UTF-16LE'),
            'UTF-8'
        );

    exit;

}