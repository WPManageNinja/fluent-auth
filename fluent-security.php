<?php

defined('ABSPATH') or die;
/*
Plugin Name:  Fluent Security
Plugin URI:   https://github.com/WPManageNinja/fluent-security
Description:  Super Simple Security Plugin for WordPress
Version:      1.0
Author:       Fluent Security Team
Author URI:   https://jewel.im
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  fluent-security
Domain Path:  /language/
*/

define('FLUENT_SECURITY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FLUENT_SECURITY_PLUGIN_URL', plugin_dir_url(__FILE__));

class FluentSecurityPlugin
{

    public function init()
    {
        $this->loadDependencies();

        // Admin Menu Init
        (new \FluentSecurity\Classes\AdminMenuHandler())->register();

        (new \FluentSecurity\Classes\LoginSecurity())->init();
        (new \FluentSecurity\Classes\MagicLogin())->register();

        /*
         * Social Auth Handler Register
         */
        (new \FluentSecurity\Classes\SocialAuthHandler())->register();

        // Maybe Remove Application Password Login
        add_filter('wp_is_application_passwords_available', function ($status) {
            if (!$status || \FluentSecurity\Helpers\Helper::getSetting('disable_app_login') == 'yes') {
                return false;
            }

            return $status;
        });

        // Disable xmlrpc
        add_filter('xmlrpc_enabled', function ($status) {
            if (!$status || \FluentSecurity\Helpers\Helper::getSetting('disable_xmlrpc') == 'yes') {
                return false;
            }
            return $status;
        });

        // Maybe disable List Users REST
        add_filter('rest_user_query', function ($query) {
            if (\FluentSecurity\Helpers\Helper::getSetting('disable_users_rest') === 'yes' && !current_user_can('list_users')) {
                $query['login'] = 'someRandomStringForThis_' . time();
            }
            return $query;
        });

        add_filter('rest_prepare_user', function ($response, $user, $request) {
            if (!empty($request['id']) && \FluentSecurity\Helpers\Helper::getSetting('disable_users_rest') === 'yes' && !current_user_can('list_users')) {
                return new \WP_Error('permission_error', 'You do not have access to list users. Restriction added from fluent security');
            }
            return $user;
        }, 10, 3);

        register_activation_hook(__FILE__, [$this, 'installDbTables']);

        register_deactivation_hook(__FILE__, function () {
            wp_clear_scheduled_hook('fluent_security_daily_tasks');
        });

        load_plugin_textdomain('fluent-security', false, dirname(plugin_basename(__FILE__)) . '/language');

        /*
         * Clean Up Old Logs
         */
        add_action('fluent_security_daily_tasks', function () {
            \FluentSecurity\Helpers\Helper::cleanUpLogs();
        });

        add_action('admin_notices', function () {
            if (get_option('__fls_auth_settings') || !current_user_can('manage_options')) {
                return '';
            }

            $url = admin_url('options-general.php?page=fluent-security#/settings');

            ?>
            <div style="padding-bottom: 10px;" class="notice notice-warning">
                <p><?php echo sprintf(__('Thank you for installing %s Plugin. Please configure the security settings to enable enhanced security of your site', 'fluent-security'), '<b>Fluent Security</b>'); ?></p>
                <a href="<?php echo esc_url($url); ?>"><?php _e('Configure Fluent Security', 'fluent-security'); ?></a>
            </div>
            <?php
        });

        $plugin_file = plugin_basename(__FILE__);
        add_filter("plugin_action_links_{$plugin_file}", [$this, 'addContextLinks'], 10, 1);
    }

    public function installDbTables()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'fls_auth_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `username` VARCHAR(192) NOT NULL,
                `user_id` BIGINT UNSIGNED NULL,
                `count` INT UNSIGNED NULL DEFAULT 1,
                `agent` VARCHAR(192) NULL,
                `browser` varchar(50) NULL,
                `device_os` varchar(50) NULL,
                `ip`    varchar(50) NULL,
                `status` varchar(50) NULL,
                `error_code` varchar(50) NULL DEFAULT '',
                `media` varchar(50) NULL DEFAULT 'web',
                `description` TINYTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                  KEY `created_at` (`created_at`),
                  KEY `ip` (`ip`(50)),
                  KEY `status` (`status`(50)),
                  KEY `media` (`media`(50)),
                  KEY `user_id` (`user_id`),
                  KEY  `username` (`username`(192))
            ) $charsetCollate;";
            dbDelta($sql);
        }

        $table_name = $wpdb->prefix . 'fls_login_hashes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
				id BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
				login_hash varchar(192),
				user_id BIGINT(20) DEFAULT 0,
				used_count INT(11) DEFAULT 0,
				use_limit INT(11) DEFAULT 1,
				status varchar(20) DEFAULT 'issued',
				ip_address varchar(20) NULL,
				redirect_intend varchar(255) NULL,
				success_ip_address varchar(20) NULL,
				country varchar(50) NULL,
				city varchar(50) NULL,
				created_by int(11) null,
				valid_till  timestamp NULL,
				created_at timestamp NULL,
				updated_at timestamp NULL,
                   KEY `created_at` (`created_at`),
                   KEY `login_hash` (`login_hash`(192)),
                   KEY `user_id` (`user_id`),
                   KEY `status` (`status`(20))
			) $charsetCollate;";
            dbDelta($sql);
        }

        if (!wp_next_scheduled('fluent_security_daily_tasks')) {
            wp_schedule_event(time(), 'daily', 'fluent_security_daily_tasks');
        }
    }

    private function loadDependencies()
    {
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/libs/wpfluent/wpfluent.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Helpers/Arr.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Helpers/Helper.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Helpers/BrowserDetection.php';

        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/Router.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/SettingsHandler.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/LogsHandler.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/AdminMenuHandler.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/LoginSecurity.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/MagicLogin.php';

        /*
         * Load Social Logins
         */
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Services/AuthService.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Services/GithubAuthService.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Services/GoogleAuthService.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/SocialAuthApi.php';
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Classes/SocialAuthHandler.php';

        add_action('rest_api_init', function () {
            require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/routes.php';
        });
    }

    public function addContextLinks($actions)
    {
        $actions['settings'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=fluent-security#/settings')),
            esc_html__('Settings', 'fluent-security')
        );

        $actions['dashboard_page'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=fluent-security#/')),
            esc_html__('Dashboard', 'fluent-security')
        );

        return $actions;
    }
}

(new FluentSecurityPlugin())->init();
