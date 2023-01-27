<?php

/**
 * Learndash custom fields
 */

add_filter(
    'learndash_settings_fields',
    function ( $setting_option_fields = array(), $settings_metabox_key = '' ) {
        if ( 'learndash-quiz-display-content-settings' === $settings_metabox_key ) {

            $post_id = get_the_ID();
            $is_adaptive = get_post_meta( $post_id, 'is_adaptive', true );
            if ( empty( $is_adaptive ) ) {
                $is_adaptive = '';
            }

            if ( ! isset( $setting_option_fields['is-adaptive-field'] ) ) {
                $setting_option_fields['is-adaptive-field'] = array(
                    'name'      => 'isAdaptive',
                    'type'      => 'checkbox-switch',
                    'label'     => esc_html__('Адаптивный тест', 'tps'),
                    'value'     => $is_adaptive,
                    'default'   => '',
                    'help_text' => esc_html__('Адаптивный тест', 'tps'),
                    'options'   => array(
                        ''   => '',
                        'on' => ''
                    ),
                    'rest'      => array(
                        'show_in_rest' => LearnDash_REST_API::enabled(),
                        'rest_args'    => array(
                            'schema' => array(
                                'field_key' => 'is_adaptive',
                                'type'      => 'boolean',
                                'default'   => false,
                            ),
                        ),
                    ),
                );
            }
        }

        return $setting_option_fields;
    },
    1,
    2
);

add_filter(
    'learndash_settings_fields',
    function ( $setting_option_fields = array(), $settings_metabox_key = '' ) {
        if ( 'learndash-course-display-content-settings' === $settings_metabox_key ) {

            $post_id = get_the_ID();
            $is_adaptive = get_post_meta( $post_id, 'is_adaptive', true );
            if ( empty( $is_adaptive ) ) {
                $is_adaptive = '';
            }

            if ( ! isset( $setting_option_fields['is-adaptive-field'] ) ) {
                $setting_option_fields['is-adaptive-field'] = array(
                    'name'      => 'prerequisiteList',
                    'type'      => 'multiselect',
                    'label'     => 'Вопросы адаптивного теста',
                    'help_text' => 'Выберите вопросы адаптивного теста, связанного с родительским курсом. Ответы на эти вопросы влияют на отображение данного модуля в родительском курсе.',

                    //@Todo

                    'value'     => '123',
                    'options'   => array(
                        1 => 'Вопрос 1',
                        2 => 'Вопрос 2',
                        3 => 'Вопрос 3',
                    ),
                    'rest'      => array(
                        'show_in_rest' => LearnDash_REST_API::enabled(),
                        'rest_args'    => array(
                            'schema' => array(
                                'field_key' => 'prerequisites',
                                'default'   => array(),
                                'type'      => 'array',
                                'items'     => array(
                                    'type' => 'integer',
                                ),
                            ),
                        ),
                    ),
                );
            }
        }
        return $setting_option_fields;
    },
    1,
    2
);

/**
 *  Customize admin menu
 */
function tps_menu_setup() {

    add_submenu_page( 'learndash-lms', 'Курсы', 'Курсы', 'manage_options', 'edit.php?post_type=tps_course', null, 0 );
    add_submenu_page( 'learndash-lms', 'Треки','Треки', 'manage_options', 'edit.php?post_type=tps_track', null, 0 );

    global $menu;
    foreach($menu as $k => $v) {
        if($v[0] == 'LearnDash LMS') {
            $menu[$k][0] = __('Teploset', 'tps');
        }
    }

    add_submenu_page(
        'learndash-lms',
        __('Actions', 'tps'),
        __('Actions', 'tps'),
        'manage_options',
        'tps_actions',
        'tps_admin_actions_page_display',
    );
}
add_action('admin_menu', 'tps_menu_setup', 50);

// actions
function tps_admin_actions_page_display() {
	include( get_template_directory() . '/admin/admin-page-actions.php' );
}

// certs export
if ( isset($_GET['action'] ) && $_GET['action'] == 'download_cert_csv' )  {
    add_action( 'admin_init', 'tps_cert_csv_export' );
}
function tps_cert_csv_export() {
    if( !current_user_can( 'manage_options' ) ){ return false; }

    if( !is_admin() ){ return false; }

    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_cert_csv' ) ) {
        die( 'Security check error' );
    }

    ob_start();
    $domain = $_SERVER['SERVER_NAME'];
    $filename = 'cerificates-' . $domain . '-' . date('Y-m-d') . '.csv';

    $header_row = array(
        '№',
        'Имя пользователя',
        'Email пользователя',
        'Название курса',
        'Дата',
        'ID пользователя',
    );
    $data_rows = array();
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}certificates ORDER BY ID DESC ";
    $results = $wpdb->get_results( $sql, 'ARRAY_A' );
    foreach ( $results as $result ) {
        $user_info = get_userdata($result['user_id']);
        $row = array(
            $result['ID'],
            $result['user_name'],
            $user_info->user_email,
            $result['course_name'],
            $result['moment'],
            $result['user_id'],
        );
        $data_rows[] = $row;
    }
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );

    ob_end_flush();

    die();
}

