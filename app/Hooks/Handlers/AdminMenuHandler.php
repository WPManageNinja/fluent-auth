<?php

namespace FluentAuth\App\Hooks\Handlers;

use FluentAuth\App\Helpers\Helper;

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
            __('FluentAuth Settings', 'fluent-security'),
            __('FluentAuth', 'fluent-security'),
            $permission,
            'fluent-auth',
            array($this, 'render'),
            $this->getMenuIcon(),
            120
        );

        add_submenu_page(
            'fluent-auth',
            __('Dashboard', 'fluent-security'),
            __('Dashboard', 'fluent-security'),
            $permission,
            'fluent-auth',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-auth',
            __('Security Settings', 'fluent-security'),
            __('Security Settings', 'fluent-security'),
            $permission,
            'fluent-auth#/settings',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-auth',
            __('Social Login', 'fluent-security'),
            __('Social Login', 'fluent-security'),
            $permission,
            'fluent-auth#/social-login-settings',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-auth',
            __('Login Redirects', 'fluent-security'),
            __('Login Redirects', 'fluent-security'),
            $permission,
            'fluent-auth#/login-redirects',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent-auth',
            __('Logs', 'fluent-security'),
            __('Logs', 'fluent-security'),
            $permission,
            'fluent-auth#/logs',
            array($this, 'render')
        );

    }

    public function render()
    {
        add_filter('admin_footer_text', function ($content) {
            return 'Thank you for using <a rel="noopener"  target="_blank" href="https://fluentauth.com">FluentAuth</a> | Write a <a target="_blank" rel="noopener" href="https://wordpress.org/support/plugin/fluent-security/reviews/?filter=5">review for FluentAuth</a>';
        });

        $currentUser = wp_get_current_user();

        wp_enqueue_script('fluent_auth_app', FLUENT_AUTH_PLUGIN_URL . 'dist/admin/app.js', ['jquery'], '1.0', true);

        wp_localize_script('fluent_auth_app', 'fluentAuthAdmin', [
            'slug'            => 'fluent-security',
            'nonce'           => wp_create_nonce('fluent-security'),
            'rest'            => [
                'base_url'  => esc_url_raw(rest_url()),
                'url'       => rest_url('fluent-auth'),
                'nonce'     => wp_create_nonce('wp_rest'),
                'namespace' => 'fluent-auth',
                'version'   => '1'
            ],
            'auth_statuses'   => [
                'failed'  => __('Failed', 'fluent-security'),
                'blocked' => __('Blocked', 'fluent-security'),
                'success' => __('Successful', 'fluent-security')
            ],
            'auth_settings'   => Helper::getAuthSettings(),
            'asset_url'       => FLUENT_AUTH_PLUGIN_URL . 'dist/',
            'me'              => [
                'id'        => $currentUser->ID,
                'full_name' => trim($currentUser->first_name . ' ' . $currentUser->last_name),
                'email'     => $currentUser->user_email
            ],
            'i18n'            => [
                'Dashboard'                     => __('Dashboard', 'fluent-security'),
                'Logs'                          => __('Logs', 'fluent-security'),
                'Settings'                      => __('Settings', 'fluent-security'),
                'Social Login'                  => __('Social Login', 'fluent-security'),
                'Login or Signup Forms'         => __('Login or Signup Forms', 'fluent-security'),
                'Login Redirects'               => __('Login Redirects', 'fluent-security'),
                'social_header'                 => __('Social Login/Signup Settings', 'fluent-security'),
                'social_auth_checkbox'          => __('Enable Social Login / Signup', 'fluent-security'),
                'login_google'                  => __('Login with Google Settings', 'fluent-security'),
                'enable_google'                 => __('Enable Login with Google', 'fluent-security'),
                'Database'                      => __('Database', 'fluent-security'),
                'wp-config'                     => __('wp-config file(recommended)', 'fluent-security'),
                'wp_config_instruction'         => __('Please add the following code in your wp-config.php file (please replace the *** with your app values)', 'fluent-security'),
                'credential_storage'            => __('Credential Storage Method', 'fluent-security'),
                'Google Client ID'              => __('Google Client ID', 'fluent-security'),
                'Google Client Secret'          => __('Google Client Secret', 'fluent-security'),
                'login_github'                  => __('Login with Github Settings', 'fluent-security'),
                'enable_github'                 => __('Enable Login with Github', 'fluent-security'),
                'Github Client ID'              => __('Github Client ID', 'fluent-security'),
                'Github Client Secret'          => __('Github Client Secret', 'fluent-security'),
                'Save Settings'                 => __('Save Settings', 'fluent-security'),
                'apply_recommended'             => __('Apply recommended settings', 'fluent-security'),
                'Core Security Settings'        => __('Core Security Settings', 'fluent-security'),
                'disable_xmlrpc'                => __('Disable XML-RPC (Most of the sites don\'t need XMLRPC)', 'fluent-security'),
                'disable_app_login'             => __('Disable App Login (Rest API) for Remote Access. (Recommended: Disable)', 'fluent-security'),
                'disable_rest_user'             => __('Disable REST Endpoint for wp users query for public (Recommended: Disable)', 'fluent-security'),
                'Login Security Settings'       => __('Login Security Settings', 'fluent-security'),
                'enable_login_security'         => __('Enable Login Security and Login Limit (recommended)', 'fluent-security'),
                'login_logs_recommendation'     => __('We recommend to enable login logs as well as set login try limit', 'fluent-security'),
                'login_how_many'                => __('How many times user can try login in', 'fluent-security'),
                'Extended Login Security'       => __('Extended Login Security', 'fluent-security'),
                'Standard'                      => __('Standard', 'fluent-security'),
                'With Login Security Code'      => __('With Login Security Code', 'fluent-security'),
                'Magic Login'                   => __('Magic Login', 'fluent-security'),
                'passcode_desc'                 => __('[Only use this if you do not have other wp users than your close circle]', 'fluent-security'),
                'user_role_magic_disable'       => __('Disable Magic Login for specific user roles (Leave blank to enable magic login for all users)', 'fluent-security'),
                'security_pass_label'           => __('Provide Login Security Pass that users need to provide when login', 'fluent-security'),
                'security_pass_desc'            => __('A new field will be shown to provide this code to login. Users can also set their own code from profile page.', 'fluent-security'),
                'Other Settings'                => __('Other Settings', 'fluent-security'),
                'delete_logs_label'             => __('Automatically delete logs older than (in days)', 'fluent-security'),
                'delete_logs_desc'              => __('Use 0 if you do not want to delete the logs', 'fluent-security'),
                'login_notification_label'      => __('Send Email notification if any of the following user roles login', 'fluent-security'),
                'notification_blocked'          => __('Send email notification when a user get blocked', 'fluent-security'),
                'notification_email'            => __('Notification Send to Email Address', 'fluent-security'),
                'refresh'                       => __('refresh', 'fluent-security'),
                'All'                           => __('All', 'fluent-security'),
                'Description'                   => __('Description', 'fluent-security'),
                'User Agent'                    => __('User Agent', 'fluent-security'),
                'Delete All Logs'               => __('Delete All Logs', 'fluent-security'),
                'Status'                        => __('Status', 'fluent-security'),
                'Login Username'                => __('Login Username', 'fluent-security'),
                'User ID'                       => __('User ID', 'fluent-security'),
                'Media'                         => __('Media', 'fluent-security'),
                'IP'                            => __('IP', 'fluent-security'),
                'Browser'                       => __('Browser', 'fluent-security'),
                'Date'                          => __('Date', 'fluent-security'),
                'Action'                        => __('Action', 'fluent-security'),
                'confirm_log_delete'            => __('Are you sure to delete all the logs?', 'fluent-security'),
                'User Role'                     => __('User Role', 'fluent-security'),
                'User Capability'               => __('User Capability', 'fluent-security'),
                'Login Redirects Settings'      => __('Login Redirects Settings', 'fluent-security'),
                'Enable Custom Login Redirects' => __('Enable Custom Login Redirects', 'fluent-security'),
                'Advanced Redirect Rules'       => __('Advanced Redirect Rules', 'fluent-security'),
                'Add Rule'                      => __('Add Rule', 'fluent-security'),
                'dashboard_message'             => __('view your security config and recent login activities', 'fluent-security'),
                'dashboard_recent_failed'       => __('Recent Failed & Blocked Logins', 'fluent-security'),
                'not_enough_failed'             => __('Not enough data. This section will show recent failed login attempts', 'fluent-security'),
                'Settings Overview'             => __('Settings Overview', 'fluent-security'),
                'disable_xml_heading'           => __('Disable XML-RPC Requests', 'fluent-security'),
                'disable_rest_heading'          => __('Disable Rest Remote App Login', 'fluent-security'),
                'Log Login Logs'                => __('Log Login Logs', 'fluent-security'),
                'Disable Public User Indexing'  => __('Disable Public User Indexing', 'fluent-security'),
                'Login Notifications'           => __('Login Notifications', 'fluent-security'),
                'Recent Successful Logins'      => __('Recent Successful Logins', 'fluent-security'),
                'not_enough_success_logs'       => __('Not enough data. This section will show recent successful logins', 'fluent-security'),
                'auth_short_heading'            => __('Login or Signup Forms and Login Redirects', 'fluent-security'),
                'enable_short_check'            => __('Enable Custom Login / Signup Shortcodes', 'fluent-security'),
                'full_auth_short'               => __('Full Authentication Flow ShortCode (includes Login Form, Registration Form and Password Reset Form)', 'fluent-security'),
                'regi_short'                    => __('Only Registration Form ShortCode', 'fluent-security'),
                'login_short'                   => __('Only Login Form ShortCode', 'fluent-security'),
                'pass_reset_short'              => __('Password Reset Form ShortCode', 'fluent-security'),
                'Username'                      => __('Username', 'fluent-security'),
                'Select Range'                  => __('Select Range', 'fluent-security'),
                'Today'                         => __('Today', 'fluent-security'),
                'Last 7 days'                   => __('Last 7 days', 'fluent-security'),
                'Last 30 days'                  => __('Last 30 days', 'fluent-security'),
                'This Month'                    => __('This Month', 'fluent-security'),
                'All Time'                      => __('All Time', 'fluent-security'),
                'Login/Signup Forms'            => __('Login/Signup Forms', 'fluent-security'),
                'pass_code_desc'                => __('Users can only login with the extended security code. Enable only if only internal team login to the site to manage', 'fluent-security'),
                'disable_admin_bar_label'       => __('Disable admin bar and <code>/wp-admin/</code> access for selected user roles.', 'fluent-security'),
                'disable_admin_bar_roles_label' => __('Roles to disable <code>wp-admin</code> access and hide admin bar', 'fluent-security')
            ]
        ]);

        echo '<div id="fluent_auth_app"><h3 style="text-align: center; margin-top: 100px;">Loading Settings..</h3></div>';
    }

    private function getMenuIcon()
    {
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 182.8 203.1"><defs><style>.cls-1{fill:#fff;}</style></defs><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M182.75,23.08S171.7,19.83,140.16,11.7C106.78,3.1,91.4,0,91.4,0S76,3.1,42.64,11.7C11.11,19.83.06,23.08.06,23.08s-1.63,32.5,12.68,91.34c11.54,47.5,54.61,73.79,78.66,88.68,24.06-14.89,67.12-41.18,78.67-88.68C184.37,55.58,182.75,23.08,182.75,23.08ZM90.89,125.68,39.63,139.41V128a17,17,0,0,1,12.58-16.39l62.3-16.71A31.9,31.9,0,0,1,90.89,125.68Zm46.66-50.45L39.63,101.46V90a17,17,0,0,1,12.58-16.4l109-29.2A31.94,31.94,0,0,1,137.55,75.23Z"/></g></g></svg>');
    }
}
