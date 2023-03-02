<?php if( !defined('WPINC') ) die;
/** Donors list table class */

if( !class_exists('WP_List_Table') ) {
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

use \Teplosocial\models\Module;
use \Teplosocial\models\Course;
use \Teplosocial\models\Track;

class Tps_Admin_Users_Activity_Modules_List_Table extends WP_List_Table {

    protected static $_items_count = NULL;

    public function __construct() {

        parent::__construct(['singular' => 'Строка', 'plural' => 'Строки', 'ajax' => true,]);

        add_filter('default_hidden_columns', [$this, 'get_default_hidden_columns'], 10);

        add_filter('leyka_admin_users_activity_modules_list_filter', [$this, 'filter_items'], 10, 2);

        if( !empty($_REQUEST['list-export']) ) {
            $this->_export();
        }

    }

    /**
     * @param $params array
     * @param $filter_type string
     * @return array|false An array of resulting params, or false if the $filter_type is wrong
     */
    public function filter_items(array $params, $filter_type = '') {

        if( !empty($_GET['course_id']) && absint($_GET['course_id']) ) {
            $params['course_post_id'] = absint($_GET['course_id']);
        }

        if( !empty($_GET['module_id']) && absint($_GET['module_id']) ) {
            $params['module_post_id'] = absint($_GET['module_id']);
        }

        if( !empty($_GET['track_id']) && absint($_GET['track_id']) ) {
            $params['track_post_id'] = absint($_GET['track_id']);
        }

        if( !empty($_GET['date_begin']) ) {

            // Dates period chosen as a str:
            if(is_string($_GET['date_begin']) && mb_stripos($_GET['date_begin'], '-') !== false) {

                $_GET['date_begin'] = array_slice(explode('-', $_GET['date_begin']), 0, 2);

                if(count($_GET['date_begin']) === 2) { // The date is set as an interval
                    $params['module_start_date'] = [trim($_GET['date_begin'][0]), trim($_GET['date_begin'][1])];
                }

            } else if(is_array($_GET['date_begin']) && count($_GET['date_begin']) === 2) { // The date is set as an interval
                $params['module_start_date'] = [trim($_GET['date_begin'][0]), trim($_GET['date_begin'][1])];
            } else { // Single date chosen
                $params['module_start_date'] = trim($_GET['date_begin']);
            }

        }

        if( !empty($_GET['date_end']) ) {

            // Dates period chosen as a str:
            if(is_string($_GET['date_end']) && mb_stripos($_GET['date_end'], '-') !== false) {

                $_GET['date_end'] = array_slice(explode('-', $_GET['date_end']), 0, 2);

                if(count($_GET['date_end']) === 2) { // The date is set as an interval
                    $params['module_end_date'] = [trim($_GET['date_end'][0]), trim($_GET['date_end'][1])];
                }

            } else if(is_array($_GET['date_end']) && count($_GET['date_end']) === 2) { // The date is set as an interval
                $params['module_end_date'] = [trim($_GET['date_end'][0]), trim($_GET['date_end'][1])];
            } else { // Single date chosen
                $params['module_end_date'] = trim($_GET['date_end']);
            }

        }

        if($filter_type !== 'count' && !empty($_GET['orderby']) && !empty($_GET['order']) ) {

            switch($_GET['orderby']) {
                case 'user_name':
                case 'display_name':
                    $params['order_by'] = 'display_name';
                    break;
                case 'email':
                case 'user_email':
                    $params['order_by'] = 'user_email';
                    break;
                case 'date_start':
                case 'date_begin':
                    $params['order_by'] = 'module_start_date';
                    break;
                case 'date_finish':
                case 'date_end':
                    $params['order_by'] = 'module_end_date';
                    break;
                default:
                    $params['order_by'] = 'ID';
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
    protected static function _get_items($per_page, $page_number = 1) {

        return tps_get_user_activity_modules(
            apply_filters('leyka_admin_users_activity_modules_list_filter', [
                'per_page' => $per_page,
                'page_number' => $page_number,
            ])
        );

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
            self::$_items_count = tps_get_user_activity_modules(apply_filters(
                'leyka_admin_users_activity_modules_list_filter', [], 'count'
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
            'user_name' => 'Имя пользователя',
            'user_email' => 'Email пользователя',
            'module' => 'Название модуля',
            'date_begin' => 'Модуль начат',
            'date_end' => 'Модуль завершён',
        ];

        return apply_filters('tps_admin_users_activity_modules_columns_names', $columns);

    }

    public function get_default_hidden_columns($hidden) {
        return array_merge($hidden, []);
    }

    /**
     * @return array
     */
    public function get_sortable_columns() {
        return [
            'id' => ['id', true],
            'user_name' => ['user_name', true],
            'user_email' => ['user_email', true],
            'date_begin' => ['date_begin', true],
            'date_end' => ['date_end', true],
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
                return $item['ID'];
            case 'user_name':
                return empty($item['display_name']) ? 'Имя пользователя неизвестно' : $item['display_name'];
            case 'user_email':
                return empty($item['user_email']) ? 'Email неизвестен' : $item['user_email'];
            case 'date_begin':
                return empty($item['module_start_date']) ? '-' : date('d.m.Y, H:i', strtotime($item['module_start_date']));
            case 'date_end':
                return empty($item['module_end_date']) ? '-' : date('d.m.Y, H:i', strtotime($item['module_end_date']));
            default:
        }

        return '';

    }

    public function column_module($item) { /** @var $item array */

        $module_post = get_post($item['module_post_id']);

        return apply_filters('tps_admin_users_activity_modules_module_column_content', $module_post ? $module_post->post_title : '', $item);

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

            <?php $tracks = Track::get_list(['orderby' => 'title', 'order' => 'ASC',]);?>
            <span class="tps-field-wrapper">
                <select name="track_id">
                    <option value="">Трек</option>
                    <?php foreach($tracks as $track) {?>
                        <option value="<?php echo $track->ID;?>" <?php echo isset($_GET['track_id']) && $_GET['track_id'] == $track->ID ? 'selected' : '';?>>
                            <?php echo $track->post_title;?>
                        </option>
                    <?php }?>
                </select>
            </span>

            <?php $courses = Course::get_list(['orderby' => 'title', 'order' => 'ASC',]);?>
            <span class="tps-field-wrapper">
                <select name="course_id">
                    <option value="">Курс</option>
                    <?php foreach($courses as $course) {?>
                        <option value="<?php echo $course->ID;?>" <?php echo isset($_GET['course_id']) && $_GET['course_id'] == $course->ID ? 'selected' : '';?>>
                            <?php echo $course->post_title;?>
                        </option>
                    <?php }?>
                </select>
            </span>

            <?php $modules = Module::get_list(['orderby' => 'title', 'order' => 'ASC',]);?>
            <span class="tps-field-wrapper">
                <select name="module_id">
                    <option value="">Модуль</option>
                    <?php foreach($modules as $module) {?>
                        <option value="<?php echo $module->ID;?>" <?php echo isset($_GET['module_id']) && $_GET['module_id'] == $module->ID ? 'selected' : '';?>>
                            <?php echo $module->post_title;?>
                        </option>
                    <?php }?>
                </select>
            </span>

            <span class="tps-field-wrapper">
                <?php $value = '';
                if(isset($_GET['date_begin']) && is_array($_GET['date_begin'])) {
                    $value = esc_attr($_GET['date_begin'][0].'-'.$_GET['date_begin'][1]);
                } else if(isset($_GET['date_begin']) && is_string($_GET['date_begin'])) {
                    $value = esc_attr($_GET['date_begin']);
                }?>
                <span class="tps-field-content">
                    <input type="text" name="date_begin" autocomplete="off" class="tps-datepicker-ranged-selector" value="<?php echo $value;?>" placeholder="Дата начала (от/до)">
                </span>
            </span>

            <span class="tps-field-wrapper">
                <?php $value = '';
                if(isset($_GET['date_end']) && is_array($_GET['date_end'])) {
                    $value = esc_attr($_GET['date_end'][0].'-'.$_GET['date_end'][1]);
                } else if(isset($_GET['date_end']) && is_string($_GET['date_end'])) {
                    $value = esc_attr($_GET['date_end']);
                }?>
                <span class="tps-field-content">
                    <input type="text" name="date_end" autocomplete="off" class="tps-datepicker-ranged-selector" value="<?php echo $value;?>" placeholder="Дата завершения (от/до)">
                </span>
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

        $per_page = $this->get_items_per_page('admin_users_activity_modules_items_per_page');

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

        $this->items = apply_filters('tps_admin_users_activity_modules_pre_export', self::_get_items(false));

        ob_clean();

        $columns = [
//            'ID', 'Имя донора', 'Email', 'Тип платежа', 'Плат. оператор', 'Способ платежа', 'Полная сумма', 'Итоговая сумма', 'Валюта', 'Дата пожертвования', 'Статус', 'Кампания', 'Назначение', 'Подписка на рассылку', 'Email подписки', 'Комментарий',
        ];

        $rows = [];
        foreach($this->items as $item) {

            $row = [
//                $item->id,
//                $item->donor_name,
//                $item->donor_email,
//                $item->payment_type_label,
//                $item->gateway_label,
//                $item->payment_method_label,
//                str_replace('.', ',', $item->amount),
//                str_replace('.', ',', $item->amount_total),
//                $currency,
//                $item->date_time_label,
//                $item->status_label,
//                $campaign->title,
//                $campaign->payment_title,
//                $donor_subscription,
//                $item->donor_subscription_email,
//                $item->donor_comment,
            ];

            $rows[] = apply_filters('tps_admin_users_activity_modules_export_line', $row, $item);

        }

        // It will exit automatically:
//        tps_generate_csv( /** @todo Get from Leyka - the leyka_generate_csv() function */
//            'donations-'.date( get_option('date_format').'-'.str_replace([':'], ['.'], get_option('time_format')) ),
//            apply_filters('tps_admin_users_activity_modules_export_rows', $rows),
//            apply_filters('tps_admin_users_activity_modules_export_headers', $columns)
//        );

    }

}