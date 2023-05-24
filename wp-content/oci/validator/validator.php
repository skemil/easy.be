<?php
/**
 * Name: Validator
 * Version: 0.2.3
 * Copyright: one.com
 * Any type of modification/duplication/distribution of this script is strictly prohibited.
 */

// Essential declarations
if ( ! defined( 'OC_VALIDATOR_DOMAIN' ) ) {
    define( 'OC_VALIDATOR_DOMAIN', 'validator' );
}
if ( ! defined( 'OC_DOMAIN_NAME' ) ) {
    define( 'OC_DOMAIN_NAME', isset($_SERVER['ONECOM_DOMAIN_NAME']) ? $_SERVER['ONECOM_DOMAIN_NAME'] : '' );
}
if ( ! defined( 'OC_WP_API' ) ) {
    define( 'OC_WP_API', empty($_SERVER['ONECOM_WP_ADDONS_API']) ? '' : $_SERVER['ONECOM_WP_ADDONS_API'] );
}

// Formal Sonar string repetition fixes
if(!defined('OC_GENERIC_ERR_MSG')){ define('OC_GENERIC_ERR_MSG', __("Some error occurred, please reload the page and try again.", OC_VALIDATOR_DOMAIN)); }
if(!defined('OC_GENERIC_LEARN_MORE')){ define('OC_GENERIC_LEARN_MORE', __('Learn more', OC_VALIDATOR_DOMAIN)); }
if(!defined('OC_MAIN_GUIDE_STR')){ define('OC_MAIN_GUIDE_STR', 'main_guide'); }
if(!defined('OC_DISC_GUIDE_STR')){ define('OC_DISC_GUIDE_STR', 'discouraged_guide'); }
if(!defined('OC_COOKIE_GUIDE_STR')){ define('OC_COOKIE_GUIDE_STR', 'cookie_guide'); }
if(!defined('OC_STG_GUIDE_STR')){ define('OC_STG_GUIDE_STR', 'staging_guide'); }
if(!defined('OC_PRM_PAGE_STR')){ define('OC_PRM_PAGE_STR', 'premium_page'); }
if(!defined('OC_ERR_STR')){ define('OC_ERR_STR', 'error'); }
if(!defined('OC_SUCCESS_STR')){ define('OC_SUCCESS_STR', 'success'); }
if(!defined('OC_THM_STR')){ define('OC_THM_STR', 'theme'); }
if(!defined('OC_THMS_STR')){ define('OC_THMS_STR', 'themes'); }
if(!defined('OC_FTR_STR')){ define('OC_FTR_STR', 'feature'); }
if(!defined('OC_AUTHOR_STR')){ define('OC_AUTHOR_STR', 'Author'); }
if(!defined('OC_NAME_STR')){ define('OC_NAME_STR', 'Name'); }
if(!defined('OC_ITM_COUNT_STR')){ define('OC_ITM_COUNT_STR', 'item_count'); }
if(!defined('OC_PUSH_STATS')){ define('OC_PUSH_STATS', 'OCPushStats'); }
if(!defined('PREMIUM')){ define('PREMIUM', 'premium'); }


// Item Identifiers
if ( ! defined( 'OC_ID_OCI' ) ) {
    define( 'OC_ID_OCI', 'ONE_CLICK_INSTALL' );
}
if ( ! defined( 'OC_ID_PRM_THMS' ) ) {
    define( 'OC_ID_PRM_THMS', 'PREMIUM_THEMES' );
}
if ( ! defined( 'OC_ID_STD_THMS' ) ) {
    define( 'OC_ID_STD_THMS', 'STANDARD_THEMES' );
}
if ( ! defined( 'ONECOM_WP_CORE_VERSION' ) ) {
    global $wp_version;
    define( 'ONECOM_WP_CORE_VERSION', $wp_version );
}
if ( ! defined( 'ONECOM_PHP_VERSION' ) ) {
    define( 'ONECOM_PHP_VERSION', phpversion() );
}

/* send validation headers with api calls */
add_filter( 'http_request_args', 'oc_add_http_headers', 10, 2 );

/* TOTP lib */

use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

$filepath = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if ( file_exists( $filepath ) ) {
    include $filepath;

}


add_action( 'plugins_loaded', 'oc_validator_load_textdomain' );
if ( ! function_exists( 'oc_validator_load_textdomain' ) ) {
    function oc_validator_load_textdomain() {

        // load english tranlsations [as] if any unsupported language is selected in WP-Admin
        if ( strpos( get_locale(), 'en_' ) === 0 ) {
            load_textdomain( OC_VALIDATOR_DOMAIN, dirname( __FILE__ ) . '/languages/validator-en_US.mo' );
        } else {
            load_plugin_textdomain( OC_VALIDATOR_DOMAIN, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . trailingslashit( 'languages' ) );
        }
    }
}

// fallback check for MIDDLEWARE_URL, define it if not already defined
if ( ! defined( 'MIDDLEWARE_URL' ) ) {
    $api_version = 'v1.0';
    if ( ! empty( OC_WP_API ) ) {
        $ONECOM_WP_ADDONS_API = OC_WP_API;
    } elseif ( defined( 'ONECOM_WP_ADDONS_API' ) && ONECOM_WP_ADDONS_API != '' && ONECOM_WP_ADDONS_API ) {
        $ONECOM_WP_ADDONS_API = ONECOM_WP_ADDONS_API;
    } else {
        $ONECOM_WP_ADDONS_API = 'https://wpapi.one.com/';
    }
    $ONECOM_WP_ADDONS_API = rtrim( $ONECOM_WP_ADDONS_API, '/' );
    define( 'MIDDLEWARE_URL', $ONECOM_WP_ADDONS_API . '/api/' . $api_version );
}

if ( ! function_exists( 'oc_generate_totp' ) ) {
    function oc_generate_totp( $valid_for = 30, $length = 6 ) {
        $fileString = '{}';
        if ( file_exists( '/run/domain.conf' ) ) {
            $fileString = trim( file_get_contents( "/run/domain.conf" ) );
        } else if ( file_exists( '/run/mail.conf' ) ) {
            $fileString = trim( file_get_contents( "/run/mail.conf" ) );
        }

        $domainInfo = json_decode( $fileString );
        $hash       = 'oc';
        if ( isset( $domainInfo->hash ) && $domainInfo->hash ) {
            $hash = $domainInfo->hash;
        }
        $mySecret = trim( Base32::encodeUpper( $hash ) );
        $otp      = TOTP::create( $mySecret, $valid_for, 'sha1', $length );

        return $otp->now();
    }
}

