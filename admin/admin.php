<?php

require_once get_template_directory() . '/admin/admin-utility-functions.php';
require_once get_template_directory() . '/admin/actions.php';
require_once get_template_directory() . '/admin/admin-assignments.php';
require_once get_template_directory() . '/admin/admin-statistics.php';
require_once get_template_directory() . '/admin/admin-users-activity.php';
require_once get_template_directory() . '/admin/page-reviews.php';

add_filter('set-screen-option', function($status, $option, $value) {

    if($option === 'admin_users_activity_modules_items_per_page' && absint($value)) {
        update_user_option(get_current_user_id(), 'admin_users_activity_modules_items_per_page', absint($value));
    }

    return $value;

}, 10, 3);

class Tps_Admin_Setup {

    /** @var WP_List_Table */
    protected static $_users_activity_modules_list_table = null;

    public static function users_activity_modules_list_screen_options() {

        add_screen_option('per_page', [
            'label' => 'Строк на странице',
            'default' => 20,
            'option' => 'admin_users_activity_modules_items_per_page',
        ]);

        require_once get_template_directory().'/admin/admin-lists/tps-class-admin-users-activity-modules-list-table.php';

        self::$_users_activity_modules_list_table = new Tps_Admin_Users_Activity_Modules_List_Table();

    }

    // Users activity: Users Modules display:
    public static function users_activity_modules_list_screen() {

        if( !current_user_can('manage_options') ) {
            wp_die(__('You do not have permissions to access this page.', 'leyka'));
        }

        do_action('tps_pre_users_activity_modules_list_actions');?>

        <div class="wrap">

            <h1 class="wp-heading-inline">Активность студентов - модули</h1>
            <a href="<?php echo admin_url(sprintf('admin.php?%s', http_build_query($_GET))).'&export=1';?>" class="page-title-action">Экспорт</a>

            <div id="poststuff">
            <div>

                <div id="post-body-content" class="<?php if(self::$_users_activity_modules_list_table->get_items_count() === 0) {?>empty-list<?php }?>">
                    <div class="meta-box-sortables ui-sortable">
                        <form method="get" action="#">

                            <input type="hidden" name="page" value="tps_users_activity_modules">

                            <?php self::$_users_activity_modules_list_table->prepare_items();
                            self::$_users_activity_modules_list_table->display();

                            if(self::$_users_activity_modules_list_table->has_items()) {
                                self::$_users_activity_modules_list_table->bulk_edit_fields();
                            }?>

                        </form>
                    </div>
                </div>

            </div>

        </div>

        <?php do_action('tps_post_users_activity_modules_list_actions');

    }
    // Users activity: Users Modules display - END

}

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

    add_submenu_page(
        'learndash-lms',
        'Статистика',
        'Статистика',
        'manage_options',
        'tps_statistics',
        'tps_admin_statistics_page_display',
    );

    $hook = add_submenu_page(
        'learndash-lms',
        'Активность - модули',
        'Активность - модули',
        'manage_options',
        'tps_users_activity_modules',
        ['Tps_Admin_Setup', 'users_activity_modules_list_screen'],
    );
    add_action("load-$hook", ['Tps_Admin_Setup', 'users_activity_modules_list_screen_options']);

}
add_action('admin_menu', 'tps_menu_setup', 50);

function tps_admin_actions_page_display() {
	include( get_template_directory().'/admin/page-actions.php' );
}

// course reviews
function tps_feedback_page_register() {
    add_submenu_page(
        'learndash-lms',
        'Теплица.Курсы - Обратная связь',
        'Обратная связь',
        'manage_options',
        'tps-feedback',
        'TPS_Feedback_List::tps_feedback_page_content'
    );
}
add_action( 'admin_menu', 'tps_feedback_page_register', 1001 );

// link to user profile
function tps_add_login_as_student_link($actions, $user)
{
    $href = admin_url("?mock-login=" . $user->user_nicename);
    $actions['login_as_student'] = '<a href="' . $href . '" target="_blank">Войти как студент</a>';

    return ($actions) ;
}
add_filter ('user_row_actions', 'tps_add_login_as_student_link', 10, 2) ;

