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
            'status'           => 'unregistered',
            'api_id'           => '',
            'api_key'          => '',
            'last_checked'     => '',
            'account_email_id' => '',
            'is_ok'            => 'yes',
            'auto_scan'        => 'no',
            'scan_interval'    => 'daily',
            'last_report_sent' => ''
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
                return !in_array($file, $ignores['files']);
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

    public static function maybeSendScanReport()
    {
        $settings = self::getSettings();
        if ($settings['auto_scan'] != 'yes') {
            return;
        }

        $scanInterval = $settings['scan_interval'];

        if ($scanInterval == 'hourly') {
            $interval = 3600;
        } else {
            $interval = 84600; // 23.5 hours
        }

        if ($settings['last_report_sent'] && (time() - strtotime($settings['last_report_sent'])) < $interval) {
            return;
        }

        $result = (new \FluentAuth\App\Services\IntegrityChecker\CoreIntegrityChecker())->checkAll();
        if (is_wp_error($result) || empty($result)) {
            $settings['last_report_sent'] = date('Y-m-d H:i:s');
            $settings['last_checked'] = date('Y-m-d H:i:s');
            $settings['is_ok'] = 'yes';
            self::saveSettings($settings);
            return false;// could not do it
        }

        $activeChnages = self::getActiveModifiedFilesFolders($result);
        $modifiedFiles = Arr::get($activeChnages, 'files', []);
        $modifiedFolders = Arr::get($activeChnages, 'folders', []);

        $settings['last_report_sent'] = date('Y-m-d H:i:s');
        $settings['last_checked'] = date('Y-m-d H:i:s');
        $settings['is_ok'] = (!$modifiedFolders && !$modifiedFiles) ? 'yes' : 'no';
        self::saveSettings($settings);

        $activeChnages = array_filter($activeChnages);
        if (!$activeChnages) {
            return false;
        }

        if (!$modifiedFolders && !$modifiedFiles) {
            return false;
        }

        $payload = [
            'api_key'          => $settings['api_key'],
            'api_id'           => $settings['api_id'],
            'user_email'       => Arr::get($settings, 'account_email_id'),
            'site_url'         => str_replace(['https://', 'http://'], '', site_url()),
            'admin_url'        => admin_url('admin.php?page=fluent-auth#/'),
            'site_title'       => get_bloginfo('name'),
            'modified_files'   => $modifiedFiles,
            'modified_folders' => array_keys($modifiedFolders),
        ];

        // Send the remote request now
        wp_remote_post(self::$apiUrl . 'send-security-email/', [
            'body'      => json_encode($payload),
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'timeout'   => 30,
            'sslverify' => false,
        ]);

        return true;
    }

    public static function assignFileTimes($files, $path = '')
    {
        if (!$files) {
            return [];
        }

        $fomattedFiles = [];
        foreach ($files as $file => $status) {
            $time = filemtime(ABSPATH . $path ? $path . '/' : '' . $file);
            $fomattedFiles [$file] = [
                'status' => $status,
                'modified_at'   => gmdate('Y-m-d H:i:s', $time)
            ];
        }

        return $fomattedFiles;
    }
}
