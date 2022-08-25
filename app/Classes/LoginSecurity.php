<?php

namespace FluentSecurity\Classes;

use FluentSecurity\Helpers\Helper;

class LoginSecurity
{
    private $failedLogged = false;

    public function init()
    {
        add_action('login_form', [$this, 'pushLoginPassCodeField'], 10);
        add_filter('authenticate', [$this, 'checkLoginPassCode'], 999, 3);
        add_filter('lostpassword_errors', [$this, 'maybeBlockPasswordReset'], 10, 2);

        add_action('show_user_profile', [$this, 'addUserMetaField'], 10, 1);
        add_action('edit_user_profile', [$this, 'addUserMetaField'], 10, 1);

        add_action('personal_options_update', [$this, 'updateUserPassCode']);
        add_action('edit_user_profile_update', [$this, 'updateUserPassCode']);

        add_action('wp_login_failed', [$this, 'logFailedAuth'], 10, 2);
    }

    public function pushLoginPassCodeField()
    {
        if (!Helper::getGlobalLoginPassCode()) {
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
                return new \WP_Error(
                    $errorCode,
                    __('<strong>Error</strong>: The username or the password is invalid. Please try different combination.', 'fluent-security')
                );
            }
            return $user;
        }

        $globalPasscode = Helper::getGlobalLoginPassCode();
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

    /**
     * @param $errors \WP_Error
     * @param $userData array
     * @return mixed|\WP_Error
     */
    public function maybeBlockPasswordReset($errors, $userData)
    {
        $minutes = Helper::getSetting('login_try_timing');
        $limit = Helper::getSetting('login_try_limit');

        if (!$minutes || !$limit) {
            return $errors;
        }

        global $wpdb;
        $ip = Helper::getIp();
        $dateTime = date('Y-m-d H:i:s', current_time('timestamp') - $minutes * 60);

        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fls_auth_logs WHERE `ip` = %s AND `created_at` > %s AND `status` IN ('failed','blocked')", $ip, $dateTime));

        if (!$count || $limit >= $count) {
            return $errors;
        }

        return new \WP_Error('blocked', sprintf(__('You are blocked for next %d minutes. Please try after that time'), $minutes));
    }

