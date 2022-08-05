<?php

use Teplosocial\models\{Certificate, Course, Track};

function certificate_api_add_routes(WP_REST_Server $server)
{
    register_rest_route('tps/v1', '/certificate/by-user/(?P<user_id>[0-9]+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function (WP_REST_Request $request) {
            $user_id = $request->get_param('user_id');

            return Certificate::get_list(['user_id' => $user_id]);
        },
        'permission_callback' => function (\WP_REST_Request $request) {
            $user_id = $request->get_param('user_id');

            return intval($user_id) === \get_current_user_id();
        }
    ]);

    register_rest_route('tps/v1', '/certificate/(?P<certificate_id>[0-9]+)/pdf', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function (WP_REST_Request $request) {
            $certificate_id = (int) $request->get_param('certificate_id');

            $certificate_pdf = $certificate_id > 569 ? Certificate::get_pdf($certificate_id) : Certificate::get_pdf_v1($certificate_id);

            $response = \rest_ensure_response($certificate_pdf);

            $response->set_status(200);
            $response->set_headers([
                'Content-Type'              => 'application/octet-stream',
                'Content-Disposition'       => 'attachment;  filename=certificate.pdf',
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges'             => 'bytes',
            ]);

            return $response;
        },
        'permission_callback' => function (WP_REST_Request $request) {
            $certificate_id = (int) $request->get_param('certificate_id');

            $certificate = Certificate::get_item($certificate_id);

            return intval($certificate->user_id) === \get_current_user_id();
        }
    ]);

    register_rest_route('tps/v1', '/course-certificate/(?P<course_slug>[- _0-9a-zA-Z]+)/pdf', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function (WP_REST_Request $request) {
            $course_slug = $request->get_param('course_slug');
            $course = Course::get($course_slug);

            if(!$course) {
                return new \WP_Error(
                    'rest_tps_course_not_found',
                    __( 'Course not found', 'tps' ),
                    array( 'status' => 404 )
                );
            }

            $user_id = \get_current_user_id();
            $certificate = Certificate::get_user_course_certificate($user_id, $course->ID);
            error_log("certificate:" . print_r($certificate, true));
            $certificate_id = $certificate->ID;
            error_log("certificate_id:" . $certificate_id);

            if(!$certificate_id) {
                return new \WP_Error(
                    'rest_tps_course_certificate_not_found',
                    __( 'Course certificate not found', 'tps' ),
                    array( 'status' => 404 )
                );
            }

            $certificate_pdf = $certificate_id > 569 ? Certificate::get_pdf($certificate_id) : Certificate::get_pdf_v1($certificate_id);

            $response = \rest_ensure_response($certificate_pdf);

            $response->set_status(200);
            $response->set_headers([
                'Content-Type'              => 'application/octet-stream',
                'Content-Disposition'       => 'attachment;  filename=certificate.pdf',
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges'             => 'bytes',
            ]);

            return $response;
        },
        'permission_callback' => '__return_true',        
    ]);

    register_rest_route('tps/v1', '/track-certificate/(?P<track_slug>[- _0-9a-zA-Z]+)/pdf', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function (WP_REST_Request $request) {
            $track_slug = $request->get_param('track_slug');
            $track = Track::get($track_slug);

            if(!$track) {
                return new \WP_Error(
                    'rest_tps_track_not_found',
                    __( 'Track not found', 'tps' ),
                    array( 'status' => 404 )
                );
            }

            $user_id = \get_current_user_id();
            $certificate = Certificate::get_user_track_certificate($user_id, $track->ID);
            error_log("certificate:" . print_r($certificate, true));
            $certificate_id = $certificate->ID;
            error_log("certificate_id:" . $certificate_id);

            if(!$certificate_id) {
                return new \WP_Error(
                    'rest_tps_track_certificate_not_found',
                    __( 'Track certificate not found', 'tps' ),
                    array( 'status' => 404 )
                );
            }

            $certificate_pdf = $certificate_id > 569 ? Certificate::get_pdf($certificate_id) : Certificate::get_pdf_v1($certificate_id);

            $response = \rest_ensure_response($certificate_pdf);

            $response->set_status(200);
            $response->set_headers([
                'Content-Type'              => 'application/octet-stream',
                'Content-Disposition'       => 'attachment;  filename=certificate.pdf',
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges'             => 'bytes',
            ]);

            return $response;
        },
        'permission_callback' => '__return_true',        
    ]);
}

add_action('rest_api_init', 'certificate_api_add_routes');