if ( ! function_exists( 'oc_validate_domain' ) ) {
    function oc_validate_domain( $domain = null ) {
        if ( ! $domain ) {
            $domain = isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : false;
        }
        if ( ! $domain ) {
            return [
                'data'    => null,
                'error'   => 'Empty domain',
                'success' => false,
            ];
        }
        $totp = oc_generate_totp();
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => MIDDLEWARE_URL . '/features',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array(
                "Cache-Control: no-cache",
                "X-Onecom-Client-Domain: " . $domain,
                "X-TOTP: " . $totp,
                "cache-control: no-cache",
            ),
        ) );
        $response = curl_exec( $curl );
        $response = json_decode( $response, true );
        $err      = curl_error( $curl );
        curl_close( $curl );

        if ( $err ) {
            return [
                'data'    => null,
                'error'   => __( "Some error occurred, please reload the page and try again.", "validator" ),
                'success' => false,
            ];
        } else {
            return $response;
        }
    }
}
add_action( 'admin_init', 'oc_set_premi_flag' );
if ( ! function_exists( 'oc_set_premi_flag' ) ) {
    function oc_set_premi_flag( $force = false ) {
        $oc_premi_flag = get_site_transient( 'oc_premi_flag' );
        if ( ! $oc_premi_flag || $force ) {
            $oc_premi_flag = oc_validate_domain();
            if ( isset( $oc_premi_flag['data'] ) && $oc_premi_flag['data'] ) {
                if(is_blog_installed()){
                    set_site_transient( 'oc_premi_flag', $oc_premi_flag['data'], 12 * HOUR_IN_SECONDS );
                }
            }
        }

        if ( ! isset( $oc_premi_flag['data'] ) ) {
            $oc_premi_flag['data'] = $oc_premi_flag;
        }

        return $oc_premi_flag;
    }
}
// hook onto WP CRON to check transients
add_action( 'wp_version_check', 'oc_set_premi_flag_cron' );
if ( ! function_exists( 'oc_set_premi_flag_cron' ) ) {
    function oc_set_premi_flag_cron() {
        oc_set_premi_flag( true );
    }
}

/**
 * Feature mapping
 */
if ( ! function_exists( 'oc_pm_features' ) ) {
    function oc_pm_features( $key, $val ) {
        $operations = [
            'ins'    => OC_ID_OCI,
            'stg'    => 'STAGING_ENV',
            'stheme' => OC_ID_STD_THMS,
            'ptheme' => OC_ID_PRM_THMS,
            'pcache' => 'PERFORMANCE_CACHE'
        ];
        if ( ! array_key_exists( $key, $operations ) ) {
            return false;
        }

        return in_array( $operations[ $key ], $val );
    }
}

/**
 * Function oc_get_logline()
 * Function to prepare log data to be sent to WPAPI
 *
 * @param array $post_data An array of varialbles intercepted from $_POST.
 *
 * @return string
 * @since v0.1.3
 */
if ( ! function_exists( 'oc_get_logline' ) ) {
    function oc_get_logline( $post_data ) {
        $is_premium = filter_var( $post_data['isPremium'], FILTER_SANITIZE_STRING );
        $message    = "isPremium:$is_premium";

        $state = 'state';

        $feature_condition = ( isset( $post_data[ OC_FTR_STR ] ) && ( $post_data[ OC_FTR_STR ] != '' ) );
        $theme_condition   = ( isset( $post_data[ OC_THM_STR ] ) && ( $post_data[ OC_THM_STR ] != '' ) );

        if ( ! ( $feature_condition || $theme_condition ) ) {
            return false;
        }

        if ( isset( $post_data[ OC_FTR_STR ] ) && $post_data[ OC_FTR_STR ] != '' ) {
            $feature = filter_var( $post_data[ OC_FTR_STR ], FILTER_SANITIZE_STRING );
            $message .= ";feature:$feature";
        }

        if ( isset( $post_data[ $state ] ) && $post_data[ $state ] != '' ) {
            $state   = filter_var( $post_data[ $state ], FILTER_SANITIZE_NUMBER_INT );
            $message .= ";state:$state";
        }

        if ( isset( $post_data['featureAction'] ) && $post_data['featureAction'] != '' ) {
            $feature_action = filter_var( $post_data['featureAction'], FILTER_SANITIZE_STRING );
            $message        .= ";featureAction:$feature_action";
        }

        if ( isset( $post_data[ OC_THM_STR ] ) && ( $post_data[ OC_THM_STR ] != '' ) ) {
            $theme   = filter_var( $post_data[ OC_THM_STR ], FILTER_SANITIZE_STRING );
            $message .= ";theme:$theme";
        }

        //append the available features at the end
        $feature_array  = oc_set_premi_flag( true );
        $feature_string = implode( '|', $feature_array['data'] );
        $feature_string = rtrim( $feature_string, '|' );

        return $message . ";features_available:$feature_string";
    }
}

/**
 * Function to handle validation by ajax
 */
add_action('wp_ajax_oc_validate_action', 'oc_validate_action_cb');

if (!function_exists('oc_validate_action_cb')) {
    function oc_validate_action_cb()
    {
        $data = isset($_POST['operation']) ? filter_var($_POST['operation'], FILTER_SANITIZE_STRING) : '';
        $action_type = isset($_POST['actionType']) ? filter_var($_POST['actionType'], FILTER_SANITIZE_STRING) : '';
        $result = oc_set_premi_flag(true);
        $status = 'status';
        $data_str = 'data';
        $referrer = '';

        if (isset($_POST['referrer'])) {

            $referrer = strpos($_POST['referrer'], 'step=theme') ? 'install_wizard' : 'themes_page';
        }

        if ($result[$data_str] == null && $result[OC_SUCCESS_STR] != 1) {
            $response = [
                $status => '',
                'msg' => OC_GENERIC_ERR_MSG . ' [' . $result[OC_ERR_STR] . ']'
            ];
        } else if (oc_pm_features($data, $result[$data_str])) {
            $response = [
                $status => OC_SUCCESS_STR,
            ];
        } else {
            $response = [
                $status => 'failed'
            ];
        }

        // push stats

        if ($action_type != '' && class_exists(OC_PUSH_STATS)) {

            $premium = (empty($_POST["isPremium"]) || $_POST["isPremium"] !== 'true') ? 0 : 1;
            $slug = isset($_POST['theme']) ? $_POST['theme'] : '';



            if ($action_type === 'wppremium_install_theme' && isset($_POST['theme'])) {
                (class_exists(OC_PUSH_STATS) ? \OCPushStats::push_stats_event_themes_and_plugins('install', 'theme', "$slug", $referrer, array(PREMIUM => "$premium")) : '');

            } elseif ($action_type === 'wppremium_preview_theme' && isset($_POST['theme'])) {
                (class_exists(OC_PUSH_STATS) ? \OCPushStats::push_stats_event_themes_and_plugins('preview', 'theme', "$slug", $referrer, array(PREMIUM => "$premium")) : '');

            } elseif ($action_type === 'wppremium_click_upgrade' && $_POST['feature'] === 'theme') {

                (class_exists(OC_PUSH_STATS) ? \OCPushStats::push_stats_event_themes_and_plugins('click_upgrade', 'theme', $_POST['theme'], $referrer, array(PREMIUM => "$premium")) : '');

            } elseif ($action_type === 'wppremium_close_upgrade' && $_POST['feature'] === 'theme') {

                (class_exists(OC_PUSH_STATS) ? \OCPushStats::push_stats_event_themes_and_plugins('close_upgrade', 'theme', $_POST['theme'], $referrer, array(PREMIUM => "$premium")) : '');

            }


        }
        echo wp_send_json($response);
        wp_die();
    }
}

