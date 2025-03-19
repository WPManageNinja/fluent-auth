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


        $editorCodes = [
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
            ],
            [
                'key'        => 'user',
                'title'      => __('User Data', 'fluent-security'),
                'shortcodes' => apply_filters('fluentcrm_auth/email_smartcodes', [
                    '{{user.first_name}}'         => __('First Name', 'fluent-security'),
                    '{{user.last_name}}'          => __('Last Name', 'fluent-security'),
                    '{{user.display_name}}'       => __('Display Name', 'fluent-security'),
                    '{{user.user_email}}'         => __('Email', 'fluent-security'),
                    '{{user.user_login}}'         => __('Username', 'fluent-security'),
                    '##user.password_reset_url##' => __('Password Reset URL', 'fluent-security'),
                    '{{user.roles}}'              => __('User Roles', 'fluent-security'),
                ])
            ]
        ];

        if (!isset($emailSettings['emails'][$remailId])) {
            return new \WP_Error('invalid_email_id', __('Email ID is invalid', 'fluent-security'), ['status' => 400]);
        }

        return [
            'email'      => $emailIndexes[$remailId],
            'settings'   => $emailSettings['emails'][$remailId],
            'smartcodes' => array_values($editorCodes)
        ];
    }

    public static function saveEmailSettings(\WP_REST_Request $request)
    {
        $remailId = $request->get_param('email_id', null);

        if (!$remailId) {
            return new \WP_Error('invalid_email_id', __('Email ID is required', 'fluent-security'), ['status' => 400]);
        }

        $emailIndexes = SystemEmailService::getEmailIndexes();

        if (!isset($emailIndexes[$remailId])) {
            return new \WP_Error('invalid_email_id', __('Email ID is invalid', 'fluent-security'), ['status' => 400]);
        }

        $allEmailSettings = SystemEmailService::getGlobalSettings();

        $settings = $request->get_param('settings', []);

        if ($settings['status'] == 'active') {
            $allEmailSettings['emails'][$remailId]['status'] = 'active';

            $subject = sanitize_text_field(Arr::get($settings, 'email.subject'));
            $body = wp_kses_post(Arr::get($settings, 'email.body'));

            if (!$subject || !$body) {
                return new \WP_Error('invalid_email_settings', __('Email subject and body are required', 'fluent-security'), ['status' => 400]);
            }

            $allEmailSettings['emails'][$remailId]['email'] = [
                'subject' => sanitize_text_field(Arr::get($settings, 'email.subject')),
                'body'    => wp_kses_post(Arr::get($settings, 'email.body')),
            ];
        } else if ($settings['status'] == 'disabled') {
            $allEmailSettings['emails'][$remailId]['status'] = 'disabled';
            $allEmailSettings['emails'][$remailId]['email'] = [
                'subject' => sanitize_text_field(Arr::get($settings, 'email.subject')),
                'body'    => wp_kses_post(Arr::get($settings, 'email.body')),
            ];
        } else {
            unset($allEmailSettings['emails'][$remailId]);
        }

        update_option('fa_system_email_settings', $allEmailSettings, 'no');

        return [
            'message' => __('Email settings has been succcesfully updated', 'fluent-security')
        ];

    }
}
