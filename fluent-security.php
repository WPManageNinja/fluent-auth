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
    protected $failedLogged = false;

    public function init()
    {
        $this->loadDependencies();

        (new \FluentSecurity\Classes\LoginSecurity())->init();

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

        // Admin Menu Init
        (new \FluentSecurity\Classes\AdminMenuHandler())->register();

        register_activation_hook(__FILE__, [$this, 'installDbTables']);

        load_plugin_textdomain('fluent-security', false, dirname(plugin_basename(__FILE__)) . '/language');
    }

    public function installDbTables()
    {
        global $wpdb;
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

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql);
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

        add_action('rest_api_init', function () {
            require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/routes.php';
        });
    }
}

(new FluentSecurityPlugin())->init();
