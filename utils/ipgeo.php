<?php

require_once(get_theme_file_path() . '/lib/ipgeo/ipgeobase.php');

class TstIPGeo {
    private $_config;
    private static $_instance = null;
    private $_ipgeo = null;
    
    private $fhandleCities;
    
    function __construct($CIDRFile = false, $CitiesFile = false) {
    	if(!$CitiesFile) {
    		$CitiesFile = dirname(__FILE__) . '/../lib/ipgeo/cities.txt';
    	}
    	$this->fhandleCities = fopen($CitiesFile, 'r') or die("Cannot open $CitiesFile");
    }
    
    public static function instance() {
        if (TstIPGeo::$_instance == NULL) {
            TstIPGeo::$_instance = new TstIPGeo ();
        }
        return TstIPGeo::$_instance;
    }
    
    private function _get_ipgeo() {
        if(!$this->_ipgeo) {
            $this->_ipgeo = new IPGeoBase();
        }
        return $this->_ipgeo;
    }
    
    public function save_location_by_ip($user_id, $ip) {
        try {
            
            $city = $this->_get_ipgeo()->getRecord($ip);
            
            if($city) {
                $city = $this->fix_region($city);
                #update_user_meta($user_id, 'user_region', isset($city['region']) ? $city['region'] : '');
                update_user_meta($user_id, 'user_city', isset($city['city']) ? $city['city'] : '');
            }
        }
        catch (Exception $ex) {
        }
    }

    public function get_city_by_ip($ip) {
        $city_str = '';
        try {
            $city = $this->_get_ipgeo()->getRecord($ip);
            $city = $this->fix_region($city);
    
            if($city) {
                $city_str = isset($city['city']) ? $city['city'] : '';
            }
        }
        catch (Exception $ex) {
        }

        return $city_str;
    }
    
    public function get_geo_region($user_id) {
        return get_user_meta($user_id, 'user_region', true);
    }
    
    public function get_geo_city($user_id) {
        return get_user_meta($user_id, 'user_city', true);
    }
    
    public function get_regions() {
        if(!$this->_ipgeo) {
            $this->_ipgeo = new IPGeoBase();
        }
        # implement to add edit region functionality
    }
    
    public function search_city($search_str) {
    	
    	$search_str = mb_strtolower($search_str);
    	
    	rewind($this->fhandleCities);
    	$list = array();
    	while(!feof($this->fhandleCities)) {
    		
    		$str = fgets($this->fhandleCities);
    		$arRecord = explode("\t", trim($str));
    		if(isset($arRecord[3]) && isset($arRecord[1]) && strpos($arRecord[3], 'Украина') === false) {
    			$lower_city_name = mb_strtolower($arRecord[1]);
    			if(strpos($lower_city_name, $search_str) === 0) {
	    			$list[] = array( 
	    				'city' => $arRecord[1],
	    				'region' => isset($arRecord[2]) ? $arRecord[2] : "",
	    			);
    			}
    		}
    	}
    	
    	usort($list, function($a, $b){
    		if ($a['city'] == $b['city']) {
    			return 0;
    		}
    		return ($a['city'] < $b['city']) ? -1 : 1;
    	});
    	
    	return $list;
    	 
    }
    
    private function fix_region($city) {
        if(isset($city['region'])) {
            if($city['region'] == 'Крым') {
                $city['region'] = 'Республика Крым';
            }
        }
        return $city;
    }

    public static function get_client_ip() {
	    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	    if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	        $ip_list = preg_split('/\s*,\s*/', $_SERVER['HTTP_X_FORWARDED_FOR']);
	        if(count($ip_list)) {
	            $ip = array_shift($ip_list);
	        }
	        else {
	            $ip = $ip_list;
	        }
	    }
	    return $ip;
    }
}

function tst_city_autocomplete() {
	
	$term = strtolower( $_GET['term'] );
	
	$suggestions = [];
	
	$cities = TstIPGeo::instance()->search_city($term);
	$list = array();
	foreach($cities as $city) {
		$list[] = $city['city'];
	}
	$list = array_unique($list);
	
	foreach($list as $val) {
		$suggestions[] = array(
			'label' => $val,
			'value' => $val,
		);
	}

	wp_send_json( $suggestions );
}

add_action( 'wp_ajax_autocomlete_city', 'tst_city_autocomplete' );
add_action( 'wp_ajax_nopriv_autocomlete_city', 'tst_city_autocomplete' );
