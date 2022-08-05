<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\Adaptest;
use \Teplosocial\models\Quiz;
use \Teplosocial\models\Course;
use \Teplosocial\models\Module;

class AdaptestHooks
{
    public static function init_quiz_adaptest_option($setting_option_fields = array(), $settings_metabox_key = '') {
        if ( 'learndash-quiz-access-settings' === $settings_metabox_key ) {

            $post_id = get_the_ID();
            $is_adaptest = get_post_meta( $post_id, Quiz::META_IS_ADAPTEST, true );
            if ( empty( $is_adaptest ) ) {
                $is_adaptest = '';
            }

            if ( ! isset( $setting_option_fields[Quiz::META_IS_ADAPTEST] ) ) {
                $setting_option_fields['is-adaptive-field'] = array(
                    'name'      => 'isAdaptive',
                    'type'      => 'checkbox-switch',
                    'label'     => esc_html__('Адаптационный тест', 'tps'),
                    'value'     => $is_adaptest,
                    'default'   => '',
                    'help_text' => esc_html__('Адаптационный тест', 'tps'),
                    'options'   => array(
                        ''   => '',
                        'on' => ''
                    ),
                    'rest'      => array(
                        'show_in_rest' => \LearnDash_REST_API::enabled(),
                        'rest_args'    => array(
                            'schema' => array(
                                'field_key' => 'is_adaptest',
                                'type'      => 'boolean',
                                'default'   => false,
                            ),
                        ),
                    ),
                );
            }
        }

        return $setting_option_fields;
    }

    public static function init_course_selector($setting_option_fields = array(), $settings_metabox_key = '') {
        // error_log("metabox_key: " . $settings_metabox_key);

        if ( 'learndash-quiz-access-settings' === $settings_metabox_key ) {

            $courses = Course::get_list();
            $courses_options = ["" => __("Select an option", "tps")];
            foreach($courses as $course) {
                $courses_options[$course->ID] = $course->post_title;
            }

            $post_id = get_the_ID();
            // error_log("post_id: " . $post_id);

            $value = intval(get_post_meta( $post_id, Adaptest::META_ADAPTEST_COURSE, true ));
            // error_log("value: " . $value);

            $setting_option_fields[Adaptest::META_ADAPTEST_COURSE] = array(
                'name'      => Adaptest::META_ADAPTEST_COURSE,
                'type'      => 'select',
                'label'     => 'Курс адаптационного теста',
                'help_text' => 'Выберите курс, для которого этот тест будет адаптационным.',
                'value'     => $value,
                'options'   => $courses_options,
            );

            $value = intval(get_post_meta( $post_id, Adaptest::META_ADAPTEST_DURATION, true ));
            $setting_option_fields[Adaptest::META_ADAPTEST_DURATION] = array(
                'name'      => Adaptest::META_ADAPTEST_DURATION,
                'type'      => 'number',
                'label'     => 'Продолжительность (минут)',
                'value'     => $value,
            );
        }
        
        return $setting_option_fields;
    }

    public static function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
        // error_log("_POST: " . print_r($_POST, true));
        // error_log("settings_values: " . print_r($settings_values, true));

        $quiz_id = get_the_ID();

        if($quiz_id && !empty($_POST)) {
            if(isset($_POST['learndash-quiz-access-settings'])) {
                $course_id = intval($_POST['learndash-quiz-access-settings'][Adaptest::META_ADAPTEST_COURSE]);
                Adaptest::set_course_adaptest($course_id, $quiz_id);

                $duration = intval($_POST['learndash-quiz-access-settings'][Adaptest::META_ADAPTEST_DURATION]);
                update_post_meta($quiz_id, Adaptest::META_ADAPTEST_DURATION, $duration);

                if($course_id) {
                    $settings_values['passingpercentage'] = 0;
                }
            }
        }

