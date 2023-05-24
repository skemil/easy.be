<?php

// block direct access
if (!defined('ABSPATH')) {
    die();
}

/*
 * API class
 * */
class OnecomPluginsApi extends WP_REST_Controller
{
    public $errorTemplate = array(
        'error' => true,
        'data' => null,
        'message' => 'Some error occurred.',
        'code' => 501
    );

    public $itemTemplate = array(
        'id' => '',
        'title' => "Title of the bullet",
        'description' => "Description of the bullet",
        'category' => "Performance",
        'issue' => 0,
    );

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $namespace = 'onecom-plugins/v' . ONECOM_PLUGIN_API_VERSION;
        register_rest_route($namespace, '/get', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_status'),
                    //'permission_callback' => array($this, 'validate_token'),
                    'args' => array(),
                ),
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args' => $this->get_endpoint_args_for_item_schema(true),
                ),
            )
        );
    }

    /**
     * Get Performance cache status
     * @return int
     */
    public function get_pcache_status(): int {
        return "true" === get_site_option('varnish_caching_enable', 'false') ? 1 : 0;
    }

    /**
     * Get Error page status
     * @return int
     */
    public function get_error_page_status(): array {
        $error_page = new Onecom_Error_Page();
        $error_class_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'fatal-error-handler.php';
        $status = (file_exists($error_class_path) && $error_page->is_onecom_plugin()) ? 1 : 0;
        return array(
            "status" => $status,
            "wp_page" => "admin.php?page=onecom-wp-error-page",
        );
    }

    /**
     * Get get_restricted_uploads_status
     * @return array
     */
    public function get_restricted_uploads_status(): array {
        $extensions = new OnecomFileSecurity();
        $files = $extensions->get_htaccess_extensions();
        return array(
            'status' => (int) !empty($files),
            'files' => $files,
            'wp_page' => "admin.php?page=onecom-wp-health-monitor",
        );
    }

    /**
     * Get get_uc_status
     * @return array
     */
    public function get_uc_status(): array {
        $template = array(
            "status" => 1,
            "wp_page" => "admin.php?page=onecom-wp-under-construction"
        );

        $plugin_exists = (is_plugin_active("onecom-under-construction/onecom-under-construction.php") ? 1 : 0);

        $settings = (array) get_site_option("onecom_under_construction_info");

        if(
            0 == $plugin_exists ||
            empty($settings) ||
            "" == $settings ||
            !array_key_exists("uc_status", $settings) ||
            "on" !== $settings["uc_status"]
        ){
            $template["status"] = 0;
            return $template;
        }

        return $template;
    }

    /**
     * Get get_spam_protection_status
     * @return array
     */
    public function get_spam_protection_status(): array {
        $template = array(
            "status" => 1,
            "wp_page" => "admin.php?page=onecom-wp-spam-protection"
        );

        $plugin_exists = (is_plugin_active("onecom-spam-protection/onecom-spam-protection.php") ? 1 : 0);

        $settings = get_site_option("one_sp_protect_options");

        if (0 === $plugin_exists || empty($settings)) {
            $template["status"] = 0;
            return $template;
        }

        return $template;
    }

    /**
     * Get Performance cache wp-admin page url
     * @return string
     */
    public function get_pcache_page(): string {
        return 'admin.php?page=onecom-vcache-plugin';
    }

    /**
     * Get Health monitor wp-admin page url
     * @return string
     */
    public function get_health_monitor_page(): string {
        return 'admin.php?page=onecom-wp-health-monitor';
    }

    /**
     * Get CDN cache status
     * @return int
     */
    public function get_cdn_status(): int {
        return "true" === get_site_option('oc_cdn_enabled', 'false') ? 1 : 0;
    }

    /**
     * Get action name based on action slug
     * @return array
     */
    public function get_health_monitor_action_name($key, $state=0):array {

        $category = array(
            "Performance" => array(
                'php_updates',
                'wp_connection',
                'check_ssl',
                'dis_plugin',
                'uploads_index',
                'options_table_count',
                'check_staging_time',
                'check_backup_zip',
                'check_performance_cache',
                'check_updated_long_ago',
                'check_pingbacks',
            ),
            "Security" => array(
                'debug_mode',
                'plugin_updates',
                'theme_updates',
                'wp_updates',
                'usernames',
                'file_edit',
                'database',
                'auto_updates',
                'file_execution',
                'file_permissions',
            ),
            "Issues" => array(
                'check_staging_time',
                'options_table_count',
                'woocommerce_sessions',
                'check_backup_zip',
                'core_updates',
                'uploads_index',
            )
        );

        $state = (int) (2 == $state) ? 0 : $state;

        $item = $this->itemTemplate;
        $item['id'] = $key;
        $item['category'] = false === array_search($key, $category["Performance"], true) ? "Security" : "Performance";
        $item['title'] = $key."_title_".$state;
        $item['description'] = $key."_desc_".$state;
        $item['needs_action'] = $state;
        $item['issue'] = (0 === $state || false === array_search($key, $category["Issues"], true)) ? 0 : 1;

        return $item;
    }

    /**
     * Force scan health monitor
     * @return void
     */
    public function health_monitor_scan() :void{

        require_once ONECOM_WP_PATH.'/inc/functions.php';
        require_once trailingslashit(plugin_dir_path(__DIR__))."health-monitor/inc/functions.php";

        oc_sh_check_php_updates();
        oc_sh_check_plugin_updates();
        oc_sh_check_theme_updates();
        oc_sh_check_wp_updates();
        oc_sh_check_ssl();
        oc_sh_check_permission();
        oc_sh_check_db_security();
        oc_sh_check_file_editing();
        oc_sh_check_usernames();
        oc_sh_check_plugins();

        /*$HMAjax = new OnecomHealthMonitorAjax();
        $HMAjax->uploads_index_cb();

        if ( class_exists( 'woocommerce' ) ) {
            $HMAjax->woocommerce_session();
        }
        $HMAjax->options_table_count();
        $HMAjax->staging_time();
        $HMAjax->backup_zips();
        $HMAjax->performance_cache();
        $HMAjax->updated_long_ago();
        $HMAjax->pingbacks();*/
    }

    /**
     * Get health monitor status based on score
     * @return string
     *
     */
    public function get_status_on_score($score):string {

        // seat belt
        if(empty($score)){
            return false;
        }

        // calculate status based on the score
        if(75 < $score){
            $status = __("Healthy", OC_PLUGIN_DOMAIN);
        }
        elseif (50 < $score){
            $status = __("Fair", OC_PLUGIN_DOMAIN);
        }
        else{
            $status = __("Unhealthy", OC_PLUGIN_DOMAIN);
        }
        return $status;
    }

    /**
     * Get health monitor last scan result from DB
     * @return array
     */
    public function get_health_monitor_recent_results(){
        $cache = get_site_transient("ocsh_site_scan_result");
        if(empty($cache) || !is_array($cache)){
            self::health_monitor_scan();
        }
        return get_site_transient("ocsh_site_scan_result");
    }

    /* Get Health monitor status */
    public function get_health_monitor_status(){

        $health_scan = self::get_health_monitor_recent_results();

        $site_scan_result       = oc_sh_calculate_score($health_scan);
        $status['score']        = round($site_scan_result['score']);
        $status['status']       = self::get_status_on_score($status['score']);


        // remove the things which are not needed.
        if(array_key_exists('time', $health_scan)){
            $status['last_scan'] = $health_scan["time"];
            unset($health_scan['time']);
        }

        $actions = [];
        foreach ($health_scan as $slug => $state) {
            if(is_null($slug) || is_null($state)){ continue; }
            $temp = $this->get_health_monitor_action_name($slug, $state);
            $actions[] = $temp;
        }
        $status['actions'] = $actions;
        $status['wp_page'] = self::get_health_monitor_page();

        return $status;
    }

    /**
     * WP-admin shortcuts
     * @return array
     */
    public function wp_shortcuts(){
        $links = [];

        $links['customise']['title'] = 'customize_your_site';
        $links['customise']['wp_path'] = 'customize.php';

        $links['add_post']['title'] = 'add_a_blog_post';
        $links['add_post']['wp_path'] = 'post-new.php';

        $links['edit_frontpage']['title'] ='edit_your_frontpage';

        // assuming the blog page is set as frontpage.
        $links['edit_frontpage']['wp_path'] = 'edit.php';

        // check if static page set as frontpage
        if(!empty(get_site_option( 'page_on_front' ))){
            $links['edit_frontpage']['wp_path'] = 'post.php?post='. (int) get_site_option( 'page_on_front' );
        }

        $links['view_site']['title'] = 'view_your_site';
        $links['view_site']['wp_path'] = '/';

        $links['add_page']['title'] = 'add_additional_pages';
        $links['add_page']['wp_path'] = 'post-new.php?post_type=page';

        $links['manage_plugins']['title'] =  'manage_plugins';
        $links['manage_plugins']['wp_path'] = 'plugins.php';

        return $links;
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_status($request)
    {

        // backward compatibility
        if(!defined('REST_REQUEST')){
            define('REST_REQUEST', true);
        }

        // exit if not authenticated
        $error = self::validate_token($request);
        if(true !== $error){
            return $error;
        }

        // any error occurred?
        $status['error'] = null;

        // PHP version
        $status['data']['php'] = phpversion();

        // WP core version
        $status['data']['wp'] = get_bloginfo('version');

        // One.com plugin exists?
        $status['data']['onecom_plugin_exists'] = (is_plugin_active("onecom-themes-plugins/onecom-themes-plugins.php") ? 1 : 0);

        // Favicon
        $status['data']['site_icon'] = get_site_icon_url( 64, includes_url( 'images/w-logo-blue.png' ));

        // Get Health monitor, Performance cache, CDN, Error page settings
        if($status['data']['onecom_plugin_exists']){
            $status['data']['health_monitor']       = self::get_health_monitor_status();
            $status['data']['error_page']           = self::get_error_page_status();
            // $status['data']['restricted_uploads']   = self::get_restricted_uploads_status();

            $status['data']['under_construction'] = self::get_uc_status();

            $status['data']['spam_protection']   = self::get_spam_protection_status();
        }


        if(is_plugin_active("onecom-vcache/vcaching.php")){
            $status['data']['cdn']['status']        = self::get_cdn_status();
            $status['data']['cdn']['wp_page']       = self::get_pcache_page();

            $status['data']['cache']['status']      = self::get_pcache_status();
            $status['data']['cache']['wp_page']     = self::get_pcache_page();
        }

        $status['data']['wp_shortcuts'] = self::wp_shortcuts();

        $status["message"] = __("Success.", OC_PLUGIN_DOMAIN);
        return new WP_REST_Response($status, 200);
    }


    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function validate_token($request)
    {

        // collect params from request
        $params = $request->get_params();

        // prepare error response
        $err = $this->errorTemplate;

        // check if token received
        if(!isset($params["onecom-auth"]) || empty($params["onecom-auth"])){
            $err['message'] = 'Token missing.';
            $err['code'] = 401;
            return new WP_REST_Response($err, $err['code']);
        }

        // check if required functionality exists
        if(!class_exists("OCLAUTH")) {
            $err['message'] = 'Required functionality to handle this request either missing or the plugin is outdated.';
            return new WP_REST_Response($err, $err['code']);
        }

        // check token
        $auth = new OCLAUTH();
        $check = $auth->checkToken($params["onecom-auth"]);

        // if token was invalid
        if(!empty($check) && false === $check["error"]){
            return true;
        }
        else if(!empty($check) && false !== $check["error"]){
            $err['message'] = $check["message"];
            $err['code'] = 400;
            return new WP_REST_Response($err, $err['code']);
        }
        else{
            // unknown error case
            $err['message'] = 'Unknown error occurred';
            $err['code'] = 501;
            return new WP_REST_Response($err, $err['code']);
        }

    }
}