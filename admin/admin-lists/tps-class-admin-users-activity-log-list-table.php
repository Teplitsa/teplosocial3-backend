<?php if( !defined('WPINC') ) die;
/** Donors list table class */

if( !class_exists('WP_List_Table') ) {
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

use \Teplosocial\models\Module;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;

class Tps_Admin_Users_Activity_Log_List_Table extends WP_List_Table {

    protected static $_items_count = NULL;

    public function __construct() {

        parent::__construct(['singular' => 'Строка', 'plural' => 'Строки', 'ajax' => true,]);

        add_filter('default_hidden_columns', [$this, 'get_default_hidden_columns'], 10);

        add_filter('leyka_admin_users_activity_log_list_filter', [$this, 'filter_items'], 10, 2);

        if( !empty($_GET['export']) ) {
            $this->_export();
        }

    }

    /**
     * @param $params array
     * @param $filter_type string
     * @return array|false An array of resulting params, or false if the $filter_type is wrong
     */
    public function filter_items(array $params, $filter_type = '') {

        if( !empty($_GET['event_type']) ) {
            $params['event_type'] = trim($_GET['event_type']);
        }

        if( !empty($_GET['user_id']) && absint($_GET['user_id']) ) {
            $params['user_id'] = absint($_GET['user_id']);
        }

        if( !empty($_GET['content_unit']) ) {
            $params['content_unit'] = trim($_GET['content_unit']);
        }

        if( !empty($_GET['date']) ) {

            // Dates period chosen as a str:
            if(is_string($_GET['date']) && mb_stripos($_GET['date'], '-') !== false) {

                $_GET['date'] = array_slice(explode('-', $_GET['date']), 0, 2);

                if(count($_GET['date']) === 2) { // The date is set as an interval
                    $params['date'] = [trim($_GET['date'][0]), trim($_GET['date'][1])];
                }

            } else if(is_array($_GET['date']) && count($_GET['date']) === 2) { // The date is set as an interval
                $params['date'] = [trim($_GET['date'][0]), trim($_GET['date'][1])];
            } else { // Single date chosen
                $params['date'] = trim($_GET['date']);
            }

        }

        if($filter_type !== 'count' && !empty($_GET['orderby']) && !empty($_GET['order']) ) {

            switch($_GET['orderby']) { /** @todo */
                case 'date': $params['order_by'] = 'date'; break;
                case 'user_id': $params['order_by'] = 'user_id'; break;
                case 'user_name': $params['order_by'] = 'user_name'; break;
                case 'event_type':
                case 'content_type':
                    $params['order_by'] = 'event_type';
                    break;
                default:
                    $params['order_by'] = $_GET['orderby'];
            }

            $params['order'] = $_GET['order'];

        }

        return $params;

    }

    /**
     * Retrieve items data from the DB. Items are Donations here.
     *
     * @param int $per_page
     * @param int $page_number
     * @return mixed
     */
    protected static function _get_items($per_page = false, $page_number = 1) {

        $user_activity_rows = tps_get_user_activity_log(
            apply_filters('leyka_admin_users_activity_log_list_filter', [
                'per_page' => $per_page,
                'page_number' => $page_number,
            ])
        );

        foreach($user_activity_rows as &$row) {

            $row['event_id'] = $row['umeta_id'];
            unset($row['umeta_id']);

            $row['date'] = date('d.m.Y H:i:s', $row['meta_value']);
            unset($row['meta_value']);

            $meta_key_parts = explode('_', $row['meta_key']);
            $event_type_id = implode('_', array_slice($meta_key_parts, 0, -1));

            $row['content_unit_id'] = end($meta_key_parts);
            $row['content_unit_title'] = get_the_title($row['content_unit_id']);

            $row['event_type_id'] = $event_type_id;
            $row['event_type'] = tps_get_user_activity_title_by_log_key($event_type_id);
            $row['content_type'] = tps_get_user_activity_content_type_by_log_key($event_type_id);
            unset($row['meta_key']);

        }

        return $user_activity_rows;

    }

    /**
     * Delete a record.
     *
     * @param int $item_id Item ID
     */
    protected static function _delete_item($item_id) {
//        Leyka_Donations::get_instance()->delete_donation(absint($item_id));
    }

    /**
     * @return int
     */
    public static function get_items_count() {

        if(self::$_items_count === NULL) {
            self::$_items_count = tps_get_user_activity_log(apply_filters(
                'leyka_admin_users_activity_log_list_filter', [], 'count'
            ), true);
        }

        return self::$_items_count;

    }

    /** Text displayed when no data is available. */
    public function no_items() {
        echo 'Нет данных';
    }

//    public function single_row($item) {
//
//        echo '<tr class="leyka-donation-'.$item->status.'-row">';
//        $this->single_row_columns($item);
//        echo '</tr>';
//
//    }

    /**
     *  An associative array of columns.
     *
     * @return array
     */
    public function get_columns() {

        $columns = [
//            'cb' => '<input type="checkbox">',
            'id' => __('ID'),
            'date' => 'Дата события',
            'event_type' => 'Тип события',
            'content_type' => 'Тип контента',
            'user' => 'Пользователь',
            'content_unit' => 'Единица контента',
            'parent_content_unit' => 'Род. единица контента',
        ];

        return apply_filters('tps_admin_users_activity_modules_columns_names', $columns);

    }

    public function get_default_hidden_columns($hidden) {
        return array_merge($hidden, []);
    }

    /**
     * @return array
     */
    public function get_sortable_columns() { /** @todo */
        return [
            'id' => ['id', true],
            'date' => ['date', true],
            'event_type' => ['event_type', true],
            'content_type' => ['event_type', true],
            'user' => ['user_id', true],
        ];
    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_id
     * @return mixed
     */
    public function column_default($item, $column_id) {

        switch($column_id) {
            case 'id':
                return $item['event_id'];
            case 'date':
                return empty($item['date']) ? '-' : $item['date'];
            case 'event_type':
                return empty($item['event_type']) ? '-' : $item['event_type'];
            case 'content_type':
                return empty($item['content_type']) ? '-' : $item['content_type'];
            case 'user':
                return '<a href="'.admin_url('user-edit.php?user_id='.$item['user_id']).'">'.absint($item['user_id']).'</a>';
//                    .'. <span>'.$item['display_name'].'</span><div>'.$item['user_email'].'</div>';

            case 'content_unit':
                return empty($item['content_unit_id']) ? '-' : '<a href="'.admin_url('post.php?post='.$item['content_unit_id'].'&action=edit').'">'.$item['content_unit_id'].'</a>. '.$item['content_unit_title'];

            case 'parent_content_unit':

                $parent_post = false;
                switch(tps_get_user_activity_content_type_by_log_key($item['event_type_id'])) {
                    case 'Модуль': $parent_post = Course::get_by_module($item['content_unit_id']); break;
                    case 'Курс': $parent_post = Track::get_by_course($item['content_unit_id']); break;
                    case 'Трек':
                        $parent_post = 'Root';
                    default:

                }

                if($parent_post) {
                    return $parent_post === 'Root' ? 'Root' : '<a href="'.admin_url('post.php?post='.$parent_post->ID.'&action=edit').'">'.$parent_post->ID.'</a>'.'. '.get_the_title($parent_post);
                } else {
                    return '-';
                }
            default:
        }

        return '';

    }

    /**
     * Table filters panel.
     *
     * @param string $which "top" from the upper panel or "bottom" for footer.
     */
    protected function extra_tablenav($which) {

        if($which !== 'top') {
            return;
        }?>

        <div class="alignleft actions">

            <span class="tps-field-wrapper">
                <select name="event_type">
                    <option value="">Тип события</option>

                    <option value="module_started" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'module_started' ? 'selected' : '';?>>Модуль начат</option>
                    <option value="module_completed" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'module_completed' ? 'selected' : '';?>>Модуль завершён</option>
                    <option value="module_completed_by_adaptest" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'module_completed_by_adaptest' ? 'selected' : '';?>>Модуль завершён адаптестом</option>
                    <option value="module_all" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'module_all' ? 'selected' : '';?>>Все события модулей</option>

                    <option value="course_started" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'course_started' ? 'selected' : '';?>>Курс начат</option>
                    <option value="course_completed" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'course_completed' ? 'selected' : '';?>>Курс завершён</option>
                    <option value="course_all" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'course_all' ? 'selected' : '';?>>Все события курсов</option>

                    <option value="track_started" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'track_started' ? 'selected' : '';?>>Трек начат</option>
                    <option value="track_completed" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'track_completed' ? 'selected' : '';?>>Трек завершён</option>
                    <option value="track_all" <?php echo isset($_GET['event_type']) && $_GET['event_type'] == 'track_all' ? 'selected' : '';?>>Все события треков</option>
                </select>
            </span>

            <span class="tps-field-wrapper">
                <?php $value = '';
                if(isset($_GET['date']) && is_array($_GET['date'])) {
                    $value = esc_attr($_GET['date'][0].'-'.$_GET['date'][1]);
                } else if(isset($_GET['date']) && is_string($_GET['date'])) {
                    $value = esc_attr($_GET['date']);
                }?>
                <span class="tps-field-content">
                    <input type="text" name="date" autocomplete="off" class="tps-datepicker-ranged-selector" value="<?php echo $value;?>" placeholder="Дата события">
                </span>
            </span>

            <span class="tps-field-wrapper">
                <input type="text" name="user_id" autocomplete="off" class="" value="<?php echo isset($_GET['user_id']) && absint($_GET['user_id']) ? absint($_GET['user_id']) : '';?>" placeholder="ID пользователя">

                <?php /*?>
                <?php $users = get_users(['orderby' => 'display_name', 'order' => 'ASC', 'fields' => ['ID', 'display_name'],]);?>
                <select name="user_id">
                    <option value="">Пользователь</option>
                    <?php foreach($users as $user) {?>
                        <option value="<?php echo $user->ID;?>" <?php echo isset($_GET['user_id']) && $_GET['user_id'] == $user->ID ? 'selected' : '';?>>
                            <?php echo $user->ID.'. '.$user->display_name;?>
                        </option>
                    <?php }?>
                </select>
                <?php */?>

            </span>

            <?php $modules = Module::get_list(['orderby' => 'title', 'order' => 'ASC',]);
            $courses = Course::get_list(['orderby' => 'title', 'order' => 'ASC',]);
            $tracks = Track::get_list(['orderby' => 'title', 'order' => 'ASC',]);?>

            <span class="tps-field-wrapper">
                <select name="content_unit">

                    <option value="">Единица контента</option>

                <?php if($modules) {?>

                    <optgroup label="Модули (всего: <?php echo count($modules);?>)">
                    <?php foreach($modules as $module) {?>
                        <option value="<?php echo 'module_'.$module->ID;?>" <?php echo isset($_GET['content_unit']) && $_GET['content_unit'] == 'module_'.$module->ID ? 'selected' : '';?>>
                            <?php echo $module->ID.'. '.$module->post_title;?>
                        </option>
                    <?php }?>
                    </optgroup>

                <?php }

                if($courses) {?>

                    <optgroup label="Курсы (всего: <?php echo count($courses);?>)">
                    <?php foreach($courses as $course) {?>
                        <option value="<?php echo 'course_'.$course->ID;?>" <?php echo isset($_GET['content_unit']) && $_GET['content_unit'] == 'course_'.$course->ID ? 'selected' : '';?>>
                            <?php echo $course->ID.'. '.$course->post_title;?>
                        </option>
                    <?php }?>
                    </optgroup>

                <?php }

                if($tracks) {?>

                    <optgroup label="Треки (всего: <?php echo count($tracks);?>)">
                    <?php foreach($tracks as $track) {?>
                        <option value="<?php echo 'track_'.$track->ID;?>" <?php echo isset($_GET['content_unit']) && $_GET['content_unit'] == 'track_'.$track->ID ? 'selected' : '';?>>
                            <?php echo $track->ID.'. '.$track->post_title;?>
                        </option>
                    <?php }?>
                    </optgroup>

                <?php }?>

                </select>
            </span>

            <?php submit_button('Фильтр', '', 'filter_action', false, array( 'id' => 'post-query-submit'));?>

        </div>



    <?php }

    protected function get_views() {

        $base_page_url = admin_url('admin.php?page=leyka_donations');
        $links = ['all' => '<a href="'.$base_page_url.'">'.__('All').'</a>',];

        return $links;

    }

    /**
     * Data query, filtering, sorting & pagination handler.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        $this->process_bulk_action();

        $per_page = $this->get_items_per_page('admin_users_activity_log_items_per_page');

        $this->set_pagination_args(['total_items' => self::get_items_count(), 'per_page' => $per_page,]);

        $this->items = self::_get_items($per_page, $this->get_pagenum());

    }

    protected function display_tablenav($which) {

        if($which === 'top') {
            wp_nonce_field('bulk-'.$this->_args['plural'], '_wpnonce', false);
        }?>

        <div class="tablenav <?php echo esc_attr($which); ?>">

        <?php if($this->has_items()) { ?>
            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions($which); ?>
            </div>
        <?php }

        $this->extra_tablenav($which);
        $this->pagination($which);?>

            <br class="clear">
        </div>

    <?php }

    /**
     * @return array
     */
    public function get_bulk_actions() {
        return [
//            'bulk-delete' => __('Delete'),
        ];
    }

    public function process_bulk_action() {

//        if($this->current_action() === 'delete') { // Single item deletion
//
//            if( !wp_verify_nonce(esc_attr($_REQUEST['_wpnonce']), 'leyka_delete_donation') ) {
//                die(__("You don't have permissions for this operation.", 'leyka'));
//            } else {
//                self::_delete_item(absint($_GET['item']));
//            }
//
//        }
//
//        if( // Bulk donations deletion
//            (isset($_REQUEST['action']) && $_REQUEST['action'] === 'bulk-delete')
//            || (isset($_REQUEST['action2']) && $_REQUEST['action2'] === 'bulk-delete')
//        ) {
//
//            if( !wp_verify_nonce(esc_attr($_REQUEST['_wpnonce']), 'bulk-'.$this->_args['plural']) ) {
//                die(__("You don't have permissions for this operation.", 'leyka'));
//            }
//
//            foreach(esc_sql($_REQUEST['bulk-delete']) as $item_id) {
//                self::_delete_item($item_id);
//            }
//
//        }

    }

    protected function _export() {

        // Just in case that export will require some time:
        ini_set('max_execution_time', 99999);
        set_time_limit(99999);

        ob_start();

        $this->items = apply_filters('tps_admin_users_activity_modules_pre_export', self::_get_items());

        ob_clean();

        $columns = [
            'ID события', 'Дата события', 'Тип события', 'Тип контента', 'ID пользователя', /*'Имя пользователя', 'Email пользователя',*/ 'ID единицы контента', 'Название единицы контента', 'ID род. единицы контента', 'Название род. единицы контента',
        ];

        $rows = [];
//        echo '<pre>'.print_r(array_slice($this->items, 0, 5), 1).'</pre>';
//        return;
        foreach($this->items as $item) {

            $parent_post = false;
            switch(tps_get_user_activity_content_type_by_log_key($item['event_type_id'])) {
                case 'Модуль': $parent_post = Course::get_by_module($item['content_unit_id']); break;
                case 'Курс': $parent_post = Track::get_by_course($item['content_unit_id']); break;
                case 'Трек':
                    $parent_post = 'Root';
                default:

            }

            $parent_post_id = $parent_post ? ($parent_post === 'Root' ? $parent_post : $parent_post->ID) : '-';
            $parent_post_title = $parent_post ? ($parent_post === 'Root' ? $parent_post : get_the_title($parent_post)) : '-';

            $row = [
                $item['event_id'],
                $item['date'],
                $item['event_type'],
                $item['content_type'],
                $item['user_id'],
//                $item['display_name'],
//                $item['user_email'],
                $item['content_unit_id'],
                $item['content_unit_title'],
                $parent_post_id,
                $parent_post_title,
            ];

            $rows[] = apply_filters('tps_admin_users_activity_modules_export_line', $row, $item);

        }

        // It will exit automatically:
        tps_generate_csv(
            'user-activity-log-'.date( get_option('date_format').'-'.str_replace([':'], ['.'], get_option('time_format')) ),
            apply_filters('tps_admin_users_activity_log_export_rows', $rows),
            apply_filters('tps_admin_users_activity_log_export_headers', $columns)
        );

    }

}