<?php

namespace FluentSecurity\Helpers;

class Helper
{
    public static function getAuthSettings()
    {
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
            'notification_email'          => '{admin_email}',
            'auto_delete_logs_day'        => 30, // in days
        ];

        $settings = get_option('__fls_auth_settings');

        if (!$settings || !is_array($settings)) {
            $defaults['require_configuration'] = 'yes';
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
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
}
