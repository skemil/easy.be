<?php
/**
 * one.com Stats Push Functionality
 * version 0.1.2
 */

if (!class_exists('OCPushStats')) {

    final class OCPushStats
    {
        const STATS_URL = MIDDLEWARE_URL . '/collect/hit';
        const GENERIC_LOG_URL = MIDDLEWARE_URL . '/log';
        const ONECOM_DOMAIN = 'ONECOM_DOMAIN_NAME';
        const SERVER_AGENT = 'HTTP_USER_AGENT';
        const DOMAIN = 'domain';
        const SUB_DOMAIN = 'subdomain';
        const WP_USER = 'wp_user';
        const WP_ROLE = 'wp_role';
        const USER_AGENT = 'user_agent';
        const HIT_TYPE = 'hit_type';
        const ITEM_AVAIL = 'item_avail';
        const HOSTING_PACKAGE = 'hosting_package';
        const PACKAGE_FEATURES = 'package_features';
        const CONTENT_TYPE = "Content-Type: application/json";
        const ITEM_SOURCE = 'item_source';
        const ITEM_CATEGORY = 'item_category';
        const EVENT = 'event';
        const EVENT_ACTION = 'event_action';
        const ITEM_NAME = 'item_name';
        const HIT_DURATION = 'hit_duration';
        const REFERRER = 'referrer';
        const WP_VERSION = 'wp_version';
        const PLUGIN_VERSION = 'generic_plugin_version';
        const ADDDITIONAL_INFO = 'additional_info';
        const WP_LANG = 'wp_locale';
        const DEFAULT_IP = '0.0.0.0';
        const MOBILE_CHECKOUT = 'mobile_checkout';
        const CART_POSITION = 'cart_position';
        const COOKIE_POLICY_LINK_ENABLED = 'cookie_policy_link_enabled';
        const BANNER_STYLE = 'banner_style';
        const PREMIUM = 'premium';
        const PLUGIN = 'plugin';


        public function __construct()
        {
            if (function_exists('add_action')) {
                add_action('admin_head', array($this, 'javascript_handler'));
                add_action('wp_ajax_handle_ajax_request', array($this, 'handle_ajax_request'));
            }

            if (!class_exists('Onecom_Usage_Stats')) {
                require dirname(__FILE__).'/class-onecom-usage-stats.php';
                new Onecom_Usage_Stats();
            }
        }

        /**
         * Function to get the client ip address
         * */
        public static function onecom_get_client_ip_env()
        {
            if (getenv('HTTP_CLIENT_IP')) {
                $ipaddress = getenv('HTTP_CLIENT_IP');
            } else if (getenv('REMOTE_ADDR')) {
                $ipaddress = getenv('REMOTE_ADDR');
            } else {
                $ipaddress = self::DEFAULT_IP;
            }
            return $ipaddress;
        }

        /**
         * Function to get the domain
         * */
        public static function get_domain()
        {
            if (isset($_SERVER[self::ONECOM_DOMAIN]) && !empty($_SERVER[self::ONECOM_DOMAIN])) {
                return $_SERVER[self::ONECOM_DOMAIN];
            } else {
                return 'localhost';
            }
        }

        public static function get_subdomain()
        {
            if (self::get_domain() === 'localhost') {
                return null;
            }
            $subdomain = substr($_SERVER['SERVER_NAME'], 0, -(strlen($_SERVER['ONECOM_DOMAIN_NAME'])));
            if ($subdomain && $subdomain !== '') {
                return rtrim($subdomain, '.');
            } else {
                return 'www';
            }
        }

        /**
         * Function to get the user name and role
         * */
        public static function get_users()
        {
            if (function_exists('is_user_logged_in') && is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $oc_user = array();
                $oc_user['name'] = $current_user->display_name;
                $oc_user['role'] = $current_user->roles[0];

            } else {
                $oc_user['name'] = '';
                $oc_user['role'] = '';
            }
            return $oc_user;
        }

        /**
         * Function to send curl request to stats url
         * @param $payload
         * @return void|array|mixed
         */
        public static function curl_request($payload)
        {
            // Get cURL resource
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => self::STATS_URL,
                CURLOPT_RETURNTRANSFER => true,
	            CURLOPT_VERBOSE => false,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => array(
                    self::CONTENT_TYPE
                ),
            ));

            // silent call
            @curl_exec($curl);
            $err = curl_error($curl);

            // Close request to clear up some resources
            curl_close($curl);
            if ($err) {
                return [
                    'data' => null,
                    'error' => __("Some error occurred, please reload the page and try again.", "validator"),
                    'success' => false,
                ];
            }
            return true;
        }

        /**
         * Generic log function
         */
        public static function generic_log($action, $message = '', $error = null){
            // exit if no action passed
            if (!isset($action) && strlen($action)) {
                return false;
            }

            // check if WP constants are available
            if( !(defined('ABSPATH') && defined('WPINC'))){
                return false;
            }

            // wp http functions file
            $wp_http = ABSPATH . WPINC . '/http.php';

            // check if wp http functions file exists
            if(!file_exists($wp_http)){
                return false;
            }

            // include wp http functions file
            require_once $wp_http;

            // if message is not an array
            if(!is_array($message)){

                @json_decode($message);

                // if message is neither an array nor a JSON
                if(json_last_error() != JSON_ERROR_NONE){
                    $message = filter_var($message, FILTER_SANITIZE_STRING);
                }
            }
            else{
                $message = json_encode($message);
            }

            $payload = json_encode(
                array(
                    'action_type' => filter_var($action ,FILTER_SANITIZE_STRING),
                    'message' => $message,
                    'error' => $error
                )
            );

            // post request array
            $postArr = array(
                'method' => 'POST',
                'timeout' => 10,
                'compress' => false,
                'decompress' => true,
                'sslverify' => true,
                'stream' => false,
                'body' => $payload,
                'headers' => array(
                    'X-ONECOM-CLIENT-IP' => self::onecom_get_client_ip_env(),
                    'X-ONECOM-CLIENT-DOMAIN' => self::get_domain(),
                ),
            );

            // user agent
            global $wp_version;
            if(!empty($wp_version) && function_exists('home_url')){
                $postArr['user-agent'] = 'WordPress/' . $wp_version . '; ' . home_url();
            }
            wp_safe_remote_post(self::GENERIC_LOG_URL, $postArr);
            return true;
        }

        public static function oc_get_versions()
        {
            $ver = array();
            if (function_exists('get_plugin_data') && is_plugin_active('onecom-themes-plugins/onecom-themes-plugins.php')) {
                $ver[self::PLUGIN_VERSION] = get_plugin_data(WP_PLUGIN_DIR . '/onecom-themes-plugins/onecom-themes-plugins.php')['Version'];
            } else {
                $ver[self::PLUGIN_VERSION] = '';
            }
            $ver[self::WP_LANG] = (function_exists('get_locale')) ? get_locale() : '';
            $ver[self::WP_VERSION] = (function_exists('get_bloginfo')) ? get_bloginfo('version') : '';
            return $ver;
        }


        public static function stats_base_parametres()
        {
            $get_features = oc_validate_domain();
            $get_version = self::oc_get_versions();
            $package_features = isset($get_features['data']) ? json_encode($get_features['data']) : null;
            $hosting_package = isset($get_features['hosting_package']) ?$get_features['hosting_package']:null ;
            $user = self::get_users();
            $param = array();
            $param[self::DOMAIN] = self::get_domain();
            $param[self::SUB_DOMAIN] = self::get_subdomain();
            $param[self::WP_USER] = $user['name'];
            $param[self::WP_ROLE] = $user['role'];
            $param['ip'] = self::onecom_get_client_ip_env();
            $param['php_version'] = PHP_VERSION;
            $param[self::WP_LANG] = $get_version[self::WP_LANG];
            $param[self::WP_VERSION] = $get_version[self::WP_VERSION];
            $param[self::PLUGIN_VERSION] = $get_version[self::PLUGIN_VERSION];
            $param[self::USER_AGENT] = $_SERVER[self::SERVER_AGENT];
            $param[self::PACKAGE_FEATURES] = $package_features;
            $param[self::HOSTING_PACKAGE] = $hosting_package;
            return $param;
        }

        /**
         * Function to push stats for events of control panel
         * @param $event_action
         * @param null $item_category
         * @param null $item_name
         * @param null $referrer
         * @param array $additional_info
         * @return mixed
         */
        public static function push_stats_event_control_panel(
            $event_action,
            $item_category = null,
            $item_name = null,
            $referrer = null,
            $additional_info = array()
        )
        {
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::REFERRER => $referrer,

            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }


        /**
         * Function to push stats for events of themes and plugins
         * @param $event_action
         * @param null $item_category
         * @param null $item_name
         * @param null $referrer
         * @param array $additional_info
         * @return boolean
         */

        public static function push_stats_event_themes_and_plugins(
            $event_action,
            $item_category = null,
            $item_name = null,
            $referrer = null,
            $additional_info = array()
        )
        {
            $premium = 0;
            if ($item_category == 'theme') {
                if ($event_action !== 'preview' && $event_action !== 'install' && $event_action !== 'click_upgrade' && $event_action !== 'close_upgrade') {
                    $theme = wp_get_theme($item_name);
                    $premium = (int)onecom_is_premium_theme($theme->display('Name', False));
                } elseif (isset($additional_info[self::PREMIUM])) {
                    $premium = $additional_info[self::PREMIUM];
                } else {
                    $premium = 0;
                }
                $key = ($premium != 0) ? 'ptheme' : 'stheme';
            } elseif ($item_category == self::PLUGIN && $item_name === 'onecom-vcache') {
                $premium = 1;
                $key = 'pcache';

            } elseif ($item_category == self::PLUGIN) {
                $key = 'ins';
            } else {
                $key = null;
            }
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features($key, $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::ITEM_AVAIL => "$item_avail",
                self::REFERRER => $referrer,
                self::PREMIUM => "$premium",
            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info) && !isset($additional_info[self::PREMIUM])) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = (json_encode(array_merge($base_params, $dynamic_params)));
            self::curl_request($payload);
            return true;
        }

        /**
         * Function to push stats for events of staging
         * @param $event_action
         * @param null $item_category
         * @param null $hit_duration
         * @param null $size
         * @param array $additional_info
         * @return mixed
         */

        public static function push_stats_event_staging(
            $event_action,
            $item_category = null,
            $hit_duration = null,
            $size = null,
            $additional_info = array()
        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('stg', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_AVAIL => "$item_avail",
                self::HIT_DURATION => $hit_duration,
                'size' => $size,
            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }

        /**
         * Function to push stats for events of staging or migration
         * @param $event_action
         * @param null $item_category
         * @param null $item_name
         * @param string $item_avail
         * @param null $hit_duration
         * @param array $additional_info for logging any additional information related to event.
         * @return mixed
         */

        public static function push_stats_event_migration(
            $event_action,
            $item_category = null,
            $item_name = null,
            $item_avail = '1',
            $hit_duration = null,
            $additional_info = array()
        )
        {
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::ITEM_AVAIL => "$item_avail",
                self::HIT_DURATION => $hit_duration,

            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });

            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }

        public static function handle_ajax_request()
        {
            $event_action       = isset($_POST['args']['event_action']) ? $_POST['args']['event_action'] : '';
            $referrer           = isset($_POST['args'][self::REFERRER]) ? $_POST['args'][self::REFERRER] : '';
            $item_avail         = isset($_POST['args'][self::ITEM_AVAIL]) ? $_POST['args'][self::ITEM_AVAIL] : '';
            $additional_info    = isset($_POST['args'][self::ADDDITIONAL_INFO]) ? $_POST['args'][self::ADDDITIONAL_INFO] : '';
            $item_category      = isset($_POST['args'][self::ITEM_CATEGORY]) ? $_POST['args'][self::ITEM_CATEGORY] : '';
            $item_name          = isset($_POST['args'][self::ITEM_NAME]) ? $_POST['args'][self::ITEM_NAME] : '';
            $item_source        = isset($_POST['args'][self::ITEM_SOURCE]) ? $_POST['args'][self::ITEM_SOURCE] : '';
            $response = '';
            switch ($item_category) {
                case 'blog':
                case 'blog_installation':
                    $response = self::push_stats_event_control_panel($event_action, $item_category, $item_name, $referrer, $additional_info);
                    break;
                case 'theme':
                case self::PLUGIN
                :
                    $response = self::push_stats_event_themes_and_plugins($event_action, $item_category, $item_name, $referrer, $additional_info);
                    break;
                case 'staging':
                    $hit_duration = $_POST['args'][self::HIT_DURATION];
                    $size = $_POST['args']['size'];
                    $response = self::push_stats_event_staging($event_action,
                        $item_category,
                        $hit_duration,
                        $size,
                        $additional_info);
                    break;
                case 'migration':
                    $hit_duration = $_POST['args'][self::HIT_DURATION];
                    $response = self::push_stats_event_migration($event_action,
                        $item_category,
                        $item_name,
                        $item_avail,
                        $hit_duration,
                        $additional_info);
                    break;

                case 'setting':
                    switch ($item_source) {
                        case 'performance_cache':
                            $response = self::push_stats_performance_cache($event_action,
                                $item_category,
                                $item_source,
                                $additional_info);
                            break;
                        case 'cookie_banner':
                            $banner_style = $_POST['args']['banner_style'];
                            $cookie_policy_link_enabled = $_POST['args']['cookie_policy_link_enabled'];
                            $response = self::push_cookie_banner_stats_request($event_action,
                                $item_category,
                                $item_name,
                                $item_source,
                                $banner_style,
                                $cookie_policy_link_enabled,
                                $additional_info);
                            break;
                        case 'onephoto':
                            $response = self::push_stats_onephoto_request(
                                $event_action,
                                $item_source,
                                null,
                                null,
                                array('item_name'=> $item_name)
                            );
                            break;

                        case 'online_shop':
                            $cart_position = isset($_POST['args']['cart_position']) ? $_POST['args']['cart_position'] : '';
                            $mobile_checkout = isset($_POST['args']['mobile_checkout']) ? $_POST['args']['mobile_checkout'] : '';

                            $response = self::push_online_shop_stats_request($event_action,
                                $item_category,
                                $item_name,
                                $item_source,
                                $cart_position,
                                $mobile_checkout,
                                $additional_info);
                            break;
                        default:
                            $response = null;
                    }
                    break;
                default:
                    $response = null;
            }

            if ($response && function_exists('wp_send_json')) {
                wp_send_json($response);
            } elseif ($response && !function_exists('wp_send_json')) {
                echo json_encode($response);
            } else {
                return false;
            }
        }


        public static function javascript_handler()
        {
            ?>
            <script>
                function oc_push_stats_by_js(args) {
                    if (!args) {
                        return false;
                    }
                    if (typeof (args.item_category) !== 'undefined' && typeof (args.event_action) !== 'undefined') {
                        var data = {
                            'action': 'handle_ajax_request',
                            'args': args
                        };
                    } else {
                        return false;
                    }
                    jQuery.post(ajaxurl, data, function (response) {console.log(response);});
                }
            </script>

        <?php }


        /**
         * Function to push stats for event action pageview
         * @param $page_name
         * @param null $item_category
         * @param array $additional_info
         * @return array|mixed
         */

        public static function push_pageview_stats_request(
            $page_name,
            $item_category = null,
            $additional_info = array()
        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('stg', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => 'pageview',
                'page_name' => $page_name,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_AVAIL => "$item_avail",
            ];
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }

        /**
         * Function to push stats for events of one photo
         * @param $event_action
         * @param null $item_source
         * @param null $onephoto_email
         * @param null $image_count
         * @param null $video_count
         * @param array $additional_info
         * @return array|mixed
         */

        public static function push_stats_onephoto_request(
            $event_action,
            $item_source = null,
            $image_count = null,
            $video_count = null,
            $additional_info = array()

        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('ins', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_SOURCE => $item_source,
                self::ITEM_AVAIL => "$item_avail",
                'image_count' => $image_count,
                'video_count' => $video_count,
            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }

        /**
         * Function to push stats for events of performance cache
         * @param $event_action
         * @param $item_category
         * @param null $item_name
         * @param null $item_source
         * @param array $additional_info
         * @return array|mixed
         */

        public static function push_stats_performance_cache(
            $event_action,
            $item_category = null,
            $item_name = null,
            $item_source = null,
            $additional_info = array()
        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('pcache', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::ITEM_SOURCE => $item_source,
                self::ITEM_AVAIL => "$item_avail",
            ];

            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }

        /**
         * Function to push stats for events of cookie banner
         * @param $event_action
         * @param null $item_category
         * @param null $item_name
         * @param null $item_source
         * @param null $banner_style
         * @param null $cookie_policy_link_enabled
         * @param array $additional_info
         * @return array|mixed
         */

        public static function push_cookie_banner_stats_request(
            $event_action,
            $item_category = null,
            $item_name = null,
            $item_source = null,
            $banner_style = null,
            $cookie_policy_link_enabled = null,
            $additional_info = array()
        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('ins', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::ITEM_SOURCE => $item_source,
                self::ITEM_AVAIL => "$item_avail",
                self::BANNER_STYLE => $banner_style,
                self::COOKIE_POLICY_LINK_ENABLED => $cookie_policy_link_enabled,
            ];

            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }

        /**
         * Function to push stats for events of cookie banner
         * @param $event_action
         * @param null $item_category
         * @param null $item_name
         * @param null $check
         * @param array $scan_result
         * @param array $additional_info
         * @return array|mixed
         */

        public static function push_health_monitor_stats_request(
            $event_action,
            $item_category = null,
            $item_name = null,
            $check = null,
            $scan_result = array(),
            $additional_info = array()
        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('ins', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::ITEM_AVAIL => "$item_avail",
                'check' => $check,
                'scan_result' => json_encode($scan_result),
            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }


        /**
         * Function to push stats for events of online shop
         * @param $event_action
         * @param null $item_category
         * @param null $item_name
         * @param null $item_source
         * @param null $cart_position
         * @param null $mobile_checkout
         * @param array $additional_info
         * @return array|mixed
         */
        public static function push_online_shop_stats_request(
            $event_action,
            $item_category = null,
            $item_name = null,
            $item_source = null,
            $cart_position = null,
            $mobile_checkout = null,
            $additional_info = array()
        )
        {
            $result = oc_set_premi_flag(true);
            $item_avail = (int)oc_pm_features('ins', $result['data']);
            $base_params = self::stats_base_parametres();
            $dynamic_params = [
                self::HIT_TYPE => self::EVENT,
                self::EVENT_ACTION => $event_action,
                self::ITEM_CATEGORY => $item_category,
                self::ITEM_NAME => $item_name,
                self::ITEM_SOURCE => $item_source,
                self::ITEM_AVAIL => "$item_avail",
                self::CART_POSITION => $cart_position,
                self::MOBILE_CHECKOUT => $mobile_checkout,
            ];
            $dynamic_params = array_filter($dynamic_params, function ($value) {
                return !is_null($value) && $value !== '';
            });
            if (!empty($additional_info)) {
                $dynamic_params = array_merge($dynamic_params, $additional_info);
            }
            $payload = json_encode(array_merge($base_params, $dynamic_params));
            return self::curl_request($payload);
        }
    }
}
$data = new OCPushStats();