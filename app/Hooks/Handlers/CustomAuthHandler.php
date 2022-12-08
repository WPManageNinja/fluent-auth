<?php

namespace FluentSecurity\App\Hooks\Handlers;

use FluentSecurity\App\Helpers\Arr;
use FluentSecurity\App\Helpers\Helper;
use FluentSecurity\App\Services\AuthService;

class CustomAuthHandler
{

    protected $loaded = false;

    public function register()
    {
        add_shortcode('fluent_security_login', array($this, 'loginForm'));
        add_shortcode('fluent_security_signup', array($this, 'registrationForm'));
        add_shortcode('fluent_security_auth', array($this, 'authForm'));
        add_shortcode('fluent_security_reset_password', array($this, 'restPasswordForm'));

        /*
         * Alter Login And Logout Redirect URLs
         */
        add_filter('login_redirect', array($this, 'alterLoginRedirectUrl'), 999, 3);
        add_filter('logout_redirect', array($this, 'alterLogoutRedirectUrl'), 999, 3);

        add_action('wp_ajax_nopriv_fluent_security_login', array($this, 'handleLoginAjax'));
        add_action('wp_ajax_nopriv_fluent_security_signup', array($this, 'handleSignupAjax'));
        add_action('wp_ajax_nopriv_fluent_security_rp', array($this, 'handlePasswordResentAjax'));
        add_action('fls_load_login_helper', array($this, 'loadAssets'));
    }

    public function alterLoginRedirectUrl($redirect_to, $intentRedirectTo, $user)
    {
        if (is_wp_error($user)) {
            return $redirect_to;
        }

        if (apply_filters('fluent_security/respect_front_login_url', true) && strpos($redirect_to, '/wp-admin') === false) {
            return $redirect_to; // it's a frontend URl so let's not alter that
        }

        if ($url = $this->getDefaultLoginRedirectUrl($user)) {
            return $url;
        }

        return $redirect_to;
    }

    public function alterLogoutRedirectUrl($redirect_to, $intentRedirectTo, $user)
    {
        if (is_wp_error($user)) {
            return $redirect_to;
        }

        if ($url = $this->getDefaultLogoutRedirectUrl($user)) {
            return $url;
        }

        return $redirect_to;
    }

    /**
     * loginForm will generate html for login form
     * @param $attributes
     * @return string
     */
    public function loginForm($attributes)
    {

        if (!$this->isEnabled()) {
            return '';
        }

        if (get_current_user_id()) {
            return '<p>' . sprintf(__('You are already logged in. <a href="%s">Go to Home Page</a>', 'fluent-security'), site_url()) . '</p>';
        }

        $this->loadAssets();
        $attributes = $this->getShortcodes($attributes);
        $this->handleAlreadyLoggedIn($attributes);

        $return = '<div id="fls_login_form" class="fls_login_wrapper">';

        if (!empty($attributes['redirect_to'])) {
            $redirect = $attributes['redirect_to'];
        } else {
            $redirect = admin_url();
        }

        /*
         * Filter login form
         *
         * @since v1.0.0
         *
         * @param array $loginArgs
         */
        $loginArgs = apply_filters('fluent_security/login_form_args', [
            'echo'           => false,
            'redirect'       => $redirect,
            'remember'       => true,
            'value_remember' => true
        ]);

        $return .= wp_login_form($loginArgs);

        if ($attributes['show-signup'] == 'true' && get_option('users_can_register')) {
            $return .= '<p style="text-align: center">'
                . __('Not registered?', 'fluent-security')
                . ' <a href="#" id="fls_show_signup">'
                . __('Create an Account', 'fluent-security')
                . '</a></p>';
        }

        if ($attributes['show-reset-password'] == 'true') {
            $return .= '<p style="text-align: center">'
                . __('Forgot your password?', 'fluent-security')
                . ' <a href="#" id="fls_show_reset_password">'
                . __('Reset Password', 'fluent-security')
                . '</a></p>';
        }

        $return .= '</div>';
        return $return;
    }