    /**
     * @param $user \WP_User
     * @return void
     */
    public function addUserMetaField($user)
    {
        if (!Helper::getGlobalLoginPassCode()) {
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

    /**
     * @param $userId int
     * @return void
     */
    public function updateUserPassCode($userId)
    {
        if (!Helper::getGlobalLoginPassCode()) {
            return;
        }

        if (isset($_POST['fluent_security_passcode'])) {
            $passCode = sanitize_text_field($_POST['fluent_security_passcode']);
            update_user_meta($userId, '__login_passcode', $passCode);
        }
    }

    /**
     * @param $username string
     * @param $error \WP_Error
     * @return void
     */
    public function logFailedAuth($username, $error)
    {
        if ($this->failedLogged || !Helper::getSetting('enable_auth_logs')) {
            return;
        }

        global $wpdb;

        $byField = 'login';
        if (is_email($username)) {
            $byField = 'email';
        }

        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $user = get_user_by($byField, $username);

        $userAgent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);

        $data = [
            'username'    => $username,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'agent'       => sanitize_text_field($userAgent),
            'ip'          => Helper::getIp(),
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
    private function logAuthSuccess($user)
    {
        if (!Helper::getSetting('enable_auth_logs')) {
            return;
        }

        global $wpdb;

        $agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);

        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $data = [
            'username'    => $user->user_login,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'agent'       => sanitize_text_field($agent),
            'ip'          => Helper::getIp(),
            'browser'     => $browserDetection->getBrowser($agent)['browser_name'],
            'device_os'   => $browserDetection->getOS($agent)['os_family'],
            'description' => '',
            'status'      => 'success',
            'user_id'     => $user->ID
        ];

        $wpdb->insert("{$wpdb->prefix}fls_auth_logs", $data);

        do_action('fluent_security/user_login_success', $user);

        $this->maybeSendSuccessEmail($user);

    }

    /**
     * @param $user \WP_User
     * @return void
     */
    private function logBlockedAuth($user, $username)
    {
        global $wpdb;

        $agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);

        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $data = [
            'username'    => $username,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
            'agent'       => sanitize_text_field($agent),
            'ip'          => Helper::getIp(),
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

       $this->maybeSendBlockedEmail($user, $username);
    }

    private function checkLoginAttempt($user, $userName)
    {
        $minutes = Helper::getSetting('login_try_timing');
        $limit = Helper::getSetting('login_try_limit');

        if (!$minutes || !$limit) {
            return true;
        }

        global $wpdb;
        $ip = Helper::getIp();
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

    private function getUserLoginPassCode($user)
    {
        return apply_filters('fluent_security/user_login_passcode', get_user_meta($user->ID, '__login_passcode', true), $user);
    }

    /**
     * @param $user \WP_User
     * @return bool
     */
    private function maybeSendSuccessEmail($user)
    {
        $notificationUserRoles = Helper::getSetting('notification_user_roles');
        if (!$notificationUserRoles || !array_intersect($notificationUserRoles, (array)$user->roles)) {
            return false;
        }

        $adminEmail = Helper::getSetting('notification_email');
        if (!$adminEmail) {
            return false;
        }

        $adminEmail = str_replace('{admin_email}', get_bloginfo('admin_email'), $adminEmail);
        if (!$adminEmail) {
            return false;
        }

        $userEditLInk = add_query_arg('user_id', $user->ID, self_admin_url('user-edit.php'));

        $agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $ip = Helper::getIp();
        $infoHtml = '<ul style="padding-left: 20px; list-style: disc; line-height: 25px; font-size: 16px;">';
        $infoHtml .= '<li><b>Site URL:</b> <a href="' . site_url() . '">' . site_url() . '</a></li>';
        $infoHtml .= '<li><b>Username:</b> <a href="' . $userEditLInk . '">' . $user->user_login . '</a></li>';
        $infoHtml .= '<li><b>Email:</b> ' . $user->user_email . '</li>';
        $infoHtml .= '<li><b>Name:</b> ' . $user->first_name . ' ' . $user->last_name . '</li>';
        $infoHtml .= '<li><b>Login IP Address:</b> <a href="https://ipinfo.io/' . $ip . '">' . $ip . '</a></li>';
        $infoHtml .= '<li><b>Browser:</b> ' . $browserDetection->getOS($agent)['os_family'] . ' / ' . $browserDetection->getBrowser($agent)['browser_name'] . '</li>';
        $infoHtml .= '</ul>';

        $lines = [
            '<p style="font-size: 16px; line-height: 25px;">Hello there, <br />The following user has been logged in to your site. Here is the details:</p>',
            $infoHtml
        ];

        $siteName = get_bloginfo('name');
        $data = [
            'body'       => implode('', $lines),
            'pre_header' => 'Login success at ' . $siteName
        ];

        $body = Helper::loadView('notification', $data);
        $subject = '[' . $siteName . '] Login success for ' . $user->user_login;

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($adminEmail, $subject, $body, $headers);
    }

    /**
     * @param $user \WP_User | \WP_Error
     * @param $userName string
     * @return void
     */
    private function maybeSendBlockedEmail($user, $userName)
    {
        if (Helper::getSetting('notify_on_blocked') != 'yes') {
            return false;
        }

        $adminEmail = Helper::getSetting('notification_email');
        if (!$adminEmail) {
            return false;
        }

        $adminEmail = str_replace('{admin_email}', get_bloginfo('admin_email'), $adminEmail);
        if (!$adminEmail) {
            return false;
        }

        $agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        $browserDetection = new \FluentSecurity\Helpers\BrowserDetection();

        $ip = Helper::getIp();
        $infoHtml = '<ul style="padding-left: 20px; list-style: disc; line-height: 25px; font-size: 16px;">';
        $infoHtml .= '<li><b>Site URL:</b> <a href="' . site_url() . '">' . site_url() . '</a></li>';
        $infoHtml .= '<li><b>Username:</b> ' . $userName . '</li>';
        $infoHtml .= '<li><b>Login IP Address:</b> <a href="https://ipinfo.io/' . $ip . '">' . $ip . '</a></li>';
        $infoHtml .= '<li><b>Browser:</b> ' . $browserDetection->getOS($agent)['os_family'] . ' / ' . $browserDetection->getBrowser($agent)['browser_name'] . '</li>';

        if (is_wp_error($user)) {
            $infoHtml .= '<li>' . $user->get_error_message() . '</li>';
        } else if($user instanceof \WP_User) {
            $userEditLInk = add_query_arg('user_id', $user->ID, self_admin_url('user-edit.php'));
            $infoHtml .= '<li><b>Username:</b> <a href="' . $userEditLInk . '">' . $user->user_login . '</a></li>';
            $infoHtml .= '<li><b>Email:</b> ' . $user->user_email . '</li>';
            $infoHtml .= '<li><b>Name:</b> ' . $user->first_name . ' ' . $user->last_name . '</li>';
        }
        $infoHtml .= '</ul>';

        $lines = [
            '<p style="font-size: 16px; line-height: 25px;">Hello there, <br />The following user has been blocked from logged in from your site. Here is the details:</p>',
            $infoHtml
        ];

        $siteName = get_bloginfo('name');
        $data = [
            'body'       => implode('', $lines),
            'pre_header' => 'Blocked from login ' . $siteName
        ];

        $body = Helper::loadView('notification', $data);
        $subject = '[' . $siteName . '] Blocked from login - ' . $userName;

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($adminEmail, $subject, $body, $headers);
    }

}
