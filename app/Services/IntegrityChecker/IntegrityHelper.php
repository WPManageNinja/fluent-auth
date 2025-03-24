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

    public static function getIgnoreLists()
    {
        $ignoreLists = get_option('__fls_integrity_ignore_lists', []);

        $defaults = [
            'files'   => [],
            'folders' => []
        ];

        if (empty($ignoreLists)) {
            return $defaults;
        }

        return wp_parse_args($ignoreLists, $defaults);
    }

    public static function updateIgnoreLists($ignoreLists)
    {
        return update_option('__fls_integrity_ignore_lists', $ignoreLists);
    }

    public static function getActiveModifiedFilesFolders($scanResults)
    {
        $ignores = self::getIgnoreLists();

        $allFiles = [];

        if (!empty($scanResults['root'])) {
            foreach ($scanResults['root'] as $file => $status) {
                $allFiles['/' . $file] = $status;
            }
        }

        if (!empty($scanResults['wp_admin'])) {
            foreach ($scanResults['wp_admin'] as $file => $status) {
                $allFiles['/wp-admin/' . $file] = $status;
            }
        }

        if (!empty($scanResults['wp_includes'])) {
            foreach ($scanResults['wp_includes'] as $file => $status) {
                $allFiles['/wp-includes/' . $file] = $status;
            }
        }

        if ($ignores['files']) {
            $allFiles = array_filter($allFiles, function ($file) use ($ignores) {
                return in_array($file, $ignores['files']);
            });
        }

        $folders = [];
        if (!empty($scanResults['extra_root_folders'])) {
            foreach ($scanResults['extra_root_folders'] as $folder) {
                $folders['/' . $folder] = 'new';
            }
        }

        if ($ignores['folders']) {
            $folders = array_filter($folders, function ($folder) use ($ignores) {
                return in_array($folder, $ignores['folders']);
            });
        }

        return [
            'files'   => $allFiles ? $allFiles : null,
            'folders' => $folders ? $folders : null,
            'ignores' => $ignores
        ];
    }
}