/* validator scripts */
add_action( 'admin_print_scripts', function () {
    ?>
    <script>
        /**
         * Top Notifier
         */
        function oc_alert(msg = '', type = '<?php echo OC_ERR_STR; ?>', time = 5000) {

            jQuery('.onecom-notifier').html(msg).attr('type', type).addClass('show');
            setTimeout(function () {
                jQuery('.onecom-notifier').removeClass('show');
                jQuery('.loading-overlay.fullscreen-loader').removeClass('show');
            }, time);
        }
    </script>

    <script type="text/javascript">
        function oc_validate_action(action) {

            return jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                dataType: "JSON",
                data: {
                    action: 'oc_validate_action',
                    operation: action
                },
                error: function (xhr, textStatus, errorThrown) {
                    oc_alert("<?php echo htmlentities( OC_GENERIC_ERR_MSG ); ?>", OC_ERR_STR, 5000)
                }
            });
        }
    </script>
    <script>
        /**
         * Function oc_trigger_log()
         * Function to trigger log after a feature is activated successfully.
         * This checks the domain's eligibility for a feature redundantly and this can be improved.
         * @param: Object data - an object consisting of following keys
         * actionType, isPremium, feature, featureAction, state and theme
         */

        function oc_trigger_log(logData) {
            jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                dataType: "JSON",
                data: {
                    action: 'oc_validate_action',
                    actionType: logData.actionType,
                    isPremium: logData.isPremium,
                    feature: logData.feature,
                    theme: logData.theme || null,
                    featureAction: logData.featureAction || null,
                    referrer:logData.referrer||null,
                    state: logData.state || null
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.log("Some error occured during logging!");
                }
            });
        }
    </script>
    <?php
}, 9999 );

/**
 * function to add Modal HTML in wp-admin footer
 */
add_action( 'admin_footer', 'onecom_upgrade_modal' );
if ( ! function_exists( 'onecom_upgrade_modal' ) ) {
    function onecom_upgrade_modal() {
        $type           = null;
        $current_screen = get_current_screen();
        $thm_screens    = array( OC_THMS_STR, 'one-com_page_onecom-wp-themes' );
        if ( in_array( $current_screen->base, $thm_screens ) ) {
            $type = OC_THMS_STR;
        }

        ?>
        <div id="oc_um_overlay" style="display:none;">
            <div id="oc_um_wrapper">
                <div id="oc_um_head">
                    <h5><em class="inline_icon"
                            style="margin-right: 8px;background:url('data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZmlsbDojOTUyNjVFOyIgd2lkdGg9IjkiIGhlaWdodD0iMTQiIHZpZXdCb3g9IjAgMCA5IDE0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0xLjQ5IDBoNi4wMTdsLTIgNC44NzNMOSA0Ljg2NSAyLjE0MiAxNGwxLjYzLTYuNzIzTDAgNy4yNzR6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=');height: 13.5px;display: inline-block;vertical-align: middle;background-repeat: no-repeat;width: 9px;"></em> <?php echo __( 'Premium feature', OC_VALIDATOR_DOMAIN ); ?>
                    </h5>
                </div>
                <div id="oc_um_body">
                    <?php
                    if ( $type == OC_THMS_STR ): ?>
                        <p><?php echo __( 'The chosen theme is not available in your current subscription.', OC_VALIDATOR_DOMAIN ); ?> </p>
                    <?php else: ?>
                        <p><?php echo __( 'This feature is not available in your current subscription.', OC_VALIDATOR_DOMAIN ); ?> </p>
                    <?php endif; ?>

                    <p><?php echo __( 'Please upgrade to a subscription with access to Premium features.', OC_VALIDATOR_DOMAIN ); ?>
                        <a class="inline_badge_link"
                           style="color: #95265e;font-family: Open Sans;-webkit-font-smoothing: antialiased;font-size: 12px;font-weight: 600;line-height: 18px;cursor: pointer;text-decoration:none;"
                           href="<?php echo onecom_generic_locale_link( OC_PRM_PAGE_STR, get_locale() ); ?>"
                           target="_blank"><?php echo OC_GENERIC_LEARN_MORE ?></a></p>
                </div>

                <div id="oc_um_footer">
                    <a href="javascript:;"
                       onclick="jQuery('#oc_um_overlay').hide();jQuery('.loading-overlay.fullscreen-loader').removeClass('show');"
                       class="oc_um_btn oc_cancel_btn"><?php echo __( 'Cancel', OC_VALIDATOR_DOMAIN ); ?></a>
                    <a href="https://login.one.com/cp/subscription#targetDomain=<?php echo ! empty( OC_DOMAIN_NAME ) ? OC_DOMAIN_NAME : ''; ?>"
                       target="_blank"
                       class="oc_um_btn oc_up_btn"><?php echo __( 'Upgrade', OC_VALIDATOR_DOMAIN ); ?></a>
                </div>

                <div id="oc_um_close"
                     onclick="jQuery('#oc_um_overlay').hide();jQuery('.loading-overlay.fullscreen-loader').removeClass('show');"></div>
            </div>
        </div>

        <style>
            #oc_um_overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.2);
                z-index: 99999;
            }

            #oc_um_wrapper {
                margin: 0;
                position: fixed;
                top: 50%;
                left: 50%;
                -ms-transform: translate(-50%, -50%);
                transform: translate(-50%, -50%);
                -webkit-transform: translate(-50%, -50%);
                width: 424px;
                height: 246px;
                background-color: #fff;
                border-top: 6px solid #76a338;
                padding: 26px 28px;
                color: #000;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                box-shadow: 0 0 12px rgba(0, 0, 0, .4);
            }

            @media (max-width: 600px) {
                #oc_um_wrapper {
                    max-width: 75%;
                }
            }

            #oc_um_wrapper h5 {
                font-size: 18px;
                margin: 0;
                font-weight: 600;
            }

            #oc_um_head {
                min-height: 56px;
            }

            #oc_um_footer {
                position: absolute;
                bottom: 0;
                right: 0;
                text-align: right;
                padding: 26px;
            }

            .oc_um_btn {
                margin: 0 0 0 14px;
                padding: 4px 25px;
                color: #656565;
                background: #fff;
                font-size: 13px;
                font-weight: 700;
                align-items: center;
                display: inline-flex;
                min-height: 23px;
                border-radius: 0;
                cursor: pointer;
                border: 1px solid #dfdfdf;
            }

            .oc_up_btn {
                background: #95265e;
                border-color: #95265e;
                color: #fff;
            }

            .oc_up_btn:focus, .oc_up_btn:active, .oc_up_btn:visited {
                color: #fff;
            }

            #oc_um_body p {
                font-size: 12px;
            }

            .oc_um_btn:hover {
                color: #fff !important;
                border: 1px solid #a4a4a4 !important;
                background-color: #a4a4a4 !important;
            }

            .oc_up_btn:hover {
                background-color: #284f90 !important;
                border-color: #284f90 !important;
            }

            #oc_um_close {
                background-image: url("data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZmlsbDojOTA5MDkwIiB3aWR0aD0iMTAiIGhlaWdodD0iMTAiIHZpZXdCb3g9IjAgMCAxMCAxMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNOC42MzcuMTUxTDUgMy43ODggMS4zNjMuMTUuMTUxIDEuMzYzIDMuNzg4IDUgLjE1IDguNjM3bDEuMjEzIDEuMjEyTDUgNi4yMTIgOC42MzcgOS44NWwxLjIxMi0xLjIxMkw2LjIxMiA1IDkuODUgMS4zNjR6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=");
                width: 12px;
                height: 12px;
                right: 7px;
                top: 7px;
                position: absolute;
                background-size: 12px;
                background-position: 50%;
                background-repeat: no-repeat;
                z-index: 99999;
                cursor: pointer;
                opacity: 0.5;
            }

            #oc_um_close:hover {
                opacity: 1;
            }
        </style>
        <?php
    }
}

