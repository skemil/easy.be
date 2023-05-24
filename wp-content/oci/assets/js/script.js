var FLAG_THM = {
    'pm_badge' : false,
    'pm_checked':false
};
(function($) {
    $(document).ready(function() {
        /**
         * Function which will return value of query parameter
         **/
        function getQueryStringValue(key, url) {
            if (typeof url == 'undefined' || url == null || url == '') {
                url = window.location.search;
            }
            return decodeURIComponent(url.replace(new RegExp("^(?:.*[&\\?]" + encodeURIComponent(key).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));
        }

        /**
         * Function to display step
         **/
        function display_step(step) {
            $('.step-setup').removeClass('active');
            $('.one-button').removeClass('active');

            $('.setup-' + step).addClass('active');
            $('.setup-' + step + '-button').addClass('active');
            $('input[name=step]').val(step);
        }
        /**
         * Change strings
         **/
        function change_strings(strings) {
            $.each(strings, function(key, value) {
                var $element = $('.' + key);
                if ($element.is('input')) {
                    $element.val(value);
                } else {
                    $element.text(value);
                }
            });
        }

        /**
         * Handle URL parameters
         **/
        var step = getQueryStringValue('step');
        if (step == '') {
            step = 'start';
        }

        //load_form_data( step );

        display_step(step);

        /**
         * If current step is contact
         **/
        if (step == 'contact') {
            $('#pass1').prop('disabled', false);
            $('#pass1-text').prop('disabled', false);
        }

        $('html').addClass('step-' + $('input[name=step]').val() + '-wrapper');

        $('.one-button').click(function() {
            step = $('input[name=step]').val();


            $('.loading-overlay').addClass('show');

            var is_errors = check_errors(step);
            if (is_errors == false) {
                $('.loading-overlay').removeClass('show');
                return false;
            }

            var next_step = '';
            if (step == 'language') {
                next_step = 'start';
            } else if (step == 'start') {
                next_step = 'contact';
            } else if (step == 'contact') {
                next_step = 'settings';
            } else if (step == 'settings') {
                next_step = 'theme';
            }

            $('input[name=next_step]').val(next_step);

            if (next_step == 'settings') {
                if (!$('#terms-and-conditions').is(':checked')) {
                    $('input[name=settings_one_setup]').prop('disabled', true);
                }
            }

            /**
             * Settings step will install the WP hence handling it differently at action
             **/
            if (step != 'settings') {
                if (next_step != 'theme') {
                    display_step(next_step);
                }

                $('.wrap').removeClass('step-' + step);
                $('.wrap').addClass('step-' + next_step);

                $('html').removeClass('step-' + step + '-wrapper');
                $('html').addClass('step-' + next_step + '-wrapper');

                /**
                 * Simple workaround for user-profile.js disabled event
                 **/
                if (next_step == 'contact') {
                    $('#pass1').prop('disabled', false);
                    $('#pass1-text').prop('disabled', false);
                }

                /**
                 *  Handle Active / Done step iterations
                 **/
                $('.one-steps-list-item-' + step).removeClass('active-step');
                $('.one-steps-list-item-' + step).addClass('done-step');
                $('.one-steps-list-item-' + next_step).addClass('active-step');

                /**
                 * Update step counter
                 **/
                var index = $('.one-steps-list').find('li.active-step').index();
                $('.step-counter').text(index + 1);

                /**
                 * Add query parameters to URL
                 **/
                if (typeof next_step != 'undefined' && next_step != '') {
                    history.pushState(null, null, '?step=' + next_step);
                }

                $('.loading-overlay').removeClass('show');
            } else {
                $('input[name=action]').val('oci_install_wp');

                var data = $('.one-setup-form').serialize();

                $.post(oci.ajaxurl, data, function(response) {

                    var result = $.parseJSON(response);

                    if (typeof result.type != 'undefined' && result.type == 'success') {
                        /**
                         * If WP installed, hide popup to show theme listing
                         **/
                        if (typeof result.install != 'undefined' && (result.install == 'true' || result.install == true)) {

                            $('.popup').hide();
                            /**
                             * Auto login to dashboard, since 4.7.4 not supporting auto login adding following custom call to wp-login.php
                             **/
                            if (typeof result.user != 'undefined') {
                                var login_url = $('form.one-setup-form').attr('data-login_url');
                                if (typeof result.user.log == 'undefined' || result.user.log == '' || typeof result.user.pwd == 'undefined' || result.user.pwd == '') {
                                    return false;
                                }
                                var log = result.user.log;
                                var pwd = result.user.pwd;
                                var login_data = {
                                    'action': 'login',
                                    'log': log,
                                    'pwd': pwd,
                                    'testcookie': true
                                }
                                $.post(login_url, login_data, function(response) {
                                    // Cannot check if successfully login or not
                                }).fail(function(response) {
                                    console.log(response);
                                });
                            }

                            /**
                             * Initiate to install dependent plugins
                             **/
                            if (typeof result.install_dependancy != 'undefined' && (result.install_dependancy == true || result.install_dependancy == 'true')) {
                                var ddata = {
                                    'action': 'oci_install_dependancy'
                                };
                                $.post(oci.ajaxurl, ddata, function(response) {
                                    var result = $.parseJSON(response);
                                    if (typeof result.type != 'undefined' && result.type == 'console') {
                                        console.log(result);
                                    }
                                }).fail(function(response) {
                                    console.log(response);
                                });
                            }
                        }

                        display_step(next_step);

                        $('.wrap').removeClass('step-' + step);
                        $('.wrap').addClass('step-' + next_step);

                        $('html').removeClass('step-' + step + '-wrapper');
                        $('html').addClass('step-' + next_step + '-wrapper');

                        /**
                         * Add query parameters to URL
                         **/
                        if (typeof next_step != 'undefined' && next_step != '') {
                            history.pushState(null, null, '?step=' + next_step);
                        }

                        ociRemoveOverlay();
                    } else {
                        console.log(response);
                        $('.oci-notifier').html(result.message).attr('type', result.type).addClass('show');
                        setTimeout(function() {
                            $('.oci-notifier').removeClass('show');
                            $('.loading-overlay').removeClass('show');
                        }, 5000);
                    }
                }).fail(function(response) {
                    console.log(response);
                });
            }
        });

        /**
         * Handle steps click
         **/
        $('.one-steps-list a').click(function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            if ($(this).parents('li:first').hasClass('done-step')) {
                var prev_step = getQueryStringValue('step', href);
                display_step(prev_step);

                /**
                 *  Handle Active / Done step iterations
                 **/
                $('.one-steps-list-item').removeClass('active-step');
                //$( '.one-steps-list-item-'+prev_step ).removeClass( 'done-step' );
                $('.one-steps-list-item-' + prev_step).addClass('active-step');

                /**
                 * Update step counter
                 **/
                var index = $('.one-steps-list').find('li.active-step').index();
                $('.step-counter').text(index + 1);

                $('.one-steps-list').find('li').each(function(i, li) {
                    if (i >= index) {
                        $(li).removeClass('done-step');
                    }
                });

                if (prev_step == 'contact') {
                    $('#pass1').prop('disabled', false);
                    $('#pass1-text').prop('disabled', false);
                }

                /**
                 * Add query parameters to URL
                 **/
                if (typeof prev_step != 'undefined' && prev_step != '') {
                    history.pushState(null, null, '?step=' + prev_step);
                }
            }
        });

        /**
         * Handle language change button text
         **/
        $(document).on('change', '#language', function() {
            var continue_text = $(this).find('option:selected').attr('data-continue');
            $('#language-continue-oci').attr('value', continue_text);
            $('input[name=oci_language]').val($(this).val());
        });

        /**
         * Install / Activate theme
         */
        function oc_install_theme(that){
            var theme_wrapper = $(that).parents('.one-theme:first');

            $(theme_wrapper).addClass('active');

            var name = $(that).attr('data-name');
            $('input[name=hit]').val(name);

            //$( document ).trigger( 'disable_one_buttons', [ 'one-install' ] );
            $('.loading-overlay').addClass('show');

            var download_url = $(that).attr('data-download');
            var theme_slug = $(that).attr('data-theme_slug');
            var redirect = $(that).attr('data-redirect');

            var retry = 0;

            var data = {
                'action': 'oci_install_theme',
                'download_url': download_url,
                'theme_slug': theme_slug,
                'redirect': redirect,
                'retry': retry
            };

            oci_post_data(data);
        }

        /**
         * Top Notifier
         */
        function oc_alert(msg='', type='error', time=5000){

            jQuery( '.onecom-notifier' ).html( msg ).attr( 'type', type ).addClass( 'show' );
            setTimeout( function(){
                jQuery( '.onecom-notifier' ).removeClass( 'show' );
                jQuery( '.loading-overlay.fullscreen-loader' ).removeClass( 'show' );
            }, time );
        }

        /**
         * Handle install theme action
         **/
        $('.one-install').click(function() {
            $( '.loading-overlay' ).addClass('show');
            var that = $(this);
            var type = ( $(this).parents('.one-theme.theme').first().attr('data-is-premium') == 1)? 'ptheme': 'stheme';
            var isPremiumInt = $(this).parents('.one-theme.theme').first().attr('data-is-premium') || 0;
            var referrer=location.search;
            var isPremium;
            if (isPremiumInt == '0'){
                isPremium = 'false';
            }else if(isPremiumInt == '1'){
                isPremium = 'true';
            }
            oc_validate_action(type).then(function(response){
                if ( response.status === 'success'){
                    oc_install_theme(that);
                    oc_log_request({
                        actionType: 'wppremium_install_theme',
                        isPremium: isPremium,
                        theme: that.data('theme_slug'),
                        referrer:referrer

                    });
                    return true;
                }else if(response.status === 'failed'){
                    jQuery('#oc_um_overlay').show();
                    ocSetModalData({
                        isPremium: isPremium,
                        feature: 'theme',
                        theme: that.data('theme_slug'),
                        actionType: 'wppremium_install_theme'
                    });
                }
                else{
                    //some unknown error occured
                    if(response.msg){
                        oc_alert(response.msg, 'error', 5000);
                    }
                }
                $( '.loading-overlay' ).removeClass( 'show' );
            });
        });

        /* Custom filter */
        var selectedClass = "";
        $(".fil-cat").click(function(e) {
            e.preventDefault();
            $('.fil-cat').removeClass('active');
            $(this).addClass('active');
            selectedClass = $(this).attr("data-rel");
            $("#portfolio").fadeTo(100, 0.1);
            $("#portfolio .one-theme").not("." + selectedClass).fadeOut().removeClass('scale-anm');
            setTimeout(function() {
                $("." + selectedClass).fadeIn().addClass('scale-anm');
                $("#portfolio").fadeTo(300, 1);
            }, 300);
        });

    });

    function oci_post_data(data) {
        $.post(oci.ajaxurl, data, function(response) {
            console.log(response);

            if (IsJson(response)) {
                var result = $.parseJSON(response);
            } else {
                /**
                 * Break not JSON data to proceed next steps
                 **/
                if (string = response.match(/\|\|(.*?)\|\|/i)[1]) {
                    var result = $.parseJSON(string);
                }
            }

            if (typeof result.type != 'undefined' && result.type == 'redirect') {
                window.location = result.url;
            } else {
                data.retry += 1;
                if (data.retry <= 2) {
                    setTimeout(function() {
                        oci_post_data(data);
                    }, 2000);
                } else {
                    $('.oci-notifier').html(result.message).attr('type', result.type).addClass('show');
                    setTimeout(function() {
                        $('.oci-notifier').removeClass('show');
                        $('.loading-overlay').removeClass('show');
                    }, 5000);
                }
            }

        }).fail(function(response) {
            if (response.status == 500) {
                $('.oci-notifier').html(response.statusText + '!<br/>Filesystem cannot connect with your directory, try adding <code>define(\'FS_METHOD\',\'direct\');</code> into wp-config.php').attr('type', 'error').addClass('show');
                setTimeout(function() {
                    $('.oci-notifier').removeClass('show');
                    $('.loading-overlay').removeClass('show');
                }, 10000);
            }
        });
    }



    /**
     * Function to toggle inline premium badge
     */
    function oc_toggle_inline_badge(flag){

        // hide all badges
        $('.inline_badge').hide();

        // check if badge to be shown
        if(flag!= "1"){
            return;
        }

        // show badge as per user
        if(FLAG_THM.pm_checked){
            pm_badge_switcher(FLAG_THM.pm_badge);
        }
        else{
            oc_validate_action('ptheme');
        }
    }


    function IsJson(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }

    function check_errors(step) {
        var errors = 0;
        var selectors = [];
        if (step == 'contact') {
            var selectors = [
                '#username',
                '#email',
                '#pass1'
            ];
        } else if (step == 'settings') {
            var selectors = [
                '#site_title',
                '#site_tagline',
            ];
        }

        $.each(selectors, function(i, selector) {
            if ($(selector).val() == '') {
                $(selector).addClass('error');
                errors++;
            } else {
                $(selector).removeClass('error');
            }
        });

        /**
         * Treat specially for specific elements like email
         **/
        if (step == 'contact') {
            var pattern = /^\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b$/i
            var email = $('#email').val();
            if ((typeof email != 'undefined') && !pattern.test(email)) {
                $('#email').addClass('error');
                errors++;
            } else {
                $('#email').removeClass('error');
            }

            var username = $('#username').val();
            //var sanitized_username = sanitize_user( username );
            //console.log( sanitized_username  + ' ' + username );
            if (!is_sanitize_user(username)) {
                $('#username').addClass('error');
                errors++;
            } else {
                $('#username').removeClass('error');
            }
        }

        if (errors == 0)
            return true;
        else
            return false;
    }

    function is_sanitize_user(username) {
        if (/^[a-zA-Z0-9 _.@-]+$/.test(username)) {
            return true;
        }
        return false;
    }

    function pm_badge_switcher(status){
        //$('.inline_badge').hide();
        if (status === "error"){

            return;
        }
        if(status=="success"){
            $('.inline_badge.standard').hide();
            $('.inline_badge.premium').css('display', 'inline-flex');
        }
        else{
            $('.inline_badge.standard').css('display', 'inline-flex');
            $('.inline_badge.premium').hide();
        }
    }

    function oc_validate_action(action){
        return jQuery.ajax({
            url: _wpUtilSettings.ajax.url,
            type: "POST",
            dataType: "JSON",
            data:{action: 'oc_validate_action',
                operation: action
            },
            success: function(response){
                FLAG_THM.pm_checked = true;
                FLAG_THM.pm_badge = response.status;
                pm_badge_switcher(FLAG_THM.pm_badge);
            },
            error: function (xhr, textStatus, errorThrown) {
                oc_alert("Some error occurred, please reload the page and try again.", 'error', 5000)}
        });
    }



    /* ==============  Theme preview JS with next/previous button events ==================== */
    // $( ".theme-screenshot .theme-overlay" ).ready(function() {
    $(document).on("click", ".preview_link", function() {



        // Toggle premium badge
        oc_toggle_inline_badge($(this).parents('.one-theme:first').attr('data-is-premium'));

        var theme_count = $(".theme-browser > div.one-theme").length;
        // Set current theme demo url in iframe
        var url = $(this).attr("data-demo-url");
        $('iframe').attr('src', url);

        var current_demo_id = $(this).attr('data-id');
        // Set next demo url id attribute
        var next_id = $(this).closest('.one-theme').next('.one-theme').find('.preview_link').attr("data-id");
        $('.header_btn_bar .next').attr('data-demo-id', next_id);
        // Set previous demo url id attribute
        var prev_id = $(this).closest('.one-theme').prev('.one-theme').find('.preview_link').attr("data-id");
        $('.header_btn_bar .previous').attr('data-demo-id', prev_id);

        // Check theme count to manage previous/next action
        $('.header_btn_bar .theme-info').attr('data-theme-count', theme_count);
        // Set current theme id in data attribute
        $('.header_btn_bar .theme-info').attr('data-active-demo-id', current_demo_id);
        $('.header_btn_bar .preview-install-button').attr('data-active-demo-id', current_demo_id);
        // Reset Previous/Next Button Style
        $('.header_btn_bar .next').removeAttr('style');
        $('.header_btn_bar .previous').removeAttr('style');
        // If no (0) previous theme preview div available, disable previous button
        var demo_id = $(this).attr('data-id');
        var prev_theme_num = $('#demo-' + demo_id).closest('.one-theme').prev('.one-theme').length;
        if (prev_theme_num === 0) {
            $('.header_btn_bar .previous').css({ 'opacity': '0.5', 'cursor': 'initial' });
            $('.header_btn_bar .previous').attr('data-demo-id', '0');
        }
        // If no (0) next theme preview div available, disable next button
        var demo_id = $(this).attr('data-id');
        var next_theme_num = $('#demo-' + demo_id).closest('.one-theme').next('.one-theme').length;
        if (next_theme_num === 0) {
            $('.header_btn_bar .next').css({ 'opacity': '0.5', 'cursor': 'initial' });
            $('.header_btn_bar .next').attr('data-demo-id', '0');
        }

        // Load Preview Overlay after preview next theme information compilation
        tb_show("Preview Popup", "#TB_inline?width=full&height=full&inlineId=thickbox_preview&modal=true&class=thickbox", null);

        // Add preview page specific class to set page width/height to full page
        $('body').addClass("preview_page");
        var referrer=location.search;
        oc_prepare_log(demo_id, 'preview',referrer);

    });

    $(document).on("click", ".close_btn", function() {
        // remove thickbox overlay
        tb_remove();
        // remove preview page specific class
        setTimeout(function() {
            $('body').removeClass("preview_page");
        }, 500);
    });

    $(document).on("click", "#desktop", function() {
        $(".preview-container .phone-content").removeClass("phone-content").addClass("desktop-content");
        $(".preview-container .preview span").remove(".screen-rotate");
        $(".preview-container").removeClass("scroll");
        $(".preview-container iframe").removeClass("horizontal");
        $(".desktop-content").removeClass("horizontal");
        $("#desktop").addClass("current");
        $("#mobile").removeClass("current");
    });

    $(document).on("click", "#mobile", function() {
        $('.preview-container .desktop-content').removeClass("desktop-content").addClass("phone-content");
        $(".preview-container").addClass("scroll");
        $(".preview-container .preview").append('<span class="screen-rotate"></span>');
        $("#desktop").removeClass("current");
        $("#mobile").addClass("current");
    });

    $(document).on("click", ".screen-rotate", function() {
        $(".preview-container iframe").toggleClass("horizontal");
        $(".phone-content").toggleClass("horizontal");
    });

    $( document ).on( 'click', '.preview-install-button', function() {
        var current_demo_id = $( this ).attr( 'data-active-demo-id' );
        var item = null;
        $( '.one-theme' ).each( function( key, theme ) {
            var demo_id = $( theme ).find( '.preview_link' ).attr( 'data-id' );
            if( demo_id === current_demo_id ) {
                item = theme;
                return false;
            }
        } );
        if( item != null ) {
            $( 'html, body' ).stop().animate({ scrollTop: ( $( item ).offset().top - 64 ) }, 300);
            $( '.close_btn' ).trigger( 'click' );
            $( item ).find( '.one-install' ).trigger( 'click' );
        }
    } );

    $(document).on("click", ".header_btn_bar .next", function() {
        // Check if current preview theme is first, disable previous button
        var demo_id = $(this).attr('data-demo-id');
        var active_demo_id = $('#preview_box .theme-info').attr('data-active-demo-id');
        var next_theme_num = $('#demo-' + demo_id).closest('.one-theme').next('.one-theme').length;
        $('.header_btn_bar .preview-install-button').attr('data-active-demo-id', demo_id);

        // Toggle premium badge
        oc_toggle_inline_badge($('[data-index="'+demo_id+'"]').attr('data-is-premium'));
        var referrer=location.search;
        oc_prepare_log(demo_id, 'navigation',referrer);
        if (demo_id === '0') {
            // demo_id 0 means, you are already on last theme. No action needed
            event.stopPropagation();
        } else if (next_theme_num === 0) {
            // next_theme_num 0 means, next theme is last theme. Disable next button
            $(this).css({ 'opacity': '0.5', 'cursor': 'initial' });
            $(this).attr('data-demo-id', 0);
            $('.header_btn_bar .previous').attr('data-demo-id', active_demo_id);
            var url = $('#demo-' + demo_id).attr('data-demo-url');
            var theme_wrapper = $('#demo-'+demo_id).parents( '.one-theme:first' );
            $('iframe').attr('src', url);
            $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
        } else {
            // Common action for rest of the themes
            $('.header_btn_bar .previous').removeAttr('style');
            var url = $('#demo-' + demo_id).attr("data-demo-url");
            var theme_wrapper = $('#demo-'+demo_id).parents( '.one-theme:first' );
            $('iframe').attr('src', url);
            var next_id = $('#demo-' + demo_id).closest('.one-theme').next('.one-theme').find('.preview_link').attr("data-id");
            $(this).attr('data-demo-id', next_id);
            $('.header_btn_bar .previous').attr('data-demo-id', active_demo_id);
            $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
        }
        if( $( theme_wrapper ).hasClass( 'installed' ) ) {
            $( '.header_btn_bar' ).find( '.preview-install-button' ).hide();
        } else {
            $( '.header_btn_bar' ).find( '.preview-install-button' ).show();
        }
    });

    $(document).on("click", ".header_btn_bar .previous", function() {
        // Check if current preview theme is first, disable previous button
        var demo_id = $(this).attr('data-demo-id');
        var active_demo_id = $('#preview_box .theme-info').attr('data-active-demo-id');
        var prev_theme_num = $('#demo-' + demo_id).closest('.one-theme').prev('.one-theme').length;
        $('.header_btn_bar .preview-install-button').attr('data-active-demo-id', demo_id);

        // Toggle premium badge
        oc_toggle_inline_badge($('[data-index="'+demo_id+'"]').attr('data-is-premium'));
        var referrer=location.search;
        oc_prepare_log(demo_id, 'navigation',referrer);
        if (demo_id === '0') {
            // demo_id 0 means, no previous theme demo available
            event.stopPropagation();
        } else if (prev_theme_num === 0) {
            // prev_theme_num 0 means, it will switch to first theme and disable previous button
            $(this).css({ 'opacity': '0.5', 'cursor': 'initial' });
            $(this).attr('data-demo-id', 0);
            $('.header_btn_bar .next').attr('data-demo-id', active_demo_id);
            var url = $('#demo-' + demo_id).attr('data-demo-url');
            $('iframe').attr('src', url);
            // Assign previous demo id 0, as this is first theme
            $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
        } else {
            $('.header_btn_bar .next').removeAttr('style');
            var url = $('#demo-' + demo_id).attr("data-demo-url");
            $('iframe').attr('src', url);
            var prev_id = $('#demo-' + demo_id).closest('.one-theme').prev('.one-theme').find('.preview_link').attr("data-id");
            $(this).attr('data-demo-id', prev_id);
            $('.header_btn_bar .next').attr('data-demo-id', active_demo_id);
            $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
        }
        if( typeof theme_wrapper != "undefined" && $( theme_wrapper ).length && $( theme_wrapper ).hasClass( 'installed' ) ) {
            $( '.header_btn_bar' ).find( '.preview-install-button' ).hide();
        } else {
            $( '.header_btn_bar' ).find( '.preview-install-button' ).show();
        }
    });

    /**
     * Snippet to control password strength notifier, weak password confirmation and submit button control
     **/
    $(document).on('keyup', '#pass1', function() {
        check_password_strength();
    });
    $(document).on('keyup', '#pass1-text', function() {
        check_password_strength();
    });
    $(document).on('change', '#pass1', function() {
        check_password_strength();
    });
    $(document).on('change', '#pass1-text', function() {
        check_password_strength();
    });

    function check_password_strength() {
        var strength = $('#pass-strength-result').attr('class');
        if ( (strength == 'short' || strength == 'bad') && ( ! $('#pw_weak').prop('checked') ) ) {
            $('.one-button[name=contact_one_setup]').attr('disabled', true);
        } else {
            $('.one-button[name=contact_one_setup]').attr('disabled', false);
        }
    }

    $(document).on('click', '.pw-weak', function() {
        if ($('input[name=pw_weak]').is(':checked')) {
            $('.one-button[name=contact_one_setup]').attr('disabled', false);
        } else {
            $('.one-button[name=contact_one_setup]').attr('disabled', true);
        }
    });

    $(document).on('click', '.wp-terms-condition', function() {
        if ($('#terms-and-conditions').is(':checked')) {
            $('input[name=settings_one_setup]').prop('disabled', false);
        } else {
            $('input[name=settings_one_setup]').prop('disabled', true);
        }
    });

    $(document).on('thickbox:iframe:loaded', function(e) {
        onecom_resize_thickbox();
    });

    $(window).on('resize', onecom_resize_thickbox);

    /**
     * Snippet to handle thickbox full size
     **/
    function onecom_resize_thickbox() {
        TB_WIDTH = (($(window).width() * 75) / 100);
        TB_HEIGHT = (($(window).height() * 85) / 100);
        $("#TB_window").css({ marginLeft: '-' + parseInt((TB_WIDTH / 2), 10) + 'px', width: TB_WIDTH + 'px' });
        $("#TB_window").css({ marginTop: '-' + parseInt((TB_HEIGHT / 2), 10) + 'px', height: TB_HEIGHT + 'px' });
    }

    /**
     * Function ociRemoveOverlay
     * Check if WordPress update locks are set in DB before removing overlay
     * This will ensure that all WordPress upgrades are finished before allowing user to interact with page
     */
    function ociRemoveOverlay(){
        $.post(oci.ajaxurl, {action:'oci_check_if_busy'}, function(res){
            try{
                var response = JSON.parse(res);
                if (response && response.code && response.code == 1){
                    setTimeout(function(){
                        ociRemoveOverlay();
                    }, 2000);
                }else{
                    $('.loading-overlay').removeClass('show');
                }
            }catch(e){
                $('.loading-overlay').removeClass('show');
            }

        });
    }
    function oc_prepare_log(obj, section,referrer){
        var tn = '';
        var index = obj;
        var targetElement = $('.theme-browser').find($('[data-index="'+index+'"]'));
        var themeName;
        tn = $(targetElement).find($('.theme-action')).find("[data-theme_slug]").data('theme_slug');
        if (tn){
            themeName = tn.trim()
        }
        isPremiumInt = $(targetElement).data('is-premium') || '0';
        if (isPremiumInt == '0'){
            isPremium = 'false';
        }else if(isPremiumInt == '1'){
            isPremium = 'true';
        }
        oc_log_request({
            actionType: 'wppremium_preview_theme',
            isPremium: isPremium,
            theme: tn ,
            referrer:referrer
        });
    }
    function ocSetModalData(data){
        if (!data){
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
    jQuery(document).ready(function(){


        jQuery("#oc_um_footer a.oc_up_btn").click(function(){
            var referrer= location.search;
            oc_log_request({
                actionType: 'wppremium_click_upgrade',
                isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
                feature: jQuery('#oc_um_wrapper').attr('data-feature'),
                theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
                featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
                referrer:referrer||null
            });
            jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
        });
        jQuery("#oc_um_close").click(function(){
            if ( ! jQuery('#oc_um_wrapper').attr('data-feature') ){
                return;
            }
            var referrer= location.search;
            oc_log_request({
                actionType: 'wppremium_close_upgrade',
                isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
                feature: jQuery('#oc_um_wrapper').attr('data-feature'),
                theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
                featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
                state: jQuery('#oc_um_wrapper').attr('data-state') || null,
                referrer:referrer||null
            });
            jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
        });

        jQuery("#oc_um_footer a.oc_cancel_btn").click(function(){
            if ( ! jQuery('#oc_um_wrapper').attr('data-feature') ){
                return;
            }
            var referrer= location.search;
            oc_log_request({
                actionType: 'wppremium_close_upgrade',
                isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
                feature: jQuery('#oc_um_wrapper').attr('data-feature'),
                theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
                featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
                state: jQuery('#oc_um_wrapper').attr('data-state') || null,
                referrer:referrer||null
            });
            jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
        });

        // Installing underconstruction during skip to dashboard
        jQuery('a.step-5-skip-dashbaord').click(function (e) {

            if (jQuery('input#uc_enable').is(':checked')) {
                // Stop anchor click and install plugin
                e.preventDefault();
                // Install plugin action
                var data = {
                    'action': 'onecom_uc_install'
                };
                jQuery.post(oci.ajaxurl, data, function (response) {
                    console.log(response);
                }).fail(function (response) {
                    console.log(response);
                }).complete(function () {
                    // @todo - verify for localhost subfolder installation
                    window.location.href = '?redirect=/wp-admin/';
                });
                
            }
        });

    });
    function oc_log_request(obj){
        var data = {};
        data.action = 'oc_validate_action';
        data.actionType = obj.actionType;
        data.isPremium = obj.isPremium;
        data.referrer=obj.referrer;

        if(obj.feature){
            data.feature = obj.feature;
        }
        if(obj.theme){
            data.theme = obj.theme
        }
        if(obj.state){
            data.state = obj.state
        }
        if (obj.featureAction){
            data.featureAction = featureAction;
        }
        jQuery.ajax({
            url: _wpUtilSettings.ajax.url,
            type: "POST",
            dataType: "JSON",
            data: data,
            error: function (xhr, textStatus, errorThrown) {
                console.log("Some error occured during logging!");
            }
        });
    }
})(jQuery);