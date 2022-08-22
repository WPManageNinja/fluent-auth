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

        add_action('login_form', [$this, 'pushLoginPassCodeField'], 10);
        add_filter('authenticate', [$this, 'checkLoginPassCode'], 999, 3);

        add_filter('lostpassword_errors', [$this, 'maybeBlockPasswordReset'], 10, 2);

        // Remove Application Password Login
        remove_filter('authenticate', 'wp_authenticate_application_password', 20);

        // Disable xmlrpc
        add_filter('xmlrpc_enabled', function ($status) {
            if (!$status || $this->getSetting('disable_xmlrpc') == 'yes') {
                return false;
            }
            return $status;
        });

        add_action('show_user_profile', [$this, 'addUserMetaField'], 10, 1);
        add_action('edit_user_profile', [$this, 'addUserMetaField'], 10, 1);

        add_action('personal_options_update', [$this, 'updateUserPassCode']);
        add_action('edit_user_profile_update', [$this, 'updateUserPassCode']);

        add_action('wp_login_failed', [$this, 'logFailedAuth'], 10, 2);

        // Admin Menu Init
        (new \FluentSecurity\Classes\AdminMenuHandler())->register();

        register_activation_hook(__FILE__, [$this, 'installDbTables']);

        load_plugin_textdomain('fluent-security', false, dirname(plugin_basename(__FILE__)) . '/language');
    }

    public function addUserMetaField($user)
    {
        if (!$this->getGlobalLoginPassCode()) {
            return;
        }
        $passCode = get_user_meta($user->ID, '__login_passcode', true);;
        ?>
        <div style="margin-top: 20px;" class="form-field">
            <label style="font-weight: bold;"
                   for="fluent_security_passcode"><?php echo __('Security Passcode', 'fluent-security'); ?></label>
            <input style="max-width: 300px; display: block;" type="text" size="30" required
                   id="fluent_security_passcode" value="<?php echo esc_attr($passCode); ?>"
                   name="fluent_security_passcode" class="input" aria-required="true">
            <p class="description" id="fluent_security_passcode">
                <?php echo __('Login security passcode which is required when you login to this site', 'fluent-security'); ?>
            </p>
        </div>
        <?php
    }

    public function updateUserPassCode($userId)
    {
        if (!$this->getGlobalLoginPassCode()) {
            return;
        }

        if (isset($_POST['fluent_security_passcode'])) {
            $passCode = sanitize_text_field($_POST['fluent_security_passcode']);
            update_user_meta($userId, '__login_passcode', $passCode);
        }
    }

    public function pushLoginPassCodeField()
    {
        if (!$this->getGlobalLoginPassCode()) {
            return;
        }
        ?>
        <div class="user-pass-wrap">
            <label for="login_passcode"><?php echo __('Security Passcode', 'fluent-security'); ?></label>
            <div class="wp-pwd">
                <input style="font-size: 14px;" placeholder="<?php echo __('Security Passcode', 'fluent-security'); ?>"
                       type="password" name="login_passcode" id="login_passcode" class="input" value="" size="20"/>
            </div>
        </div>
        <?php
    }

    /**
     * @param $user \WP_User | \WP_Error
     * @param $username
     * @param $password
     * @return bool|mixed|\WP_Error
     */
    public function checkLoginPassCode($user, $username, $password)
    {
        if (empty($_POST) && !$username) {
            return $user;
        }

        $isLimitExceeded = $this->checkLoginAttempt($user, $username);
        if (is_wp_error($isLimitExceeded)) {
            $this->logBlockedAuth($user, $username);
            return $isLimitExceeded;
        }

        if (is_wp_error($user)) {
            $errorCode = $user->get_error_code();
            if ($errorCode == 'invalid_username' || $errorCode == 'incorrect_password') {
                return new WP_Error(
                    $errorCode,
                    __('<strong>Error</strong>: The username or the password is invalid. Please try different combination.', 'fluent-security')
                );
            }
            return $user;
        }

        $globalPasscode = $this->getGlobalLoginPassCode();
        if (!$globalPasscode) {
            $this->logAuthSuccess($user);
            return $user;
        }

        if (empty($_POST['login_passcode'])) {
            return new \WP_Error('invalid_passcode', __('Login Passcode is required', 'fluent-security'));
        }

        $secureCode = sanitize_text_field($_POST['login_passcode']);
        $userPasscode = $this->getUserLoginPassCode($user);

        if ($userPasscode) {
            if ($userPasscode === $secureCode) {
                $this->logAuthSuccess($user);
                return $user;
            }
            return new \WP_Error('invalid_passcode', __('Login Passcode verification failed', 'fluent-security'));
        }

        if ($globalPasscode === $secureCode) {
            $this->logAuthSuccess($user);
            return $user;
        }

        return new \WP_Error('invalid_passcode', __('Login Passcode verification failed', 'fluent-security'));
    }

    private function checkLoginAttempt($user, $userName)
    {
        $minutes = $this->getSetting('login_try_timing');
        $limit = $this->getSetting('login_try_limit');

        if (!$minutes || !$limit) {
            return true;
        }

        global $wpdb;
        $ip = $this->getIp();
        $dateTime = date('Y-m-d H:i:s', current_time('timestamp') - $minutes * 60);

        // check if already blocked then no need to create a new row
        $blocked = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}fls_auth_logs WHERE `ip` = %s AND `created_at` > %s AND `status` = 'blocked' LIMIT 1", $ip, $dateTime));

        if ($blocked) {
            $wpdb->update($wpdb->prefix . 'fls_auth_logs', [
                'created_at' => current_time('mysql'),
                'count'      => $blocked->count + 1
            ], [
                'id' => $blocked->id
            ]);
        } else {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fls_auth_logs WHERE `ip` = %s AND `created_at` > %s AND `status` IN ('failed','blocked')", $ip, $dateTime));

            if (!$count || $limit >= $count) {
                return true;
            }
        }

        return new \WP_Error('login_error', sprintf(__('You are trying too much. Please try after %d minutes', 'fluent-security'), $minutes));
    }

    private function getGlobalLoginPassCode()
    {
        if ($this->getSetting('extended_auth_security_type') != 'pass_code') {
            return false;
        }

        if (defined('FLUENT_SECURITY_LOGIN_CODE')) {
            return FLUENT_SECURITY_LOGIN_CODE;
        }

        return apply_filters('fluent_security/global_login_passcode', $this->getSetting('global_auth_code'));
    }

    private function getUserLoginPassCode($user)
    {
        return apply_filters('fluent_security/user_login_passcode', get_user_meta($user->ID, '__login_passcode', true), $user);
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

    /**
     * @param $username
     * @param $error \WP_Error
     * @return void
     */
    public function logFailedAuth($username, $error)
    {
        if (!$this->getSetting('enable_auth_logs') || $this->failedLogged) {
            return;
        }

        global $wpdb;

        $byField = 'login';
        if (is_email($username)) {
            $byField = 'email';
        }

        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $user = get_user_by($byField, $username);

        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $data = [
            'username'    => $username,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'agent'       => sanitize_text_field($userAgent),
            'ip'          => $this->getIp(),
            'error_code'  => $error->get_error_code(),
            'description' => $error->get_error_message(),
            'browser'     => $browserDetection->getBrowser($userAgent)['browser_name'],
            'device_os'   => $browserDetection->getOS($userAgent)['os_family'],
            'status'      => 'failed',
            'count'       => 1
        ];

        if ($user) {
            $data['user_id'] = $user->ID;
        }

        $wpdb->insert("{$wpdb->prefix}fls_auth_logs", $data);

        $this->failedLogged = true;
    }

    /**
     * @param $user \WP_User
     * @return void
     */
    public function logBlockedAuth($user, $username)
    {
        global $wpdb;

        $agent = $_SERVER['HTTP_USER_AGENT'];

        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $data = [
            'username'    => $username,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'agent'       => sanitize_text_field($agent),
            'ip'          => $this->getIp(),
            'error_code'  => 'blocked',
            'browser'     => $browserDetection->getBrowser($agent)['browser_name'],
            'device_os'   => $browserDetection->getOS($agent)['os_family'],
            'description' => 'Blocked by Fluent Security',
            'status'      => 'blocked',
            'count'       => 1
        ];

        if (!is_wp_error($user)) {
            $data['user_id'] = $user->ID;
        }

        $wpdb->insert("{$wpdb->prefix}fls_auth_logs", $data);

        $this->failedLogged = true;
    }

    /**
     * @param $user \WP_User
     * @return void
     */
    public function logAuthSuccess($user)
    {
        if (!$this->getSetting('enable_auth_logs')) {
            return;
        }
        global $wpdb;

        $agent = $_SERVER['HTTP_USER_AGENT'];

        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();


        $data = [
            'username'    => $user->user_login,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'agent'       => sanitize_text_field($agent),
            'ip'          => $this->getIp(),
            'browser'     => $browserDetection->getBrowser($agent)['browser_name'],
            'device_os'   => $browserDetection->getOS($agent)['os_family'],
            'description' => '',
            'status'      => 'success',
            'user_id'     => $user->ID
        ];

        $wpdb->insert("{$wpdb->prefix}fls_auth_logs", $data);
    }

    public function getIp($anonymize = false)
    {
        // Get real visitor IP behind CloudFlare network
        // https://stackoverflow.com/questions/13646690/how-to-get-real-ip-from-visitor
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

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

    public function maybeBlockPasswordReset($errors, $userData)
    {
        $minutes = $this->getSetting('login_try_timing');
        $limit = $this->getSetting('login_try_limit');

        if (!$minutes || !$limit) {
            return $errors;
        }

        global $wpdb;
        $ip = $this->getIp();
        $dateTime = date('Y-m-d H:i:s', current_time('timestamp') - $minutes * 60);
        
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fls_auth_logs WHERE `ip` = %s AND `created_at` > %s AND `status` IN ('failed','blocked')", $ip, $dateTime));

        if (!$count || $limit >= $count) {
            return $errors;
        }

        return new \WP_Error('blocked', sprintf(__('You are blocked for next %d minutes. Please try after that time'), $minutes));
    }

    protected function getConfig()
    {
        static $config;

        if ($config) {
            return $config;
        }

        $config = apply_filters('fluent_security/config', \FluentSecurity\Helpers\Helper::getAuthSettings());

        return $config;
    }

    protected function getSetting($key, $default = false)
    {
        $config = $this->getConfig();
        if (isset($config[$key])) {
            return $config[$key];
        }

        return $default;
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

        add_action('rest_api_init', function () {
            require_once FLUENT_SECURITY_PLUGIN_PATH . 'app/routes.php';
        });
    }
}

(new FluentSecurityPlugin())->init();
