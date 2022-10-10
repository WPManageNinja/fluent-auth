<?php

namespace FluentSecurity\Services;

use FluentSecurity\Helpers\Arr;
use FluentSecurity\Helpers\Helper;

class GoogleAuthService
{
    public static function getAuthRedirect($state = '')
    {
        $config = Helper::getSocialAuthSettings('edit');

        $params = [
            'response_type' => 'code',
            'client_id'     => $config['google_client_id'],
            'redirect_uri'  => self::getAppRedirect(),
            'scope'         => 'openid%20email%20profile',
            'state'         => $state,
            'nonce'         => wp_generate_uuid4()
        ];

        return add_query_arg($params, 'https://accounts.google.com/o/oauth2/v2/auth');
    }

    public static function getTokenByCode($code)
    {
        $postUrl = 'https://oauth2.googleapis.com/token';
        $params = self::getAuthConfirmParams($code);

        $response = wp_remote_post($postUrl, [
            'body'    => $params,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return Arr::get($data, 'id_token');
    }

    public static function getAuthConfirmParams($code = '')
    {
        $config = Helper::getSocialAuthSettings('edit');

        return [
            'client_id'     => $config['google_client_id'],
            'redirect_uri'  => self::getAppRedirect(),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'client_secret' => $config['google_client_secret']
        ];
    }

    public static function getDataByIdToken($token)
    {
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload, true);

        $username = Arr::get($jwtPayload, 'email');
        $emailArray = explode('@', $username);
        if (count($emailArray)) {
            $username = $emailArray[0];
        }

        return [
            'full_name' => Arr::get($jwtPayload, 'name'),
            'email'     => Arr::get($jwtPayload, 'email'),
            'username'  => $username
        ];
    }

    private static function getAppRedirect()
    {
        return 'https://fluentcrm.com/wp-login.php';
        return wp_login_url();
    }
}