        return $settings_values;
    }

    public static function show_question_module_selector($question_id, $quiz_id, $quiz_pro_id) {
        if(!Adaptest::is_quiz_adaptest($quiz_id)) {
            return;
        }

        $course_id = Adaptest::get_course_id($quiz_id);
        // \error_log("course_id: " . $course_id);
        $modules = Module::get_list_by_course($course_id);
        // \error_log("modules: " . count($modules));

        $options = [0 => __("Выберите модуль", "tps")];
        foreach($modules as $module) {
            $options[$module->ID] = $module->post_title;
        }

        // \error_log("passed question_id: " . $question_id);
        if(!$question_id && isset($_GET['questionId'])) {
            $question_id = intval(@$_GET['questionId']);
        }

        // \error_log("question_id: " . $question_id);
        if($question_id) {
            $qm = Adaptest::get_questions_modules($quiz_id);
            // \error_log("qm: " . print_r($qm, true));
            $selected_module = $qm[$question_id];
        }
        else {
            $selected_module = 0;
        }
        // \error_log("selected_module: " . $selected_module);

        ?>
        <div class="postbox">
            <h3 class="hndle"><?php esc_html_e( 'Модуль, который будет закрыт в случае правильного ответа', 'tps' ); ?></h3>
            <div class="inside">
                <select name="tps_adaptest_question_module">
                    <?php foreach($options as $k => $option): ?>
                        <option value="<?php echo $k?>" <?php echo $selected_module === $k ? "selected" : ""?>><?php echo $option?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    public static function save_quiz_question_module($question_id) {
        // \error_log("save_quiz_question_module...");        
        // \error_log("question_id: " . $question_id);

        $quiz_id = $_REQUEST['post_id'];
        // \error_log("quiz_id: " . $quiz_id);

        if(!$quiz_id) {
            return;
        }

        $quiz = get_post($quiz_id);

        if( !$quiz ) {
            return;
        }
    
        // \error_log("post_type: " . $quiz->post_type);

        if ( Quiz::$post_type != $quiz->post_type ) {
            return;
        }
        
        if( !Adaptest::is_quiz_adaptest($quiz_id) ) {
            return;
        }

        if(isset($_POST['tps_adaptest_question_module'])) {
            // \error_log("saving tps_adaptest_question_module...");
            $module_id = \intval($_POST['tps_adaptest_question_module']);
            // \error_log("module_id: " . $module_id);
            Adaptest::save_question_module($quiz_id, $question_id, $module_id);
        }
    }

    public static function handle_complete_adaptest_quiz($statisticRefMapper_id) {
        // \error_log("handle_complete_adaptest_quiz....");
        // \error_log("_POST: " . print_r($_POST, true));

        if(!isset($_POST['results']) || !isset($_POST['quiz'])) {
            return;
        }

        $results = $_POST['results'];
        $quiz_id = intval($_POST['quiz']);

        if(!$quiz_id || empty($results)) {
            return;
        }

        $user_id = \get_current_user_id();
        $adaptest_course_id = Adaptest::get_course_id($quiz_id);
        $is_adaptest = \boolval($adaptest_course_id);

        // \error_log("[complete_quiz] quiz_id:" . $quiz_id);
        // \error_log("[complete_quiz] user_id:" . $user_id);
        // \error_log("[complete_quiz] course_id:" . $course_id);        

        if(!$is_adaptest) {
            return;
        }

        if(!$user_id) {
            return;
        }

        $modules_to_complete = [];

        foreach($results as $k => $v) {
            $question_id = intval($k);
            $is_correct = boolval($v['correct']);

            // \error_log("[complete_quiz] question_id:" . $question_id);
            // \error_log("[complete_quiz] correct:" . $is_correct);

            $question_module_id = Adaptest::get_question_module_id($quiz_id, $question_id);
            if(!$question_module_id) {
                continue;
            }

            if(isset($modules_to_complete[$question_module_id])) {
                $modules_to_complete[$question_module_id] = $modules_to_complete[$question_module_id] && $is_correct;
            }
            else {
                $modules_to_complete[$question_module_id] = $is_correct;                
            }
        }

        // \error_log("[complete_quiz] modules_to_complete:" . print_r($modules_to_complete, true));
        foreach($modules_to_complete as $module_id => $must_complete)
        {
            if(!$must_complete) {
                continue;
            }

            Adaptest::complete_module($module_id, $user_id);            
        }

        Adaptest::complete_course_adaptest($adaptest_course_id, $user_id);
    }
}

// add_filter( 'learndash_settings_fields', '\Teplosocial\hooks\AdaptestHooks::init_quiz_adaptest_option', 1, 2 );
add_filter( 'learndash_settings_fields', '\Teplosocial\hooks\AdaptestHooks::init_course_selector', 1, 2 );
add_filter( 'learndash_metabox_save_fields_learndash-quiz-admin-data-handling-settings', '\Teplosocial\hooks\AdaptestHooks::filter_saved_fields', 1, 2 );
add_action( 'tps_show_question_custom_metabox', '\Teplosocial\hooks\AdaptestHooks::show_question_module_selector', 1, 3 );
add_action( 'tps_save_question', '\Teplosocial\hooks\AdaptestHooks::save_quiz_question_module', 10, 1 );
add_action( 'wp_pro_quiz_completed_quiz', '\Teplosocial\hooks\AdaptestHooks::handle_complete_adaptest_quiz', 10, 1 );