
var is_mobile = false;
//check is mobile devices or not
if(window.innerWidth <= 1190){
    is_mobile = true;
}
//Reload page if the page access from browser history
window.addEventListener( "pageshow", function ( event ) {
    var historyCheck = event.persisted || 
                           ( typeof window.performance != "undefined" && 
                                window.performance.navigation.type === 2 );
    if ( historyCheck ) {
      // reload page if load from history.
      window.location.reload();
    }
});

var LANG = oci.LANG;
$('#oc-wizard')
    .on("leaveStep", function (e, anchorObject, currentStepIndex, nextStepIndex, stepDirection) {
        oc_toggle_step(e, nextStepIndex, currentStepIndex, stepDirection);
        ValidateNextButton();
    })
    .on("showStep", function (e, anchorObject, stepNumber, stepDirection, stepPosition) {
        oc_reposition_nav_buttons();
        ValidateNextButton();
        $("aside.nav").css('opacity', '1');
        $("article.tab-content").css('opacity', '1');
    });

var initData = $('#oc-wizard').smartWizard({
    selected: 0,
    theme: 'none',
    autoAdjustHeight: true,
    enableURLhash: false,
    transition: {
    },
    toolbarSettings: {
        toolbarPosition: 'top', // both bottom
    },
    anchorSettings: {
        anchorClickable: false,
    },
    lang: { // Language variables for button
        next: LANG.NEXTSTEP,
        previous: LANG.BACK
    },
});

(function ($, jQuery, window, initData) {
    var OCI = {
        UI: {
            initialized: 0,
            wizardId: '#oc-wizard',
            navItem: '.nav-list .nav-item',
            navLink: 'a.nav-link'
        },

        _init: function () {

            if (OCI.UI.initialized) {
                return;
            }

            initData.init(function () {

                //active tab on first load
                if (jQuery(OCI.UI.navItem).length == 'undefined') {
                    return;
                }

                var getHash = '';
                jQuery(OCI.UI.navItem).each(function (index, value) {
                    var currNavItem = jQuery(this).find(OCI.UI.navLink);
                    if (currNavItem.hasClass('active')) {
                        getHash = currNavItem.attr('href');
                    }
                });

                var step = jQuery("[href='" + getHash + "']").closest('.nav-item');
                jQuery(step).removeClass('inactive').addClass('active');
                jQuery(step).siblings().removeClass('active').addClass('inactive');

                var backdash = '<button class="back-dashboard">'+LANG.BACK+'</button>';
                jQuery('#oc-wizard .nav .toolbar').prepend(backdash);

            });

            OCI.UI.initialized = 1;
        }
    }

    //entry point
    jQuery(document).ready(function () {
         //Reload page if the page access from browser history
         OCI._init();
    });
})(document, jQuery, window, initData);


var FLAG_THM = {
    'pm_badge': false,
    'pm_checked': false
};
var getHash = window.location.hash;

