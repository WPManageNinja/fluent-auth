<?php

namespace FluentAuth\App\Services\IntegrityChecker;

use FluentAuth\App\Helpers\Arr;

class Api
{
    protected static $apiUrl = 'https://wp-version-hashes.techjewel.workers.dev/';

    public static function registerSite($infoData)
    {
        $payload = [
            'user_display_name' => Arr::get($infoData, 'full_name'),
            'user_email'        => Arr::get($infoData, 'email'),
            'site_url'          => str_replace(['https://', 'http://'], '', site_url()),
            'admin_url' => admin_url('admin.php?page=fluent-auth#/'),
            'site_title' => get_bloginfo('name'),
        ];

        $request = wp_remote_post(self::$apiUrl . 'register/', [
            'body'      => json_encode($payload),
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'timeout'   => 30,
            'sslverify' => false,
        ]);
        if (is_wp_error($request)) {
            return $request;
        }

        $response = json_decode(wp_remote_retrieve_body($request), true);

        if (!$response) {
            return new \WP_Error('invalid_response', __('Invalid response from the server. Please try again', 'fluent-security'), ['status' => 500]);
        }

        if (Arr::get($response, 'status') !== 'success') {
            return new \WP_Error('invalid_response', Arr::get($response, 'message', 'Something went wrong, please try again.'), ['status' => 422]);
        }

        $apiId = Arr::get($response, 'data.api_id', '');

        if (!$apiId) {
            return new \WP_Error('invalid_response', __('API ID could not be generated. Please try again', 'fluent-security'), ['status' => 500]);
        }

        return $apiId;
    }

    public static function confirmSite($infoData)
    {
        $url = self::$apiUrl . 'confirm/?api_id=' . $infoData['api_id'] . '&api_key=' . $infoData['api_key'];

        $request = wp_remote_get($url, [
            'body'      => [],
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'timeout'   => 30,
            'sslverify' => false,
        ]);

        if (is_wp_error($request)) {
            return $request;
        }

        $response = json_decode(wp_remote_retrieve_body($request), true);

        if (!$response) {
            return new \WP_Error('invalid_response', __('Invalid response from the server. Please try again', 'fluent-security'), ['status' => 500]);
        }

        if (Arr::get($response, 'status') !== 'success') {
            return new \WP_Error('invalid_response', Arr::get($response, 'message', 'Something went wrong, please try again.'), ['status' => 422]);
        }

        $apiId = Arr::get($response, 'data.api_id', '');

        if (!$apiId) {
            return new \WP_Error('invalid_response', __('API Key could not be verified. Please try again', 'fluent-security'), ['status' => 500]);
        }

        return $apiId;
    }

    public static function getFileContentFromGithub($filePath, $wpVersion = null)
    {
        if (!$wpVersion) {
            global $wp_version;
            $wpVersion = $wp_version;
        }

        $wpRepo = 'https://raw.githubusercontent.com/WordPress/WordPress/' . $wpVersion . '/';

        $fileUrl = $wpRepo . $filePath;

        $response = wp_remote_get($fileUrl, [
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            return new \WP_Error('invalid_response', __('Invalid response from the server. Please try again', 'fluent-security'), ['status' => 422]);
        }

        $responseCode = wp_remote_retrieve_response_code($response);

        if ($responseCode !== 200) {
            return new \WP_Error('invalid_response', __('Invalid response from the server. Please try again', 'fluent-security'), [
                'status' => $responseCode,
                'data'   => json_decode($body, true)
            ]);
        }

        return $body;
    }

    public static function disableApi()
    {
        $settings = IntegrityHelper::getSettings();

        $url = self::$apiUrl . 'disable/?api_id=' . $settings['api_id'] . '&api_key=' . $settings['api_key'];

        $request = wp_remote_get($url, [
            'body'      => [],
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'timeout'   => 30,
            'sslverify' => false,
        ]);

        if (is_wp_error($request)) {
            return $request;
        }

        $response = json_decode(wp_remote_retrieve_body($request), true);

        if (!$response) {
            return new \WP_Error('invalid_response', __('Invalid response from the server. Please try again', 'fluent-security'), ['status' => 500]);
        }

        if (Arr::get($response, 'status') !== 'success') {
            return new \WP_Error('invalid_response', Arr::get($response, 'message', 'Something went wrong, please try again.'), ['status' => 422]);
        }

        return $response;
    }

    public static function sendPostRequest($route, $payload = [])
    {
        // Send the remote request now
        $response = wp_remote_post(self::$apiUrl . $route, [
            'body'      => json_encode($payload),
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'timeout'   => 30,
            'sslverify' => false,
        ]);

        return $response;
    }
}
