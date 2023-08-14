<?php

namespace FluentAuth\App\Services\ReCaptcha;

class RecaptchaService
{
    public static $settings = null;

    public function __construct()
    {
        if (is_null(self::$settings)) {
            self::$settings = new Settings();
        }
    }

    public function renderMenuPage($permission, $menuSlug, $cb)
    {
        $menuPage = new MenuPage($permission, $menuSlug, $cb);
        $menuPage->registerSubmenuPage('ReCaptcha', 'ReCaptcha', 'fluent-auth#/recaptcha');
    }

    public function settings()
    {
        if (is_null(self::$settings)) {
            self::$settings = new Settings();
        }

        return self::$settings;
    }


    public function register()
    {
        add_action('login_form', array($this, 'renderCaptcha'));
        add_action('register_form', array($this, 'renderCaptcha'));
        add_action('signup_extra_fields', array($this, 'renderCaptcha'));
        add_action('lostpassword_form', array($this, 'renderCaptcha'));
        add_action('login_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('wp_ajax_nopriv_fluent_auth_recaptcha_verify', array($this, 'verifyRecaptcha'));

    }

    public function enqueueScripts()
    {
        $settings = self::settings();

        if (!$settings->enabled()) {
            return;
        }

        wp_enqueue_script('fluent-auth-recaptcha', 'https://www.google.com/recaptcha/api.js', [], '1.0', true);
        wp_enqueue_script('fluent-auth-recaptcha-script', FLUENT_AUTH_PLUGIN_URL . '/src/public/recaptcha.js', ['fluent-auth-recaptcha', 'jquery'], '1.0', true);

        wp_localize_script('fluent-auth-recaptcha-script', 'fluent_auth_recaptcha', [
            'fls_ajax_url' => admin_url('admin-ajax.php'),
            'fls_action'   => 'fluent_auth_recaptcha_verify'
        ]);
    }

    public function renderCaptcha()
    {

        $this->renderCaptchaStyle();

        $settings = self::settings();

        if ($settings->enabled()) {
            $siteKey = $settings->site_key;
            return printf('<div class="g-recaptcha" data-sitekey="%s"></div>', $siteKey);
        }

        return $this;
    }

    public function verifyRecaptcha()
    {
        $settings = self::settings();

        $secretKey = $settings->secret_key;
        $token = $_POST['token'];
        $remoteIp = $_SERVER['REMOTE_ADDR'];


        if(!$secretKey) {
            wp_send_json([
                'success' => false,
                'message' => 'Please add the secret key'
            ], 422);

            return;
        }


        if(!$token) {
            wp_send_json([
                'success' => false,
                'message' => 'Please Check the Captcha'
            ], 422);

            return;
        }

        $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$token&remoteip=$remoteIp";

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {

            wp_send_json([
                'success' => false,
                'message' => 'Captcha verification failed'
            ], 500);

            return;
        }

        $response = json_decode(wp_remote_retrieve_body($response), true);

        if (!$response['success']) {

            wp_send_json([
                'success' => false,
                'message' => 'Invalid captcha'
            ], 422);

            return;
        }

        wp_send_json($response);
    }

    public function registerShortcodeCaptcha()
    {
        $settings = self::settings();

        if(!$settings->enabled()) {
            return;
        }

        if($settings->enable_on_shortcode_login !== 'yes') {
            return;
        }

        add_action('wp_loaded', function() use ($settings){

            add_filter('login_form_middle', function($data) use ($settings){
                $data .= '<div class="g-recaptcha" data-sitekey="'. $settings->site_key .'"></div>';
                return $data;
            });

            wp_enqueue_script('fluent-auth-recaptcha', 'https://www.google.com/recaptcha/api.js', [], '1.0', true);
            wp_enqueue_script('fluent-auth-recaptcha-script', FLUENT_AUTH_PLUGIN_URL . '/src/public/recaptcha.js', ['fluent-auth-recaptcha', 'jquery'], '1.0', true);

            wp_localize_script('fluent-auth-recaptcha-script', 'fluent_auth_recaptcha', [
                'fls_ajax_url' => admin_url('admin-ajax.php'),
                'fls_action'   => 'fluent_auth_recaptcha_verify'
            ]);
        });
    }

    public function renderCaptchaStyle()
    {
        echo '<style>
          .g-recaptcha {
            margin-bottom: 15px;
          }
          #login {
            width: 352px;
          }
        </style>';
    }
}
