<?php
//error_reporting(0);

//Exceptions
class NotCLIRunException extends Exception {
}

class CLIHostNotSetException extends Exception {
}

function find_wordpress_base_path() {
    $dir = dirname(__FILE__);
    do {
        if( file_exists($dir."/wp-config.php") ) {
            return $dir;
        }
    } while( $dir = realpath("$dir/..") );
    return null;
}

define('BASE_PATH', find_wordpress_base_path()."/");
define('WP_USE_THEMES', false);
define('WP_CURRENT_THEME', 'tstsite');

if(php_sapi_name() !== 'cli') {
	throw new NotCLIRunException("Should be run from command line!");
}

$options = getopt("", array('host:'));
$host = isset($options['host']) ? $options['host'] : '';

echo "=======start=======\n";

if(empty($host)) {
 	throw new CLIHostNotSetException("Host must be defined!");
}
else {
    $_SERVER = array(
        "HTTP_HOST" => $host,
        "SERVER_NAME" => $host,
        "REQUEST_URI" => "/",
        "REQUEST_METHOD" => "GET",
    );
}

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require_once(BASE_PATH . 'wp-load.php');
echo "HOST: " . $host . "\n";
echo "DATETIME: " . date( 'Y-m-d H:i:s' ) . chr(10);
echo "gmt_offset=" . get_option('gmt_offset') . chr(10);
echo "script_timezone=" . date('T') . chr(10);
echo "timezone_string=" . get_option('timezone_string') . chr(10);