function tps_load_admin_scripts() {
    $url = get_template_directory_uri();
    wp_enqueue_style( 'tps-admin', $url . '/assets/css/admin.css', null );
}
add_action( 'admin_enqueue_scripts', 'tps_load_admin_scripts', 30 );

// For Datepickers:
add_action('admin_enqueue_scripts', function(){

    if(isset($_GET['page']) && in_array($_GET['page'], ['tps_statistics', 'tps_users_activity_modules', 'tps_users_activity_courses', 'tps_users_activity_tracks',])) {

        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script(
            'jquery-ui-datepicker-range-extension',
            get_template_directory_uri().'/assets/js/jquery.datepicker.extension.range.min.js',
            ['jquery-ui-datepicker',]
        );

        $wp_scripts = wp_scripts();

        wp_enqueue_style(
            'jquery-ui-theme-smoothness',
            sprintf(
                'https://ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/base/jquery-ui.css',
                $wp_scripts->registered['jquery-ui-core']->ver
            )
        );

    }

}, 1);

function tps_admin_stats_footer_scripts() {

    if(isset($_GET['page']) && in_array($_GET['page'], ['tps_statistics', 'tps_users_activity_modules', 'tps_users_activity_courses', 'tps_users_activity_tracks',])) {?>
        <script type="text/javascript">

            jQuery(function($){

                $('.tps-admin-datepicker').each(function(){

                    let $this = $(this);

                    $this.datepicker({
                        dateFormat: 'dd.mm.yy',
                        changeMonth: true,
                        changeYear: true,
                        minDate: $this.data('min-date'),
                        maxDate: $this.data('max-date'),
                        defaultDate: $this.data('default-date'),
                        altFormat: 'yy-mm-dd',
                        altField: $this.data('formatted-value-field'),
                    });

                });

                // Ranged (optionally) datepicker fields for admin lists filters:
                jQuery.fill_datepicker_input_period = function fill_datepicker_input_period(inst, extension_range) {
            
                    let input_text = extension_range.startDateText;
                    if(extension_range.endDateText && extension_range.endDateText !== extension_range.startDateText) {
                        input_text += ' - '+extension_range.endDateText;
                    }
                    $(inst.input).val(input_text);
            
                };
            
                jQuery.admin_filter_datepicker_ranged = function($input /*, options*/){
            
                    $input.datepicker({
                        range: 'period',
                        onSelect:function(dateText, inst, extensionRange){
                            $.fill_datepicker_input_period(inst, extensionRange);
                        },
            
                        beforeShow: function(input, instance) {
            
                            let selectedDatesStr = $(input).val(),
                                selectedDatesStrList = selectedDatesStr.split(' - '),
                                selectedDates = [];
            
                            for(let i in selectedDatesStrList) {
            
                                if(selectedDatesStrList[i]) {
            
                                    let singleDate;
                                    try {
                                        singleDate = $.datepicker
                                            .parseDate($(input).datepicker('option', 'dateFormat'), selectedDatesStrList[i]);
                                    } catch {
                                        singleDate = new Date();
                                    }
            
                                    selectedDates.push(singleDate);
            
                                }
            
                            }
            
                            $(instance.input).val(selectedDates[0]);
                            $(instance.input).datepicker('setDate', selectedDates);
            
                            setTimeout(function(){
                                $.fill_datepicker_input_period(instance, $(instance.dpDiv).data('datepickerExtensionRange'));
                            });
            
                        }
                    });
            
                };
                // Ranged (optionally) datepicker fields for admin lists filters - END
            
                // Ranged datepicker fields (for admin list filters mostly):
                $.admin_filter_datepicker_ranged($('input.tps-datepicker-ranged-selector'), {
                    warningMessage: 'Некорректно указана дата'
                });
                // Ranged datepicker fields - END

            });

        </script>
    <?php }?>

    <?php
}
add_action('admin_footer', 'tps_admin_stats_footer_scripts');
// For Datepickers - END