    /**
     * registrationForm method will generate html for sign up form
     * @param $attributes
     * @return string
     */
    public function registrationForm($attributes)
    {
        if (!$this->isEnabled()) {
            return '';
        }

        if (!get_option('users_can_register')) {
            return '<p>' . sprintf(__('User registration is not enabled. <a href="%s">Go to Home Page</a>', 'fluent-security'), esc_url(site_url())) . '</p>';
        }

        if (get_current_user_id()) {
            return '<p>' . sprintf(__('You are already logged in. <a href="%s">Go to Home Page</a>', 'fluent-security'), esc_url(site_url())) . '</p>';
        }

        $attributes = $this->getShortcodes($attributes);
        $this->handleAlreadyLoggedIn($attributes);

        $registrationFields = $this->getSignupFields();
        $hide = $attributes['hide'] == 'true' ? 'hide' : '';

        $this->loadAssets($hide);

        return $this->buildRegistrationForm($registrationFields, $hide, $attributes);
    }

    // This method `buildRegistrationForm` will generate html for sign up form
    private function buildRegistrationForm($registrationFields, $hide, $attributes)
    {
        $registrationForm = '<div class="fls_registration_wrapper ' . esc_attr($hide) . '"><form id="flsRegistrationForm" class="fls_registration_form" method="post" name="fls_registration_form">';

        foreach ($registrationFields as $fieldName => $registrationField) {
            $registrationForm .= $this->renderField($fieldName, $registrationField);
        }

        $registrationForm .= '<input type="hidden" name="__redirect_to" value="' . esc_url($attributes['redirect_to']) . '">';
        $registrationForm .= '<input type="hidden" name="_fls_signup_nonce" value="' . wp_create_nonce('fluent_security_signup_nonce') . '">';
        $registrationForm .= '<button type="submit" id="fls_submit">' . $this->submitBtnLoadingSvg() . '<span>' . __('Signup', 'fluent-security') . '</span></button>';

        $registrationForm .= '</form>';

        $registrationForm .= apply_filters('fluent_security/after_registration_form_close', '', $registrationFields, $attributes);

        if ($hide) {
            $registrationForm .= '<p style="text-align: center">'
                . __('Already have an account?', 'fluent-security')
                . ' <a href="#" id="fls_show_login">'
                . __('Login', 'fluent-security')
                . '</a></p>';
        }

        $registrationForm .= '</div>';

        return $registrationForm;
    }

    public function restPasswordForm($attributes)
    {
        if (!$this->isEnabled()) {
            return '';
        }

        if (get_current_user_id()) {
            return '<p>' . sprintf(__('You are already logged in. <a href="%s">Go to Home Page</a>', 'fluent-security'), esc_url(site_url())) . '</p>';
        }

        $attributes = $this->getShortcodes($attributes);
        $this->handleAlreadyLoggedIn($attributes);

        $resetPasswordFields = static::resetPasswordFields();
        $hide = $attributes['hide'] == 'true' ? 'hide' : '';

        $this->loadAssets($hide);

        return $this->buildResetPassForm($resetPasswordFields, $hide, $attributes);
    }

    // This method `buildResetPassForm` will generate html for password reset form
    private function buildResetPassForm($resetPasswordFields, $hide, $attributes)
    {
        $restePasswordForm = '<div class="fls_reset_pass_wrapper ' . $hide . '"><form id="flsResetPasswordForm" class="fls_reset_pass_form" method="post" name="fls_reset_pass_form">';

        foreach ($resetPasswordFields as $fieldName => $resetPasswordField) {
            $restePasswordForm .= $this->renderField($fieldName, $resetPasswordField);
        }

        $restePasswordForm .= '<input type="hidden" name="__redirect_to" value="' . $attributes['redirect_to'] . '">';
        $restePasswordForm .= '<input type="hidden" name="_fls_reset_pass_nonce" value="' . wp_create_nonce('fluent_security_reset_pass_nonce') . '">';
        $restePasswordForm .= '<button type="submit" id="fls_reset_pass">' . $this->submitBtnLoadingSvg() . '<span>' . __('Reset Password', 'fluent-security') . '</span></button>';

        $restePasswordForm .= '</form>';

        $restePasswordForm .= '</div>';

        return $restePasswordForm;
    }

