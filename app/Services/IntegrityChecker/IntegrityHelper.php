<?php

namespace FluentAuth\App\Services\IntegrityChecker;


use FluentAuth\App\Helpers\Arr;

class IntegrityHelper
{

    //protected static $apiUrl = 'https://api.fluentauth.com/';
    protected static $apiUrl = 'http://localhost:8787/';

    public static function getSettings()
    {
        $defaults = [
            'status'             => 'unregistered',
            'api_id'             => '',
            'api_key'            => '',
            'last_checked'       => '',
            'account_email_id'   => '',
            'is_ok'              => 'yes',
            'affected_resources' => [],
            'auto_scan'          => 'no',
            'scan_interval'      => 'daily',
        ];

        $settings = get_option('__fls_integrity_settings', []);

        if (empty($settings)) {
            return $defaults;
        }

        $settings = wp_parse_args($settings, $defaults);

        return $settings;
    }

    public static function saveSettings($settings)
    {
        return update_option('__fls_integrity_settings', $settings);
    }
}
