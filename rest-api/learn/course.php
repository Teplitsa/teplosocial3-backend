<?php

namespace Teplosocial\API;

use \Teplosocial\API\PostRestApi;
use \Teplosocial\models\Track;
use \Teplosocial\models\Course;
use \Teplosocial\models\Module;
use \Teplosocial\models\Adaptest;
use \Teplosocial\models\CourseReview;
use Teplosocial\models\Teacher;
use Teplosocial\models\CourseTestimonial;
use \Teplosocial\models\UserProgress;

class CourseRestApi extends PostRestApi
{
    public static function add_routes(\WP_REST_Server $server)
    {
        register_rest_route('tps/v1', '/courses/by-user/(?P<user_id>[0-9]+/filter/(?P<filter>[- _0-9a-zA-Z]+))', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {
                $result = [];
                $user_id = $request->get_param('user_id');
                $filter = $request->get_param('filter');

                if (!in_array($filter, ['study', 'completed', 'all'])) {
                    return null;
                } elseif ($filter === 'all') {
                    $filter = ['study', 'completed'];
                } else {
                    $filter = [$filter];
                }

                foreach ($filter as $current_filter) {
                    $course_id = UserProgress::{"get_{$current_filter}_course_id"}($user_id);

                    if (!$course_id) continue;

                    $request->set_route('/wp/v2/' . Course::$post_type . '/' . $course_id);

                    ['data' => $courses] = (array) \rest_do_request($request);

                    // error_log("courses: " . print_r($courses, true));

                    $result[$current_filter] = $courses;
                }

                if (count($result) === 0) {
                    return new \WP_Error(
                        'rest_tps_course_not_found',
                        __('Course not found', 'tps'),
                        array('status' => 404)
                    );
                }

                $request->offsetUnset('_fields');

                return $result;
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                $user_id = $request->get_param('user_id');

                return intval($user_id) === \get_current_user_id();
            }
        ]);

