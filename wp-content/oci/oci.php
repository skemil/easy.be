<?php
if( !defined( 'MIDDLEWARE_URL' ) ) {
	$api_version = 'v1.0';
	if( isset( $_SERVER[ 'ONECOM_WP_ADDONS_API' ] ) && $_SERVER[ 'ONECOM_WP_ADDONS_API' ] != '' ) {
		$ONECOM_WP_ADDONS_API = $_SERVER[ 'ONECOM_WP_ADDONS_API' ];
	} elseif( defined( 'ONECOM_WP_ADDONS_API' ) && ONECOM_WP_ADDONS_API != '' && ONECOM_WP_ADDONS_API != false ) {
		$ONECOM_WP_ADDONS_API = ONECOM_WP_ADDONS_API;
	} else {
		$ONECOM_WP_ADDONS_API = 'http://wpapi.one.com/';
	}
	$ONECOM_WP_ADDONS_API = rtrim( $ONECOM_WP_ADDONS_API, '/' );
	define( 'MIDDLEWARE_URL', $ONECOM_WP_ADDONS_API.'/api/'.$api_version );
}
/**
* Function to tell URL for localhost
**/
if( ! function_exists( 'localhost_oci_path' ) ) {
	function localhost_oci_path() {
		$url = $_SERVER['REQUEST_URI']; //returns the current URL

		$parts = explode('/',$url);
		$dir = $_SERVER['SERVER_NAME'];
		for ($i = 0; $i <= 1; $i++) {
		 $dir .= $parts[$i] . "/";
		}
		$dir = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" ) ? 'https://'.$dir : 'http://'.$dir;
		$oci_url =  $dir.'wp-content/oci/';
		return $oci_url;
	}
}
if( !defined( 'OCI_URL' ) ) {
	if ( $_SERVER["SERVER_ADDR"] == '127.0.0.1' || $_SERVER["SERVER_ADDR"] == 'localhost' || $_SERVER["SERVER_ADDR"] == '::1' ) {
		define( 'OCI_URL', localhost_oci_path() );
	} else {
		$parts = explode('/', $_SERVER['REQUEST_URI']);
		
		$remove_values = array('wp-admin', 'install.php', 'install.php?step=start', 'install.php?step=contact','install.php?step=settings', 'install.php?step=theme');

		foreach($remove_values as $value) {
			$key = array_search($value, $parts);
		   	unset($parts[$key]);
		}

		$dir = implode('/', $parts);
		$url = '//'.$_SERVER['SERVER_NAME'].$dir;
		define( 'OCI_URL',$url.'/wp-content/oci/' );
	}
}



if( !defined( 'OCI_DIR' ) ) {
	define( 'OCI_DIR', realpath( WP_CONTENT_DIR.'/oci/' ) );
}
if( !defined( 'ONECOM_WP_CORE_VERSION' ) ) {
	global $wp_version;
	define( 'ONECOM_WP_CORE_VERSION' , $wp_version );
}
if( !defined( 'ONECOM_PHP_VERSION' ) ) {
	define( 'ONECOM_PHP_VERSION' , phpversion() );
}

/**
 * Function to show premium badges on theme thumbnails.
 **/
if(!function_exists('oc_theme_badge')){
	function oc_theme_badge( $tag ) {
		if(! (is_array($tag) && in_array('premium', $tag))){
			return ;
		}
		echo '<span class="badge_bg" style="position: absolute;transform: rotate(45deg);z-index: 90;width: 105px;height: 73px;padding-top: 0px;top: -26px;right: -42px;background-color: #95265e;"></span>
	<span class="badge_icon" style="position: absolute;transform: rotate(45deg);z-index: 90;pointer-events: none;top: 8px;right: 13px;">
	<svg style="height: 15px;width: 9px;display: inline-block;"><use xlink:href="#topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119">
	<svg viewBox="0 0 9 15" id="topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119"><path d="M1.486 0h6L5.492 5.004l3.482-.009-6.839 9.38 1.627-6.903L0 7.469z" fill="#FFF" fill-rule="evenodd"></path></svg></use></svg></span>
	<span class="badge_text" style="position: absolute;transform: rotate(45deg);z-index: 90;color: #fff;text-transform: uppercase;font-style: normal;font-weight: 600;font-family: \'Open Sans\', sans-serif;display: block;text-align: center;top: 18px;font-size: 11px;right: 2px;-webkit-font-smoothing: antialiased;">Premium</span>';
	}
	add_filter( 'onecom_premium_theme_badge', 'oc_theme_badge', 10 );
}

