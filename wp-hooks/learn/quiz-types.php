<?php

namespace Teplosocial\hooks;

use \Teplosocial\models\Adaptest;
use Teplosocial\models\Course;
use \Teplosocial\models\Quiz;
//use \Teplosocial\models\Course;
//use \Teplosocial\models\Module;

class QuizTypesHooks {

    // Quiz type field - addition. The field is simple, so LD fields feature/hook are used:
//    public static function init_quiz_type_field($setting_option_fields = array(), $settings_metabox_key = '') {
//
//        if('learndash-quiz-access-settings' === $settings_metabox_key) {
//
//            $quiz_post_id = get_the_ID();
//            $value = get_post_meta($quiz_post_id, 'tps_quiz_type', true); // TODO Add meta name to the Quiz model as a class const
//
//            $setting_option_fields['tps_quiz_type'] = array(
//                'name'      => 'tps_quiz_type',
//                'type'      => 'select',
//                'label'     => 'Тип теста',
//                'help_text' => 'Выберите тип теста. В зависимости от типа, тест будет по-разному отображаться и вести себя в паблике (т.е. для студентов).',
//                'value'     => $value ? : 'normal',
//                'options'   => [
//                    'normal' => 'Обычный тест',
//                    'adaptest' => 'Адаптационный тест',
//                    'quiz' => 'Квиз',
//                    'checklist' => 'Чеклист',
//                ],
//            );
//
//        }
//
//        return $setting_option_fields;
//
//    }

    public static function init_metabox() {

//        $quiz_post_id = get_the_ID();
//        $value = get_post_meta($quiz_post_id, 'tps_quiz_type', true); // TODO Add meta name to the Quiz model as a class const

        $cmb = new_cmb2_box(array(
            'id'            => 'tps_quiz_type_metabox',
            'title'         => 'Тип и специальные настройки теста',
            'object_types'  => array(Quiz::$post_type),
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ));

        $cmb->add_field(array(
            'id' => 'tps_quiz_type', // TODO Add meta name to the Quiz model as a class const
            'name' => 'Тип теста',
            'type' => 'select',
            'description' => 'ВНИМАНИЕ: некоторые настройки теста появятся только при выборе определённых типов теста. Чтобы увидеть либо скрыть дополнительные настройки теста, укажите его тип и сохраните запись теста.',
            'show_option_none' => false,
            'default' => 'normal',
            'options'          => array(
                'normal' => 'Обычный тест',
                'adaptest' => 'Адаптационный тест',
                'quiz' => 'Квиз',
                'checklist' => 'Чеклист',
            ),
        ));

        // Adaptest quiz type additional settings:
        $courses = Course::get_list();
        $courses_options = ['' => 'Выберите курс адаптационного теста'];
        foreach($courses as $course) {
            $courses_options[$course->ID] = $course->post_title;
        }

        $cmb->add_field(array(
            'id' => Adaptest::META_ADAPTEST_COURSE, // TODO Add meta name to the Quiz model as a class const
            'name' => 'Курс адаптационного теста',
            'type' => 'select',
            'description' => '',
            'show_option_none' => false,
            'default' => '',
            'options' => $courses_options,
            'show_on_cb' => function($field){

                // TODO Add meta name to the Quiz model as a class const:
                $value = get_post_meta($field->object_id, 'tps_quiz_type', true);

                return $value === 'adaptest';

            },
        ));
        // Adaptest quiz type additional settings - END

        // Checklist quiz type additional settings:
        $group_field_id = $cmb->add_field(array(
            'id'  => 'tps_quiz_checklist_results_intervals',
            'type' => 'group',
            'description' => 'Интервалы возможного результата теста (в пунктах)',
            // 'repeatable'  => false, // use false if you want non-repeatable group
            'options' => array(
                'group_title'       => 'Интервал {#}', // since version 1.1.4, {#} gets replaced by row number
                'add_button'        => 'Добравить интервал',
                'remove_button'     => 'Удалить интервал',
                'sortable'          => true,
                // 'closed'         => true, // true to have the groups closed by default
                // 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'cmb2' ), // Performs confirmation before removing group.
            ),
            'show_on_cb' => function($field){

                // TODO Add meta name to the Quiz model as a class const:
                $value = get_post_meta($field->object_id, 'tps_quiz_type', true);

                return $value === 'quiz';

            },
        ));

        $cmb->add_group_field($group_field_id, array(
            'name' => 'Название интервала',
            'description' => 'Напр., «8-12 пунктов»',
            'id'   => 'interval_title',
            'type' => 'text',
        ));

        $cmb->add_group_field($group_field_id, array(
            'name' => 'Кол-во пунктов, необходимое для попадания в интервал',
            'id'   => 'points_needed',
            'type' => 'text',
            'attributes' => array(
                'type' => 'number',
                'min'  => '0',
                'step'  => '1',
            ),
        ));

        $cmb->add_group_field($group_field_id, array(
            'name' => 'Описание интервала',
            'description' => 'Текстовое описание интервала, которое увидит студент',
            'id'   => 'description',
            'type' => 'textarea_small',
        ));
        // Checklist quiz type additional settings - END


    }

    public static function save_metabox($quiz_post_id, $quiz_post, $update) {

//        $block = Block::get($post_id);
//
//        if( !$block) {
//            return;
//        }
//
//        if(Block::$post_type != $block->post_type) {
//            return;
//        }
//
//        if( !Block::is_video_block($block)) {
//            return;
//        }

    }

    // Quiz type field - saving:
//    public static function filter_saved_fields($settings_values = array()) {
//
//        $quiz_post_id = get_the_ID();
//
//        if($quiz_post_id && !empty($_POST) && !empty($_POST['learndash-quiz-access-settings'])) {
//
//            $value = esc_attr($_POST['learndash-quiz-access-settings']['tps_quiz_type']);
//
//            update_post_meta($quiz_post_id, 'tps_quiz_type', $value);
//
//        }
//
//        return $settings_values;
//
//    }

    /*
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
    */
}

// add_filter( 'learndash_settings_fields', '\Teplosocial\hooks\AdaptestHooks::init_quiz_adaptest_option', 1, 2 );
//add_filter('learndash_settings_fields', '\Teplosocial\hooks\QuizTypesHooks::init_quiz_type_field', 1, 2);
add_action('cmb2_admin_init', '\Teplosocial\hooks\QuizTypesHooks::init_metabox');
add_action('save_post', '\Teplosocial\hooks\QuizTypesHooks::save_metabox', 50, 3);
//add_filter('learndash_metabox_save_fields_learndash-quiz-admin-data-handling-settings', '\Teplosocial\hooks\QuizTypesHooks::filter_saved_fields', 1);

//add_action( 'tps_show_question_custom_metabox', '\Teplosocial\hooks\AdaptestHooks::show_question_module_selector', 1, 3 );
//add_action( 'tps_save_question', '\Teplosocial\hooks\AdaptestHooks::save_quiz_question_module', 10, 1 );
//add_action( 'wp_pro_quiz_completed_quiz', '\Teplosocial\hooks\AdaptestHooks::handle_complete_adaptest_quiz', 10, 1 );