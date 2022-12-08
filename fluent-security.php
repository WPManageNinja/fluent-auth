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
define('FLUENT_SECURITY_VERSION', '1.0');

class FluentSecurityPlugin
{
    public function init()
    {
        $this->autoLoad();

        register_activation_hook(__FILE__, [$this, 'activatePlugin']);
        register_deactivation_hook(__FILE__, [$this, 'deactivatePlugin']);

        load_plugin_textdomain('fluent-security', false, dirname(plugin_basename(__FILE__)) . '/language');

        $plugin_file = plugin_basename(__FILE__);
        add_filter("plugin_action_links_{$plugin_file}", [$this, 'addContextLinks'], 10, 1);
    }

    public function activatePlugin($siteWide = false)
    {
        \FluentSecurity\App\Helpers\Activator::activate($siteWide);
    }

    public function deactivatePlugin()
    {
        wp_clear_scheduled_hook('fluent_security_daily_tasks');
    }

    private function autoLoad()
    {
        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/libs/wpfluent/wpfluent.php';

        spl_autoload_register(function($class) {
            $match = 'FluentSecurity';

            if (!preg_match("/\b{$match}\b/", $class)) {
                return;
            }

            $path = plugin_dir_path(__FILE__);

            $file = str_replace(
                ['FluentSecurity', '\\', '/App/'],
                ['', DIRECTORY_SEPARATOR, 'app/'],
                $class
            );

            require(trailingslashit($path) . trim($file, '/') . '.php');
        });

        add_action('rest_api_init', function () {
            require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Http/routes.php';
        });

        require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/Hooks/hooks.php';
    }

    public function addContextLinks($actions)
    {
        $actions['settings'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=fluent-security#/settings')),
            esc_html__('Settings', 'fluent-security')
        );

        $actions['dashboard_page'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=fluent-security#/')),
            esc_html__('Dashboard', 'fluent-security')
        );

        return $actions;
    }
}

(new FluentSecurityPlugin())->init();
