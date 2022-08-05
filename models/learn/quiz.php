<?php 

namespace Teplosocial\models;

use \Teplosocial\models\Post;
use \Teplosocial\Config;

class Quiz extends Post
{
    public static $post_type = 'sfwd-quiz';

    const META_QUIZ_PRO_ID = 'quiz_pro_id';
    const META_QUESTION_PRO_ID = 'question_pro_id';
    const META_IS_ADAPTEST = 'tps_is_adaptest';

    public static function get_quiz_pro_id($quiz_id) {
        return intval(get_post_meta($quiz_id, self::META_QUIZ_PRO_ID, true));
    }

    public static function get_questions($quiz_id) {
        $quiz_pro_id = self::get_quiz_pro_id($quiz_id);
        // error_log("quiz_pro_id=" . $quiz_pro_id);

		$m = new \WpProQuiz_Model_QuestionMapper();
        $q_list = $m->fetchAll($quiz_pro_id);
        $list = [];
        foreach($q_list as $pro_q) {
            $q = \Teplosocial\utils\remove_start_underscore( $pro_q->get_object_as_array() );
            $a_data_list = [];
            foreach($q['answerData'] as $i => $answer) {
                $a = $answer->get_object_as_array();
                $a['datapos'] = \LD_QuizPro::datapos( $pro_q->getId(), $i );
                $a_data_list[] = \Teplosocial\utils\remove_start_underscore($a);
            }
            $q['answerData'] = $a_data_list;
            $q['uploadNonce'] = $q["answerType"] === "essay" ? \wp_create_nonce( 'learndash-upload-essay-' . $pro_q->getId() ) : "";
            // error_log("answerType: " . $q["answerType"]);
            // error_log("a_data_list: " . print_r($a_data_list, true));
            // $q['questionProId'] = ;

            $list[] = $q;
        }

        // error_log("questions list:" . print_r($list, true));
        return $list;
    }
}