/**
 * Function to show premium Ribbon on theme thumbnails.
 **/
add_filter( 'onecom_premium_theme_badge', 'oc_theme_badge', 10 );
if(!function_exists('oc_theme_badge')){
    function oc_theme_badge( $tag ) {
        if(! (is_array($tag) && in_array(PREMIUM, $tag))){
            return ;
        }
        echo '<span class="badge_bg" style="position: absolute;transform: rotate(45deg);z-index: 80;width: 105px;height: 73px;padding-top: 0px;top: -26px;right: -42px;background-color: #95265e;"></span><span class="badge_icon" style="position: absolute;transform: rotate(45deg);z-index: 80;pointer-events: none;top: 8px;right: 13px;"><svg style="height: 15px;width: 9px;display: inline-block;"><use xlink:href="#topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119">
		<svg viewBox="0 0 9 15" id="topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119"><path d="M1.486 0h6L5.492 5.004l3.482-.009-6.839 9.38 1.627-6.903L0 7.469z" fill="#FFF" fill-rule="evenodd"></path></svg></use></svg></span><span class="badge_text" style="position: absolute;transform: rotate(45deg);z-index: 80;color: #fff;text-transform: uppercase;font-style: normal;font-weight: 600;font-family: \'Open Sans\', sans-serif;display: block;text-align: center;top: 18px;font-size: 11px;right: 2px;-webkit-font-smoothing: antialiased;">'.__("Premium", OC_VALIDATOR_DOMAIN).'</span>';
    }
}

/**
 * Function to show inline premium badge
 */
$oc_inline_badge_fn = 'oc_inline_badge';
add_filter( 'onecom_premium_inline_badge', $oc_inline_badge_fn, 10 );
add_filter( 'oc_preview_install', $oc_inline_badge_fn, 10, 3 ); // attach with preview install
add_filter( 'oc_staging_button_create', $oc_inline_badge_fn, 10, 3 ); // attach with staging create
add_filter( 'oc_staging_button_delete', $oc_inline_badge_fn, 10, 3 ); // attach with staging delete
if ( ! function_exists( $oc_inline_badge_fn ) ) {
    function oc_inline_badge( $html, $type = '', $feature = '' ) {
        $features = (array) oc_set_premi_flag( true );
        // Check if Premium features

        if ( isset( $features['data'] ) ) {
            $features = $features['data'];
        }

        if ( oc_pm_features( $feature, $features ) ) {
            $badge = '<span class="inline_badge" style="display: inline-flex;height: 28px;vertical-align: middle;margin-left: 20px;align-items: center;color: #76a338;font-size: 14px;-webkit-font-smoothing: antialiased;"><svg style="width: 16px;height: 16px;pointer-events: none;margin-right:6px;"><use xlink:href="#premium_checkmark_91c2f8cf40d052f90c7b36218d17f875"><svg viewBox="0 0 13 13" id="premium_checkmark_91c2f8cf40d052f90c7b36218d17f875"><path d="M5.815 7.383L8.95 4.271l1.06 1.06-3.255 3.232-.953.953L3.354 7.01l1.06-1.06 1.4 1.433zM6.5 12.5a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-1a5 5 0 1 0 0-10 5 5 0 0 0 0 10z" fill="#76a338"></path></svg></use></svg> Premium</span>';
        } else {
            if ( $type == '' ) {
                $type = __( "This is a Premium Theme", OC_VALIDATOR_DOMAIN );
            }
            $badge = '<span class="inline_badge" style="display: inline-flex; height: 28px; vertical-align: middle; margin-left: 20px; align-items: center;"><em class="inline_icon" style="background:url(\'data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZmlsbDojOTUyNjVFOyIgd2lkdGg9IjkiIGhlaWdodD0iMTQiIHZpZXdCb3g9IjAgMCA5IDE0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0xLjQ5IDBoNi4wMTdsLTIgNC44NzNMOSA0Ljg2NSAyLjE0MiAxNGwxLjYzLTYuNzIzTDAgNy4yNzR6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=\');height: 13.5px;display: inline-block;vertical-align: middle;background-repeat: no-repeat;width: 9px;"></em><span class="inline_badge_text" style="-webkit-font-smoothing: antialiased;margin-left: 10px; opacity: 0.9;color: #333;font-family: Open Sans;font-size: 13px;line-height: 18px;">' . $type . '</span> <a class="inline_badge_link" target="_blank" style="border-bottom:0;margin-left: 5px;color: #95265e;font-family: Open Sans;-webkit-font-smoothing: antialiased;font-size: 13px;font-weight: 600;line-height: 18px;cursor: pointer;text-decoration:none;" href="' . onecom_generic_locale_link( OC_PRM_PAGE_STR, get_locale() ) . '">' . __( "Learn more", OC_VALIDATOR_DOMAIN ) . '</a></span>';
        }

        return $html . $badge;
    }
}

