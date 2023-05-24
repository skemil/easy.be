(function ($, wp) {
    jQuery(document).ready(function () {
        ocsh_checkPHPUpdates();
        jQuery(document).on('click', '.ocsh-scan-result li', function (e) {
            //exclude click events coming from description text
            if (
                (jQuery(e.target).attr('class') != 'ocsh-scan-title') && (jQuery(e.target).attr('class') != 'ocsh-error') && (jQuery(e.target).parent().attr('class') == 'osch-desc' || jQuery(e.target).parent().attr('class') == 'ocsh-bullet')) {
                return;
            }
            // jQuery(this).parent().find('.osch-desc').slideToggle('slow');
            jQuery(this).find('.osch-desc').toggleClass('hidden');
        });
        $('#oc_ht_text_button').click(function () {
            saveHtContent();

        });
        $('#oc-manual-edit').click(function () {
            toggleHtEdit();
        });
        $('.oc_file_extensions').click(function () {
            $(this).parent().toggleClass("checked")
        });
        if (window.localStorage.getItem('oc_manual_edit') == 1) {
            $('#oc-manual-edit').trigger('click');
            $('#oc-manual-edit').prop('checked', true)
        }
        $('#oc-ht-reset').click(function () {
            var content = atob(oc_constants.resetHtaccess);
            $('#oc_ht_textarea').val(content);
            $('#oc_ht_text_button').trigger('click');
        });
    });

    function saveHtContent() {
        $('#oc_ht_spinner').addClass('is_active').show();
        $('#oc_ht_message').html("");
        $('#oc_ht_text_button').attr('disabled', 'disabled');
        var content = $('#oc_ht_textarea').val();
        var extensions = [], postContent = '';
        if ($('#oc-manual-edit').prop("checked")) {
            postContent = btoa(content);
        } else {
            $.each($('.oc_file_extensions:checked'), function (index, val) {
                extensions.push($(val).val());
            })
        }
        $.post(ajaxurl, {
            action: 'oc_save_ht',
            _ajax_nonce: oc_constants.nonce,
            content: postContent,
            manual_edit: $('#oc-manual-edit').prop("checked"),
            extensions: extensions,
            file_string: $('#oc-file-string').val(),
            original_file_content: $('#oc-original-content').val()
        }, function (res) {
            if (res == -1) {
                $('#oc_ht_message').html(oc_constants.nonce_error);
            } else {
                $('#oc-original-content').val(res.new_file_content);
                $('#oc_ht_textarea').val(atob(res.new_file_content));
                $('#oc_ht_message').html(res.message);
            }
        }).fail(function () {
            $('#oc_ht_message').html(oc_constants.nonce_error);
        }).always(function () {
            $('#oc_ht_spinner').removeClass('is_active').addClass('success').hide().removeClass('success');
            $('#oc_ht_text_button').removeAttr('disabled');
        });
    }

    function toggleHtEdit() {
        if ($('#oc-manual-edit').prop("checked")) {
            $("#oc-ht-checkbox-wrap").slideUp(function () {
                $("#oc-ht-wrap").css("visibility", "hidden");
                $("#oc-ht-wrap").slideDown(function () {
                    if (false && $('.CodeMirror').length === 0) {
                        wp.codeEditor.initialize($('#oc_ht_textarea'), oc_constants.cm_settings);
                    }
                    $("#oc-ht-wrap").css("visibility", "visible");
                });
            });
            window.localStorage.setItem('oc_manual_edit', '1');
            $('#oc-ht-reset').slideDown();
        } else {
            $("#oc-ht-wrap").slideUp(function () {
                $("#oc-ht-checkbox-wrap").slideDown();
            });
            window.localStorage.setItem('oc_manual_edit', '0');
            $('#oc-ht-reset').slideUp();
        }
    }

    function ocsh_checkPHPUpdates() {
        // show the Error reporting section first as its aleady loaded
        jQuery('#ocsh-err-reporting').slideDown('slow');
        var data = {
            action: 'ocsh_check_php_updates'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-updates', response);
            ocsh_checkPluginUpdates();
        });
    }

    function ocsh_checkPluginUpdates() {
        var data = {
            action: 'ocsh_check_plugin_updates'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-plugin-updates', response);
            ocsh_checkThemeUpdates();
        });
    }

    function ocsh_checkThemeUpdates() {
        var data = {
            action: 'ocsh_check_theme_updates'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-theme-updates', response);
            ocsh_checkWPUpdates();
        });
    }

    function ocsh_checkWPUpdates() {
        var data = {
            action: 'ocsh_check_wp_updates'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-wp-updates', response);
            ocsh_checkWPCon();
        });
    }

    function ocsh_checkWPCon() {
        var data = {
            action: 'ocsh_wp_connection'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-wp-org-comm', response);
            ocsh_checkCoreUpdates();
        });
    }

    function ocsh_checkCoreUpdates() {
        var data = {
            action: 'ocsh_check_core_updates'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-core-updates', response);
            ocsh_checkSSL()
        });
    }

    function ocsh_checkSSL() {
        var data = {
            action: 'ocsh_check_ssl'
        };

        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-ssl', response);
            ocsh_checkFileExecution()
        });
    }

    function ocsh_checkFileExecution() {
        var data = {
            action: 'ocsh_check_file_execution'
        };
        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-file-execution', response);
            ocsh_checkFilePermissions();
        });
    }

    function ocsh_checkFilePermissions() {
        var data = {
            action: 'ocsh_check_file_permissions'
        };

        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-file-permissions', response);
            ocsh_checkDB();
        });
    }

    function ocsh_checkDB() {
        var data = {
            action: 'ocsh_DB'
        };

        jQuery.post(ajaxurl, data, function (response) {
            ocsh_processReponse('#ocsh-db', response);
            ocsh_checkFileEdit()
        });
    }

    function ocsh_checkFileEdit() {
        jQuery.post(ajaxurl, {
            action: 'ocsh_check_file_edit'
        }, function (response) {
            ocsh_processReponse('#ocsh-file-edit', response);
            ocsh_checkUserNames();
        });
    }

    function ocsh_checkUserNames() {
        jQuery.post(ajaxurl, {
            action: 'ocsh_check_usernames'
        }, function (response) {
            ocsh_processReponse('#ocsh-usernames', response);
            ocsh_checkDiscouragedPlugins()
        });
    }

    function ocsh_checkDiscouragedPlugins() {
        jQuery.post(ajaxurl, {
            action: 'ocsh_check_dis_plugin'
        }, function (response) {
            ocsh_processReponse('#ocsh-discouraged-plugins', response);
            ocsh_calculateSiteSecurity();
        });
    }

    function ocsh_processReponse(element, response) {
        var desc = response.desc;
        var html = desc;

        jQuery(element).find('.osch-desc').html(html);

        var clone = jQuery(element).clone();

        // if a fix is detected
        if (response.status === oc_constants.OC_RESOLVED) {
            jQuery(clone).find('span').addClass('ocsh-success');
            jQuery(clone).appendTo('#ocsh-all-ok').slideDown('slow');

            // remove description for a fix issue
            jQuery(clone).find('h4.ocsh-scan-title').html(desc)
            jQuery(clone).find('.osch-desc').remove();
            jQuery(clone).addClass('resolved');
        }
        // if an issue is detected
        else {
            jQuery(clone).find('span').addClass('ocsh-error');
            jQuery(clone).clone().appendTo('#ocsh-needs-attention').slideDown('slow');
        }

        // show separator if there are both kind of bullets
        if (jQuery('#ocsh-needs-attention li').length > 0 && jQuery('#ocsh-all-ok li').length > 0) {
            jQuery('.ocsh-separator').removeClass('hidden');
        }
    }

    function ocsh_calculateSiteSecurity() {
        var okCount = parseInt(jQuery('#ocsh-all-ok li').length);
        var errCount = parseInt(jQuery('#ocsh-needs-attention li').length);
        var healthPercent = ((okCount * 100) / (okCount + errCount)).toFixed(2);
        if (healthPercent == '100.00') {
            healthPercent = 100;
        }
        ocsh_save_result(healthPercent);
    }

    function ocsh_save_result(result) {

        var color = '#4ab865';
        if (result < 85 && result >= 50) {
            color = '#ffb900';
        } else if (result < 50) {
            color = '#dc3232';
        }
        var score = '<i style="color:' + color + '">' + Number(result).toFixed(0) + '%</i> ';

        jQuery.post(ajaxurl, {
            action: 'ocsh_save_result',
            osch_Result: result
        }, function (response) {
            var existingText = jQuery('#ocsh-site-security').text();
            jQuery('#ocsh-site-security').html(existingText + ' - ' + score + '<a class="button" href="' + oc_constants.ocsh_page_url + '">' + oc_constants.ocsh_scan_btn + '</a>');
        });
    }
})(jQuery, wp)