// assignmetns
function tps_unappprove_assignment_link( $actions, $post ) {
    //    $actions['approve_assignmentss'] = 'appr-'.\Teplosocial\models\Assignment::is_assignment_approved($post->ID);
    //    $actions['approve_assignmentsss'] = 'decl-'.\Teplosocial\models\Assignment::is_block_assignment_declined($post->ID);
    
    if ( $post->post_type == 'sfwd-assignment' ) {
        if ( learndash_is_assignment_approved_by_meta( $post->ID ) ) {
            $unapprove_url = admin_url( 'edit.php?post_type=sfwd-assignment&tps_ld_action=tps_unappprove_assignment&post=' . $post->ID . '&ret_url=' . rawurlencode( @$_SERVER['REQUEST_URI'] ) );
            $actions['approve_assignment'] = "<a href='".$unapprove_url."' >Отменить одобрение</a>";
        }
    }
    return $actions;
}
add_filter( 'post_row_actions', 'tps_unappprove_assignment_link', 10, 2 );

function tps_unappprove_assignment( $post_id ) {
    if ( ( isset( $_REQUEST['tps_ld_action'] ) ) && ( $_REQUEST['tps_ld_action'] == 'tps_unappprove_assignment') )
        update_post_meta($_REQUEST['post'], 'approval_status', '');
        //\Teplosocial\models\Assignment::set_decline_block_assignment_flag($_REQUEST['post']);
    if ( ! empty( $_REQUEST['ret_url'] ) ) {
        header( 'Location: ' . rawurldecode( $_REQUEST['ret_url'] ) );
        exit;
    }
}
add_action( 'load-edit.php', 'tps_unappprove_assignment' );


function set_assignment_query_meta($key, $value) {
    $meta = array(
        'relation' => 'AND',
        array(
            'key' => $key,
            'value' => $value,
            'compare' => '='
        ),
        array(
            'key' => 'approval_status',
            'value' => '',
            'compare' => 'NOT EXISTS'
        )
    );
    return $meta;
}


function assignment_posts_filter( $query ){

    //var_dump($query);
    global $pagenow;

    if ( $_GET['post_type'] == \Teplosocial\models\Assignment::$post_type && is_admin() && $pagenow=='edit.php' && $_GET['tps_assignment_declined'] && $query->is_main_query()) {
        $query->set('meta_query', set_assignment_query_meta('tps_assignment_declined', 1));
    } elseif ( $_GET['post_type'] == \Teplosocial\models\Assignment::$post_type && is_admin() && $pagenow=='edit.php' && $_GET['tps_assignment_review'] && $query->is_main_query()){
        $query->set('meta_query', array(
            'relation' => 'AND',
            array(
                'key' => 'tps_assignment_declined',
                'value' => '',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'approval_status',
                'value' => '',
                'compare' => 'NOT EXISTS'
            )
        ));
    }

}
add_action( 'pre_get_posts', 'assignment_posts_filter' );

function update_tps_assignment_quicklinks($views) {

    global $current_user, $wp_query;



    $approved_query = new WP_Query( array(
        'post_type'   =>\Teplosocial\models\Assignment::$post_type,
        'post_status' => 'publish',
        'meta_key' => 'approval_status',
        'meta_value' => 1
    ));

    $declined_query = new WP_Query( array(
        'post_type'   =>\Teplosocial\models\Assignment::$post_type,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'AND',
                array(
                    'key' => 'tps_assignment_declined',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'approval_status',
                    'value' => '',
                    'compare' => 'NOT EXISTS'
                )
        )
    ));

    $review_query = new WP_Query( array(
        'post_type'   =>\Teplosocial\models\Assignment::$post_type,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'tps_assignment_declined',
                'value' => '',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'approval_status',
                'value' => '',
                'compare' => 'NOT EXISTS'
            )
        )
    ));

    $assignment_views['tocheck'] = '<a href="edit.php?post_type='.\Teplosocial\models\Assignment::$post_type.'&tps_assignment_review=1">На проверку <span class="count">('.$review_query->found_posts.')</span></a>';
    $assignment_views['reupload'] = '<a href="edit.php?post_type='.\Teplosocial\models\Assignment::$post_type.'&tps_assignment_declined=1">На доработку <span class="count">('.$declined_query->found_posts.')</span></a>';
    $assignment_views['approved'] = '<a href="edit.php?post_type='.\Teplosocial\models\Assignment::$post_type.'&approval_status=1">Одобренные <span class="count">('.$approved_query->found_posts.')</span></a>';
    $assignment_views['all'] = $views['all'];
    $assignment_views['trash'] = $views['trash'];

    return $assignment_views;

}
add_filter('views_edit-sfwd-assignment','update_tps_assignment_quicklinks', 100);