/**
 * Function oc_val_exclude_themes
 * Remove ILO app theme from Theme listing  in plugin section
 *
 * @param array $themes , an array of onecom themes
 * @param bool $exclude_themes , weather or not to exclude ilo theme?
 *
 * @return array
 */
if ( ! function_exists( 'oc_val_exclude_themes' ) ) {
    function oc_val_exclude_themes( $themes, $exclude_themes ) {
        if ( ! $exclude_themes || ! is_array( $themes ) ) {
            return $themes;
        }
        foreach ( $themes as $theme_item ) {
            if ( isset( $theme_item->collection ) ) {
                foreach ( $theme_item->collection as $key => $theme ) {
                    if ( isset( $theme->slug ) && ( $theme->slug === 'onecom-ilotheme' ) ) {
                        unset( $theme_item->collection[ $key ] );
                    }
                }
            }
        }

        return $themes;
    }
}

/**
 * Function to query update
 **/
if ( ! function_exists( 'onecom_query_check' ) ) {
    function onecom_query_check( $url, $page = null ) {
        if ( $page != null || $page != 1 || $page != "1" ) {
            $url = add_query_arg(
                array(
                    'page' => $page,
                ), $url
            );
        }

        return add_query_arg(
            array(
                'wp'             => ONECOM_WP_CORE_VERSION,
                'php'            => ONECOM_PHP_VERSION,
                OC_ITM_COUNT_STR => 1000
            ), $url
        );
    }
}

/**
 * Fetch one.com themes
 */
if ( ! function_exists( 'onecom_fetch_themes' ) ) {
    function onecom_fetch_themes( $page = 1, $exclude_ilotheme = false ) {
        $themes        = array();
        $transientName = 'onecom_themes';

        $themes = (array) get_site_transient( $transientName );

        /* Note- simple switch over from previous data to new data structure */
        if ( ! isset( $themes['total'] ) && ! empty( $themes ) ) {
            delete_site_transient( $transientName );
            $themes = (array) get_site_transient( $transientName );
        }

        // If requested page already exists in transient, return
        if ( ! empty( $themes ) && isset( $themes[ OC_ITM_COUNT_STR ] ) && $themes[ OC_ITM_COUNT_STR ] >= 1000 ) {
            if ( array_key_exists( $page, $themes ) ) { // page exists in current themes
                $themes = oc_val_exclude_themes( $themes, $exclude_ilotheme );

                return $themes[ $page ];
            }
        }

        $fetch_themes_url = MIDDLEWARE_URL . '/themes';

        $fetch_themes_url = onecom_query_check( $fetch_themes_url, $page );

        global $wp_version;
        $args     = array(
            'timeout'     => 5,
            'httpversion' => '1.0',
            'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => true,
            'stream'      => false,
        );
        $response = wp_remote_get( $fetch_themes_url, $args );

        if ( is_wp_error( $response ) ) {
            if ( isset( $response->errors['http_request_failed'] ) ) {
                $errorMessage = __( 'Connection timed out', OC_VALIDATOR_DOMAIN );
            } else {
                $errorMessage = $response->get_error_message();
            }
        } else {
            if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
                $errorMessage = '(' . wp_remote_retrieve_response_code( $response ) . ') ' . wp_remote_retrieve_response_message( $response );
            } else {
                $body = wp_remote_retrieve_body( $response );
                $body = json_decode( $body );
                if ( ! empty( $body ) && $body->success ) {
                    $themes[ OC_ITM_COUNT_STR ]                       = $body->data->item_count;
                    $themes['total']                                  = $body->data->total;
                    $themes[ $body->data->current_page ]              = (object) array();
                    $themes[ $body->data->current_page ]->collection  = $body->data->collection;
                    $themes[ $body->data->current_page ]->page_number = $body->data->current_page;
                } elseif ( ! $body->success ) {
                    if ( $body->error == 'RESOURCE NOT FOUND' ) {
                        $try_again_url = add_query_arg(
                            array(
                                'request' => OC_THMS_STR,
                            ),
                            ''
                        );
                        $try_again_url = wp_nonce_url( $try_again_url, '_wpnonce' );
                        $errorMessage  = __( 'Sorry, no compatible themes found for your version of WordPress and PHP.', OC_VALIDATOR_DOMAIN ) . '&nbsp;<a href="' . $try_again_url . '">' . __( 'Try again', OC_VALIDATOR_DOMAIN ) . '</a>';
                    } else {
                        $errorMessage = $body->error;
                    }
                }
            }
            $themes = oc_val_exclude_themes( $themes, $exclude_ilotheme );

            if(is_blog_installed()){
                set_site_transient( $transientName, $themes, 3 * HOUR_IN_SECONDS );
            }
        }

        if ( empty( $themes ) || ! isset( $themes[ $page ] ) ) {
            return new WP_Error( 'message', $errorMessage );
        } else {
            return $themes[ $page ];
        }
    }
}

/**
 * Get premium themes names
 */
if(!function_exists('onecom_is_premium_theme')){
    function onecom_is_premium_theme($name=NULL){
        $themes = onecom_fetch_themes();
        $themes = ( isset( $themes->collection ) && ! empty( $themes->collection ) ) ? $themes->collection : array();
        $themes = array_reverse(array_reverse($themes));

        $premium_themes = [];
        foreach ($themes as $theme){
            if(in_array(PREMIUM, (array) $theme->tags)){
                $premium_themes[]=$theme->name;
            }
        }

        if($name==NULL){
            return $premium_themes;
        }


        if(in_array($name, $premium_themes)){
            return true;
        }
        return false;
    }
}

