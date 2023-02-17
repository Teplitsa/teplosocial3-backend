<?php if( !defined('WPINC') ) die;
/** Donors list table class */

if( !class_exists('WP_List_Table') ) {
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class Tps_Admin_Users_Activity_Modules_List_Table extends WP_List_Table {

    protected static $_items_count = NULL;

    public function __construct() {

        parent::__construct(['singular' => __('Item', 'leyka'), 'plural' => __('Items', 'leyka'), 'ajax' => true,]);

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

//        if( !empty($_GET['type']) && in_array($_GET['type'], array_keys(leyka_get_payment_types_list())) ) {
//            $params['payment_type'] = $_GET['type'];
//        }

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

//        $params = ['orderby' => 'id', 'order' => 'desc',];
//        if(empty($per_page)) {
//            $params['get_all'] = true;
//        } else {
//            $params = $params + ['results_limit' => absint($per_page), 'page' => absint($page_number),];
//        }
//
//        return get_posts($params);

        return [];

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
            self::$_items_count = 0;
//             = WP_Query(
//                apply_filters('leyka_admin_users_activity_modules_list_filter', [], 'get_users_activity_modules_items_count')
//            );
        }

        return self::$_items_count;

    }

    /** Text displayed when no data is available. */
    public function no_items() {
        _e('No records avaliable.', 'leyka');
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
            'cb' => '<input type="checkbox">',
            'id' => __('ID'),
            'user_name' => 'Имя пользователя',
            'user_email' => 'Email пользователя',
            'module' => 'Название модуля',
            'date_begin' => 'Модуль начат',
            'date_end' => 'Модуль завершён',
        ];

        return apply_filters('leyka_admin_users_activity_modules_columns_names', $columns);

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
        ];
    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_id
     * @return mixed
     */
    public function column_default($donation, $column_id) {

        switch($column_id) {
            case 'id':
            default:
        }

        return '';

    }

    /**
     * Table filters panel.
     *
     * @param string $which "top" from the upper panel or "bottom" for footer.
     */
    protected function extra_tablenav($which) { // The table filters are external - no need for them here
        /** @todo Table filters are displayed here */
    }

//    protected function get_views() {
//
//        $base_page_url = admin_url('admin.php?page=leyka_donations');
//        $links = ['all' => '<a href="'.$base_page_url.'">'.__('All').'</a>',];
//
//        return $links;
//
//    }

    /**
     * Data query, filtering, sorting & pagination handler.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        $this->process_bulk_action();

        $per_page = $this->get_items_per_page('admin_users_activity_modules_per_page');

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