/**
 * Function to show inline premium badge
 */
add_filter( 'onecom_premium_inline_badge', 'oc_inline_badge', 10 );
if(!function_exists('oc_inline_badge')){
	function oc_inline_badge() {
		echo '<span class="inline_badge standard" style="display: none;height: 28px; vertical-align: middle; margin-left: 20px; align-items: center;"><i class="inline_icon" style="background:url(\'data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZmlsbDojOTUyNjVFOyIgd2lkdGg9IjkiIGhlaWdodD0iMTQiIHZpZXdCb3g9IjAgMCA5IDE0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0xLjQ5IDBoNi4wMTdsLTIgNC44NzNMOSA0Ljg2NSAyLjE0MiAxNGwxLjYzLTYuNzIzTDAgNy4yNzR6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=\');height: 13.5px;display: inline-block;vertical-align: middle;background-repeat: no-repeat;width: 9px;"></i><span class="inline_badge_text" style="-webkit-font-smoothing: antialiased;margin-left: 10px; opacity: 0.9;color: #333;font-family: Open Sans;font-size: 13px;line-height: 18px;">'.__("This is a Premium Theme", "oci").'</span> <a class="inline_badge_link" style="margin-left: 5px;color: #95265e;font-family: Open Sans;-webkit-font-smoothing: antialiased;font-size: 13px;font-weight: 600;line-height: 18px;cursor: pointer;text-decoration:none;" href="'.onecom_generic_locale_link('premium_page', $loaded_language).'">'.__("Learn more", "oci").'</a></span>';

		echo '<span class="inline_badge premium" style="display: none;height: 28px;vertical-align: middle;margin-left: 20px;align-items: center;color: #76a338;font-size: 14px;-webkit-font-smoothing: antialiased;"><svg style="width: 16px;height: 16px;pointer-events: none;margin-right:6px;"><use xlink:href="#premium_checkmark_91c2f8cf40d052f90c7b36218d17f875"><svg viewBox="0 0 13 13" id="premium_checkmark_91c2f8cf40d052f90c7b36218d17f875"><path d="M5.815 7.383L8.95 4.271l1.06 1.06-3.255 3.232-.953.953L3.354 7.01l1.06-1.06 1.4 1.433zM6.5 12.5a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-1a5 5 0 1 0 0-10 5 5 0 0 0 0 10z" fill="#76a338"></path></svg></use></svg> Premium</span>';
	}
}

/**
* Function to query update
**/
if( ! function_exists( 'onecom_query_check' ) ) {
	function onecom_query_check( $url ) {
		$url = add_query_arg(
			array(
				'wp' => ONECOM_WP_CORE_VERSION,
				'php' => ONECOM_PHP_VERSION
			), $url
		);
		return $url;
	}
}
/**
* Load OCI ajax handler
**/

/** Register OCI assets */
wp_register_style('one-wp-style', OCI_URL . "assets/css/style.css", null, null );
wp_register_script('one-wp-script', OCI_URL . "assets/js/script.js", array( 'jquery', 'thickbox', 'user-profile' ), null );
wp_add_inline_script( 'jquery', 'function oc_validate_action(action){return jQuery.ajax({url: _wpUtilSettings.ajax.url,type: "POST",dataType: "JSON",data:{action: \'oc_validate_action\',operation: action},error: function (xhr, textStatus, errorThrown) {oc_alert(\'<?php echo __("Some error occurred, please reload the page and try again.", "oci"); ?>\', \'error\', 5000)}});};' );

wp_localize_script( 'one-wp-script', 'oci', array( 'ajaxurl' => OCI_URL.'ajax.php' ) );

$scripts_to_print = array(
	'jquery',
	'one-wp-script',
    'thickbox'
);