/**
 * Check if theme is to be activated
 */
if ( ! function_exists( 'oc_check_theme_eligibility' ) ) {
    function oc_check_theme_eligibility( $features, $stylesheet = '' ) {
        // exit if it is not a one.com theme
        $theme = wp_get_theme( $stylesheet );

        if ( 'one.com' !== strtolower( $theme->display( OC_AUTHOR_STR, false ) ) ) {
            return true;
        }

        // exit if premium package
        if ( in_array( OC_ID_PRM_THMS, $features ) ) {
            return true;
        }

        // check if non-premium WP package & trying to use STANDARD THEME
        if (
            ( in_array( OC_ID_OCI, $features ) || in_array( OC_ID_STD_THMS, $features ) )
            && ! onecom_is_premium_theme( $theme->display( OC_AUTHOR_STR, false ) )
        ) {
            return true;
        }

        return false;
    }
}

// Show notice for a non-premium WP package
if ( ! function_exists( 'onecom_premium_theme_admin_notice' ) ) {
    function onecom_premium_theme_admin_notice( $html = '' ) {
        $badge = '<div class="oc_notice notice notice-error"><p><em class="inline_icon" style="background:url(\'data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZmlsbDojOTUyNjVFOyIgd2lkdGg9IjkiIGhlaWdodD0iMTQiIHZpZXdCb3g9IjAgMCA5IDE0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0xLjQ5IDBoNi4wMTdsLTIgNC44NzNMOSA0Ljg2NSAyLjE0MiAxNGwxLjYzLTYuNzIzTDAgNy4yNzR6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=\');height: 13.5px;display: inline-block;vertical-align: middle;background-repeat: no-repeat;width: 9px;"></em> &nbsp;' . __( 'You have chosen a Premium feature that is not included in your current subscription.', OC_VALIDATOR_DOMAIN ) . ' ' . __( 'Please upgrade to a subscription with access to Premium features.', OC_VALIDATOR_DOMAIN ) . ' <a class="inline_badge_link" style="margin-left: 5px;color: #95265e;font-family: Open Sans;-webkit-font-smoothing: antialiased;font-size: 13px;font-weight: 600;line-height: 18px;cursor: pointer;text-decoration:none;" href="' . onecom_generic_locale_link( OC_PRM_PAGE_STR, get_locale() ) . '" target="_blank">' . OC_GENERIC_LEARN_MORE . '</a></p></div>';
        if ( $html != '' ) {
            return $html . $badge;
        }
        echo $badge;

    }
}

// Admin one.com notice css
if ( ! function_exists( 'onecom_premium_error_activation_style' ) ) {
    function onecom_premium_error_activation_style() {
        echo '<style>span.inline_badge{margin-left: 0px !important;height: 17px !important;}.notice:not(.oc_notice){display:none !important;}</style>';
    }
}

/**
 * Validate premium theme activation
 */
add_action( 'after_switch_theme', 'onecom_premium_theme_check', 10, 2 );
if ( ! function_exists( 'onecom_premium_theme_check' ) ) {
    function onecom_premium_theme_check( $oldtheme_name, $oldtheme ) {

        // just using the variable to reduce sonar warning
        $oldtheme_name;

        // exit if it is not a one.com theme
        $theme = wp_get_theme();
        if ( 'one.com' !== strtolower( $theme->display( OC_AUTHOR_STR, false ) ) ) {
            return true;
        }

        // exit if premium package
        $features = (array) oc_set_premi_flag();
        if ( in_array( OC_ID_PRM_THMS, $features ) ) {
            return true;
        }

        // check if non-premium WP package & trying to use STANDARD THEME
        if (
            ( in_array( OC_ID_OCI, $features ) || in_array( OC_ID_STD_THMS, $features ) ) &&
            ! onecom_is_premium_theme( $theme->display( OC_NAME_STR, false ) )
        ) {
            return true;
        }

        // Show notice for a non-WP package
        add_action( 'admin_notices', 'onecom_premium_theme_admin_notice', 2 );

        // Custom styling for default admin notice.
        add_action( 'admin_head', 'onecom_premium_error_activation_style' );

        // Switch back to previous theme.
        switch_theme( $oldtheme->stylesheet );

        return true;
    }
}

/**
 * Add headers to the provided object
 * This function intends to add domain validation headers in outgoing requests
 */
if ( ! function_exists( 'oc_add_http_headers' ) ) {
    function oc_add_http_headers( $data, $url ) {
        if ( strpos( $url, 'wpapi' ) === false || strpos( $url, '.one.com' ) === false ) {
            return $data;
        }
        $totp                                      = oc_generate_totp();
        $domain                                    = ! empty( OC_DOMAIN_NAME ) ? OC_DOMAIN_NAME : 'localhost';
        $data['headers']['X-Onecom-Client-Domain'] = $domain;
        $data['headers']['X-TOTP']                 = $totp;
        $data['headers']['X-ONECOM-CLIENT-IP']     = onecom_get_client_ip_env();

        return $data;
    }
}
/**
 * Function for checking the theme before activating/enabling it.
 */
