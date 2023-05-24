<?php
/**
 * adds one.com Shortcuts to wordpress admin
 */


if(!class_exists('Onecom_Shortcuts')) {
    class Onecom_Shortcuts
    {


        public $url;
        public $plugin_url;
        public $theme_url;
        public $staging_url;
        public $cookie_banner_url;
        public $health_tooltip;
        public $theme_tooltip;
        public $plugin_tooltip;
        public $onephoto_tooltip;
        public $cookie_banner_tooltip;
        const WID = 'ocsh_dashboard_widget';
        const ONECOM='one.com ';
        const DASHBOARD='dashboard';
        const SCORE='score';
        const HEALTH_MONITOR='Health Monitor';
        const SCAN_NOW='Scan now';
        const CREATE_STAGING_SITE='Create staging site';


        public function __construct()
        {
            $this->url = menu_page_url( 'onecom-wp-health-monitor', false );
            $this->staging_url = menu_page_url( 'onecom-wp-staging', false );
            $this->theme_url=menu_page_url( 'onecom-wp-themes', false );
            $this->cookie_banner_url=menu_page_url( 'onecom-wp-cookie-banner', false );
            $this->plugin_url=menu_page_url( 'onecom-wp-plugins', false );
            $this->health_tooltip= addslashes (__('Health Monitor scans your website for potential security issues and checks the overall state of your site.',OC_PLUGIN_DOMAIN));
            $this->theme_tooltip= addslashes(__('Exclusive themes specially crafted for one.com customers.',OC_PLUGIN_DOMAIN));
            $this->plugin_tooltip=addslashes ( __('Plugins that bring the one.com experience and services to WordPress.',OC_PLUGIN_DOMAIN));
            $this->onephoto_tooltip = addslashes (__('Import images and videos from your One Photo galleries into your WordPress website with just 1-click.',OC_PLUGIN_DOMAIN));
            $this->cookie_banner_tooltip = addslashes (__('Show a banner on your website to inform visitors about cookies and get their consent.',OC_PLUGIN_DOMAIN));


            add_action('wp_dashboard_setup', array($this,'osch_widget_cb'));
            add_action('admin_head-index.php', array($this, 'append_link'));
            add_action('admin_head-upload.php',array($this,'onephoto_button'));
            add_action('admin_head-media-new.php',array($this,'onephoto_button'));
            add_action('admin_head-themes.php',array($this,'oc_themes_button'));
            add_action('admin_head-plugins.php',array($this,'oc_plugins_button'));
            add_action('admin_head-plugin-install.php',array($this,'oc_plugins_button'));
            add_action('admin_head-widgets.php',array($this,'oc_cookie_banner_button'));
            add_action('admin_head-options-privacy.php',array($this,'oc_cookie_banner_box'));
            add_action('admin_head-options-general.php',array($this,'oc_staging_button'));
            add_action('admin_head-site-health.php',array($this,'oc_site_health_info'));
            add_action('tool_box',array($this,'tools_page_staging_box'));
            add_action('tool_box',array($this,'tools_page_health_monitor_box'));
            add_action('admin_head',array($this,'oc_button_css'));




        }

        /**
         * adds widget to the wp admin dashboard
         */

        public function osch_widget_cb()
        {

            $user = wp_get_current_user();

            if ( (!isset($user->roles)) || (! in_array( 'administrator', (array) $user->roles )) ) {
                return;
            }
            wp_add_dashboard_widget(
                self::WID,
                self::ONECOM.__('Features', OC_PLUGIN_DOMAIN),
                array($this,'ocsh_widget_cb')
            );

            global $wp_meta_boxes;
            if(isset($wp_meta_boxes[self::DASHBOARD])){
                $normal_dashboard = $wp_meta_boxes[self::DASHBOARD]['normal']['core'];
                $example_widget_backup = array(self::WID => $normal_dashboard[self::WID]);
                unset($normal_dashboard[self::WID]);
                $sorted_dashboard = array_merge($example_widget_backup, $normal_dashboard);
                $wp_meta_boxes[self::DASHBOARD]['normal']['core'] = $sorted_dashboard;
            }
        }


        /**
         * checks for health monitor scan in db and returns value based on the result
         */

        public function ocsh_widget_cb()
        {

            $site_scan_transient = get_site_transient('ocsh_site_scan_result');
            $site_scan_result = oc_sh_calculate_score($site_scan_transient);
            if ( ! $site_scan_result ){
                echo $this->render_html(false);
                return;
            }
            $color = '#4ab865';
            if ($site_scan_result[self::SCORE] < 85 && $site_scan_result[self::SCORE] >=50){
                $color = '#ffb900';
            }elseif($site_scan_result[self::SCORE] < 50){
                $color = '#dc3232';
            }
            $time_format = get_option( 'time_format' );
            $date_format = get_option( 'date_format' );
            echo '<div class="activity-block"><h3>'.__(self::HEALTH_MONITOR, OC_PLUGIN_DOMAIN).'</h3>
            <p>'.__('Health Monitor score is', OC_PLUGIN_DOMAIN).' <strong style="color:'.$color.'">'.round($site_scan_result[self::SCORE]).'%</strong> ('.__('Last checked on', OC_PLUGIN_DOMAIN).' '.date_i18n($date_format.' '.$time_format, $site_scan_result['time']).')</p><p><a class="button button" href="'.$this->url.'">'.__('Scan again', OC_PLUGIN_DOMAIN).'</a></p></div>';
            echo $this->render_html(true);
        }


        /**
         * generates html for the dashboard widget
         * @param bool $scan
         */


        public function render_html($scan=false){

            if(!$scan){
                ?>
                <div class="activity-block">
                    <h3><?php _e(self::HEALTH_MONITOR, OC_PLUGIN_DOMAIN) ;?></h3>
                    <p><a class="button" title="<?php echo $this->health_tooltip;  ?>" href="<?php echo $this->url; ?>"><?php _e(self::SCAN_NOW, OC_PLUGIN_DOMAIN) ;?></a></p>
                </div>
                <?php
            }
            ?>
            <div class="activity-block"><h3><?php _e('Staging', OC_PLUGIN_DOMAIN) ?></h3>
                <p><?php _e('Create a staging version of your site to try out new plugins, themes and customizations',OC_PLUGIN_DOMAIN)?>.</p>
                <p><a class="button" href="<?php echo $this->staging_url ?>"><?php _e(self::CREATE_STAGING_SITE, OC_PLUGIN_DOMAIN) ?></a></p>
            </div>
            <div class="activity-block"><h3><?php _e('Cookie Banner', OC_PLUGIN_DOMAIN) ?></h3>
                <p><?php _e('Show a banner on your website to inform visitors about cookies and get their consent.',OC_PLUGIN_DOMAIN)?></p>
                <p><a class="button" href="<?php echo $this->cookie_banner_url ?> "><?php _e('Cookie banner', OC_PLUGIN_DOMAIN) ?></a></p>
            </div>


            <div class="activity-block oc-reset-wlk-tour"><span class="oc_reset dashicons dashicons-info"></span><a href="#"><?php _e('Restart tour', OC_PLUGIN_DOMAIN) ?></a>
            </div>


            <?php


        }

        /**
         * appends link through jquery to the welcome panel in dashboard
         */

        public function append_link(){
            $label = addslashes('one.com ' .__('Themes',OC_PLUGIN_DOMAIN));

            ?>
            <script type="text/javascript">
                (function($) {
                    $(document).ready(function () {

                        var column = $('#welcome-panel').find('.welcome-panel-column');

                        $(column[0]).append('<p><?php _e('View&nbsp;')?><a title="<?php echo $this->theme_tooltip ?>" href="<?php echo $this->theme_url  ?>"><?php echo $label ?></a></p>')


                    })
                })( jQuery );

            </script>



            <?php

        }


        /**
         * adds the one photo importer button to the media screens
         */

        public function onephoto_button(){

            global $hook_suffix;



            if (is_dir( WP_PLUGIN_DIR . '/onecom-onephoto' ) ) {
                return false;
            }

            $label= addslashes(__('One Photo Importer',OC_PLUGIN_DOMAIN));

            if($hook_suffix !== 'media-new.php') {

                $this->oc_append_buttons($this->plugin_url, $label,'',$this->onephoto_tooltip);
            }else{
                $this->oc_append_buttons($this->plugin_url, $label,'',$this->onephoto_tooltip,true);


            }


        }

        /**
         * adds the one.com themes button to the themes screen
         */

        public function oc_themes_button(){

            $label= self::ONECOM. __('Themes',OC_PLUGIN_DOMAIN);
            $title= $this->theme_tooltip ;

            $this->oc_append_buttons($this->theme_url,$label,'',$title);

        }

        /**
         * adds the one.com plugins button to the plugins screen
         */


        public function oc_plugins_button(){

            $label= self::ONECOM. __('Plugins',OC_PLUGIN_DOMAIN);

            $this->oc_append_buttons($this->plugin_url,$label,'',$this->plugin_tooltip);

        }


        /**
         * adds the cookie banner button to the widget screen
         */

        public function oc_cookie_banner_button(){

            $label= __('Cookie banner',OC_PLUGIN_DOMAIN);

            $this->oc_append_buttons($this->cookie_banner_url,$label,'',$this->cookie_banner_tooltip);


        }

        /**
         * generates and appends the buttons through jquery
         * @param $url url link to be plaved on the button
         * @param $label string  label of button
         * @param string $desc  description for general screen under settings
         * @param string $title  tooltip for buttons
         * @param bool $new  if the button is for add new media screen
         */

        public function oc_append_buttons($url,$label,$desc='',$title='',$new=false){

            if ($desc === ''){

                ?>
                <script type="text/javascript">
                    jQuery(document).ready( function($)
                    {
                        <?php if( !$new ){  ?>
                        $('<a href="<?php echo $url ?>" target="_blank" title="<?php echo $title ?>" class="oc_button"><?php echo $label ?></a>').insertAfter('.page-title-action');

                        <?php  }else{ ?>
                        $('.wrap h1').append('<a href="<?php echo $url ?>" title="<?php echo $title ?>" target="_blank" class="oc_button"><?php echo $label ?></a>');

                        <?php } ?>

                    });
                </script>

                <?php
            }else{
                ?>
                <script type="text/javascript">
                    jQuery(document).ready( function($)
                    {
                        $('<p class="description"><?php echo $desc ?> <a href="<?php echo $url ?>" target="_blank"><?php echo $label ?></a>.</p>').insertAfter('#home-description');
                    });
                </script>


                <?php
            }


        }
        /**
         * adds the staging link to the settings screen
         */

        public function oc_staging_button(){

            $desc=addslashes(__('Create a staging version of your site to try out new plugins, themes and customizations',OC_PLUGIN_DOMAIN));
            $label=__(self::CREATE_STAGING_SITE,OC_PLUGIN_DOMAIN);

            $this->oc_append_buttons($this->staging_url,$label,$desc);


        }

        /**
         * adds the staging box to the tools screen
         */
        public function tools_page_staging_box(){
            $title=__('Staging',OC_PLUGIN_DOMAIN);
            $desc= __('Create a staging version of your site to try out new plugins, themes and customizations',OC_PLUGIN_DOMAIN);
            $label=__(self::CREATE_STAGING_SITE,OC_PLUGIN_DOMAIN);
            $this->tools_page_content_render_html($title,$desc,$label,$this->staging_url);
        }


        /**
         * adds the health monitor box to the tools screen
         */

        public function tools_page_health_monitor_box(){
            $title=__(self::HEALTH_MONITOR,OC_PLUGIN_DOMAIN);
            $desc= __('Health Monitor scans your website for potential security issues and checks the overall state of your site.',OC_PLUGIN_DOMAIN);
            $label=__(self::SCAN_NOW,OC_PLUGIN_DOMAIN);
            $this->tools_page_content_render_html($title,$desc,$label,$this->url);
        }


        /**
         * returns html for the boxes on tools screen
         */

        public function tools_page_content_render_html($title,$desc,$label,$url){
            echo '<div class="card">
                <h2 class="title">'.$title.'</h2>
                <p>'.$desc.'</p> 
                <p><a class="button" href="'.$url.'">'.$label.'</a></p>
            </div>';
        }

        /**
         * css for the shortcuts to be added
         */

        public function oc_button_css(){



            echo '<style>
                  .oc_button{
                    margin-left: 10px;
                    padding: 4px 8px;
                    position: relative;
                    top: -3px;
                    text-decoration: none;
                    border: 1px solid #0071a1;
                    border-radius: 2px;
                    text-shadow: none;
                    font-weight: 600;
                    font-size: 13px;
                    line-height: normal;
                    color: #0071a1;
                    background: #f3f5f6;
                    cursor: pointer;}
                  .oc_button:hover{
                     background: #f1f1f1;
                     border-color: #016087;
                     color: #016087;
                    }
                    
                    @media screen and (max-width: 782px){
                    
                    .oc_button{
                    
                        clear: both;
                        white-space: nowrap;
                        display: inline-block;
                        margin-bottom: 14px;
                        padding: 10px 15px;
                        font-size: 14px;
                        }
                        }
                    @media (max-width: 576px) {
                    .oc_button{
                    margin-left:0px;
                    }
                    .plugins-php .oc_button{
                    margin-left:10px;
                    }
                    
                    }</style>';

        }





        /**
         * adds the cookie banner box to the privacy screen
         */
        public function oc_cookie_banner_box(){

            $title=__('one.com Cookie Banner',OC_PLUGIN_DOMAIN);
            $description=addslashes (__('Show a banner on your website to inform visitors about cookies and get their consent.',OC_PLUGIN_DOMAIN));
            $label=__('Cookie banner',OC_PLUGIN_DOMAIN);

            ?>
            <script type="text/javascript">
                jQuery(document).ready( function($)
                {
                    $('<div class="card">\n' +
                        '<h2 class="title"><?php echo $title ?></h2>\n' +
                        ' <p><?php echo $description?></p> \n' +
                        ' <p><a class="button" href="<?php echo $this->cookie_banner_url ?>"><?php echo $label ?></a></p>\n' +
                        ' </div>').insertAfter('.tools-privacy-policy-page');
                });
            </script>

            <?php
        }

        /**
         * adds the health monitor text to the site health screen
         */

        public function oc_site_health_info(){
            $health_des=addslashes (__('Health Monitor scans your website for potential security issues and checks the overall state of your site',OC_PLUGIN_DOMAIN));
            ?>
            <script type="text/javascript">
                jQuery(document).ready( function($)
                {
                    $('body').find('.health-check-body').append('<br/><p class="description"><?php echo $health_des ?>&nbsp;<a title="<?php echo $this->health_tooltip;  ?>" href="<?php echo $this->url; ?>"><?php _e(self::SCAN_NOW, OC_PLUGIN_DOMAIN) ;?></a></p>');
                });
            </script>


        <?php }





    }
}
