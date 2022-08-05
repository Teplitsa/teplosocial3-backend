<?php

if ( ! defined( 'WPINC' ) )
	die();

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class TPS_Feedback_List extends WP_List_Table
{

    public function prepare_items()
    {
        $per_page = 20;

        $data = $this -> table_data();

        $this -> set_pagination_args( array(
            'total_items' => count($data),
            'per_page'    => $per_page
        ));

        $data = array_slice(
            $data,
            (($this -> get_pagenum() - 1) * $per_page),
            $per_page
        );

        $this -> _column_headers = array(
            $this -> get_columns(),
            $this -> get_hidden_columns(),
            $this -> get_sortable_columns()
        );

        $this -> items = $data;
    }

    public function get_columns()
    {
        return array(
            'mark_id' => 'ID',
            'user_id' => 'Автор',
            'course_id'	=> 'Курс',
            'mark' => 'Оценка',
            'mark_comment' => 'Комментарий',
            'mark_time' => 'Дата',
        );
    }

    private function table_data()
    {
        global $wpdb;

        $table_name = \Teplosocial\models\CourseReview::$table_name;
        $sql = "SELECT * FROM {$wpdb->prefix}{$table_name}";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;

    }

    public function column_default($item, $column_name )
    {
        switch($column_name)
        {
            case 'mark_id':
                return $item[$column_name];
            case 'user_id':
                return '<a href='.get_edit_user_link($item[$column_name]).'>'.get_userdata($item[$column_name])->display_name.'</a>';
            case 'course_id':
                return '<a href='.get_edit_post_link($item[$column_name]).'>'.get_the_title($item[$column_name]).'</a>';
            case 'mark':
            case 'mark_comment':
            case 'mark_time':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public static function tps_feedback_page_content() {
        $Table = new TPS_Feedback_List();
        $Table -> prepare_items();

        ?>
        <div class="wrap">
            <h2>Оценки тайлов</h2>
            <?php $Table -> display(); ?>
            <a href="<?php echo admin_url( 'admin.php?page=tps-feedback' ) ?>&action=download_csv&_wpnonce=<?php echo wp_create_nonce( 'download_csv' )?>" class="page-title-action">Экспорт в CSV</a>
        </div>
        <?php
    }
}