add_action( 'load-themes.php', 'oc_check_theme_before_switch' );
if ( ! function_exists( 'oc_check_theme_before_switch' ) ) {
    function oc_check_theme_before_switch() {

        // Exit if not a GET request
        if ( ! isset( $_GET ) || empty( $_GET ) ) {
            return;
        }
        // return if no action
        if(!isset($_GET['action'])){
	        return;
        }

        if ( ! ( isset( $_GET['enable'] ) ) && ! ( isset( $_GET[ OC_THM_STR ] ) || isset( $_GET['stylesheet'] ) ) ) {
            return;
        }

        // Exit for all these types of requests
        if ( defined( 'XMLRPC_REQUEST' ) || defined( 'DOING_AJAX' ) || defined( 'IFRAME_REQUEST' ) || wp_is_json_request() ) {
            return;
        }

        // get current screen
        $current_page = get_current_screen()->base;

        // exit if not on themes page
        if ( ! ( isset( $_GET ) && ! empty( $_GET ) && ( $current_page == OC_THMS_STR || $current_page == 'themes-network' ) ) ) {
            return;
        }

        // check if oc_error
        if ( isset( $_GET[ OC_ERR_STR ] ) && 'oc_error' == $_GET[ OC_ERR_STR ] ) {
            $network = is_multisite() ? 'network_' : '';
            add_action( $network . 'admin_notices', 'onecom_premium_theme_admin_notice', 2 );
        }

        // exit if theme action is not for activating/enabling
        if ( ! ( $_GET['action'] == 'enable' || $_GET['action'] == 'activate' ) ) {
            return;
        }

        // exit if theme stylesheet not available
        if ( ! ( isset( $_GET[ OC_THM_STR ] ) || isset( $_GET['stylesheet'] ) ) ) {
            return;
        }

        // get stylesheet name for single/network site
        $stylesheet = isset( $_GET[ OC_THM_STR ] ) ? $_GET[ OC_THM_STR ] : $_GET['stylesheet'];

        // check if this theme is available for the current package; exit if available
        $features = (array) oc_set_premi_flag( true );
        if ( oc_check_theme_eligibility( $features, $stylesheet ) ) {
            return true;
        }

        // if theme not available, prepare for redirecting back on the themes page
        $temp_args              = array( 'enabled', OC_ERR_STR );
        $_SERVER['REQUEST_URI'] = remove_query_arg( $temp_args, $_SERVER['REQUEST_URI'] );
        $referer                = remove_query_arg( $temp_args, wp_get_referer() );

        if ( false === strpos( $referer, '/network/themes.php' ) ) {
            wp_redirect( network_admin_url( 'themes.php?error=oc_error' ) );
        } else {
            wp_safe_redirect( add_query_arg( OC_ERR_STR, 'oc_error', $referer ) );
        }
        exit;
    }
}

add_action( 'admin_print_footer_scripts', 'oc_pm_badge_injection', 999 );

if ( ! function_exists( 'oc_pm_badge_injection' ) ) {
    function oc_pm_badge_injection( $hook_suffix ) {
        $installed_themes     = wp_get_themes();
        $themes_to_mark_array = [];
        foreach ( $installed_themes as $theme ) {
            if ( 'one.com' === strtolower( $theme->display( OC_AUTHOR_STR, false ) )
                && onecom_is_premium_theme( $theme->display( OC_AUTHOR_STR, false ) )
            ) {
                $themes_to_mark_array[] = str_replace( ' ', '-', strtolower( $theme->display( OC_AUTHOR_STR, false ) ) );
                $themes_to_mark         = json_encode( $themes_to_mark_array );
            }
        }
        ?>
        <script>
            function _oc_pm_ribbon_injection(element) {
                jQuery(element).append('<span class="badge_bg" style="position: absolute;transform: rotate(45deg);z-index: 80;width: 105px;height: 73px;padding-top: 0px;top: -26px;right: -42px;background-color: #95265e;"></span><span class="badge_icon" style="position: absolute;transform: rotate(45deg);z-index: 80;pointer-events: none;top: 8px;right: 13px;"><svg style="height: 15px;width: 9px;display: inline-block;"><use xlink:href="#topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119"><svg viewBox="0 0 9 15" id="topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119"><path d="M1.486 0h6L5.492 5.004l3.482-.009-6.839 9.38 1.627-6.903L0 7.469z" fill="#FFF" fill-rule="evenodd"></path></svg></use></svg></span><span class="badge_text" style="position: absolute;transform: rotate(45deg);z-index: 80;color: #fff;text-transform: uppercase;font-style: normal;font-weight: 600;font-family: \'Open Sans\', sans-serif;display: block;text-align: center;top: 18px;font-size: 11px;right: 2px;-webkit-font-smoothing: antialiased;">Premium</span>').css('overflow', 'hidden');
            }

            function _oc_pm_ribbon_btn(themes_list) {
                if (!jQuery('.theme-info .theme-name').html()) {
                    return
                }
                $exp_name = jQuery('.theme-info .theme-name').html().toLowerCase();
                $exp_name = $exp_name.split("<span");
                if (-1 !== themes_list.indexOf($exp_name[0])) {
                    _oc_pm_ribbon_injection('.theme-overlay .screenshot');
                }
            }

            jQuery(document).ready(function () {
                //get a list of themes to mark as premium, if found none, initiate with empty json array
                var themes_to_mark = '<?php echo isset( $themes_to_mark ) ? $themes_to_mark : "[]"; ?>';
                var themes_list = JSON.parse(themes_to_mark);
                var dataslug;
                jQuery(".theme-browser .themes .theme").each(function (i, v) {
                    dataslug = jQuery(v).attr('data-slug');
                    if (dataslug) {
                        if (-1 !== themes_list.indexOf(dataslug) || -1 !== themes_list.indexOf(dataslug.replace('onecom-', ''))) {
                            _oc_pm_ribbon_injection(v)
                        }
                    }

                });
                jQuery(document).on('click', ".theme-browser .themes .theme", function () {
                    _oc_pm_ribbon_btn(themes_list);
                    jQuery(document).on('click', ".theme-header button.left,   .theme-header button.right", function () {
                        _oc_pm_ribbon_btn(themes_list);
                    });
                });

            });

        </script>
        <!-- Bind action with Upgrade button -->
        <script>
            function ocSetModalData(data) {
                if (!data) {
                    console.info('ValidateAction :: No data to set!');
                }
                jQuery('#oc_um_wrapper').attr({
                    'data-is_premium': data.isPremium,
                    'data-feature': data.feature,
                    'data-theme': data.theme,
                    'data-feature_action': data.featureAction,
                    'data-state': data.state || null
                });
            }

            jQuery(document).ready(function () {
                jQuery("#oc_um_footer a.oc_up_btn").click(function () {
                    jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        dataType: "JSON",
                        data: {
                            action: 'oc_validate_action',
                            operation: 'click_upgrade',
                            actionType: 'wppremium_click_upgrade',
                            isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
                            feature: jQuery('#oc_um_wrapper').attr('data-feature'),
                            theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
                            featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action')
                        },
                        error: function (xhr, textStatus, errorThrown) {
                            console.log("Some error occured during logging!");
                        }
                    });
                    jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
                });

                jQuery("#oc_um_close").click(function () {
                    if (!jQuery('#oc_um_wrapper').attr('data-feature')) {
                        return;
                    }
                    jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        dataType: "JSON",
                        data: {
                            action: 'oc_validate_action',
                            operation: 'close_upgrade',
                            actionType: 'wppremium_close_upgrade',
                            isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
                            feature: jQuery('#oc_um_wrapper').attr('data-feature'),
                            theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
                            featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
                            state: jQuery('#oc_um_wrapper').attr('data-state') || null
                        },
                        error: function (xhr, textStatus, errorThrown) {
                            console.log("Some error occured during logging!");
                        }
                    });
                    jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
                });

                jQuery("#oc_um_footer a.oc_cancel_btn").click(function () {
                    if (!jQuery('#oc_um_wrapper').attr('data-feature')) {
                        return;
                    }
                    jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        dataType: "JSON",
                        data: {
                            action: 'oc_validate_action',
                            operation: 'close_upgrade',
                            actionType: 'wppremium_close_upgrade',
                            isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
                            feature: jQuery('#oc_um_wrapper').attr('data-feature'),
                            theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
                            featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
                            state: jQuery('#oc_um_wrapper').attr('data-state') || null
                        },
                        error: function (xhr, textStatus, errorThrown) {
                            console.log("Some error occured during logging!");
                        }
                    });
                    jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
                });


            });
        </script>

        <?php
    }
}


