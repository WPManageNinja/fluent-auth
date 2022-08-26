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

        add_submenu_page(
            'options-general.php',
            __('Fluent Security', 'fluent-security'),
            __('Fluent Security', 'fluent-security'),
            $permission,
            'fluent-security',
            array($this, 'render'),
            100
        );
    }

    public function render()
    {
        $currentUser = wp_get_current_user();

        wp_enqueue_script('fluent_security_app', FLUENT_SECURITY_PLUGIN_URL . 'dist/admin/app.js', ['jquery'], true);

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
        return 'dashicons-shield';
    }
}