$(document).ready(function () {

    $( document ).ajaxStart(function() {
        $('.loading-overlay.show .loading-overlay-content p').replaceWith('<p>'+LANG.STARTAJAX+'</p>');
    });
    $('#oci-install').prop('disabled','true');
    $('#oc-wizard .nav .toolbar button.btn.sw-btn-next').prop("disabled", true);
    $('#oc-wizard .nav .toolbar button.btn.sw-btn-next').addClass("disabledNext");
    if(is_mobile){
        //remove preview theme button
        $('#oc-wizard .tab-content .tab-pane .one-preview a.preview_link').remove();
        $('#oc-wizard .tab-content .tab-pane .one-preview a.select_theme').remove();
        //add mobile class for theme block
        $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw').addClass('mobile-active');
    }else{
        $('.mb-devices-preview-notification').remove();
    }
    
    //for hide get help button
    $('#oc-wizard,#step-3').scroll(function(){
        var thisObjScroll = $(this);
        var scroll = $(this).scrollTop();
        if(thisObjScroll.hasClass('tab-pane') && window.innerWidth <= 768){
            return false;
        }
       
        if (scroll >= 25) {
            $('.gethelp').hide();
        }
        else{
            $('.gethelp').show();
        }
    });

    /* remove preview notification on click ok */
    $('.mb-devices-preview-notification .inner-mb-devices .mb-inner-content button').click(function(){
        $('.mb-devices-preview-notification').remove();
    });
    /* on template block click */
    $('#oc-wizard .tab-content .tab-pane .one-preview a.select_theme, #oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw.mobile-active').click(function(){
        
        var thisObj = '';
        if($(this).hasClass('mobile-active') && is_mobile){
            thisObj = $(this);
        }else{
            thisObj = $(this).closest('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw');
        }
        //check if already class added
        if(thisObj.hasClass('template-selected')){
            return;
        }
        var removeObj = $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw');
        var themeSlug = thisObj.attr('data-tslug');
        var thisRow = thisObj.find('.theme-overlay .theme-action .one-install');
        var download_url = thisRow.attr('data-download');
        var afterRedirect = thisRow.attr('data-redirect');

        removeObj.removeClass('template-selected');
        thisObj.addClass('template-selected');
        $('#oci-selected-template').val(themeSlug);
        $('#oci-download-url').val(download_url);
        $('#oci-redirect-url').val(afterRedirect);

        //enable install button
        $('#oci-install').removeClass('btn-disabled');
        $('#oci-install').prop("disabled", false);

        if(is_mobile){
            $('.mb-devices-preview-notification').css('display','block');
        }

    });

    /* remove selected theme */
    $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw .selected_theme').click(function(e){
        
        var thisObj = $(this).closest('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw');
        thisObj.removeClass('template-selected');
        $('#oci-selected-template').val('');
        $('#oci-download-url').val('');
        $('#oci-redirect-url').val('');

        //disable install button
        $('#oci-install').addClass('btn-disabled');
        $('#oci-install').prop("disabled", true);
        e.stopPropagation();
    });
    
    /* on template filter */
    $('select#oc_theme_filter_select').change(function () {
        var filterVal = $(this).val();
        var checkRow = $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw');
        var counter = 0;
        
        checkRow.css('display','none');
        /* loop per row for class check */
        checkRow.each(function (index, val) {
            console.log(filterVal);
            let innerObj = $(this);
            if (innerObj.hasClass(filterVal)) {
                if(counter >= 4 && is_mobile){
                    return false;
                }
                innerObj.css('display', 'block');
                counter++;
            } else {
                innerObj.css('display', 'none');
            }
        });

        if(is_mobile){
            if($('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw'+'.'+filterVal+':hidden').length === 0){
                $('.mobile_loader').css('display','none');
            }else{
                $('.mobile_loader').css('display','block');
            }
        }
    });

    //load more on mobile devices
    $('.mobile_loader a').click(function(){
        var filterVal = $('select#oc_theme_filter_select').val();
        var checkRow = $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw');
        var counter = 0;
        
        
        /* loop per row for class check */
        checkRow.each(function (index, val) {
            console.log(filterVal);
            let innerObj = $(this);
            if (innerObj.hasClass(filterVal) && innerObj.is(':hidden')) {
                if(counter >= 2 && is_mobile){
                    return false;
                }
                innerObj.css('display', 'block');
                counter++;
            } else {
                //condition
            }
        });

        if($('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw'+'.'+filterVal+':hidden').length === 0){
            $('.mobile_loader').css('display','none');
         }
    });


});

/**
 * Redirect on cp dashboard 
 */
$(document).on("click",'.back-dashboard', function () {
    window.location.href = 'https://www.one.com/admin/managedwp/wp-installation.do';
});
/**
 * Theme preview
 */
$(document).on("click", ".preview_link", function () {
    // Toggle premium badge
    oc_toggle_inline_badge($(this).parents('.one-theme:first').attr('data-is-premium'));

    var theme_count = $(".theme-browser > div.one-theme").length;
    // Set current theme demo url in iframe
    var url = $(this).attr("data-demo-url");
    $('iframe').attr('src', url);

    var current_demo_id = $(this).attr('data-id');
    // Set next demo url id attribute
    var next_id = $(this).closest('.one-theme').nextAll('.one-theme:visible').find('.preview_link').attr("data-id");
    $('.header_btn_bar .next').attr('data-demo-id', next_id);
    // Set previous demo url id attribute
    var prev_id = $(this).closest('.one-theme').prevAll('.one-theme:visible:first').find('.preview_link').attr("data-id");
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
    var prev_theme_num = $('#demo-' + demo_id).closest('.one-theme').prevAll('.one-theme:visible:first').length;
    if (prev_theme_num === 0) {
        $('.header_btn_bar .previous').css({ 'opacity': '0.5', 'cursor': 'initial' });
        $('.header_btn_bar .previous').attr('data-demo-id', '0');
    }
    // If no (0) next theme preview div available, disable next button
    demo_id = $(this).attr('data-id');
    var next_theme_num = $('#demo-' + demo_id).closest('.one-theme').nextAll('.one-theme:visible').length;
    if (next_theme_num === 0) {
        $('.header_btn_bar .next').css({ 'opacity': '0.5', 'cursor': 'initial' });
        $('.header_btn_bar .next').attr('data-demo-id', '0');
    }

    // Load Preview Overlay after preview next theme information compilation
    tb_show("Preview Popup", "#TB_inline?width=full&height=full&inlineId=thickbox_preview&modal=true&class=thickbox", null);
    $('.preview-container').addClass('scroll');
    // Add preview page specific class to set page width/height to full page
    $('body').addClass("preview_page");
    var referrer = location.search;
    oc_prepare_log(demo_id, 'preview', referrer);

});