    /**
     * authForm will render the login form html
     * @param $attributes
     * @return string
     */
    public function authForm($attributes)
    {
        if (get_current_user_id()) {
            return '<p>' . sprintf(__('You are already logged in. <a href="%s">Go to Home Page</a>', 'fluent-security'), site_url()) . '</p>';
        }

        $atts = $this->getShortcodes($attributes);

        $authForm = '<div class="fls_auth_wrapper">';

        $authForm .= do_shortcode('[fluent_security_login redirect_to="' . esc_url($atts['redirect_to']) . '" show-signup=true show-reset-password=true]');

        if (get_option('users_can_register')) {
            $authForm .= do_shortcode('[fluent_security_signup redirect_to="' . esc_url($atts['redirect_to']) . '" hide=true]');
        }

        $authForm .= do_shortcode('[fluent_security_reset_password redirect_to="' . esc_url($atts['redirect_to']) . '" hide=true]');

        $authForm .= '</div>';

        return $authForm;
    }

    /**
     * renderField method will generate html for a field
     * @param $fieldName
     * @param $field
     * @return string
     */
    private function renderField($fieldName, $field)
    {
        $fieldType = Arr::get($field, 'type');
        $isRequired = Arr::get($field, 'required');
        $isRequired = $isRequired ? 'is-required' : '';

        $textTypes = ['text', 'email', 'password'];

        $html = '<div class="fls_field_group fls_field_' . $fieldName . '">';
        if ($label = Arr::get($field, 'label')) {
            $html .= '<div class="fls_field_label ' . $isRequired . '"><label for="' . Arr::get($field, 'id') . '">' . $label . '</label></div>';
        }

        if (in_array($fieldType, $textTypes)) {

            $inputAtts = array_filter([
                'type'        => esc_attr($fieldType),
                'id'          => esc_attr(Arr::get($field, 'id')),
                'placeholder' => esc_attr(Arr::get($field, 'placeholder')),
                'name'        => esc_attr($fieldName)
            ]);

            $atts = '';

            foreach ($inputAtts as $attKey => $att) {
                $atts .= $attKey . '="' . $att . '" ';
            }

            if (Arr::get($field, 'required')) {
                $atts .= 'required';
            }

            $html .= '<div class="fs_input_wrap"><input ' . $atts . '/></div>';
        } else {
            return '';
        }

        return $html . '</div>';
    }

    /**
     * getSignupFields method will return the list of fields that will be used for sign up form
     * @return mixed
     */
    public function getSignupFields()
    {
        /*
         * Filter signup form field
         *
         * @since v1.0.0
         *
         * @param array $fields Form fields
         */
        return apply_filters('fluent_support/registration_form_fields', [
            'first_name' => [
                'required'    => true,
                'type'        => 'text',
                'label'       => __('First name', 'fluent-support'),
                'id'          => 'fls_first_name',
                'placeholder' => __('First name', 'fluent-support')
            ],
            'last_name'  => [
                'type'        => 'text',
                'label'       => __('Last Name', 'fluent-support'),
                'id'          => 'fls_last_name',
                'placeholder' => __('Last name', 'fluent-support')
            ],
            'username'   => [
                'required'    => true,
                'type'        => 'text',
                'label'       => __('Username', 'fluent-support'),
                'id'          => 'fls_reg_username',
                'placeholder' => __('Username', 'fluent-support')
            ],
            'email'      => [
                'required'    => true,
                'type'        => 'email',
                'label'       => __('Email Address', 'fluent-support'),
                'id'          => 'fls_reg_email',
                'placeholder' => __('Your Email Address', 'fluent-support')
            ],
            'password'   => [
                'required'    => true,
                'type'        => 'password',
                'label'       => __('Password', 'fluent-support'),
                'id'          => 'fls_reg_password',
                'placeholder' => __('Password', 'fluent-support')
            ]
        ]);
    }

