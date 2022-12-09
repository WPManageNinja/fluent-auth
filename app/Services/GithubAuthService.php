<?php

namespace FluentAuth\App\Services;

use FluentAuth\App\Helpers\Arr;
use FluentAuth\App\Helpers\Helper;

class GithubAuthService
{
    public static function getAuthRedirect($state = '')
    {
        $config = Helper::getSocialAuthSettings('edit');

        $params = [
            'client_id'    => $config['github_client_id'],
            'redirect_uri' => self::getAppRedirect(),
            'scope'        => 'user',
            'state'        => $state
        ];

        return add_query_arg($params, 'https://github.com/login/oauth/authorize');
    }

    public static function getTokenByCode($code)
    {
        $postUrl = 'https://github.com/login/oauth/access_token';
        $params = self::getAuthConfirmParams($code);

        $response = wp_remote_post($postUrl, [
            'body'    => $params,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return Arr::get($data, 'access_token');
    }

    public static function getAuthConfirmParams($code = '')
    {
        $config = Helper::getSocialAuthSettings('edit');

        return [
            'client_id'     => $config['github_client_id'],
            'redirect_uri'  => self::getAppRedirect(),
            'code'          => $code,
            'client_secret' => $config['github_client_secret']
        ];
    }

    public static function getDataByAccessToken($token)
    {
        $response = wp_remote_get('https://api.github.com/user', [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        if(is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if(!$data || empty($data['login'])) {
            return new \WP_Error('api_error', __('API Error when authenticate via github', 'fluent-security'));
        }

        return [
            'full_name' => Arr::get($data, 'name'),
            'user_url' => Arr::get($data, 'blog'),
            'email' => Arr::get($data, 'email'),
            'username' => Arr::get($data, 'login'),
            'description' => Arr::get($data, 'bio')
        ];
    }

    public static function getAppRedirect()
    {
        return add_query_arg([
            'fs_auth' => 'github',
            'fs_type' => 'confirm'
        ], wp_login_url());
    }
}
