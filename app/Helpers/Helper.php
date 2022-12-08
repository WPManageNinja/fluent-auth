<?php

namespace FluentSecurity\App\Helpers;

class Helper
{
    public static function getAuthSettings()
    {
        static $settings;
        if ($settings) {
            return $settings;
        }

        $defaults = [
            'disable_xmlrpc'          => 'no',
            'disable_app_login'       => 'no',
            'enable_auth_logs'        => 'yes',
            'login_try_limit'         => 5,
            'login_try_timing'        => 30,
            'disable_users_rest'      => 'no',
            'notification_user_roles' => [],
            'notify_on_blocked'       => 'no',
            'notification_email'      => '{admin_email}',
            'auto_delete_logs_day'    => 30, // in days
            'magic_login'             => 'no',
            'magic_restricted_roles'  => [],
            'email2fa'                => 'no',
            'email2fa_roles'          => ['administrator', 'editor', 'author']
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

    public static function getWpPermissions($keyed = false)
    {
        $allCaps = [];
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }

        $roles = \get_editable_roles();
        foreach ($roles as $role) {
            $allCaps = array_merge((array)$allCaps, (array)$role['capabilities']);
        }

        $formattedCaps = [];
        foreach ($allCaps as $capName => $cap) {
            if (!$capName) {
                continue;
            }
            if ($keyed) {
                $formattedCaps[$capName] = $capName;
            } else {
                $formattedCaps[] = [
                    'id'    => $capName,
                    'title' => $capName
                ];
            }
        }

        return $formattedCaps;
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
        $ip = '';

        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP']));
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
            // Make sure we always only send through the first IP in the list which should always be the client IP.
            $ip = (string)rest_is_ip_address(trim(current(preg_split('/,/', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']))))));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }


        if (!$ip || $ip == '127.0.0.1') {
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

        $template = str_replace('.', DIRECTORY_SEPARATOR, $template);

        ob_start();
        include FLUENT_SECURITY_PLUGIN_PATH . 'app/Views/' . $template . '.php';
        return ob_get_clean();
    }

    public static function cleanUpLogs()
    {
        $oldDays = self::getSetting('auto_delete_logs_day');

        if (!$oldDays) {
            return;
        }

        $dateTime = date('Y-m-d H:i:s', current_time('timestamp') - $oldDays * 86400);

        flsDb()->table('fls_auth_logs')
            ->where('created_at', '<', $dateTime)
            ->delete();

        if ($oldDays < 30) {
            $dateTime = date('Y-m-d H:i:s', current_time('timestamp') - 30 * 86400);
        }

        flsDb()->table('fls_login_hashes')
            ->where('valid_till', '<', current_time('mysql'))
            ->where('status', 'issued')
            ->update([
                'status' => 'expired'
            ]);

        flsDb()->table('fls_login_hashes')
            ->where('status', '!=', 'issued')
            ->where('created_at', '<', $dateTime)
            ->delete();

    }

    public static function getSocialAuthSettings($context = 'view')
    {
        static $settings;
        if ($settings) {
            return $settings;
        }

        $defaults = [
            'enabled'              => 'no',
            'enable_google'        => 'no',
            'google_key_method'    => 'wp_config',
            'google_client_id'     => '',
            'google_client_secret' => '',
            'enable_github'        => 'no',
            'github_key_method'    => 'wp_config',
            'github_client_id'     => '',
            'github_client_secret' => ''
        ];

        $settings = get_option('__fls_social_auth_settings');

        if (!$settings || !is_array($settings)) {
            $settings = $defaults;
            return $settings;
        }

        $settings = wp_parse_args($settings, $defaults);

        if ($context == 'edit') {
            if ($settings['google_key_method'] == 'wp_config') {
                $settings['google_client_id'] = (defined('FLUENT_AUTH_GOOGLE_CLIENT_ID')) ? FLUENT_AUTH_GOOGLE_CLIENT_ID : '';
                $settings['google_client_secret'] = (defined('FLUENT_AUTH_GOOGLE_CLIENT_SECRET')) ? FLUENT_AUTH_GOOGLE_CLIENT_SECRET : '';
            }

            if ($settings['github_key_method'] == 'wp_config') {
                $settings['github_client_id'] = (defined('FLUENT_AUTH_GITHUB_CLIENT_ID')) ? FLUENT_AUTH_GITHUB_CLIENT_ID : '';
                $settings['github_client_secret'] = (defined('FLUENT_AUTH_GITHUB_CLIENT_SECRET')) ? FLUENT_AUTH_GITHUB_CLIENT_SECRET : '';
            }
        }

        return $settings;
    }

    public static function getAuthFormsSettings()
    {
        $settingsDefault = [
            'enabled'                 => 'no',
            'login_redirects'         => 'no',
            'default_login_redirect'  => '',
            'default_logout_redirect' => '',
            'redirect_rules'          => []
        ];

        $settings = get_option('__fls_auth_forms_settings', []);

        if (!$settings) {
            return $settingsDefault;
        }

        return wp_parse_args($settings, $settingsDefault);
    }
}