    public static function resetPasswordFields()
    {
        /*
         * Filter reset password form field
         *
         * @since v1.5.7
         *
         * @param array $fields Form fields
         */
        return apply_filters('fluent_support/reset_password_form', [
            'user_login' => [
                'required'    => true,
                'type'        => 'text',
                'label'       => __('Email Address', 'fluent-support'),
                'id'          => 'fls_email',
                'placeholder' => __('Your Email Address', 'fluent-support')
            ]
        ]);
    }

    protected function submitBtnLoadingSvg()
    {
        $loadingIcon = '<svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
           width="40px" height="20px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve">
        <path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z">
          <animateTransform attributeType="xml"
            attributeName="transform"
            type="rotate"
            from="0 25 25"
            to="360 25 25"
            dur="0.6s"
            repeatCount="indefinite"/>
          </path>
        </svg>';

        /*
         * Filter signup form loading icon
         *
         * @since v1.0.0
         *
         * @param string $loadingIcon this accepts html element
         */
        return apply_filters('fluent_security/signup_loading_icon', $loadingIcon);
    }

    protected function getShortcodes($attributes)
    {
        /*
         * Filter shortcode behavior for agent
         *
         * @since v1.0.0
         *
         * @param array $shortCodeDefaults
         */
        $shortCodeDefaults = apply_filters('fluent_security/auth_shortcode_defaults', [
            'auto-redirect'       => false,
            'redirect_to'         => '',
            'hide'                => false,
            'show-signup'         => false,
            'show-reset-password' => false,
        ]);

        if ($shortCodeDefaults['redirect_to'] == 'self') {
            $shortCodeDefaults['redirect_to'] = get_the_permalink();
        }

        return shortcode_atts($shortCodeDefaults, $attributes);
    }

    protected function handleAlreadyLoggedIn($attributes)
    {
        if (get_current_user_id() && !wp_is_json_request() && is_singular()) {
            if ($attributes['auto-redirect'] === 'true') {
                $redirect = $attributes['redirect_to'];

                if (!$redirect) {
                    return;
                }

                ?>
                <script type="text/javascript">
                    document.addEventListener("DOMContentLoaded", function () {
                        var redirect = "<?php echo esc_url($redirect); ?>";
                        window.location.replace(redirect);
                    });
                </script>
                <?php
            }
            die();
        }
    }

    public function loadAssets($hide = '')
    {
        if ($this->loaded) {
            return false;
        }

        wp_enqueue_script('fluent_security_login_helper', FLUENT_SECURITY_PLUGIN_URL . 'dist/public/login_helper.js', [], FLUENT_SECURITY_VERSION);

        wp_localize_script('fluent_security_login_helper', 'fluentSecurityPublic', [
            'hide'              => $hide,
            'redirect_fallback' => site_url(),
            'fls_login_nonce'   => wp_create_nonce('fsecurity_login_nonce'),
            'ajax_url'          => admin_url('admin-ajax.php')
        ]);


        $this->loaded = true;
    }

    public function isEnabled()
    {
        $settings = Helper::getAuthFormsSettings();
        return Arr::get($settings, 'enabled') === 'yes';
    }

    /**
     * @param $user \WP_User
     * @return false|string
     */
    private function getDefaultLoginRedirectUrl($user)
    {
        $settings = Helper::getAuthFormsSettings();

        if (Arr::get($settings, 'login_redirects') != 'yes') {
            return false;
        }

        $defaultLoginRedirect = Arr::get($settings, 'default_login_redirect', false);

        $rules = Arr::get($settings, 'redirect_rules', []);

        if (!$rules) {
            return $defaultLoginRedirect;
        }

        $caps = (array)$user->allcaps;
        $caps = array_filter(array_keys(array_filter($caps)));

        foreach ($rules as $rule) {
            $result = $this->isConditionsMatched($rule['conditions'], $user, $caps);
            if ($result && !empty($rule['login'])) {
                return $rule['login'];
            }
        }

        return $defaultLoginRedirect;
    }