/**
* Function to fetch themes
**/
if( ! function_exists( 'oci_fetch_themes' ) ) {
	function oci_fetch_themes() {
		$option_key = 'oci_themes';
		$themes = array();

		$url = onecom_query_check( MIDDLEWARE_URL.'/themes' );

		$url = add_query_arg(
			array(
				'item_count' => 1000
			), $url
		);

		$ip = onecom_get_client_ip_env();
		$domain = ( isset( $_SERVER[ 'ONECOM_DOMAIN_NAME' ] ) && ! empty( $_SERVER[ 'ONECOM_DOMAIN_NAME' ] ) ) ? $_SERVER[ 'ONECOM_DOMAIN_NAME' ] : 'localhost';

		if( empty( $themes ) || $themes == false ) {
			global $wp_version;
			$args = array(
			    'timeout'     => 5,
			    'httpversion' => '1.0',
			    'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			    'body'        => null,
			    'compress'    => false,
			    'decompress'  => true,
			    'sslverify'   => true,
			    'stream'      => false,
			    'headers'       => array(
		            'X-ONECOM-CLIENT-IP' => $ip,
		            'X-ONECOM-CLIENT-DOMAIN' => $domain
		        )
			); 
			$response = wp_remote_get( $url, $args );
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body );

			$themes = array();

			if( $body->success ) {
				$themes = $body->data->collection;
				if (is_array($themes) && !empty($themes)){
					foreach ($themes as $key=>$theme){
						if (isset($theme->slug) && $theme->slug === 'onecom-ilotheme'){
							unset($themes[$key]);
						}
					}
				}
			}

		}		

		return $themes;
	}
}

/**
 * Function to get the client ip address
 **/
if( ! function_exists( 'onecom_get_client_ip_env' ) ) {
	function onecom_get_client_ip_env() {
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = '0.0.0.0';
	 
	    return $ipaddress;
	}
}


/**
 * Function to buil URLs as per locale
 */
global $onecom_global_links;
$onecom_global_links = array();
$onecom_global_links[ 'en' ] = array(
	'premium_page' => 'https://www.one.com/en/wordpress-hosting'
);
$onecom_global_links[ 'cs_CZ' ] = array(
	'premium_page' => 'https://www.one.com/cs/wordpress'
);
$onecom_global_links[ 'da_DK' ] = array(
	'premium_page' => 'https://www.one.com/da/wordpress'
);
$onecom_global_links[ 'de_DE' ] = array(
	'premium_page' => 'https://www.one.com/de/wordpress'
);
$onecom_global_links[ 'es_ES' ] = array(
	'premium_page' => 'https://www.one.com/es/wordpress'
);
$onecom_global_links[ 'fr_FR' ] = array(
	'premium_page' => 'https://www.one.com/fr/wordpress'
);
$onecom_global_links[ 'it_IT' ] = array(
	'premium_page' => 'https://www.one.com/it/wordpress'
);
$onecom_global_links[ 'nb_NO' ] = array(
	'premium_page' => 'https://www.one.com/no/wordpress'
);
$onecom_global_links[ 'nl_NL' ] = array(
	'premium_page' => 'https://www.one.com/nl/wordpress-hosting'
);
$onecom_global_links[ 'pl_PL' ] = array(
	'premium_page' => 'https://www.one.com/pl/wordpress'
);
$onecom_global_links[ 'pt_PT' ] = array(
	'premium_page' => 'https://www.one.com/pt/wordpress'
);
$onecom_global_links[ 'fi' ] = array(
	'premium_page' => 'https://www.one.com/fi/wordpress'
);
$onecom_global_links[ 'sv_SE' ] = array(
	'premium_page' => 'https://www.one.com/sv/wordpress-hosting'
);

if( ! function_exists( 'onecom_generic_locale_link' ) ) {
	function onecom_generic_locale_link( $request, $locale, $lang_only=0 ) {
		global $onecom_global_links;
		if( ! empty( $onecom_global_links )  && array_key_exists( $locale, $onecom_global_links ) ) {

			if($lang_only != 0){ return strstr($locale, '_', true); }

			if( ! empty( $onecom_global_links[ $locale ][ $request ] ) ) {
				return $onecom_global_links[ $locale ][ $request ];
			}
		}

		if($lang_only != 0){ return 'en'; }

		return $onecom_global_links[ 'en' ][ $request ];
	}
}

/*
 * Filter out themes marked as hidden
 * */
if( ! function_exists( 'onecom_filter_hidden_themes' ) ) {
    function onecom_filter_hidden_themes($themesArr = [])
    {
        // return if empty themes array
        if (empty($themesArr)) {
            return $themesArr;
        }

        // iterate through themes array and filter out which are hidden
        foreach ($themesArr as $key => $theme) {
            if ($theme->hidden === "true" || $theme->hidden === true) {
                unset($themesArr[$key]);
            }
        }
        return $themesArr;
    }
}