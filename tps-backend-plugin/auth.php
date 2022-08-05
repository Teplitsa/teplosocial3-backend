<?php

namespace Teplosocial\Plugin;

use function \Teplosocial\Plugin\utils\{base64url_encode, base64url_decode};
use function \Teplosocial\Plugin\Config;

class Auth
{
    public static function parse_token_from_request()
    {
        $headerkey = 'HTTP_AUTHORIZATION';
        $auth = isset($_SERVER[$headerkey]) ? $_SERVER[$headerkey] : false;
        // error_log("auth1: " . $auth);
        $token = "";

        if (!$auth) {
            $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
            // error_log("auth2: " . $auth);
        }

        if($auth) {
            list($token) = sscanf($auth, 'Bearer %s');
        }
        else {
            // error_log("auth3: cookie");
            $token = isset($_COOKIE[Config::AUTH_TOKEN_COOKIE_NAME]) ? $_COOKIE[Config::AUTH_TOKEN_COOKIE_NAME] : "";
        }

        // error_log("token: " . $token);

        if (!$token) {
            throw new NoAuthHeaderException();
        }

        return $token;
    }

    public static function parse_token($token)
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

        $secret = \Teplosocial\Plugin\Config::AUTH_SECRET_KEY;
        $signature_check = hash_hmac('sha256', $base64_header . "." . $base64_payload, $secret, true);
        $base64_signature_check = base64url_encode($signature_check);

        $token_check = $base64_header . "." . $base64_payload . "." . $base64_signature_check;
        $is_signature_valid = $token === $token_check;

        return [!$is_expired && $is_signature_valid, $payload->data];
    }

    public static function determine_current_user( $user_id ) {
        // error_log("tps_determine_current_user...");
        // error_log("input user_id=" . $user_id);
        // error_log("is_admin:" . is_admin());
    
        if((strpos($_SERVER['REQUEST_URI'], "/tps/v1/auth/") !== false)
            || strpos($_SERVER['REQUEST_URI'], "wp-cron.php") !== false 
            || (defined('WP_CLI') && boolval(WP_CLI)) 
            || ((is_admin()
                    || (strpos($_SERVER['REQUEST_URI'], Config::LOGIN_PATH) !== false)
                ) && !wp_doing_ajax()
            )
        ) {
            return $user_id;
        }
	
        if($user_id !== false) {
            return $user_id;
        }	

        $is_valid = false;
        try {
            $token = self::parse_token_from_request();
            list($is_valid, $payload_data) = self::parse_token($token);
            if($is_valid) {
                $user_id = $payload_data->user->id;
            }
        }
        catch(\Teplosocial\Plugin\NoAuthHeaderException $ex) {
        }
        catch(\Teplosocial\Plugin\InvalidAuthTokenException $ex) {
            error_log("InvalidAuthTokenException");        
        }
        catch(\Teplosocial\Plugin\UserNotFoundException $ex) {
            error_log("UserNotFoundException");
        }
    
        // $user_id = 2;
        // error_log("result user_id=" . $user_id);
        return $user_id;
    }
    
    public static function rest_cookie_collect_status($cookie_elements) {
        global $wp_rest_auth_cookie;
        $wp_rest_auth_cookie = false;
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

add_filter( 'determine_current_user', '\Teplosocial\Plugin\Auth::determine_current_user' );
add_filter( 'auth_cookie_valid', '\Teplosocial\Plugin\Auth::rest_cookie_collect_status', 100 );