    /**
     * @param $user \WP_User
     * @return false|string
     */
    private function getDefaultLogoutRedirectUrl($user)
    {
        $settings = Helper::getAuthFormsSettings();
        if (Arr::get($settings, 'login_redirects') != 'yes') {
            return false;
        }

        $defaultLogoutRedirect = Arr::get($settings, 'default_logout_redirect');

        $rules = Arr::get($settings, 'redirect_rules', []);

        if (!$rules) {
            return $defaultLogoutRedirect;
        }

        $caps = (array)$user->allcaps;
        $caps = array_filter(array_keys(array_filter($caps)));

        foreach ($rules as $rule) {
            $result = $this->isConditionsMatched($rule['conditions'], $user, $caps);
            if ($result && !empty($rule['logout'])) {
                return $rule['logout'];
            }
        }

        return $defaultLogoutRedirect;
    }

    /**
     * @param $conditions array
     * @param $user \WP_User
     * @param $caps array
     * @return boolean
     */
    private function isConditionsMatched($conditions, $user, $caps = [])
    {
        $isMatched = false;

        foreach ($conditions as $condition) {
            if (!$condition['values']) {
                continue;
            }
            $key = $condition['condition'];
            if ($key == 'user_role') {
                $isMatched = (bool)array_intersect((array)$condition['values'], (array)$user->roles);
            } else if ($key == 'user_capability') {
                $isMatched = (bool)array_intersect((array)$condition['values'], (array)$caps);
            }

            if (!$isMatched) {
                return false;
            }
        }

        return $isMatched;
    }

    /**
     * handleLogin method will perform login functionality and redirect
     */
    public function handleLoginAjax()
    {
        if (!$this->isEnabled()) {
            wp_send_json([
                'message' => __('Login is not enabled', 'fluent-security')
            ], 423);
        }

        $nonce = Arr::get($_REQUEST, '_nonce');

        if (!$nonce) {
            wp_send_json([
                'message' => __('Security nonce is required', 'fluent-security')
            ], 423);
        }

        if (!wp_verify_nonce(Arr::get($_REQUEST, '_nonce'), 'fsecurity_login_nonce')) {
            wp_send_json([
                'message' => __('Security verification failed', 'fluent-security')
            ], 423);
        }

        $data = $_REQUEST;

        if (empty($data['pwd']) || empty($data['log'])) {
            wp_send_json([
                'message' => __('Email and Password is required', 'fluent-security')
            ], 423);
        }

        $redirectUrl = admin_url();
        if (isset($data['redirect_to']) && filter_var($data['redirect_to'], FILTER_VALIDATE_URL)) {
            $redirectUrl = sanitize_url($data['redirect_to']);
        }

        if ($currentUserId = get_current_user_id()) { // user already registered

            $user = get_user_by('ID', $currentUserId);
            $redirectUrl = apply_filters('login_redirect', $redirectUrl, false, $user);

            wp_send_json([
                'redirect' => $redirectUrl
            ], 200);
        }

        $email = sanitize_user($data['log']);

        if (is_email($email)) {
            $user = get_user_by('email', $email);
        } else {
            $user = get_user_by('login', $email);
        }

        if (!$user) {
            $user = new \WP_Error('authentication_failed', __('<strong>Error</strong>: Invalid username, email address or incorrect password.', 'fluent-security'));

            do_action('wp_login_failed', $email, $user);

            wp_send_json([
                'message' => __('Email or Password is not valid. Please try again', 'fluent-security')
            ], 423);

        }

        $user = wp_signon();
        if (is_wp_error($user)) {
            wp_send_json([
                'message' => $user->get_error_message()
            ], 423);
        }

        $redirectUrl = apply_filters('login_redirect', $redirectUrl, false, $user);

        wp_send_json([
            'redirect' => $redirectUrl
        ], 200);
    }

