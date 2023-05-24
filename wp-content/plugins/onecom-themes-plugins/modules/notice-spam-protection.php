<?php
// No Direct Access
defined( "WPINC" ) or die(); // No Direct Access

/*
 * Spam Protection Notice
 * */


if(!function_exists('onecom_fetch_antispam_plugins')){
    function onecom_fetch_antispam_plugins(){
        $fetch_plugins_url = MIDDLEWARE_URL . '/antispam-plugins';
        $get_plugins='';

        $args = array(
            'timeout' => 5,
            'httpversion' => '1.0',
            'sslverify' => true,
        );

        $response = wp_remote_get($fetch_plugins_url, $args);

        if (!is_wp_error($response && is_array($response))) {

            $body = wp_remote_retrieve_body($response);
            $body = json_decode($body);
            if (!empty($body) && $body->success) {
                $get_plugins = $body->data;
            } else {

                error_log(print_r($body, true));
            }

        } else {
            $errorMessage = '(' . wp_remote_retrieve_response_code($response) . ') ' . wp_remote_retrieve_response_message($response);

            error_log(print_r($errorMessage, true));


        }
        if (is_array($get_plugins) && !empty($get_plugins)) {

            set_site_transient('onecom_fetched_plugins', $get_plugins, 10 * HOUR_IN_SECONDS);

            return $get_plugins;
        }


    }

}

if( ! function_exists( 'onecom_spam_protection_notice' ) ) {
    function onecom_spam_protection_notice()
    {

        if ( ! current_user_can( 'deactivate_plugin' ) ) {
            return false;
        }

        $screen = get_current_screen();

        $screens = array(
            'dashboard',
            'plugins',
        );

        // return if screen not allowed
        if(! in_array($screen->base, $screens)) {
            return false;
        }

        // get active plugins
        $act_plugins = get_site_option('active_plugins');
        $display_notice=true;



        $activated_plugins_slug=[];
        foreach ($act_plugins as $plg){
            $activated_plugins_slug[] = explode( '/', $plg)[0];
        }


        $get_plugins=get_site_transient('onecom_fetched_plugins');

        if(!$get_plugins) {

            $get_plugins=(array) onecom_fetch_antispam_plugins();

        }

        $active_spam_plugin=array_intersect($get_plugins,$activated_plugins_slug);

        if(get_site_option('dismiss-oc-spam-notice') && (get_site_option('dismiss-oc-spam-notice') > strtotime('-60 days'))){

            $display_notice=false;

        }

        if($active_spam_plugin || !$display_notice){
            return false;

        }else{

            // Display Spam protection warning

            $link = admin_url( 'admin.php?page=onecom-wp-recommended-plugins' );
            $text =  __('Your website forms are not protected against spam and abuse. We recommend installing a captcha or spam protection plugin. &nbsp;<a href='.$link.'>View recommended plugins</a> ', OC_PLUGIN_DOMAIN);




            echo "<div id='oc-spam-nt' class='notice notice-warning is-dismissible'><p> {$text}</p></div>";
        }


    }
}

add_action( 'admin_notices', 'onecom_spam_protection_notice', 2 );

add_action('admin_head','dismiss_notice_script');

if( ! function_exists( 'dismiss_notice_script' ) ) {

    function dismiss_notice_script()
    {?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $(document).on('click', '#oc-spam-nt .notice-dismiss', function( event ) {
                    data = {
                        action : 'display_oc_spam_admin_notice',
                    };

                    $.post(ajaxurl, data, function (response) {
                        console.log(response, 'DONE!');
                    });
                });
            });


        </script>

    <?php }


}

add_action( 'wp_ajax_display_oc_spam_admin_notice', 'display_oc_spam_admin_notice');


function display_oc_spam_admin_notice()
{


    update_site_option('dismiss-oc-spam-notice',current_time('timestamp'));
    wp_die();


}