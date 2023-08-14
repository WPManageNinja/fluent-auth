<?php

namespace FluentAuth\App\Http\Controllers;

use FluentAuth\App\Services\ReCaptcha\Recaptcha;

class ReCaptchaController
{
    public static function getSettings()
    {
        return [
            'data' => Recaptcha::settings()->get()
        ];
    }

    public static function updateSettings(\WP_REST_Request $request)
    {
        // :Todo validate request before save

        $data = [
            'site_key'                  => sanitize_text_field($request['site_key']),
            'secret_key'                => sanitize_text_field($request['secret_key']),
            'enable_recaptcha'          => sanitize_text_field($request['enable_recaptcha']),
            'enable_on_shortcode_login' => sanitize_text_field($request['enable_on_shortcode_login']),
        ];

        $response = Recaptcha::settings()->update($data);

        return [
            'message' => __('Settings has been updated', 'fluent-security'),
            'data'    => $response
        ];
    }
}
