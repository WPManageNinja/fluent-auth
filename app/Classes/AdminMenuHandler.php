<?php

namespace FluentSecurity\Classes;

use FluentSecurity\Helpers\Helper;

class AdminMenuHandler
{
    public function register()
    {
        add_action('admin_menu', array($this, 'addMenu'));
    }

    public function addMenu()
    {
        $permission = Helper::getAppPermission();

        if (!$permission) {
            return;
        }

        add_menu_page(
            __('Fluent Login & Security', 'fluent-security'),
            __('Fluent Login', 'fluent-security'),
            $permission,
            'fluent-security',
            array($this, 'render'),
            $this->getMenuIcon(),
            120
        );

        add_submenu_page(
            'fluent-security',
            __('Dashboard', 'fluent-support'),
            __('Dashboard', 'fluent-support'),
            $permission,
            'fluent-security',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-security',
            __('Security Settings', 'fluent-support'),
            __('Security Settings', 'fluent-support'),
            $permission,
            'fluent-security#/settings',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-security',
            __('Social Login', 'fluent-support'),
            __('Social Login', 'fluent-support'),
            $permission,
            'fluent-security#/social-login-settings',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-security',
            __('Login Redirects', 'fluent-support'),
            __('Login Redirects', 'fluent-support'),
            $permission,
            'fluent-security#/login-redirects',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-security',
            __('Logs', 'fluent-support'),
            __('Logs', 'fluent-support'),
            $permission,
            'fluent-security#/logs',
            array($this, 'render')
        );

    }

    public function render()
    {
        $currentUser = wp_get_current_user();

        wp_enqueue_script('fluent_security_app', FLUENT_SECURITY_PLUGIN_URL . 'dist/admin/app.js', ['jquery'], '1.0', true);

        wp_localize_script('fluent_security_app', 'fluentSecurityAdmin', [
            'slug'          => 'fluent-security',
            'nonce'         => wp_create_nonce('fluent-security'),
            'rest'          => [
                'base_url'  => esc_url_raw(rest_url()),
                'url'       => rest_url('fluent-security'),
                'nonce'     => wp_create_nonce('wp_rest'),
                'namespace' => 'fluent-security',
                'version'   => '1'
            ],
            'auth_statuses' => [
                'failed'  => __('Failed', 'fluent-security'),
                'blocked' => __('Blocked', 'fluent-security'),
                'success' => __('Successful', 'fluent-security')
            ],
            'auth_settings' => Helper::getAuthSettings(),
            'asset_url'     => FLUENT_SECURITY_PLUGIN_URL . 'dist/',
            'me'            => [
                'id'        => $currentUser->ID,
                'full_name' => trim($currentUser->first_name . ' ' . $currentUser->last_name),
                'email'     => $currentUser->user_email
            ],
        ]);

        echo '<div id="fluent_security_app"><h3 style="text-align: center; margin-top: 100px;">Loading Settings..</h3></div>';
    }

    private function getMenuIcon()
    {
        return 'dashicons-shield-alt';
    }
}
