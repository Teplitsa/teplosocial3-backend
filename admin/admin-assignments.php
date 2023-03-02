<?php

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
        delete_post_meta($_REQUEST['post'], \Teplosocial\models\Assignment::META_APPROVAL_STATUS);
        // update_post_meta($_REQUEST['post'], \Teplosocial\models\Assignment::META_APPROVAL_STATUS, '');
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
            'key' => \Teplosocial\models\Assignment::META_APPROVAL_STATUS,
            'value' => '',
            'compare' => 'NOT EXISTS'
        )
    );
    return $meta;
}


function assignment_posts_filter( $query ){

    //var_dump($query);
    global $pagenow;

    if(empty($_GET['post_type'])) {
        return;
    }

    if (
        $_GET['post_type'] == \Teplosocial\models\Assignment::$post_type
        && is_admin()
        && $pagenow == 'edit.php'
        && !empty($_GET['tps_assignment_declined'])
        && $query->is_main_query()
    ) {
        $query->set('meta_query', set_assignment_query_meta(\Teplosocial\models\Assignment::META_DECLINE_ASSIGNMENT, 1));
    } else if (
        $_GET['post_type'] == \Teplosocial\models\Assignment::$post_type
        && is_admin()
        && $pagenow == 'edit.php'
        && !empty($_GET['tps_assignment_review'])
        && $query->is_main_query()
    ) {

        $query->set('meta_query', array(
            'relation' => 'AND',
            array(
                'key' => \Teplosocial\models\Assignment::META_DECLINE_ASSIGNMENT,
                'value' => '',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => \Teplosocial\models\Assignment::META_APPROVAL_STATUS,
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
        'meta_key' => \Teplosocial\models\Assignment::META_APPROVAL_STATUS,
        'meta_value' => 1
    ));

    $declined_query = new WP_Query( array(
        'post_type'   =>\Teplosocial\models\Assignment::$post_type,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'AND',
                array(
                    'key' => \Teplosocial\models\Assignment::META_DECLINE_ASSIGNMENT,
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => \Teplosocial\models\Assignment::META_APPROVAL_STATUS,
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
                'key' => \Teplosocial\models\Assignment::META_DECLINE_ASSIGNMENT,
                'value' => '',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => \Teplosocial\models\Assignment::META_APPROVAL_STATUS,
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


function tps_user_task_action_row($actions, $post){
    
    if ($post->post_type == \Teplosocial\models\Assignment::$post_type) {
        if(!$post->approval_status && (!isset($_GET['post_status']) || $_GET['post_status'] != 'trash')) {
            $nonce = wp_create_nonce( 'decline-assignment-action-'.$post->ID );
            $actions['decline'] = '<a href="'.admin_url( sprintf('admin-post.php?action=decline_task&assignment_id=%d&tps-decline-assignment-nonce=%s', $post->ID, $nonce)).'">'.__('Decline', 'tps').'</a>';
        }
    }
    
    return $actions;
}
add_filter('post_row_actions', 'tps_user_task_action_row', 10, 2);


function tps_user_task_action_decline() {
    
    //echo "<pre>";
    //print_r($_REQUEST);

    $assignment_id = empty($_REQUEST['assignment_id']) ? 0 : (int)$_REQUEST['assignment_id'];
    $nonce = empty($_REQUEST['tps-decline-assignment-nonce']) ? '' : $_REQUEST['tps-decline-assignment-nonce'];
    
    //echo "assignment_id={$assignment_id}\n";
    
    if( !wp_verify_nonce($nonce, 'decline-assignment-action-' . $assignment_id) ) {
        die(esc_html__('Wrong data given.', 'tps'));
    }
    
    if(!$assignment_id) {
        die(esc_html__('Wrong data given.', 'tps'));
    }
    
    $block_id = \Teplosocial\models\Assignment::get_assignment_block_id($assignment_id);
    \Teplosocial\models\Assignment::set_decline_block_assignment_flag($assignment_id);
    \Teplosocial\models\Assignment::decline_assignment($assignment_id, $block_id);
    \Teplosocial\models\Block::set_block_main_assignment($block_id, $assignment_id);
    
    $redirect_url = wp_get_referer();
    if(!$redirect_url) {
        $redirect_url = admin_url('/edit.php?post_type=sfwd-assignment');
    }
    wp_redirect( add_query_arg( array('tps-decline-assignment-notice' => 'tps_user_task_admin_notice__assignment_declined'), $redirect_url) );
}
add_action( 'admin_post_decline_task', 'tps_user_task_action_decline' );


function tps_provide_decline_assignment_notice() {
    $admin_notice = isset($_GET['tps-decline-assignment-notice']) ? $_GET['tps-decline-assignment-notice'] : null;
    if($admin_notice) {
        add_action( 'admin_notices', $admin_notice );
    }
}
add_action( 'admin_init', 'tps_provide_decline_assignment_notice' );


function tps_user_task_admin_notice__assignment_declined() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Assignment declined. User can re-uploaded it now.', 'tps' )?></p>
    </div>
    <?php
}
