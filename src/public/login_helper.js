require('./login_helper.scss');
document.addEventListener('DOMContentLoaded', () => {
    const registrationForm = document.getElementById('flsRegistrationForm');
    const resetPasswordForm = document.getElementById('flsResetPasswordForm');
    const loginForm = document.getElementById('loginform');
    const twoFaForm = document.getElementById('fls_2fa_form');

    function toggleLoading(submitBtn) {
        submitBtn.classList.toggle('fls_loading');
        submitBtn.disabled = !submitBtn.disabled;
    }

    if (registrationForm) {
        registrationForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleFormSubmission(registrationForm, 'fls_submit', 'fluent_auth_signup');
        });
    }

    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleFormSubmission(resetPasswordForm, 'fls_reset_pass', 'fluent_auth_rp');
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleFormSubmission(loginForm, 'fls_login_form', 'fluent_auth_login');
        });
    }

    if (twoFaForm) {
        twoFaForm.addEventListener('submit', (event) => {
            event.preventDefault();
            handleFormSubmission(twoFaForm, 'fls_2fa_confirm', 'fluent_auth_2fa_email');
        });
    }

    if (document.getElementById('fls_show_signup')) {
        document.getElementById('fls_show_signup').addEventListener('click', function (event) {
            fsToggleForms(event, this, '.fls_registration_wrapper');
        });
    }

    if (document.getElementById('fls_show_reset_password')) {
        document.getElementById('fls_show_reset_password').addEventListener('click', function (event) {
            fsToggleForms(event, this, '.fls_reset_pass_wrapper');
        });
    }

    if (document.getElementById('fls_show_login')) {
        document.getElementById('fls_show_login').addEventListener('click', function (event) {
            fsToggleForms(event, this, '.fls_login_wrapper');
        });
    }

    function handleFormSubmission(form, submitBtnId, action) {
        const submitBtn = document.getElementById(submitBtnId);
        toggleLoading(submitBtn);

        document.querySelectorAll('.error.text-danger').forEach(e => {
            e.parentNode.parentNode.classList.remove('is-error');
            e.remove();
        })

        const data = new FormData(form);

        data.append('action', action);
        data.append('_nonce', window.fluentAuthPublic.fls_login_nonce);
        data.append('_is_fls_form', 'yes');

        const request = new XMLHttpRequest();

        const reqUrl = window.fluentAuthPublic.ajax_url;

        request.open('POST', reqUrl, true);
        request.responseType = 'json';

        request.onload = function () {
            if (this.status === 200) {
                if (this.response.redirect) {
                    window.location.href = this.response.redirect;
                } else if (this.response.message) {
                    let el = document.createElement("div");
                    el.classList.add('success', 'text-success', 'fls-text-success');
                    el.innerHTML = this.response.message;
                    form.appendChild(el);
                    form.reset();
                } else {
                    window.location.reload();
                }
            } else {

                let genericError = this.response.error;

                if (!genericError && this.response.message) {
                    genericError = this.response.message;
                } else if (genericError && this.response.data.status === 403) {
                    genericError = this.response.message;
                }

                if (genericError) {
                    let el = document.createElement("div");
                    el.classList.add('error', 'text-danger');
                    el.innerHTML = genericError;

                    form.appendChild(el);
                } else {
                    for (const property in this.response) {
                        const field = document.getElementById('flt_' + property);
                        if (field) {
                            let el = document.createElement("div");
                            el.classList.add('error', 'text-danger');
                            el.innerHTML = Object.values(this.response[property])[0];
                            field.parentNode.insertBefore(el, field.nextSibling);
                            field.parentNode.parentNode.classList.add('is-error');
                        }
                    }
                }
            }

            toggleLoading(submitBtn);
        };

        request.send(data);
    }

    function fsToggleForms(event, that, target) {
        event.preventDefault();
        that.parentNode.parentNode.classList.toggle('hide');
        document.querySelector(target).classList.toggle('hide');
    }
});
