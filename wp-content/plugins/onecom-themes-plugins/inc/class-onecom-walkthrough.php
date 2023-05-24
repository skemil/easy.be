<?php

/**
 * adds one.com walkthrough tour to wordpress admin
 */


if(!class_exists('Onecom_Walkthrough')) {

    class Onecom_Walkthrough
    {


        const BUTTON_2 = 'button2';
        const CONTENT = 'content';
        const PARENT = 'parent';
        const H3_CLOSE = '</h3>';
        const H3_START = '<h3>one.com';
        const VCACHE_PLUGIN='onecom-vcache/vcaching.php';
        const WEBSHOP_PLUGIN='onecom-webshop/webshop.php';
        const WEBSHOP_SETTINGS='one-webshop-settings';
        const PHP_SCANNER = 'onecom-php-scanner/onecom-compatibility-scanner.php';
        const SCANNER_PAGE='onecom-php-compatibility-scanner';
        const SCREEN='screen';
        const OC_FUNCTION = 'function';
        const DISMISSED_POINTERS='dismissed_wp_pointers';


        // Initiate construct
        function __construct () {
            add_action('admin_enqueue_scripts', array($this, 'oc_enqueue_scripts'));  // Hook to admin_enqueue_scripts
            add_action('wp_ajax_ocwkt_reset_tour',array($this,'oc_walkthrough_reset_tour'));
            add_action('admin_head-index.php', array($this, 'onecom_restart_tour'));

        }



        function oc_enqueue_scripts () {

            // Check to see if user has already dismissed the pointer tour
            $dismissed = explode (',', get_user_meta (wp_get_current_user ()->ID, self::DISMISSED_POINTERS, true));
            $do_tour = !in_array ('oc_walthrough_pointer', $dismissed);

            // If not, we are good to continue
            if ($do_tour) {

                // Enqueue WP pointer scripts and styles
                wp_enqueue_style ('wp-pointer');
                wp_enqueue_script ('wp-pointer');

                // Finish hooking to WP admin areas
                add_action('admin_print_footer_scripts', array($this, 'walkthrough_footer_scripts'));  // Hook to admin footer scripts
            }
            add_action('admin_head', array($this, 'css_admin_head'));  // Hook to admin head
        }

        // Used to add css of walkthrough
        function css_admin_head () { ?>
            <style type="text/css">#pointer-primary,#oc-pointer {margin: 0 0 0 5px;} .ocwt_pointer .button-secondary{border-color: transparent !important;background-color: transparent !important;} .ocwt_pointer{z-index:99999!important;}  .oc-reset-wlk-tour span.oc_reset{text-decoration: none !important;color: #d5d5d5;float: left;margin-right: 10px;}</style>
        <?php }

        // Define footer scripts
        function walkthrough_footer_scripts () {


            $tour = $this->oc_generate_array();


            // Determine the current page in query parameter
            $page = isset($_GET['page']) ? $_GET['page'].'-tour' : '';
            $tab = isset($_GET['tab']) ? $_GET['tab'] : '';

//          // Define other variables
            $function = '';
            $button2 = '';
            $options = array ();
            $show_pointer = false;


            // ****************************************************************************************************************
            // This will be the first pointer shown to the user on the dashboard and plugins screen
            // ****************************************************************************************************************
            if (!array_key_exists($page, $tour) && get_current_screen()->base=='dashboard' || get_current_screen()->base=='plugins' ) {

                $show_pointer = true;
                $parent= false;

                $default_tour=$this ->oc_begin_tour();
                $id = $default_tour['id'];  // Define ID used on page html element where we want to display pointer
                $options = $default_tour['options'];
                $button2 = $default_tour[self::BUTTON_2];
                $function = $default_tour[self::OC_FUNCTION];
                $screen= $default_tour[self::SCREEN];
            }elseif ($page != '' && in_array ($page, array_keys ($tour) ) && $tab == '') {

                $show_pointer = true;
                $parent=true;
                $screen=$page;
                if (isset ($tour[$page]['id'])) {
                    $id = $tour[$page]['id'];
                }

                $options = array (
                    self::CONTENT => $tour[$page][self::CONTENT],
                    'position' => array ('edge' => 'left', 'align' => 'left')
                );

                $button2 = false;
                $function = '';

                if (isset ($tour[$page][self::BUTTON_2])) {
                    $button2 = $tour[$page][self::BUTTON_2];
                }
                if (isset ($tour[$page][self::OC_FUNCTION])) {
                    $function = $tour[$page][self::OC_FUNCTION];
                }

                $parent = $tour[$page][self::PARENT];

            }

            if ($show_pointer) {
                $this->make_pointer_script ($id, $options, __('Close', OC_PLUGIN_DOMAIN), $button2, $function,$parent,$screen);
            }
        }



        /**
         * generates parameters array for first pointer
         */
        public function oc_begin_tour(){
            $default_tour=array();
            $default_tour['id'] = '#toplevel_page_onecom-wp';
            $default_tour[self::CONTENT] = '<h3>one.com ' . __('Features', OC_PLUGIN_DOMAIN) . self::H3_CLOSE;
            $default_tour[self::CONTENT] .= wpautop(__('Welcome to one.com features tour!', OC_PLUGIN_DOMAIN));
            $default_tour[self::CONTENT] .= wpautop(__('Here you can see how to make the best use of one.com features to power up your website.', OC_PLUGIN_DOMAIN));
            $default_tour[self::CONTENT] .= wpautop(sprintf(__('Click "Begin tour" to get started', OC_PLUGIN_DOMAIN), '<a href="'.admin_url("upload.php").'" target="_blank">', '</a>'));

            $default_tour['options'] = array (
                self::CONTENT => $default_tour[self::CONTENT],
                'position' => array ('edge' => 'left','align' => 'left')
            );

            $default_tour[self::BUTTON_2] = __('Begin tour', OC_PLUGIN_DOMAIN );
            $default_tour[self::OC_FUNCTION] = 'document.location="' . menu_page_url('onecom-wp-themes',false) . '";';

            if(get_current_screen()->base=='dashboard'){
                $default_tour[self::SCREEN]= 'wp_dashboard';
            }else{

                $default_tour[self::SCREEN] = 'plugins_page';

            }

            return $default_tour;

        }


        /**
         * returns array which will be used for generating pointers(apart from default)
         */

        public function oc_generate_array($arr=array()){


            $themes_page = $this->onecom_theme_page_tour();
            $plugins_page = $this->onecom_plugins_tour();
            $staging_page = $this->onecom_staging_tour();
            $health_monitor_page= $this -> onecom_health_monitor_tour();
            $cookie_banner_page= $this -> onecom_cookie_banner_tour();
            $error_page = $this->onecom_error_page_tour();
            $performance_cache_page= $this -> onecom_performance_cache_tour();
            $webshop= $this -> onecom_webshop_tour();
            $php_scanner= $this -> onecom_php_scanner_tour();
            $one_photo= $this ->onecom_onephoto_tour();

            $tours= array (
                'onecom-wp-themes-tour' => $themes_page,
                'onecom-wp-plugins-tour' => $plugins_page,
                'onecom-wp-staging-tour' => $staging_page,
                'onecom-wp-health-monitor-tour' => $health_monitor_page,
                'onecom-wp-cookie-banner-tour' => $cookie_banner_page,
                'onecom-wp-error-page-tour'=> $error_page,
                'onecom-vcache-plugin-tour' => $performance_cache_page,
                'one-webshop-settings-tour' => $webshop,
                'onecom-php-compatibility-scanner-tour' => $php_scanner,
                'oc_onephoto-tour'=> $one_photo,

            );





            if(isset($arr) && !empty($arr)){

                return array_merge($tours,$arr);
            }

            return $tours;


        }

        /**
         * generates parameters array for themes
         */
        public function onecom_theme_page_tour(){
            return array (
                'id' => '#onecom_themes',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Themes') . self::H3_CLOSE
                    . '<p>' . __('Exclusive themes specially crafted for One.com customers.', OC_PLUGIN_DOMAIN) . '</p>',
                self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-wp-plugins',false) . '"' ,
                self::PARENT => true
            );

        }

        /**
         * generates parameters array for plugins
         */

        public function onecom_plugins_tour(){
            return array (
                'id' => '#onecom_plugins',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Plugins') . self::H3_CLOSE
                    . '<p>' . __('Plugins that bring the One.com experience and services to WordPress.', OC_PLUGIN_DOMAIN) . '</p>',
                self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-wp-staging',false) . '"' ,
                self::PARENT => true
            );

        }

        /**
         * generates parameters array for staging
         */

        public function onecom_staging_tour(){
            return array (
                'id' => '#onecom_staging',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Staging',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('Create a staging version of your site to try out new plugins, themes and customizations', OC_PLUGIN_DOMAIN) . '.</p>',
                self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-wp-health-monitor',false) . '"' ,
                self::PARENT => true
            );
        }

        /**
         * generates parameters array for health monitor
         */

        public function onecom_health_monitor_tour(){
            return  array (
                'id' => '#onecom_health_monitor',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Health Monitor',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('Health Monitor lets you monitor the essential security checkpoints and fix them if needed.', OC_PLUGIN_DOMAIN) . '</p>',
                self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-wp-cookie-banner',false) . '"' ,
                self::PARENT => true
            );

        }

        /**
         * generates parameters array for cookie banner
         */

        public function onecom_cookie_banner_tour(){
            return  array (
                'id' => '#onecom_cookie_banner',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Cookie Banner',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('Show a banner on your website to inform visitors about cookies and get their consent.', OC_PLUGIN_DOMAIN) . '</p>',
                self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-wp-error-page',false) . '"' ,
                self::PARENT => true
            );

        }

        /**
         * generates parameters array for error page & checks for other installed plugins
         */


        public function onecom_error_page_tour()
        {

            $error_page = array(
                'id' => '#onecom_errorpage',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Error page', OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('Display useful information if there is a problem on your site. This information will be visible only to the admin users.', OC_PLUGIN_DOMAIN) . '</p>',
                self::PARENT => true


            );

            if (is_plugin_active('onecom-onephoto/onecom-onephoto.php')) {

                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url('oc_onephoto', false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $error_page = array_merge($error_page, $button2);


            }elseif(is_plugin_active(self::VCACHE_PLUGIN)) {

                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-vcache-plugin', false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $error_page = array_merge($error_page, $button2);


            }elseif (is_plugin_active(self::WEBSHOP_PLUGIN)){

                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::WEBSHOP_SETTINGS, false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $error_page = array_merge($error_page, $button2);


            }elseif (is_plugin_active(self::PHP_SCANNER)){
                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::SCANNER_PAGE, false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $error_page = array_merge($error_page, $button2);


            }
            return $error_page;

        }

        /**
         * generates parameters array for one photo & checks for other installed plugins
         */

        public function onecom_onephoto_tour(){

            if (!is_plugin_active('onecom-onephoto/onecom-onephoto.php')){

                return false;
            }

            $onephoto = array (
                'id' => '#toplevel_page_oc_onephoto',
                self::CONTENT => self::H3_START .'&nbsp;'. __('One Photo',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('Download images and videos from your One Photo gallery(s) right inside WordPress media library.', OC_PLUGIN_DOMAIN) . '</p>',
                self::PARENT => false
            );
            if (is_plugin_active(self::VCACHE_PLUGIN)) {

                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url('onecom-vcache-plugin', false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $onephoto = array_merge($onephoto, $button2);


            }elseif (is_plugin_active(self::WEBSHOP_PLUGIN)){

                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::WEBSHOP_SETTINGS, false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $onephoto = array_merge($onephoto, $button2);


            }elseif (is_plugin_active(self::PHP_SCANNER)) {
                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::SCANNER_PAGE, false) . '"'
                );

                $onephoto = array_merge($onephoto, $button2);

            }

            return $onephoto;




        }

        /**
         * generates parameters array for performance cache & checks for other installed plugins
         */

        public function onecom_performance_cache_tour(){

            if (!is_plugin_active(self::VCACHE_PLUGIN)){

                return false;
            }
            $performance_cache = array (
                'id' => '#toplevel_page_onecom-vcache-plugin',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Performance Cache',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('One.com Performance Cache improves your website\'s performance', OC_PLUGIN_DOMAIN) . '.</p>',
                self::PARENT => false
            );

            if (is_plugin_active(self::WEBSHOP_PLUGIN)){

                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::WEBSHOP_SETTINGS, false) . '"'  // We are relocating to "Settings" page with the 'site_title' query var
                );
                $performance_cache = array_merge($performance_cache, $button2);


            }elseif (is_plugin_active(self::PHP_SCANNER)) {
                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::SCANNER_PAGE, false) . '"'
                );

                $performance_cache = array_merge($performance_cache, $button2);

            }

            return $performance_cache;




        }

        /**
         * generates parameters array for web shop & checks for other installed plugins
         */

        public function onecom_webshop_tour(){

            if (!is_plugin_active(self::WEBSHOP_PLUGIN)){

                return false;
            }
            $webshop = array (
                'id' => '#toplevel_page_one-webshop',
                self::CONTENT => self::H3_START .'&nbsp;'. __('Online Shop',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('Easily create an online shop for your business.', OC_PLUGIN_DOMAIN) . '</p>',
                self::PARENT => false
            );

            if (is_plugin_active(self::PHP_SCANNER)) {
                $button2 = array(
                    self::BUTTON_2 => __('Next', OC_PLUGIN_DOMAIN),
                    self::OC_FUNCTION => 'window.location="' . menu_page_url(self::SCANNER_PAGE, false) . '"'
                );

                $webshop = array_merge($webshop, $button2);

            }

            return $webshop;




        }

        /**
         * generates parameters array for php scanner & checks for other installed plugins
         */

        public function onecom_php_scanner_tour(){

            if (!is_plugin_active(self::PHP_SCANNER)){

                return false;
            }
            return array (
                'id' => '#toplevel_page_onecom-php-compatibility-scanner',
                self::CONTENT => self::H3_START .'&nbsp;'. __('PHP compatibility scanner',OC_PLUGIN_DOMAIN) . self::H3_CLOSE
                    . '<p>' . __('The PHP compatibility scanner can be used on any WordPress website on any web host.', OC_PLUGIN_DOMAIN) . '</p>',
                self::PARENT => false
            );


        }



        /**
         * generates Jquery script for the pointers
         */

        // Print footer scripts
        function make_pointer_script ($id, $options, $button1, $button2=false, $function='',$parent=false,$screen='') { ?>
            <script type="text/javascript">

                (function ($) {

                    $(document).ready(function () {

                        // Define pointer options
                        var wp_pointers_tour_opts = <?php echo json_encode ($options); ?>, setup;

                        var id= '<?php echo $id; ?>';
                        var screen= '<?php echo $screen ;?>';


                        wp_pointers_tour_opts = $.extend (wp_pointers_tour_opts, {

                            pointerClass: 'ocwt_pointer',

                            // Add 'Close' button
                            buttons: function (event, t) {

                                button = jQuery ('<a id="ocwk-pointer-close" href="javascript:;"  class="button-secondary">' + '<?php echo $button1; ?>' + '</a>');
                                button.bind ('click.pointer', function () {
                                    t.element.pointer ('close');

                                });
                                return button;
                            },

                            close: function () {

                                // Post to admin ajax to disable pointers when user clicks "Close"
                                $.post (ajaxurl, {
                                    pointer: 'oc_walthrough_pointer',
                                    action: 'dismiss-wp-pointer'
                                });

                                var args= {
                                    'event_action': 'close',
                                    'item_category': 'blog',
                                    'item_name': 'onecom_tour',
                                    'referrer': screen,
                                }

                                oc_push_stats_by_js(args);
                            }


                        });

                        // This is used for our "button2" value above (advances the pointers)
                        setup = function () {

                            <?php  if($parent) { ?>
                            $('<?php echo $id; ?>').parent().pointer(wp_pointers_tour_opts).pointer('open');

                            <?php      }else{ ?>
                            $('<?php echo $id; ?>').pointer(wp_pointers_tour_opts).pointer('open');


                            <?php }
                            if ($button2) { ?>

                            if(id = '#toplevel_page_oc_onephoto'){


                                setTimeout(function(){
                                    $(".ocwt_pointer #pointer-primary").removeAttr('id').attr("id","oc-pointer");
                                }, 3000);


                                $('.wp-pointer-buttons').on('click','#oc-pointer', function() {
                                    <?php echo $function; ?>// Execute button2 function

                                    if(id='#toplevel_page_onecom-wp'){

                                        var args= {
                                            'event_action': 'start',
                                            'item_category': 'blog',
                                            'item_name': 'onecom_tour',
                                            'referrer': screen,
                                        }

                                        oc_push_stats_by_js(args); // for pushing stats

                                    }

                                });



                            }

                            jQuery ('#ocwk-pointer-close').before ('<a id="pointer-primary" class="button-primary">' + '<?php echo $button2; ?>' + '</a>');
                            jQuery ('#pointer-primary').click (function () {
                                <?php echo $function; ?>  // Execute button2 function
                            });

                            <?php } ?>
                        };

                        if (wp_pointers_tour_opts.position && wp_pointers_tour_opts.position.defer_loading) {

                            $(window).bind('load.wp-pointers', setup);
                        }
                        else {
                            setup ();
                        }

                    });
                }) (jQuery);
            </script>
            <?php
        }






        function oc_walkthrough_reset_tour(){
            $pointers = get_user_meta(get_current_user_id(), self::DISMISSED_POINTERS, true);
            $pointersArr = explode(',', $pointers);
            $pointer_key = array_search('oc_walthrough_pointer', $pointersArr);
            if($pointer_key!==false) {
                unset($pointersArr[$pointer_key]);
            }

            $newpointers = join(",",$pointersArr);
            if($newpointers === $pointers){
                die(json_encode(array("status"=>false)));
            }elseif(update_user_meta(get_current_user_id(), self::DISMISSED_POINTERS, $newpointers)){
                die(json_encode(array("status"=>true)));
            }
        }

        function onecom_restart_tour(){
            ?>
            <script  type="text/javascript">

                jQuery(document).ready(function () {
                    jQuery(".oc-reset-wlk-tour a").on('click', function (e) {
                        e.preventDefault();
                        jQuery.post (ajaxurl,
                            {
                                'action': 'ocwkt_reset_tour',
                                'nonce': 'asdsadsad'
                            },
                            function (response) {
                                if ("object" === typeof response) {
                                    let pURL = window.location.href;

                                    window.location.href=pURL;


                                } else {
                                    console.log('Could not restart tour. Retrying..');
                                }
                            },
                            'json',
                            false,
                            0
                        );
                    });
                })
            </script>

            <?php
        }

    }
}