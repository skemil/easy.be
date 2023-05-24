<?php
/**
 * @Version 2.2
 **/
if (!defined('OC_ACTIVATE_STR')) {
    define('OC_ACTIVATE_STR', 'activate');
}
if (!defined('OC_DEACTIVATE_STR')) {
    define('OC_DEACTIVATE_STR', 'deactivate');
}
if (!defined('OC_DM_NAME')) {
    define('OC_DM_NAME', 'ONECOM_DOMAIN_NAME');
}
if (!defined('ONECOM')) {
    define('ONECOM', 'one.com');
}
if (!defined('AUTHOR')) {
    define('AUTHOR', 'Author');
}
if (!defined('OCPUSHSTATS')) {
    define('OCPUSHSTATS', 'OCPushStats');
}
if (!defined('THEME')) {
    define('THEME', 'theme');
}
if (!defined('THEMESPAGE')) {
    define('THEMESPAGE', 'themes_page');
}
if (!defined('UPDATE')) {
    define('UPDATE', 'update');
}
/**
 * WordPress action to trigger after activating the theme
 **/
add_action('after_switch_theme', 'onecom_activate_theme_stats');
/**
 * WordPress action to trigger after deactivating the theme
 **/
add_action('switch_theme', 'onecom_deactivate_theme_stats', 10, 3);
/**
 * Function after activating the theme
 **/
if (!function_exists('onecom_activate_theme_stats')) {
    function onecom_activate_theme_stats()
    {

        $theme = wp_get_theme();

        if (ONECOM !== strtolower($theme->display(AUTHOR, FALSE))) {
            return false;
        }

        $url = (isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY) : '');

        if (!empty($url) && $url == 'step=theme') {
            $referrer = 'install_wizard';
        } else {
            $referrer = THEMESPAGE;
        }

        (class_exists(OCPUSHSTATS) ? \OCPushStats::push_stats_event_themes_and_plugins('activate', THEME, $theme->stylesheet, "$referrer") : '');
        return true;
    }
}

/**
 * Function after deactivating the theme
 **/
if (!function_exists('onecom_deactivate_theme_stats')) {
    function onecom_deactivate_theme_stats($new_name, $new_theme, $old_theme)
    {
        if (ONECOM !== strtolower($old_theme->display(AUTHOR, FALSE))) {
            return false;
        }
        // send stats
        (class_exists(OCPUSHSTATS) ? \OCPushStats::push_stats_event_themes_and_plugins('deactivate', THEME, $old_theme->stylesheet, THEMESPAGE) : '');
        return true;
    }
}


if (!function_exists('onecom_upgradation_check')) {
    function onecom_upgradation_check($upgrader_object, $options)
    {

        if ($options['action'] == UPDATE && $options['type'] == 'plugin' && isset($options['plugins'])) {

            if ($upgrader_object->skin->plugin_info['AuthorName'] !== ONECOM) {
                return;
            }

            // send stats
            (class_exists(OCPUSHSTATS) ? \OCPushStats::push_stats_event_themes_and_plugins(UPDATE, 'plugin', $upgrader_object->result['destination_name'], 'plugins_page') : '');

        } elseif ($options['action'] == UPDATE && $options['type'] == THEME && isset($options['themes'])) {

            if ($upgrader_object->skin->theme_info->get(AUTHOR) !== ONECOM) {
                return;
            }

            // send stats
            (class_exists(OCPUSHSTATS) ? \OCPushStats::push_stats_event_themes_and_plugins(UPDATE, THEME, $upgrader_object->result['destination_name'], THEMESPAGE) : '');
        }
    }
}
add_action('upgrader_process_complete', 'onecom_upgradation_check', 10, 2);

/**
 * one.com register Imagify partner
 */
function onecom_register_imagify_partner()
{
    if (!is_admin()) {
        return;
    }
    require_once ONECOM_WP_PATH . 'vendor/imagify/class-imagify-partner.php';
    if (Imagify_Partner::has_imagify_api_key()) {
        return;
    }

    // Saving the Partner ID in DB as a fallback.
    update_site_option("imagifyp_id", OCIPID);

    // The class needs to be initiated to launch hooks.
    $imagify = new Imagify_Partner(OCIPID);
    $imagify->init();
}
// Save Imagify partner ID
add_action( 'plugins_loaded', 'onecom_register_imagify_partner' );