    public function handleSignupAjax()
    {
        if (!$this->isEnabled() || !get_option('users_can_register')) {
            wp_send_json([
                'message' => __('User registration is not enabled', 'fluent-security')
            ], 423);
        }

        if (!wp_verify_nonce(Arr::get($_REQUEST, '_fls_signup_nonce'), 'fluent_security_signup_nonce')) {
            wp_send_json([
                'message' => __('Security verification failed. Please try again', 'fluent-security')
            ], 423);
        }

        /*
         * Filter user signup form data
         *
         * @since v1.0.0
         * @param array $formData
         */
        $formData = apply_filters('fluent_security/signup_form_data', $_REQUEST);

        /*
         * Action before validate user signup
         *
         * @since v1.0.0
         * @param array $formData
         */
        do_action('fluent_security/before_signup_validation', $formData);

        $errors = $this->validateSignUpData($formData);

        if ($errors) {
            wp_send_json([
                'message' => __('Form validation failed. Please provide the correct data', 'fluent-security'),
                'errors'  => $errors
            ], 423);
        }

        /*
         * Action After validate user signup validation success
         *
         * @since v1.0.0
         * @param array $formData
         */
        do_action('fluent_security/after_signup_validation', $formData);

        if (empty($formData['username'])) {
            $formData['username'] = sanitize_user($formData['email']);
        }

        $userId = AuthService::registerNewUser($formData['username'], $formData['email'], $formData['password'], [
            'role'       => apply_filters('fluent_security/signup_default_role', get_option('default_role'), $formData),
            'first_name' => Arr::get($formData, 'first_name'),
            'last_name'  => Arr::get($formData, 'last_name'),
        ]);

        if (is_wp_error($userId)) {
            wp_send_json([
                'message' => $userId->get_error_message()
            ], 423);
        }

        /*
         * Action After creating WP user from ticket sign up form
         *
         * @since v1.0.0
         * @param array $formData
         */
        do_action('fluent_security/after_creating_user', $userId, $formData);

        $user = get_user_by('ID', $userId);

        $isAutoLogin = apply_filters('fluent_security/auto_login_after_signup', true, $user);

        $message = __('Registration has been completed. Please login now', 'fluent-security');

        $redirectUrl = false;
        if ($isAutoLogin) {
            $this->login($userId);
            $redirectUrl = Arr::get($formData, 'redirect_to', admin_url());
            $redirectUrl = apply_filters('login_redirect', $redirectUrl, false, $user);
            $message = __('Successfully registered to the site.', 'fluent-security');
        }

        $response = [
            'message' => $message
        ];

        if ($redirectUrl) {
            $response['redirect'] = $redirectUrl;
        }

        /*
         * Filter for user signup complete message and redirect
         *
         * @since v1.0.0
         * @param array $response
         */
        $response = apply_filters('fluent_security/signup_complete_response', $response, $user);

        wp_send_json($response, 200);
    }

    public function handlePasswordResentAjax()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $data = $_REQUEST;

        $errors = new \WP_Error();

        if (!wp_verify_nonce(Arr::get($_REQUEST, '_fls_reset_pass_nonce'), 'fluent_security_reset_pass_nonce')) {

            wp_send_json([
                'message' => __('Security verification failed. Please try again', 'fluent-security')
            ], 423);

        }

        $usernameOrEmail = trim(wp_unslash(Arr::get($data, 'user_login')));

        if (!$usernameOrEmail) {
            wp_send_json([
                'message' => __('Username or email is required', 'fluent-security')
            ], 423);
        }

        $user_data = get_user_by('email', $usernameOrEmail);

        if (!$user_data) {
            $user_data = get_user_by('login', $usernameOrEmail);
        }

        if (!$user_data) {
            wp_send_json([
                'message' => __('Invalid username or email', 'fluent-security')
            ], 423);
        }

        $user_data = apply_filters('lostpassword_user_data', $user_data, $errors);

        do_action('lostpassword_post', $errors, $user_data);

        $errors = apply_filters('lostpassword_errors', $errors, $user_data);

