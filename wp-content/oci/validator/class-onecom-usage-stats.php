<?php
/**
 * one.com general wp usage stats
 * version 0.1.1
 */


if (!class_exists('Onecom_Usage_Stats')) {

	class Onecom_Usage_Stats
	{

		const HOSTING_PACKAGE = 'hosting_package';
		const VERSION = 'version';
		const OC_CU_CONSENT = 'oc_cu_consent';

		public function __construct()
		{
			if (!get_site_option(self::OC_CU_CONSENT)) {
				add_action('admin_notices', array($this, 'display_notice'));
				add_action('admin_init', array($this, 'notice_submission'));
				add_action('admin_head', array($this, 'oc_toggle'));
			}
			add_action('admin_init', array($this, 'check_status'));
			if (!function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
		}

		/**
		 * checks for the flag in db and push stats if timestamp is older than 30 days
		 */
		function check_status()
		{
			if (get_site_option(self::OC_CU_CONSENT) &&
			    is_numeric(get_site_option(self::OC_CU_CONSENT)) &&
			    get_site_option(self::OC_CU_CONSENT) < strtotime('-30 days')) {
				// update timestamp
				update_site_option(self::OC_CU_CONSENT, current_time('timestamp'));
				// push stats
				$this->prepare_payload();
			}
		}

		/**
		 * Displays notice if the flag is not present in database
		 */
		function display_notice()
		{
			if (get_site_option(self::OC_CU_CONSENT)) {
				return false;
			}
			$text = __('For delivering best customer experience, one.com wants to collect non-sensitive data from your website.', OC_VALIDATOR_DOMAIN);
			$link_txt = __('Which data?', OC_VALIDATOR_DOMAIN);
			$consent_text = __('Allow one.com to collect data.', OC_VALIDATOR_DOMAIN);

			$html = "<div class='notice notice-info'><p>{$text}&nbsp;<a href='javascript:;' onclick='ocToggle()'>{$link_txt}</a></p>";

			$html .= "<div class='oc-wht-data'><ul>
                            <li>" . __('Installed plugins', OC_VALIDATOR_DOMAIN) . "</li>
                            <li>" . __('Installed themes', OC_VALIDATOR_DOMAIN) . "</li>
                            <li>" . __('Counts of ', OC_VALIDATOR_DOMAIN) . ":
                            <ul>
                                <li>" . __('Posts') . "</li>
                                <li>" . __('Pages') . "</li>
                                <li>" . __('Media') . "</li>
                                <li>" . __('Products') . "</li>
                                <li>" . __('Comments') . "</li>
                                <li>" . __('Users') . "</li>
                            </ul></li>
                            <li>" . __('Staging exists', OC_VALIDATOR_DOMAIN) . "</li>
                            <li>" . __('Frontpage settings', OC_VALIDATOR_DOMAIN) . "</li>
                            <li>" . __('Multisite') . "</li>
                    </ul></div>";

			$html .= "<form  method='post'><input type='checkbox' checked name='oc_cu_consent'/>&nbsp;<span>{$consent_text}</span>&nbsp;&nbsp;";
			$html .= "<input type='hidden' name='oc-save-consent'/>";
			$html .= "<button class='button oc-wht-btn' type='submit' value='submit' >Save</button></form>";
			$html .= "</div>";

			echo $html;
			echo '<style>
                   .oc-wht-data ul{list-style: circle;padding-inline-start: 40px;}
                   .oc-wht-data ul > li > ul{list-style: disc;}
                   .oc-wht-data{display: none;}
                   .oc-wht-btn{vertical-align: middle!important;}
                </style>';
		}

		/**
		 * Handles notice submission form and calls the push stats function if cu provides consent
		 */
		function notice_submission()
		{
			if (isset($_POST['oc-save-consent'])) {
				if (isset($_POST[self::OC_CU_CONSENT]) && $_POST[self::OC_CU_CONSENT] == 'on') {
					if (update_site_option(self::OC_CU_CONSENT, current_time('timestamp'))) {
                        //push stats
						$this->prepare_payload();
					}
				} else {
					update_site_option(self::OC_CU_CONSENT, 'false');

					if(strpos('onecom-vcache',dirname(__FILE__))){
						$referrer= basename(dirname(dirname(dirname(dirname(__FILE__)))));
					} else{
						$referrer= basename(dirname(dirname(dirname(__FILE__))));
					}
					(class_exists('OCPushStats')?OCPushStats::push_stats_event_control_panel('deny','terms','statistics',"$referrer"):'');
				}
			}
		}

		/**
		 * Prepares payload for log request and calls the function responsible for curl request
		 */
		function prepare_payload()
		{
			$front_page_display = get_option('show_on_front') === 'posts';
			$posts_count = (int)wp_count_posts()->publish;
			$page_count = (int)wp_count_posts('page')->publish;
			$product_count = (post_type_exists('product')) ? (int)wp_count_posts('product')->publish : 0;
			$comments_count = (int)wp_count_comments()->approved;
			$media_counts = array_sum((array)wp_count_attachments());
			$staging_exists = get_option('onecom_staging_existing_staging') !== false;
			$get_features = oc_validate_domain();
			$package_features = isset($get_features['data']) ? $get_features['data'] : [];
			$hosting_package = isset($get_features[self::HOSTING_PACKAGE]) ? $get_features[self::HOSTING_PACKAGE] : '';
			$params = [
				'domain' => OCPushStats::get_domain(),
				'subdomain' => OCPushStats::get_subdomain(),
				'plugins' => $this->get_plugins_array(),
				'themes' => $this->get_themes_array(),
				'post_count' => $posts_count,
				'page_count' => $page_count,
				'media_count' => $media_counts,
				'woocommerce_product_count' => $product_count,
				'comment_count' => $comments_count,
				'wp_users' => $this->get_users_array(),
				'wp_version' => get_bloginfo(self::VERSION),
				'php_version' => PHP_VERSION,
				'package_features' => $package_features,
				'is_staging_exist' => $staging_exists,
				'wp_locale' => get_locale(),
				'wp_timezone' => get_option('timezone_string'),
				'is_posts_on_homepage' => $front_page_display,
				'is_multisite' => is_multisite(),
				self::HOSTING_PACKAGE => $hosting_package
			];
			$this->curl_request(json_encode($params));
		}

		/**
		 * gets and returns users array in required format
		 */
		function get_users_array()
		{
			$users = count_users();
			$user_arr = array();

			if (is_array($users['avail_roles'])) {

				foreach ($users['avail_roles'] as $role => $count) {
					$user_arr[$role] = "$count";
				}
			}
			return $user_arr;
		}

		/**
		 * gets and returns plugin array in required format
		 */
		function get_plugins_array()
		{
			$plugins = get_plugins();
			$plugin_arr = array();
			foreach ($plugins as $plugin => $data) {
				if (strpos($plugin, "/")) {
					$plugin_slug = substr($plugin, 0, strpos($plugin, "/"));
				} else {
					$plugin_slug = $plugin;
				}
				$plugin_arr[$plugin_slug]['name'] = $data['Name'];
				$plugin_arr[$plugin_slug]['uri'] = $data['PluginURI'];
				$plugin_arr[$plugin_slug][self::VERSION] = $data['Version'];
				$plugin_arr[$plugin_slug]['author'] = $data['Author'];
				$plugin_arr[$plugin_slug]['status'] = (is_plugin_active($plugin)) ? 'active' : 'inactive';
			}
			return $plugin_arr;
		}

		/**
		 * gets and returns themes array in required format
		 */
		function get_themes_array()
		{
			$themes = wp_get_themes();
			$theme_arr = array();
			$current_theme = get_template();
			foreach ($themes as $theme => $data) {
				$theme_arr[$theme]['name'] = $data->get('Name');
				$theme_arr[$theme]['uri'] = $data->get('ThemeURI');
				$theme_arr[$theme][self::VERSION] = $data->get('Version');
				$theme_arr[$theme]['author'] = $data->get('Author');
				$theme_arr[$theme]['status'] = ($theme == $current_theme) ? 'active' : 'inactive';
			}
			return $theme_arr;
		}

		/**
		 * executes the curl request
		 */
		function curl_request($payload)
		{
			// Get cURL resource
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => MIDDLEWARE_URL . '/collect/usage',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_VERBOSE => false,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $payload,
				CURLOPT_HTTPHEADER => array(
					"Content-Type: application/json"
				),
			));
			@curl_exec($curl);
			$err = curl_error($curl);
			//$response = json_decode($response, true);
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
		 * prints jquery for toggle effect
		 */
		function oc_toggle()
		{
			?><script type="text/javascript">function ocToggle() {jQuery('.oc-wht-data').toggle();}</script><?php
		}
	}
}
