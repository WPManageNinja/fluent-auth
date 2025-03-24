<?php

namespace FluentAuth\App\Http\Controllers;

use FluentAuth\App\Helpers\Arr;
use FluentAuth\App\Services\IntegrityChecker\Api;
use FluentAuth\App\Services\IntegrityChecker\IntegrityHelper;

class SecurityScanController
{
    public static function getSettings(\WP_REST_Request $request)
    {
        $settings = IntegrityHelper::getSettings();

        if ($settings['last_checked']) {
            $settings['last_checked_human'] = human_time_diff(strtotime($settings['last_checked']), current_time('timestamp'));
        }

        return [
            'settings' => $settings,
            'ignores'  => IntegrityHelper::getIgnoreLists(),
        ];
    }

    public static function registerSite(\WP_REST_Request $request)
    {
        $info = $request->get_param('info');

        if (!is_array($info)) {
            $info = [];
        }

        // Validate the data
        $infoData = [
            'email'     => sanitize_email(Arr::get($info, 'email', '')),
            'full_name' => sanitize_text_field(Arr::get($info, 'full_name', '')),
            'api_id'    => sanitize_text_field(Arr::get($info, 'api_id', '')),
            'api_key'   => sanitize_text_field(Arr::get($info, 'api_key', ''))
        ];

        if (!is_email($infoData['email']) || empty($infoData['full_name'])) {
            return new \WP_Error('invalid_data', __('Please provide a valid email address and full name.', 'fluent-security'), ['status' => 400, 'data' => $infoData]);
        }

        $settings = IntegrityHelper::getSettings();
        $isConfirmed = false;
        if ($settings['status'] == 'unregistered') {
            $apiId = Api::registerSite($infoData);

        } else {
            $infoData['api_id'] = $settings['api_id'];
            $apiId = Api::confirmSite($infoData);
            $isConfirmed = true;
        }

        if (is_wp_error($apiId)) {
            return $apiId;
        }

        if ($isConfirmed) {
            $settings['api_key'] = $infoData['api_key'];
            $settings['status'] = 'active';
        } else {
            $settings['api_id'] = $apiId;
            $settings['status'] = 'pending';
            $settings['account_email_id'] = $infoData['email'];
        }

        IntegrityHelper::saveSettings($settings);

        return [
            'message'  => 'Your site has been successfully registered. Please provide the API token.',
            'settings' => $settings
        ];

    }

    public static function scanSite(\WP_REST_Request $request)
    {
        $result = (new \FluentAuth\App\Services\IntegrityChecker\CoreIntegrityChecker())->checkAll();
        if (is_wp_error($result)) {
            return $result;
        }

        $hasIssues = !!$result;

        return [
            'scan_results'  => $result,
            'activeChanges' => IntegrityHelper::getActiveModifiedFilesFolders($result),
            'has_issues'    => $hasIssues
        ];
    }

    public static function toggleIgnore(\WP_REST_Request $request)
    {
        $willRemove = $request->get_param('will_remove') == 'yes';
        $file = $request->get_param('file');

        if (!is_string($file) || empty($file)) {
            return new \WP_Error('invalid_data', __('Please provide a valid file name.', 'fluent-security'), ['status' => 400, 'data' => $file]);
        }

        $isFolder = $request->get_param('is_folder') == 'yes';

        $settings = IntegrityHelper::getIgnoreLists();

        if ($isFolder) {
            $ignoreLists = $settings['folders'];
        } else {
            $ignoreLists = $settings['files'];
        }

        if ($willRemove) {
            $ignoreLists = array_diff($ignoreLists, [$file]);
        } else {
            $ignoreLists[] = $file;
        }

        if ($isFolder) {
            $settings['folders'] = array_values(array_unique($ignoreLists));
        } else {
            $settings['files'] = array_values(array_unique($ignoreLists));
        }

        IntegrityHelper::updateIgnoreLists($settings);

        return [
            'message' => __('Ignore status has been updated.', 'fluent-security'),
            'lists'   => $settings
        ];
    }
}