/* Cutomizer controls */
add_action( 'customize_controls_enqueue_scripts', function () {

    $installed_themes   = wp_get_themes();
    $themes_to_mark_arr = [];
    $themes_to_mark     = "[]";
    foreach ( $installed_themes as $theme ) {
        if ( 'one.com' === strtolower( $theme->display( OC_AUTHOR_STR, false ) )
            && onecom_is_premium_theme( $theme->display( OC_AUTHOR_STR, false ) )
        ) {
            $themes_to_mark_arr[] = strtolower( $theme->display( OC_AUTHOR_STR, false ) );
            $themes_to_mark       = json_encode( $themes_to_mark_arr );
        }
    }

    wp_add_inline_script( 'customize-controls', '(function ( api ) {
        api.bind( "ready", function () {
            var _query = api.previewer.query;

            api.previewer.query = function () {
                var theme_ = ' . $themes_to_mark . ';
                var query = _query.call( this );
                // console.log($themes_to_mark);
                // console.log(query.customize_theme)
                if(-1 !== theme_.indexOf(query.customize_theme)){
                   //alert("halt!!");
                }
                query.foo = "bar";
                return query;
            };
        });
    })( wp.customize );'
    );
} );

/**
 * Function to get the client ip address
 **/
if ( ! function_exists( 'onecom_get_client_ip_env' ) ) {
    function onecom_get_client_ip_env() {
        if ( getenv( 'HTTP_CLIENT_IP' ) ) {
            $clientIP = @getenv( 'HTTP_CLIENT_IP' );
        } else if ( getenv( 'REMOTE_ADDR' ) ) {
            $clientIP = @getenv( 'REMOTE_ADDR' );
        } else {
            $clientIP = $_SERVER['ONECOM_CLIENT_IP'] = '0.0.0.0';
        }

        return $clientIP;
    }
}

/**
 * Function to buil URLs as per locale
 */
global $onecom_global_links;
$onecom_global_links          = array();
$onecom_global_links['en']    = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/en-us/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/en-us/articles/115005586029-Discouraged-WordPress-plugins',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/en-us/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/en-us/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/en/wordpress-hosting'
);
$onecom_global_links['cs_CZ'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/cs/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/cs/articles/115005586029-Nedoporu%C4%8Dovan%C3%A9-moduly-plug-in-ve-WordPressu',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/cs/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/cs/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/cs/wordpress'
);
$onecom_global_links['da_DK'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/da/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/da/articles/115005586029-Frar%C3%A5dede-WordPress-plugins',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/da/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/da/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/da/wordpress'
);
$onecom_global_links['de_DE'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/de/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/de/articles/115005586029-Nicht-empfohlene-Plugins',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/de/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/de/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/de/wordpress'
);
$onecom_global_links['es_ES'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/es/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/es/articles/115005586029-Plugins-de-WordPress-no-recomendados',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/es/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/es/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/es/wordpress'
);
$onecom_global_links['fr_FR'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/fr/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/fr/articles/115005586029-Les-plugins-WordPress-d%C3%A9conseill%C3%A9s',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/fr/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/fr/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/fr/wordpress'
);
$onecom_global_links['it_IT'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/it/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/it/articles/115005586029-Plugin-per-WordPress-sconsigliati',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/it/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/it/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/it/wordpress'
);
$onecom_global_links['nb_NO'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/no/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/no/articles/115005586029-Ikke-anbefalte-WordPress-plugins',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/no/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/no/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/no/wordpress'
);
$onecom_global_links['nl_NL'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/nl/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/nl/articles/115005586029-Niet-aanbevolen-WordPress-plugins',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/nl/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/nl/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/nl/wordpress-hosting'
);
$onecom_global_links['pl_PL'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/pl/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/pl/articles/115005586029-Niezalecane-wtyczki-WordPress',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/pl/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/pl/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/pl/wordpress'
);
$onecom_global_links['pt_PT'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/pt/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/pt/articles/115005586029-Plugins-para-o-WordPress-desaconselh%C3%A1veis',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/pt/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/pt/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/pt/wordpress'
);
$onecom_global_links['fi']    = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/fi/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/fi/articles/115005586029-WordPress-lis%C3%A4osat-joiden-k%C3%A4ytt%C3%B6%C3%A4-ei-suositella',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/fi/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/fi/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/fi/wordpress'
);
$onecom_global_links['sv_SE'] = array(
    OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/sv/categories/360002171377-WordPress',
    OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/sv/articles/115005586029-WordPress-till%C3%A4gg-som-vi-avr%C3%A5der-fr%C3%A5n',
    OC_STG_GUIDE_STR    => 'https://help.one.com/hc/sv/articles/360000020617',
    OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/sv/articles/360001472758',
    OC_PRM_PAGE_STR     => 'https://www.one.com/sv/wordpress-hosting'
);

if ( ! function_exists( 'onecom_generic_locale_link' ) ) {
    function onecom_generic_locale_link( $request, $locale, $lang_only = 0 ) {
        global $onecom_global_links;
        if ( ! empty( $onecom_global_links ) && array_key_exists( $locale, $onecom_global_links ) ) {

            if ( $lang_only != 0 ) {
                return strstr( $locale, '_', true );
            }

            if ( ! empty( $onecom_global_links[ $locale ][ $request ] ) ) {
                return $onecom_global_links[ $locale ][ $request ];
            }
        }

        if ( $lang_only != 0 ) {
            return 'en';
        }

        return $onecom_global_links['en'][ $request ];
    }
}
