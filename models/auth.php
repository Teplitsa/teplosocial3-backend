<?php

namespace Teplosocial\models;

use function \Teplosocial\utils\{base64url_encode, base64url_decode, translit_sanitize};
use Teplosocial\models\Student;
use Teplosocial\models\Course;

class Auth
{

    public function login($login, $password, $remember = false)
    {

        $user = wp_authenticate($login, $password);

        if (is_wp_error($user)) {
            return $user;
        }

        $token = $this->generate_token($user);

        $request = new \WP_REST_Request($_SERVER['REQUEST_METHOD'], "/");
        $rc = new \WP_REST_Users_Controller();
        $user_rest_data = $rc->prepare_item_for_response($user, $request);
        $user_data = $this->optimize_rest_data($user_rest_data, $user);

        return [
            'token' => $token,
            'user' => $user_data,
        ];
    }

    public function register($user_params)
    {

        $user_login = self::get_unique_user_login(translit_sanitize($user_params['first_name']), translit_sanitize($user_params['last_name']));

        $insert_user_params = array(
            'user_login' => $user_login,
            'user_email' => $user_params['email'],
            'user_pass' => $user_params['password'],
            'first_name' => $user_params['first_name'],
            'last_name' => $user_params['last_name'],
            'role' => 'author',
        );

        $user_id = wp_insert_user($insert_user_params);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $user = get_user_by('ID', $user_id);
        if (is_wp_error($user)) {
            return $user;
        }

        // $email_subject = __("Teplitsa.Kurs account confirmation", 'tps');
        // $email_body_template = __("Hello, %s!\nPlease confirm your Teplitsa.Kurs account following this link: %s", 'tps');
        // $this->send_activation_email($user, $email_subject, $email_body_template);

        $this->send_activation_email_atvetka($user);

        $token = $this->generate_token($user);

        $request = new \WP_REST_Request($_SERVER['REQUEST_METHOD'], "/");
        $rc = new \WP_REST_Users_Controller();
        $user_rest_data = $rc->prepare_item_for_response($user, $request);
        $user_data = $this->optimize_rest_data($user_rest_data, $user);

        return [
            'token' => $token,
            'user' => $user_data,
        ];
    }

    public function logout($user_id)
    {
        setcookie(\Teplosocial\Config::AUTH_TOKEN_COOKIE_NAME, "", time() - 3600, '/');
    }

    public function generate_token($user)
    {

        $secret = \Teplosocial\Config::AUTH_SECRET_KEY;
        $expire = time() + (DAY_IN_SECONDS * \Teplosocial\Config::AUTH_EXPIRE_DAYS);

        $payload = json_encode([
            'iss' => get_bloginfo('url'),
            'exp' => $expire,
            'data' => [
                'user' => [
                    'id' => $user->ID,
                ],
            ],
        ]);

        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        $base64_header = base64url_encode($header);

        $base64_payload = base64url_encode($payload);
        $signature = hash_hmac('sha256', $base64_header . "." . $base64_payload, $secret, true);
        $base64_signature = base64url_encode($signature);

        $token = $base64_header . "." . $base64_payload . "." . $base64_signature;

        return $token;
    }

    public function parse_token_from_request()
    {
        $headerkey = 'HTTP_AUTHORIZATION';
        $auth = isset($_SERVER[$headerkey]) ? $_SERVER[$headerkey] : false;

        if (!$auth) {
            $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
        }

        if (!$auth) {
            throw new NoAuthHeaderException();
        }

        list($token) = sscanf($auth, 'Bearer %s');

        return $token;
    }

    public function validate_token($token, $short_response = false)
    {

        list($is_valid, $payload_data) = $this->parse_token($token);

        if (!$is_valid) {
            throw new InvalidAuthTokenException();
        }

        if ($short_response) {
            global $wpdb;

            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE ID = %d", $payload_data->user->id));

            $user = empty($count) || 1 > $count ? null : ['id' => $payload_data->user->id];
        } else {
            $user = \get_user_by('ID', $payload_data->user->id);
        }

        if (!$user) {
            throw new UserNotFoundException();
        }

        if ($short_response) {
            return [
                'is_valid' => $is_valid,
                'user'     => $user,
            ];
        }

        $request = new \WP_REST_Request($_SERVER['REQUEST_METHOD'], "/");
        $rc = new \WP_REST_Users_Controller();
        $user_rest_data = $rc->prepare_item_for_response($user, $request);
        $user_data = $this->optimize_rest_data($user_rest_data, $user);
        $token = $this->generate_token($user);

        return [
            'is_valid' => $is_valid,
            'user' => $user_data,
            'token' => $token,
        ];
    }

    public function parse_token($token)
    {
        if (!$token) {
            return [false, []];
        }

        $parts = explode('.', $token);

        if (count($parts) < 2) {
            return [false, []];
        }

        $base64_header = $parts[0];
        $base64_payload = $parts[1];

        $payload = json_decode(base64url_decode($base64_payload));

        $is_expired = $payload->exp - time() < 0;

        $secret = \Teplosocial\Config::AUTH_SECRET_KEY;
        $signature_check = hash_hmac('sha256', $base64_header . "." . $base64_payload, $secret, true);
        $base64_signature_check = base64url_encode($signature_check);

        $token_check = $base64_header . "." . $base64_payload . "." . $base64_signature_check;
        $is_signature_valid = $token === $token_check;

        return [!$is_expired && $is_signature_valid, $payload->data];
    }

