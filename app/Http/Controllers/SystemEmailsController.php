<?php

namespace FluentAuth\App\Http\Controllers;

use FluentAuth\App\Helpers\Arr;
use FluentAuth\App\Helpers\Helper;
use FluentAuth\App\Services\SystemEmailService;

class SystemEmailsController
{
    public static function getEmails(\WP_REST_Request $request)
    {
        return [
            'emailIndexes' => array_values(SystemEmailService::getEmailIndexes()),
        ];
    }

    public static function findEmail(\WP_REST_Request $request)
    {
        $remailId = $request->get_param('email_id', null);

        if (!$remailId) {
            return new \WP_Error('invalid_email_id', __('Email ID is required', 'fluent-security'), ['status' => 400]);
        }

        $emailIndexes = SystemEmailService::getEmailIndexes();

        if (!isset($emailIndexes[$remailId])) {
            return new \WP_Error('invalid_email_id', __('Email ID is invalid', 'fluent-security'), ['status' => 400]);
        }

        $emailSettings = SystemEmailService::getGlobalSettings();

        $targetEmail = $emailIndexes[$remailId];

        $contextSmartCodes = Arr::get($targetEmail, 'additional_smartcodes', []);

        $userSmartCodes = [
            '{{user.first_name}}'   => __('First Name', 'fluent-security'),
            '{{user.last_name}}'    => __('Last Name', 'fluent-security'),
            '{{user.display_name}}' => __('Display Name', 'fluent-security'),
            '{{user.user_email}}'   => __('Email', 'fluent-security'),
            '{{user.user_login}}'   => __('Username', 'fluent-security'),
            '{{user.roles}}'        => __('User Roles', 'fluent-security'),
        ];

        if ($contextSmartCodes) {
            $userSmartCodes = array_merge($contextSmartCodes, $userSmartCodes);
        }

        $editorCodes = [
            [
                'key'        => 'user',
                'title'      => __('User Data', 'fluent-security'),
                'shortcodes' => apply_filters('fluentcrm_auth/email_smartcodes', $userSmartCodes)
            ],
            [
                'key'        => 'site',
                'title'      => __('Site Data', 'fluent-security'),
                'shortcodes' => apply_filters('fluentcrm_auth/email_smartcodes', [
                    '{{site.name}}'        => __('Site Title', 'fluent-security'),
                    '{{site.description}}' => __('Site tagline', 'fluent-security'),
                    '{{site.admin_email}}' => __('Admin Email', 'fluent-security'),
                    '##site.url##'         => __('Site URL', 'fluent-security'),
                    '##site.login_url##'   => __('Login Url', 'fluent-security'),
                ])
            ]
        ];

        if (!isset($emailSettings['emails'][$remailId])) {
            return new \WP_Error('invalid_email_id', __('Email ID is invalid', 'fluent-security'), ['status' => 400]);
        }

        $defaultEmails = SystemEmailService::getEmailDefaults();

        return [
            'email'      => $targetEmail,
            'settings'   => $emailSettings['emails'][$remailId],
            'smartcodes' => array_values($editorCodes),
            'default_content' => Arr::get($defaultEmails, $remailId),
        ];
    }

    public static function saveEmailSettings(\WP_REST_Request $request)
    {
        $emailId = $request->get_param('email_id', null);

        if (!$emailId) {
            return new \WP_Error('invalid_email_id', __('Email ID is required', 'fluent-security'), ['status' => 400]);
        }

        $emailIndexes = SystemEmailService::getEmailIndexes();

        if (!isset($emailIndexes[$emailId])) {
            return new \WP_Error('invalid_email_id', __('Email ID is invalid', 'fluent-security'), ['status' => 400]);
        }

        $allEmailSettings = SystemEmailService::getGlobalSettings();

        $settings = $request->get_param('settings', []);

        if ($settings['status'] == 'active') {
            $allEmailSettings['emails'][$emailId]['status'] = 'active';

            $subject = sanitize_text_field(Arr::get($settings, 'email.subject'));
            $emailBody = wp_kses_post(Arr::get($settings, 'email.body'));

            if (!$subject || !$emailBody) {
                return new \WP_Error('invalid_email_settings', __('Email subject and body are required', 'fluent-security'), ['status' => 400]);
            }

            $requiredSmartCodes = Arr::get($emailIndexes, $emailId . '.required_smartcodes', []);

            if (!self::validateEmailBody($emailBody, $requiredSmartCodes)) {
                return new \WP_Error('invalid_email_settings', __('Email body is not valid. Please check the smartcodes.', 'fluent-security'), [
                    'status'              => 400,
                    'required_smartcodes' => $requiredSmartCodes
                ]);
            }

            $allEmailSettings['emails'][$emailId]['email'] = [
                'subject' => sanitize_text_field(Arr::get($settings, 'email.subject')),
                'body'    => $emailBody,
            ];
        } else if ($settings['status'] == 'disabled') {
            $allEmailSettings['emails'][$emailId]['status'] = 'disabled';
            $allEmailSettings['emails'][$emailId]['email'] = [
                'subject' => sanitize_text_field(Arr::get($settings, 'email.subject')),
                'body'    => wp_kses_post(Arr::get($settings, 'email.body')),
            ];
        } else {
            unset($allEmailSettings['emails'][$emailId]);
        }

        update_option('fa_system_email_settings', $allEmailSettings, 'no');

        return [
            'message' => __('Email settings has been succcesfully updated', 'fluent-security')
        ];
    }

    private static function validateEmailBody($emailBody, $smartCodes = [])
    {
        if (!$smartCodes) {
            return true;
        }

        foreach ($smartCodes as $smartCode) {
            $codes = ['{{' . $smartCode . '}}', '##' . $smartCode . '##'];
            $hasCode = str_contains($emailBody, $codes[0]) || str_contains($emailBody, $codes[1]);
            if (!$hasCode) {
                return false;
            }
        }

        return true;
    }
}
