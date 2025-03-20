<?php

namespace FluentAuth\App\Services\IntegrityChecker;

class Api
{
    private static $apiUrl = 'https://api.fluentauth.com/info/';

    public static function getRemoteHashes($extended = false, $wpVersion = null)
    {
        if (!$wpVersion) {
            global $wp_version;
            $wpVersion = $wp_version;
        }

        $apiUrl = self::$apiUrl . '?version=' . $wpVersion;

        if ($extended) {
            $apiUrl .= '&action=get_all_hashes';
        }

        $response = wp_remote_get($apiUrl, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            return [];
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        if ($responseCode !== 200) {
            return [];
        }

        return json_decode($body, true);
    }
}