/**
 * Function to next template preview in popup
 */
$(document).on("click", ".header_btn_bar .next", function () {

    // Check if current preview theme is first, disable previous button
    var demo_id = $(this).attr('data-demo-id');
    var active_demo_id = $('#preview_box .theme-info').attr('data-active-demo-id');
    var next_theme_num = $('#demo-' + demo_id).closest('.one-theme').nextAll('.one-theme:visible').length;
    var url = '';
    var theme_wrapper = '';
    $('.header_btn_bar .preview-install-button').attr('data-active-demo-id', demo_id);

    // Toggle premium badge
    oc_toggle_inline_badge($('[data-index="' + demo_id + '"]').attr('data-is-premium'));
    var referrer = location.search;
    oc_prepare_log(demo_id, 'navigation', referrer);
    if (demo_id === '0') {
        // demo_id 0 means, you are already on last theme. No action needed
        event.stopPropagation();
    } else if (next_theme_num === 0) {
        // next_theme_num 0 means, next theme is last theme. Disable next button
        $(this).css({ 'opacity': '0.5', 'cursor': 'initial' });
        $(this).attr('data-demo-id', 0);
        $('.header_btn_bar .previous').attr('data-demo-id', active_demo_id);
        url = $('#demo-' + demo_id).attr('data-demo-url');
        theme_wrapper = $('#demo-' + demo_id).parents('.one-theme:first');
        $('iframe').attr('src', url);
        $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
    } else {
        // Common action for rest of the themes
        $('.header_btn_bar .previous').removeAttr('style');
        url = $('#demo-' + demo_id).attr("data-demo-url");
        theme_wrapper = $('#demo-' + demo_id).parents('.one-theme:first');
        $('iframe').attr('src', url);
        var next_id = $('#demo-' + demo_id).closest('.one-theme').nextAll('.one-theme:visible').find('.preview_link').attr("data-id");
        $(this).attr('data-demo-id', next_id);
        $('.header_btn_bar .previous').attr('data-demo-id', active_demo_id);
        $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
    }
    if ($(theme_wrapper).hasClass('installed')) {
        $('.header_btn_bar').find('.preview-install-button').hide();
    } else {
        $('.header_btn_bar').find('.preview-install-button').show();
    }
});

/**
 * Function to previous template preview in popup
 */