        register_rest_route('tps/v1', '/courses/by-track/(?P<slug>[- _0-9a-zA-Z]+)', [
            'methods' => \WP_REST_Server::ALLMETHODS,
            'callback' => function ($request) {
                $slug = $request->get_param('slug');
                $track = Track::get($slug);

                if (!$track) {
                    return new \WP_Error(
                        'rest_tps_track_not_found',
                        __('Track not found', 'tps'),
                        array('status' => 404)
                    );
                }

                $request->set_param('track_id', $track->ID);
                $request->set_route('/wp/v2/' . Course::$post_type);

                return rest_do_request($request);
            },
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('tps/v1', '/courses/(?P<slug>[- _0-9a-zA-Z]+)/start', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function ($request) {
                $slug = $request->get_param('slug');
                $action = $request->get_param('action');
                $course = Course::get($slug);

                if (!$course) {
                    return new \WP_Error(
                        'rest_tps_course_not_found',
                        __('Course not found', 'tps'),
                        array('status' => 404)
                    );
                }

                // error_log("course:" . $course->post_name);
                // error_log("course_id:" . $course->ID);

                try {
                    return Track::start_course($course, $_POST);
                } catch (\Teplosocial\exceptions\AuthenticationRequiredException $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'authentication_required',
                            'message' => __('Error', 'tps'),
                        ),
                        500
                    );
                } catch (\Exception $ex) {
                    error_log($ex);
                    return new \WP_REST_Response(
                        array(
                            'code' => 'start_course_error',
                            'message' => __('Error', 'tps'),
                        ),
                        500
                    );
                }
            },
            'permission_callback' => '__return_true',
        ]);
    }

    public static function post_query($args, $request)
    {
        // filter by track
        $track_id = $request->get_param('track_id');
        // error_log("track_id: " . $track_id);

        if ($track_id) {
            $args = array_merge($args, [
                'connected_type'    => Course::$connection_track_course,
                'connected_items'   => $track_id,
            ]);
        }

        return $args;
    }

    public static function register_fields($server)
    {
        $fields = [
            'duration' => [
                'type'        => 'Int',
                'resolve'     => function ($course) {
                    return Course::get_duration($course->ID);
                },
            ],
            'points' => [
                'type'        => 'Int',
                'resolve'     => function ($course) {
                    return Course::get_points($course->ID);
                },
            ],
            'description' => [
                'type'        => 'String',
                'resolve'     => fn ($course) => \do_shortcode(\get_post_meta($course->ID, Course::META_DESCRIPTION, true)),
            ],
            'suitableFor' => [
                'type'        => 'String',
                'resolve'     => function ($course) {
                    return \do_shortcode(get_post_meta($course->ID, Course::META_SUITABLE_FOR, true));
                },
            ],
            'learningResult' => [
                'type'        => 'String',
                'resolve'     => function ($course) {
                    return \do_shortcode(get_post_meta($course->ID, Course::META_LEARNING_RESULT, true));
                },
            ],
            'numberOfBlocks' => [
                'type'        => 'Int',
                'resolve'     => function ($module) {
                    return Course::count_blocks($module->ID);
                },
            ],
            'numberOfCompletedBlocks' => [
                'type'        => 'Int',
                'resolve'     => function ($module) {
                    $user_id = \get_current_user_id();
                    return Course::count_completed_blocks($module->ID, $user_id);
                },
            ],
            'isStarted' => [
                'type'        => 'Bool',
                'description' => __('Currently logged in student started the course', 'tps'),
                'resolve'     => function ($course, $args, $context) {
                    $user_id = \get_current_user_id();
                    return Course::is_started_by_user($course->ID, $user_id);
                },
            ],
            'isCompleted' => [
                'type'        => 'Bool',
                'description' => __('Currently logged in student completed the course', 'tps'),
                'resolve'     => function ($course, $args, $context) {
                    $user_id = \get_current_user_id();
                    return Course::is_completed_by_user($course->ID, $user_id);
                },
            ],
            'nextBlockSlug' => [
                'type'        => 'String',
                'resolve'     => function ($course) {
                    $user_id = \get_current_user_id();
                    if ($user_id) {
                        $module = $user_id ? Course::get_first_uncompleted_module($course->ID, $user_id) : null;
                        $nextBlock = $module ? Module::get_next_uncompleted_block_by_user($module->ID, $user_id) : null;
                    } else {
                        $modules = Module::get_list([
                            'connected_type'    => Module::$connection_course_module,
                            'connected_items'   => $course->ID,
                        ]);
                        $module = $modules[0] ?? null;
                        $nextBlock = $module ? Module::get_next_block_for_guest($module->ID) : null;
                    }
                    return $nextBlock ? $nextBlock->post_name : "";
                },
            ],
            'nextBlockTitle' => [
                'type'        => 'String',
                'resolve'     => function ($course) {
                    $user_id = \get_current_user_id();
                    $module = $user_id ? Course::get_first_uncompleted_module($course->ID, $user_id) : null;
                    $nextBlock = $module ? Module::get_next_uncompleted_block_by_user($module->ID, $user_id) : null;
                    return $nextBlock ? $nextBlock->post_title : "";
                },
            ],
            'review' => [
                'type'        => 'Object',
                'resolve'     => function ($course) {
                    $user_id = \get_current_user_id();
                    return CourseReview::get_review($course->ID, $user_id);
                },
            ],
            'adaptestSlug' => [
                'type'        => 'String',
                'resolve'     => function ($course) {
                    $quiz = Adaptest::get_by_course($course->ID);
                    return $quiz ? $quiz->post_name : "";
                },
            ],
            'isAdaptestCompleted' => [
                'type'        => 'Boolean',
                'resolve'     => function ($course) {
                    $user_id = \get_current_user_id();
                    return Adaptest::is_course_adaptest_completed_by_user($course->ID, $user_id);
                },
            ],
            'track' => [
                'type'        => 'Object',
                'resolve'     => function ($course) {
                    $result = new \stdClass();

                    $track = Track::get_by_course($course->ID);

                    if (!empty($track)) {
                        $result->title = $track->post_title;
                        $result->slug = $track->post_name;
                    }

                    return $result;
                },
            ],
            'teacherList' => [
                'type'    => 'Array',
                'resolve' => fn ($course) => Teacher::get_list($course->ID),
            ],
            'testimonialList' => [
                'type'    => 'Array',
                'resolve' => fn ($course) => CourseTestimonial::get_list($course->ID),
            ],
        ];

        self::register_post_type_fields(Course::$post_type, $fields);
    }

    public static function fix_seo_integration($server)
    {
        self::fix_post_type_seo_integration($server, Course::$post_type);
    }

    public static function customize_collection_params($params)
    {
        $params['orderby']['enum'][] = 'menu_order';
        return $params;
    }
}

add_action('rest_api_init', '\Teplosocial\API\CourseRestApi::add_routes');
add_filter('rest_' . Course::$post_type . '_query', '\Teplosocial\API\CourseRestApi::post_query', 10, 2);
add_action('rest_api_init', '\Teplosocial\API\CourseRestApi::register_fields', 11);
add_action('rest_api_init', '\Teplosocial\API\CourseRestApi::fix_seo_integration', 11);
add_filter('rest_' . Course::$post_type . '_collection_params', '\Teplosocial\API\CourseRestApi::customize_collection_params', 10, 1);