        if ($errors->has_errors()) {
            wp_send_json([
                'message' => $errors->get_error_message()
            ], 423);
        }

        if (!$user_data) {
            wp_send_json([
                'message' => __('<strong>Error</strong>: There is no account with that username or email address.', 'fluent-security')
            ], 423);
        }

        if (is_multisite() && !is_user_member_of_blog($user_data->ID, get_current_blog_id())) {
            wp_send_json([
                'message' => __('<strong>Error</strong>: Invalid username or email', 'fluent-security')
            ], 423);
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;

        do_action('retrieve_password', $user_login);

        $allow = apply_filters('allow_password_reset', true, $user_data->ID);

        if (!$allow) {
            wp_send_json([
                'message' => __('Password reset is not allowed for this user', 'fluent-security')
            ], 423);
        }

        if (is_wp_error($allow)) {
            wp_send_json([
                'message' => $allow->get_error_message()
            ], 423);
        }


        /*
         * Filter reset password link text
         *
         * @since v1.5.7
         * @param string $linkText
         */
        $linkText = apply_filters("fluent_security/reset_password_link", sprintf(__('Reset your password for %s', 'fluent-security'), get_bloginfo('name')));

        $resetUrl = add_query_arg([
            'action' => 'rp',
            'key'    => get_password_reset_key($user_data),
            'login'  => rawurlencode($user_data->user_login)
        ], wp_login_url());

        $resetLink = '<a href="' . esc_url($resetUrl) . '">' . esc_html($linkText) . '</a>';

        /*
         * Filter reset password email subject
         *
         * @since v1.5.7
         * @param string $mailSubject
         */
        $mailSubject = apply_filters("fluent_security/reset_password_mail_subject", sprintf(__('Reset your password for %s', 'fluent-security'), get_bloginfo('name')));

        $message = sprintf(__('<p>Hi %s,</p>', 'fluent-security'), $user_data->first_name) .
            __('<p>Someone has requested a new password for the following account on WordPress:</p>', 'fluent-security') .
            sprintf(__('<p>Username: %s</p>', 'fluent-support'), $user_login) .
            sprintf(__('<p>%s</p>', 'fluent-security'), $resetLink) .
            sprintf(__('<p>If you did not request to reset your password, please ignore this email.</p>', 'fluent-security'));

        /*
         * Filter reset password email body text
         *
         * @since v1.5.7
         * @param string $message
         * @param object $user
         * @param string $resetLink
         */
        $message = apply_filters('fluent_security/reset_password_message', $message, $user_data, $resetLink);

        $data = [
            'body'        => $message,
            'pre_header'  => 'reset your password',
            'show_footer' => false
        ];

        $message = Helper::loadView('notification', $data);

        $headers = array('Content-Type: text/html; charset=UTF-8');

        \wp_mail($user_data->user_email, $mailSubject, $message, $headers);

        wp_send_json([
            'message' => __('Please check your email for the reset link', 'fluent-security')
        ]);
    }

    public function validateSignUpData($data)
    {
        $fields = $this->getSignupFields();

        $errors = [];

        foreach ($fields as $fieldName => $field) {
            if (!empty($field['required']) && empty($data[$fieldName])) {
                $errors[$fieldName] = sprintf(__('%s is required', 'fluent-security'), esc_html($field['label']));
            }

            if ($field['type'] === 'email') {
                if (!is_email(Arr::get($data, $fieldName))) {
                    $errors[$fieldName] = sprintf(__('Provided %s is not a valid email', 'fluent-security'), esc_html(strtolower($field['label'])));
                }
            }
        }

        return $errors;
    }

    protected function login($userId)
    {
        /*
         * Action before login
         *
         * @since v1.0.0
         * @param integer $userId
         */
        do_action('fluent_security/before_logging_in_user', $userId);

        wp_clear_auth_cookie();
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);

        /*
         * Action after login
         *
         * @since v1.0.0
         * @param integer $userId
         */
        do_action('fluent_security/after_logging_in_user', $userId);
    }
}
