require('./magic_url.scss');

jQuery(document).ready(function ($) {
    var $loginForm = $('#loginform');
    $loginForm.append($('#fls_magic_login'));
    $('#fls_magic_login').show();

    var $initialWrapper = $('.fls_magic_initial');
    var $magicFormWrapper = $('.fls_magic_login_form');
    $('.fls_magic_show_btn').on('click', function (e) {
        e.preventDefault();
        $initialWrapper.hide();
        $magicFormWrapper.show();
        $loginForm.addClass('showing_magic_form');
    });

    $('.fls_magic_show_regular').on('click', function (e) {
        e.preventDefault();
        $initialWrapper.show();
        $magicFormWrapper.hide();
        $loginForm.removeClass('showing_magic_form');
    });

    $('#loginform').on('submit', function (e) {
        if($(this).hasClass('showing_magic_form')) {
            e.preventDefault();
            return false;
        }
    });

    $("#fls_magic_logon").keyup(function (e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            return false;
        }
    });

    function setSuccess(data) {
        let html = '<div class="login_magic_success">';
        html += '<div class="login_success_icon"><img src="'+window.fls_magic_login_vars.success_icon+'" /></div>'
        html += '<div class="login_success_heading"><h3>'+data.heading+'</h3></div>';
        html += '<div class="login_success_message"><p>'+data.message+'</p></div>';
        html += '</div>';
        $magicFormWrapper.html(html);
    }

    function showAjaxLoading() {
        var $submitbtn = $('#fls_magic_submit');
        let prevText = $submitbtn.text();
        $submitbtn.data('prev_text', prevText).addClass('fls_loading');
        $submitbtn.html(window.fls_magic_login_vars.wait_text).attr('disabled', true);
    }

    function removeAjaxLoading() {
        var $submitbtn = $('#fls_magic_submit');
        var prevText = $submitbtn.data('prev_text');
        $submitbtn.html(prevText).attr('disabled', false);
    }


    $('#fls_magic_submit').on('click', function (e) {
        e.preventDefault();
        var loginValue = $('#fls_magic_logon').val();
        if(!loginValue) {
            alert(window.fls_magic_login_vars.empty_text);
            return;
        }
        showAjaxLoading();

        let redirectTo = jQuery('#loginform').find('input[name=redirect_to]').val();
        if(!redirectTo) {
            redirectTo = jQuery('#fls_magic_login').find('input[name=redirect_to]').val();
        }

        $.post(window.fls_magic_login_vars.ajaxurl, {
            action: 'fls_magic_send_magic_email',
            email: loginValue,
            redirect_to: redirectTo,
            _nonce: $('#fls_magic_logon_nonce').val()
        })
            .then(function (response) {
                setSuccess(response.data);
            })
            .fail(function (error) {
                alert(error.responseJSON.data.message);
            })
            .always(function () {
                removeAjaxLoading();
            });
    });
});
