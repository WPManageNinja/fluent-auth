<?php

namespace FluentAuth\App\Hooks\Handlers;

use FluentAuth\App\Helpers\Helper;

class BasicTasksHandler
{
    public function register()
    {
        // Maybe Remove Application Password Login
        add_filter('wp_is_application_passwords_available', [$this, 'maybeDisableAppPassword']);

        // Disable xmlrpc
        add_filter('xmlrpc_enabled', [$this, 'maybeDisableXmlRpc']);

        // Maybe disable List Users REST
        add_filter('rest_user_query', [$this, 'maybeInterceptRestUserQuery']);

        // Maybe disable List Users REST
        add_filter('rest_user_query', [$this, 'maybeInterceptRestUserQuery']);
        add_filter('rest_prepare_user', [$this, 'maybeInterceptRestUserResponse'], 10, 3);

        add_action('admin_notices', [$this, 'maybeAddAdminNotice']);

        /*
         * Clean Up Old Logs
         */
        add_action('fluent_auth_daily_tasks', function () {
            \FluentAuth\App\Helpers\Helper::cleanUpLogs();
        });
        
        /*
         * Maybe Disable Admin Bar
         */
        add_filter('show_admin_bar', function ($status) {
            if (!$status) {
                return $status;
            }

            if (is_admin()) {
                return $status;
            }

            if (Helper::getSetting('disable_admin_bar') !== 'yes') {
                return $status;
            }

            $roles = Helper::getSetting('disable_bar_roles');

            $user = get_user_by('ID', get_current_user_id());

            if (!$user || !$roles) {
                return $status;
            }

            if (array_intersect($roles, array_values($user->roles)) && !current_user_can('publish_posts')) {
                return false;
            }

            return $status;
        });

        /*
        * Maybe Redirect Non-Admin Users
        */
        add_action('admin_init', function () {

            if (Helper::getSetting('disable_admin_bar') !== 'yes' || wp_doing_ajax()) {
                return false;
            }

            $roles = Helper::getSetting('disable_bar_roles');

            $user = get_user_by('ID', get_current_user_id());

            if (!$user || !$roles) {
                return false;
            }

            if (array_intersect($roles, array_values($user->roles)) && !current_user_can('publish_posts')) {
                wp_safe_redirect(home_url()); // Replace this with the URL to redirect to.
                exit;
            }

        }, 100);

    }

    public function maybeDisableAppPassword($status)
    {
        if (!$status || Helper::getSetting('disable_app_login') === 'yes') {
            return false;
        }
        return $status;
    }

    public function maybeDisableXmlRpc($status)
    {
        if (!$status || Helper::getSetting('disable_xmlrpc') === 'yes') {
            return false;
        }

        return $status;
    }

    public function maybeInterceptRestUserQuery($query)
    {
        if (Helper::getSetting('disable_users_rest') === 'yes' && !current_user_can('list_users')) {
            $query['login'] = 'someRandomStringForThis_' . time();
        }

        return $query;
    }

    public function maybeInterceptRestUserResponse($response, $user, $request)
    {
        if (!empty($request['id']) && Helper::getSetting('disable_users_rest') === 'yes' && !current_user_can('list_users')) {
            return new \WP_Error('permission_error', 'You do not have access to list users. Restriction added from fluent auth plugin');
        }
        return $response;
    }

    public function maybeAddAdminNotice()
    {
        if (get_option('__fls_auth_settings') || !current_user_can('manage_options')) {
            return '';
        }

        $url = admin_url('options-general.php?page=fluent-auth#/settings');

        ?>
        <div style="padding-bottom: 10px;" class="notice notice-warning">
            <p><?php echo sprintf(__('Thank you for installing %s Plugin. Please configure the security settings to enable enhanced security of your site', 'fluent-security'), '<b>FluentAuth</b>'); ?></p>
            <a href="<?php echo esc_url($url); ?>"><?php _e('Configure Fluent Auth', 'fluent-security'); ?></a>
        </div>
        <?php
    }

}
