<?php

namespace FluentSecurity\Classes;

use FluentSecurity\Helpers\Arr;
use FluentSecurity\Helpers\Helper;

class SettingsHandler
{
    public static function getSettings(\WP_REST_Request $request)
    {
        return [
            'settings' => Helper::getAuthSettings(),
            'user_roles' => Helper::getUserRoles()
        ];
    }

    public static function updateSettings(\WP_REST_Request $request)
    {
        $settings = self::validateSettings($request->get_param('settings'));
        if (is_wp_error($settings)) {
            return $settings;
        }

        update_option('__fls_auth_settings', $settings);

        return [
            'settings' => $settings,
            'message'  => __('Settings has been updated', 'fluent-security')
        ];
    }


    private static function validateSettings($settings)
    {
        $oldSettings = Helper::getAuthSettings();
        if (isset($settings['require_configuration'])) {
            unset($settings['require_configuration']);
        }

        $settings = Arr::only($settings, array_keys($oldSettings));

        $numericTypes = [
            'login_try_limit',
            'login_try_timing'
        ];

        foreach ($settings as $settingKey => $setting) {
            if (in_array($settingKey, $numericTypes)) {
                $settings[$settingKey] = (int) $setting;
            } else {
                if(is_array($setting)) {
                    $settings[$settingKey] = map_deep($setting, 'sanitize_text_field');
                } else {
                    $settings[$settingKey] = sanitize_text_field($setting);
                }
            }
        }

        $errors = [];

        if ($settings['enable_auth_logs'] == 'yes') {
            if (!$settings['login_try_limit']) {
                $errors['login_try_limit'] = [
                    'required' => 'Login try limit is required'
                ];
            }
            if (!$settings['login_try_timing']) {
                $errors['login_try_timing'] = [
                    'required' => 'Login Timing is required'
                ];
            }
        }

        if ($settings['extended_auth_security_type'] == 'pass_code') {
            if (empty($settings['global_auth_code'])) {
                $errors['global_auth_code'] = [
                    'required' => 'Global Auth Code is required'
                ];
            }
        } else {
            $settings['global_auth_code'] = '';
        }

        if ($errors) {
            return new \WP_Error('validation_error', 'Form Validation failed', $errors);
        }

        return $settings;

    }


}
