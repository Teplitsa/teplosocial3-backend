<?php

namespace Teplosocial\API;

use \Teplosocial\models\Course;
use \Teplosocial\models\CourseReview;

class CourseReviewRestApi
{
    public static function add_routes($server)
    {
        register_rest_route( 'tps/v1', '/course-review/add', [
            'methods' => \WP_REST_Server::CREATABLE,

            'callback' => function($request) {
                $slug = $request->get_param('course_slug');
                $course = Course::get($slug);
    
                if(!$course) {
                    return new \WP_Error(
                        'rest_tps_course_not_found',
                        __( 'Course not found', 'tps' ),
                        array( 'status' => 404 )
                    );
                }

                $user_id = \get_current_user_id();

                if(!$user_id) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'authentication_required',
                            'message' => __( 'Error', 'tps' ),
                        ),
                        500
                    );
                }

                try {
                    $review_data = [
                        'mark' => $_POST['mark'],
                        'comment' => $_POST['comment'],
                    ];

                    $review_id = CourseReview::add_review($course->ID, $user_id, $review_data);

                    if(\is_wp_error($review_id)) {
                        return new \WP_REST_Response(
                            array(
                                'code' => 'add_review_error',
                                'message' => $review_id->get_error_message(),
                            ),
                            500
                        );
                    }
                    else {
                        return ['status' => 'ok', 'review_id' => $review_id];
                    }
                }
                catch(\Exception $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'block_action_error',
                            'message' => __( 'Error', 'tps' ),
                        ),
                        500
                    );
                }
            },
            'permission_callback' => '__return_true',
        ] );

    }    
}

add_action( 'rest_api_init', '\Teplosocial\API\CourseReviewRestApi::add_routes' );