$(document).on("click", ".header_btn_bar .previous", function () {

    // Check if current preview theme is first, disable previous button
    var demo_id = $(this).attr('data-demo-id');
    var active_demo_id = $('#preview_box .theme-info').attr('data-active-demo-id');
    var prev_theme_num = $('#demo-' + demo_id).closest('.one-theme').prevAll('.one-theme:visible').length;
    $('.header_btn_bar .preview-install-button').attr('data-active-demo-id', demo_id);
    var url = '';
    // Toggle premium badge
    oc_toggle_inline_badge($('[data-index="' + demo_id + '"]').attr('data-is-premium'));
    var referrer = location.search;
    oc_prepare_log(demo_id, 'navigation', referrer);
    if (demo_id === '0') {
        // demo_id 0 means, no previous theme demo available
        event.stopPropagation();
    } else if (prev_theme_num === 0) {
        // prev_theme_num 0 means, it will switch to first theme and disable previous button
        $(this).css({ 'opacity': '0.5', 'cursor': 'initial' });
        $(this).attr('data-demo-id', 0);
        $('.header_btn_bar .next').attr('data-demo-id', active_demo_id);
        url = $('#demo-' + demo_id).attr('data-demo-url');
        $('iframe').attr('src', url);
        // Assign previous demo id 0, as this is first theme
        $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
    } else {
        $('.header_btn_bar .next').removeAttr('style');
        url = $('#demo-' + demo_id).attr("data-demo-url");
        $('iframe').attr('src', url);
        var prev_id = $('#demo-' + demo_id).closest('.one-theme').prevAll('.one-theme:visible:first').find('.preview_link').attr("data-id");
        $(this).attr('data-demo-id', prev_id);
        $('.header_btn_bar .next').attr('data-demo-id', active_demo_id);
        $('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
    }
    if (typeof theme_wrapper != "undefined" && $(theme_wrapper).length && $(theme_wrapper).hasClass('installed')) {
        $('.header_btn_bar').find('.preview-install-button').hide();
    } else {
        $('.header_btn_bar').find('.preview-install-button').show();
    }
});

/**
 * Close theme popup
 */
$(document).on("click", "#preview_box", function (e) {
    if( $(e.target).hasClass('view-icon') ) {
        return false;
    } else {
        closePreviewWindow();
    } 
});

/**
 * Hide preview window on escape key
 */
$(document).keydown(function(e) {
    // ESCAPE key pressed
    if (e.keyCode == 27) {
        closePreviewWindow();
    }
});

/**
 * Install WP on skip button click
 */
$(document).on("click", ".sw-btn-skip", function () {
    $('#oci-selected-template').val('');
    $('.loading-overlay').addClass('show');
    $('input[name=action]').val('oci_install_wp');
    oci_install_handler();

});

/**
 * Handle install theme action
 **/
$(document).on("click", "#oci-install", function (e) {

    $('.loading-overlay').addClass('show');
    $('input[name=action]').val('oci_install_wp');
    oci_validate_before_installer();

});

/**
 * Handle popup install theme button
 */
$(document).on("click", ".preview-install-button", function (e) {

    $('.loading-overlay').addClass('show');
    $('.left-header .close_btn').trigger('click');

    var popupInstall = $(this);
    var activeDemoid = popupInstall.attr('data-active-demo-id');
    var activeRow = $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw[data-index="' + activeDemoid + '"]').find('.one-install');
    var removeObj = $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw');
    var themeSlug = activeRow.attr('data-theme_slug');
    var download_url = activeRow.attr('data-download');
    var afterRedirect = activeRow.attr('data-redirect');

    removeObj.removeClass('template-selected');

    $('#oci-selected-template').val(themeSlug);
    $('#oci-download-url').val(download_url);
    $('#oci-redirect-url').val(afterRedirect);
    $('input[name=action]').val('oci_install_wp');
    oci_validate_before_installer();
    
});

/**
 * Preview desktop version template
 */
$(document).on("click", "#desktop", function () {
    $(".preview-container .phone-content").removeClass("phone-content").addClass("desktop-content");
    $(".preview-container .tablet-content").removeClass("tablet-content").addClass("desktop-content");
    $(".preview-container .preview div").remove(".scrn-wrap");
    $(".preview-container").addClass("scroll");
    $(".preview-container iframe").removeClass("horizontal");
    $(".desktop-content").removeClass("horizontal");
    $("#desktop").addClass("current");
    $('#tablet').removeClass("current");
    $("#mobile").removeClass("current");
    
});

/**
 * Preview desktop version template
 */
$(document).on("click", "#tablet", function () {
    $(".preview-container .phone-content").removeClass("phone-content").addClass("tablet-content");
    $('.preview-container .desktop-content').removeClass("desktop-content").addClass("tablet-content");
    $(".preview-container .preview div").remove(".scrn-wrap");
    $(".preview-container").addClass("scroll");
    $(".preview-container iframe").removeClass("horizontal");
    $(".desktop-content").removeClass("horizontal");
    
    $('#tablet').addClass("current");
    $("#desktop").removeClass("current");
    $("#mobile").removeClass("current");
});

/**
 * Preview mobile version template
 */
$(document).on("click", "#mobile", function () {
    $('.preview-container .desktop-content').removeClass("desktop-content").addClass("phone-content");
    $('.preview-container .tablet-content').removeClass("tablet-content").addClass("phone-content");
    $(".preview-container").addClass("scroll");
    $("#desktop").removeClass("current");
    $("#mobile").addClass("current");
    $("#tablet").removeClass("current");
});

/**
 * Preview mobile version template in landscape and potrait view
 */
$(document).on("click", ".scrn-wrap", function () {
    $(".preview-container iframe").toggleClass("horizontal");
    $(".phone-content").toggleClass("horizontal");
});

/**
 * Validate form field on change and enter
 */
$(document).on("change keyup", ".oci-field", function () {
    ValidateNextButton();
});

/**
 * Validate form field on change and enter
 */
$(document).on("change keyup", ".oci-field", function () {

    var thisField = $(this);
    var thisFieldType = thisField.attr('type');
    var thisFieldVal = thisField.val().trim().length;
    var errorClass = 'fieldError';
    var thisId = thisField.attr('id');

    //validate text fields
    if (thisFieldType == 'text') {
        if (thisFieldVal == 0) {
            thisField.addClass(errorClass);
        } else if (thisId == 'oci-username' && (thisFieldVal <= 3 || thisFieldVal >= 45)) {
            thisField.addClass(errorClass);
        } else {
            thisField.removeClass(errorClass);
        }
    }

    //validate password 
    if (thisFieldType == 'password') {
        if (thisFieldVal == 0) {
            thisField.addClass(errorClass);
        } else if (thisFieldVal < 4 || thisFieldVal > 40) {
            thisField.addClass(errorClass);
        } else {
            thisField.removeClass(errorClass);
        }
    }

    //validate email
    if (thisFieldType == 'email') {

        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        var emailStatus = re.test(String(thisField.val()).toLowerCase());
        
        if (thisFieldVal == 0) {
            thisField.addClass(errorClass);
        } else if (thisFieldVal < 7 || thisFieldVal >= 40) {
            thisField.addClass(errorClass);
        } else if (!emailStatus) {
            thisField.addClass(errorClass);
        } else {
            thisField.removeClass(errorClass);
        }
    }
    //validate checkbox
    if (thisFieldType == 'checkbox') {
        if (!thisField.is(':checked') && thisId == 'oci-tc') {
            thisField.parent().addClass(errorClass);
        } else {
            thisField.parent().removeClass(errorClass);
        }
    }
    ValidateNextButton();
});

/**
 * Check enable under construction
 */
$(document).on("change", ".enableum .switch input[type='checkbox']", function () {
    var thisCobj = $(this);
    if (thisCobj.is(':checked')) {
        $('#oci-enableuc').val(thisCobj.val());
    } else {
        $('#oci-enableuc').val('');
    }
});

/**
 * Hide preview window
 */
function closePreviewWindow(){
    // remove thickbox overlay
    tb_remove();
    // remove preview page specific class
    setTimeout(function () {
        $('body').removeClass("preview_page");
    }, 500);
}

/**
 * Validate next button on slide show
 */
function ValidateNextButton(){
    var slideVisible    =   $('#oc-wizard .tab-content .tab-pane:visible');
    var SlideId         =   slideVisible.attr('id');

    /* handle theme install button when no theme is installed */
    if(slideVisible && SlideId == 'step-3'){
        return false;
    }
    
    if(slideVisible && SlideId == 'step-1'){
       if($('#oci-email').val() === null || $('#oci-email').val() == '' || $('#oci-username').val() === null || $('#oci-username').val() == '' || $('#oci-passwd').val() === null || $('#oci-passwd').val() == ''){
        disableNextButton();
       }else if(slideVisible.find('.fieldset .field-wrap .fieldError').length > 0){
        disableNextButton();
       }else{
        enableNextButton();
       }
    }else if(slideVisible && SlideId == 'step-2'){
       
       if($('#oci-username-title').val() === null || $('#oci-username-title').val() == '' || $('#oci-tagline').val() === null || $('#oci-tagline').val() == '' || !$('#oci-tc').is(':checked')){
        disableNextButton();
       }else if(slideVisible.find('.fieldset .field-wrap .fieldError').length > 0){
        disableNextButton();
       }else{
        enableNextButton();
       }
    }
}

/**
 * Disable next button on slide show
 */
function disableNextButton(){
    $('#oc-wizard .nav .toolbar button.btn.sw-btn-next:not(#oci-install)').prop("disabled", true);
    $('#oc-wizard .nav .toolbar button.btn.sw-btn-next:not(#oci-install)').addClass("disabledNext");
}

/**
 * Enable next button on slide show
 */
function enableNextButton(){
    $('#oc-wizard .nav .toolbar button.btn.sw-btn-next:not(#oci-install)').prop("disabled", false);
    $('#oc-wizard .nav .toolbar button.btn.sw-btn-next:not(#oci-install)').removeClass("disabledNext");
}

/**
 * Function to toggle inline premium badge
 */
function oc_toggle_inline_badge(flag) {

    // hide all badges
    $('.inline_badge').hide();

    // check if badge to be shown
    if (flag != "1") {
        return;
    }

    // show badge as per user
    if (FLAG_THM.pm_checked) {
        pm_badge_switcher(FLAG_THM.pm_badge);
    }
    else {
        oc_validate_action('ptheme');
    }
}

/**
 * Function to check validation for premium theme
 */
function oci_validate_before_installer() {

    $('.loading-overlay').addClass('show');
    var that = $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw.template-selected');
    var type = (that.attr('data-is-premium') == 1) ? 'ptheme' : 'stheme';
    var isPremiumInt = that.attr('data-is-premium') || 0;
    var referrer = location.search;
    var isPremium;
    if (isPremiumInt == '0') {
        isPremium = 'false';
    } else if (isPremiumInt == '1') {
        isPremium = 'true';
    }
    oc_validate_action(type).then(function (response) {

        if (response.status === 'success') {

            oci_install_handler();
            oc_log_request({
                actionType: 'wppremium_install_theme',
                isPremium: isPremium,
                theme: that.data('theme_slug'),
                referrer: referrer

            });
            return true;
        } else if (response.status === 'failed') {
            jQuery('#oc_um_overlay').show();
            ocSetModalData({
                isPremium: isPremium,
                feature: 'theme',
                theme: that.data('theme_slug'),
                actionType: 'wppremium_install_theme'
            });
        }
        else {
            //some unknown error occured
            if (response.msg) {
                oc_alert(response.msg, 'error', 5000);
            }
        }
        $('.loading-overlay').removeClass('show');
    });
}

/**
 * Function to make request for installation
 */
function oci_install_handler() {

    var data = $('#one-setup-form').serialize();
    $.post(oci.ajaxurl, data, function (response) {

        var result = $.parseJSON(response);

        if (typeof result.type != 'undefined' && result.type == 'success') {
            /**
            * If WP installed, hide popup to show theme listing
            **/
            if (typeof result.install != 'undefined' && (result.install == 'true' || result.install == true)) {
                var redirectUrl = result.url;
                
                /**
                * Initiate to install dependent plugins
                **/
                if (typeof result.install_dependancy != 'undefined' && (result.install_dependancy == true || result.install_dependancy == 'true')) {
                    var ddata = {
                        'action': 'oci_install_dependancy'
                    };
                    $.post(oci.ajaxurl, ddata, function (depResponse) {
                        console.log(depResponse);

                    }).done(function (ocidepResponse) {
                       
                        var ociDepresult = $.parseJSON(ocidepResponse);
                        if (typeof ociDepresult.type != 'undefined' && ociDepresult.type == 'console') {
                            console.log(ociDepresult);
                            $('.loading-overlay.show .loading-overlay-content p').replaceWith('<p>'+ociDepresult.message+'</p>');
                        
                            var logincp     = $('#oci-username').val();
                            var passwordcp  = $('#oci-passwd').val();

                            //cp details
                            $('#oci-username-cp').val(logincp);
                            $('#oci-passwd-cp').val(passwordcp);

                            $('aside.nav .nav-list, #oc-wizard .nav .toolbar').css('display', 'none');
                            $('#oc-wizard .tab-content > div:not(#step-4)').css('display', 'none');
                            
                            $('#step-4').css('display', 'flex');
                            $('aside.nav').addClass('oci-doneall');
                            $('aside.nav').css('width','calc(100% - 40px)');
                            $('#one-setup-form').remove();
                            $('#oc-wizard .nav .toolbar').remove();//remove nav button from last steps
                            
                            $('.loading-overlay').removeClass('show');
                            
                            ociDepresult.redirectUrl = redirectUrl;
                            $('#admin_redirect_login').bind("click", function () {
                                autoLogin(ociDepresult);
                            });
                        }
                    }).fail(function (failedResponse) {
                        console.log(failedResponse);
                    });
                }
            }
        } else {
            $('.oci-notifier').html(result.message).attr('type', result.type).addClass('show');
            setTimeout(function () {
                $('.oci-notifier').removeClass('show');
                $('.loading-overlay').removeClass('show');
            }, 5000);
        }
    }).done(function (response) {
        var result = $.parseJSON(response);
        $('.loading-overlay.show .loading-overlay-content p').replaceWith('<p>'+result.message+'</p>');
        console.log("parent success");
    }).fail(function (response) {
        console.log(response);
    });
}

/**
 * Function to set modal data
 */
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

/**
 * Function to autologin into wordpress
 */
function autoLogin(result) {

    /**
    * Auto login to dashboard, since 4.7.4 not supporting auto login adding following custom call to wp-login.php
    **/
    if (typeof result.redirectUrl == 'undefined' || typeof result.loginUrl == 'undefined') {
        return false;
    }

    var login, password = '';
    login = $('#oci-username-cp').val();
    password = $('#oci-passwd-cp').val();

    if (!login.length || !password.length) {
        return false;
    }

    var login_data = {
        'action': 'login',
        'log': login,
        'pwd': password,
        'testcookie': true
    }
    $.post(result.loginUrl, login_data, function (response) {
        // Cannot check if successfully login or not
    }).done(function (response) {
        window.location.href = result.redirectUrl;
    }).fail(function (response) {
        console.log(response);
    });
}

/**
 * Function to toggle step visibility
 */
function oc_toggle_step(e, anchorObject, currentStepIndex, stepDirection) {

    var totalSlide = $('#oc-wizard .nav .nav-list .nav-item').length;
    var navParnt = $('#oc-wizard .nav .toolbar');
    var nextbtn = $('#oc-wizard button.sw-btn-next');
    var prevbtn = $('#oc-wizard button.sw-btn-prev');
    var skipbtn = '<a href="javascript:void(0);" class="sw-btn-skip" title="'+LANG.SKIP+'" disabled>'+LANG.SKIP+'</a>';
    var installBtn = '<button class="btn sw-btn-next btn-disabled" id="oci-install" type="button" disabled="disabled">'+LANG.INSTALL+'</button>';
    
    if (anchorObject == null) {
        anchorObject = 0;
    }

    anchorObject += 1;

    //validate step before go to next slide
    if (stepDirection == 'forward') {

        var validStep = formValidate(e, currentStepIndex);

        if (validStep == false) {
            return;
        }

        $('.fieldset .field-wrap').find('input').removeClass('fieldError');
    }

    var backdash = '<button class="back-dashboard">'+LANG.BACK+'</button>';
    
    if(stepDirection == 'forward'){
        $('.back-dashboard').remove();
    }else if(stepDirection == 'backward' && anchorObject == 1){
        $('#oc-wizard .nav .toolbar').prepend(backdash);
    }

    $('.sw-btn-next:not(#oci-install)').css('display', 'inline-flex');
    $('#oci-install.sw-btn-next').remove();
    prevbtn.text(LANG.BACK); //change text of previous button
    $('.sw-btn-skip').remove(); // remove skip button if not 3rd step
    //reset template value
    $('#oci-selected-template').val('');
    $('#oci-download-url').val('');
    $('#oci-redirect-url').val('');
    $('#oci-retry').val('');

    $('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw').removeClass('template-selected');

    if (anchorObject == (totalSlide - 1)) {

        $(installBtn).insertAfter(nextbtn);
        $('#oci-install.sw-btn-next').css('display', 'inline-flex');
        $('.sw-btn-next:not(#oci-install)').css('display', 'none');
        navParnt.append(skipbtn);
        
    }

    //hide next and prev button on last step
    navParnt.css('display', 'block');
    if (anchorObject == totalSlide) {
        navParnt.css('display', 'none');
    }
    anchorObject = "#step-" + anchorObject;

    var step = $("[href='" + anchorObject + "']").closest('.nav-item');
    $(step).removeClass('inactive').addClass('active');
    $(step).siblings().removeClass('active').addClass('inactive');
}

/**
 * Function to validation form field
 */
function formValidate(e, currentStepIndex) {

    var errorClass = 'fieldError';
    var validationFlag = true;

    //slide first validation
    if (currentStepIndex == 0) {

        var username = $('#oci-username');
        var password = $('#oci-passwd');
        var email = $('#oci-email');

        var usernameVal = username.val().trim();
        var passwordVal = password.val().trim();
        var emailVal = email.val().trim();

        //validate all field
        if (usernameVal.length == 0 && passwordVal.length == 0 && emailVal.length == 0) {
            username.addClass(errorClass);
            password.addClass(errorClass);
            email.addClass(errorClass);

        } else if (usernameVal.length == 0 && passwordVal.length == 0) {
            username.addClass(errorClass);
            password.addClass(errorClass);

        } else if (usernameVal.length == 0 && emailVal.length == 0) {
            username.addClass(errorClass);
            email.addClass(errorClass);

        } else if (passwordVal.length == 0 && emailVal.length == 0) {
            password.addClass(errorClass);
            email.addClass(errorClass);

        }

        //validate usename
        if (usernameVal.length == 0 || usernameVal.length <= 3 || usernameVal.length >= 45) {
            username.addClass(errorClass);
            validationFlag = false;
        } else {
            username.removeClass(errorClass);
        }

        //validate password
        if (passwordVal.length == 0 || passwordVal.length < 4 || passwordVal.length > 40) {
            password.addClass(errorClass);
            validationFlag = false;
        } else {
            password.removeClass(errorClass);
        }

        //validate email
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        var emailStatus = re.test(String(emailVal).toLowerCase());

        if (emailVal.length == 0 || emailVal.length < 7 || emailVal.length >= 40 || !emailStatus) {
            email.addClass(errorClass);
            validationFlag = false;
        } else {
            email.removeClass(errorClass);
        }
        //validate email
        if (validationFlag == false) {
            e.preventDefault();
            return validationFlag;
        }
        return validationFlag;
    }

    //slide 2nd validation
    if (currentStepIndex == 1) {

        var blogTitle = $('#oci-username-title');
        var tagLine = $('#oci-tagline');
        var tcCheck = $('#oci-tc');

        var blogTitleVal = blogTitle.val().trim();
        var tagLineVal = tagLine.val().trim();
        var tcCheckVal = tcCheck.val().trim();

        //validate all field
        if (blogTitleVal.length == 0 && tagLineVal.length == 0 && tcCheckVal.length == 0) {
            blogTitle.addClass(errorClass);
            tagLine.addClass(errorClass);
            tcCheck.addClass(errorClass);
        } else if (blogTitleVal.length == 0 && tagLineVal.length == 0) {
            blogTitle.addClass(errorClass);
            tagLine.addClass(errorClass);
        } else if (blogTitleVal.length == 0 && tcCheckVal.length == 0) {
            blogTitle.addClass(errorClass);
            tcCheck.addClass(errorClass);
        } else if (tagLineVal.length == 0 && tcCheckVal.length == 0) {
            tagLine.addClass(errorClass);
            tcCheck.addClass(errorClass);
        }
        //validate blog title
        if (blogTitleVal.length == 0) {
            blogTitle.addClass('fieldError');
            validationFlag = false;
        } else {
            blogTitle.removeClass('fieldError');
        }

        //validate tagline
        if (tagLineVal.length == 0) {
            tagLine.addClass('fieldError');
            validationFlag = false;
        } else {
            tagLine.removeClass('fieldError');
        }

        //validate terms and conditions
        if (!tcCheck.is(':checked')) {
            tcCheck.parent().addClass('fieldError');
            validationFlag = false;
        } else {
            tcCheck.parent().removeClass('fieldError');
        }

        if (validationFlag == false) {
            e.preventDefault();
            return validationFlag;
        }
        return validationFlag;
    }
}

/**
 * Function to reposition Nav(Next and Back) Buttons
 */
function oc_reposition_nav_buttons() {
    var tabEle = '[role="toolbar"]';
    var aside = 'aside.nav';

    if (!$(document).find(tabEle).length) {
        return;
    }

    if ($(aside).find(tabEle).length) {
        return;
    }

    $(aside).append($(tabEle));

}

/**
 * Function to prepare log
 */
function oc_prepare_log(obj, section, referrer) {
    var tn = '';
    var index = obj;
    var targetElement = $('.theme-browser').find($('[data-index="' + index + '"]'));
    var themeName;
    tn = $(targetElement).find($('.theme-action')).find("[data-theme_slug]").data('theme_slug');
    if (tn) {
        themeName = tn.trim();
    }
    var isPremiumInt = $(targetElement).data('is-premium') || '0';
    var isPremium = '';
    if (isPremiumInt == '0') {
        isPremium = 'false';
    } else if (isPremiumInt == '1') {
       isPremium = 'true';
    }
    oc_log_request({
        actionType: 'wppremium_preview_theme',
        isPremium: isPremium,
        theme: tn,
        referrer: referrer
    });
}

/**
 * Function to make log request
 */
function oc_log_request(obj) {
    var data = {};
    data.action = 'oc_validate_action';
    data.actionType = obj.actionType;
    data.isPremium = obj.isPremium;
    data.referrer = obj.referrer;

    if (obj.feature) {
        data.feature = obj.feature;
    }
    if (obj.theme) {
        data.theme = obj.theme
    }
    if (obj.state) {
        data.state = obj.state
    }
    if (obj.featureAction) {
        data.featureAction = featureAction;
    }

    jQuery.ajax({
        url: oci.ajaxurl,//_wpUtilSettings.ajax.url
        type: "POST",
        dataType: "JSON",
        data: data,
        error: function (xhr, textStatus, errorThrown) {
            console.log("Some error occured during logging!");
        }
    });
}

/**
 * Function to validate theme premium or standard
 */
function oc_validate_action(action) {
    return jQuery.ajax({
        url: oci.ajaxurl,//_wpUtilSettings.ajax.url,
        type: "POST",
        dataType: "JSON",
        data: {
            action: 'oc_validate_action',
            operation: action
        },
        success: function (response) {
            FLAG_THM.pm_checked = true;
            FLAG_THM.pm_badge = response.status;
            pm_badge_switcher(FLAG_THM.pm_badge);
        },
        error: function (xhr, textStatus, errorThrown) {
            oc_alert("Some error occurred, please reload the page and try again.", 'error', 5000)
        }
    });
}

/**
 * Function to Change theme badge
 */
function pm_badge_switcher(status) {

    if (status === "error") {
        return;
    }
    if (status == "success") {
        $('.inline_badge.standard').hide();
        $('.inline_badge.premium').css('display', 'inline-flex');
    }
    else {
        $('.inline_badge.standard').css('display', 'inline-flex');
        $('.inline_badge.premium').hide();
    }
}

/**
 * Top Notifier
 */
function oc_alert(msg = '', type = 'error', time = 5000) {

    jQuery('.onecom-notifier').html(msg).attr('type', type).addClass('show');
    setTimeout(function () {
        jQuery('.onecom-notifier').removeClass('show');
        jQuery('.loading-overlay.fullscreen-loader').removeClass('show');
    }, time);

}