    public function optimize_rest_data($user_rest_data, $user)
    {
        $user_data = $user_rest_data->data;

        if (empty($user_data["email"])) {
            $user_data["email"] = $user->user_email;
        }

        $fullNameParts = [];
        if (trim($user->first_name)) {
            $fullNameParts[] = $user->first_name;
        }
        if (trim($user->last_name)) {
            $fullNameParts[] = $user->last_name;
        }
        $user_data['fullName'] = implode(" ", $fullNameParts);

        unset($user_data['yoast_head']);

        return $user_data;
    }

    public function retrieve_password($email)
    {
        $errors    = new \WP_Error();
        $user_data = false;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors->add('empty_username', __('<strong>Error</strong>: Please enter correct email address.'));
        } else {
            $user_data = get_user_by('email', trim(wp_unslash($email)));

            if (empty($user_data)) {
                $errors->add('invalid_email', __('<strong>Error</strong>: There is no account with that email address.'));
            }
        }

        do_action('lostpassword_post', $errors, $user_data);
        $errors = apply_filters('lostpassword_errors', $errors, $user_data);

        if ($errors->has_errors()) {
            return $errors;
        }

        if (!$user_data) {
            $errors->add('invalidcombo', __('<strong>Error</strong>: There is no account with that email address.'));
            return $errors;
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key        = get_password_reset_key($user_data);

        if (is_wp_error($key)) {
            return $key;
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
        $message .= sprintf(__('Site Name: %s'), $site_name) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= network_site_url("auth/reset-password?key=$key&login=" . rawurlencode($user_login), 'login') . "\r\n";

        $title = sprintf(__('[%s] Password Reset'), $site_name);
        $title = apply_filters('retrieve_password_title', $title, $user_login, $user_data);

        $message = apply_filters('retrieve_password_message', nl2br($message), $key, $user_login, $user_data);

        if (!empty($message) && !wp_mail($user_email, wp_specialchars_decode($title), $message)) {
            $errors->add(
                'retrieve_password_email_failure',
                sprintf(
                    /* translators: %s: Documentation URL. */
                    __('<strong>Error</strong>: The email could not be sent. Your site may not be correctly configured to send emails. <a href="%s">Get support for resetting your password</a>.'),
                    esc_url(__('https://wordpress.org/support/article/resetting-your-password/'))
                )
            );
            return $errors;
        }

        return true;
    }

    public function change_password(int $user_id, string $old_password, string $new_password, string $new_password_repeat): ?\WP_Error
    {
        $errors    = new \WP_Error();

        global $wpdb;

        $old_password_hash = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE ID = %d", $user_id));

        if (\wp_check_password($old_password, $old_password_hash, $user_id) === false) {
            $errors->add('wrong_old_password', __('<strong>Error</strong>: Please enter correct old password.'));
        } elseif ($new_password !== $new_password_repeat) {
            $errors->add('password_mismatch', __('<strong>Error</strong>: Confirm password field value doesn\'t match the new password field value.'));
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        \wp_set_password($new_password, $user_id);

        return null;
    }

    public static function get_unique_user_login($first_name, $last_name = '')
    {
        $new_ok_login = sanitize_user($first_name, true);
        $is_ok = false;

        if (!username_exists($new_ok_login)) {
            $is_ok = true;
        }

        if (!$is_ok && $last_name) {
            $new_ok_login = sanitize_user($last_name, true);
            if (!username_exists($new_ok_login)) {
                $is_ok = true;
            }
        }

        if (!$is_ok) {
            $user_login = sanitize_user($first_name . ($last_name ? '_' . $last_name : ''), true);
            $new_ok_login = $user_login;
            $iter = 1;
            while (username_exists($new_ok_login) && $iter < 1000) {
                $new_ok_login = $user_login . $iter;
                $iter += 1;
            }
        }

        return $new_ok_login;
    }

    public function send_activation_email($user, $email_subject, $email_body_template)
    {
        $user_id = $user->ID;
        $user_email = $user->user_email;
        $user_first_name = get_user_meta($user->ID, Student::META_FIRST_NAME, true);

        $activation_code = sha1($user_id . '-activation-' . time());
        update_user_meta($user_id, 'activation_code', $activation_code);
        update_user_meta($user_id, 'activation_email_time', date('Y-m-d H:i:s'));

        $account_activation_url = "/account-activation/?uid=$user_id&code=$activation_code";
        $link = home_url($account_activation_url);

        wp_mail(
            $user_email,
            $email_subject,
            sprintf($email_body_template, $user_first_name, $link)
        );
    }

    public function send_activation_email_atvetka($user)
    {
        $user_email = $user->user_email;
        $user_first_name = get_user_meta($user->ID, Student::META_FIRST_NAME, true);
        $courses_count = count(Course::get_list(['fields' => 'ids']));

        $atvetka_data = [
            'mailto' => $user->user_email,
            'email_placeholders' => [
                '{user_first_name}' => $user_first_name,
                '{courses_count}' => $courses_count,
            ],
        ];
        $mail_slug = 'onboarding_welcome';
        do_action('atv_email_notification', $mail_slug, $atvetka_data);
    }
}

class NoAuthHeaderException extends \Exception
{
}
class InvalidAuthTokenException extends \Exception
{
}
class UserNotFoundException extends \Exception
{
}
