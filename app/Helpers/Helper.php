<?php

namespace FluentSecurity\Helpers;

class Helper
{
    public static function getAuthSettings()
    {
        static $settings;
        if ($settings) {
            return $settings;
        }

        $defaults = [
            'extended_auth_security_type' => 'none', // none | 2fa | pass_code
            'global_auth_code'            => '',
            'disable_xmlrpc'              => 'no',
            'disable_app_login'           => 'no',
            'enable_auth_logs'            => 'yes',
            'login_try_limit'             => 5,
            'login_try_timing'            => 30,
            'disable_users_rest'          => 'no',
            'notification_user_roles'     => [],
            'notify_on_blocked'           => 'no',
            'notification_email'          => '{admin_email}',
            'auto_delete_logs_day'        => 30, // in days
        ];

        $settings = get_option('__fls_auth_settings');

        if (!$settings || !is_array($settings)) {
            $defaults['require_configuration'] = 'yes';
            $settings = $defaults;
            return $settings;
        }

        $settings = wp_parse_args($settings, $defaults);
        return $settings;
    }

    public static function getAppPermission()
    {
        return 'manage_options';
    }

    public static function getUserRoles($keyed = false)
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }

        $roles = \get_editable_roles();
        $formattedRoles = [];
        foreach ($roles as $roleKey => $role) {

            if ($keyed) {
                $formattedRoles[$roleKey] = $role['name'];
            } else {
                $formattedRoles[] = [
                    'id'    => $roleKey,
                    'title' => $role['name']
                ];
            }

        }
        return $formattedRoles;
    }

    public static function getGlobalLoginPassCode()
    {
        $settings = self::getAuthSettings();

        if (self::getSetting('extended_auth_security_type') != 'pass_code') {
            return false;
        }

        if (defined('FLUENT_SECURITY_LOGIN_CODE')) {
            return FLUENT_SECURITY_LOGIN_CODE;
        }

        return apply_filters('fluent_security/global_login_passcode', self::getSetting('global_auth_code'));

    }

    public static function getSetting($key, $default = false)
    {
        $config = self::getAuthSettings();
        if (isset($config[$key])) {
            return $config[$key];
        }

        return $default;
    }

    public static function getIp($anonymize = false)
    {
        // Get real visitor IP behind CloudFlare network
        // https://stackoverflow.com/questions/13646690/how-to-get-real-ip-from-visitor
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = sanitize_text_field($_SERVER["HTTP_CF_CONNECTING_IP"]);
            $_SERVER['HTTP_CLIENT_IP'] = sanitize_text_field($_SERVER["HTTP_CF_CONNECTING_IP"]);
        }
        $client = sanitize_text_field(@$_SERVER['HTTP_CLIENT_IP']);
        $forward = sanitize_text_field(@$_SERVER['HTTP_X_FORWARDED_FOR']);
        $remote = sanitize_text_field($_SERVER['REMOTE_ADDR']);

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        if ($anonymize) {
            return wp_privacy_anonymize_ip($ip);
        }

        return $ip;
    }

    public static function loadView($template, $data)
    {
        extract($data, EXTR_OVERWRITE);

        $template = sanitize_file_name($template);

        ob_start();
        include FLUENT_SECURITY_PLUGIN_PATH.'app/Views/'.$template.'.php';

        return ob_get_clean();
    }
}
