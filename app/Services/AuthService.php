<?php

namespace FluentSecurity\Services;

use FluentSecurity\Classes\LoginSecurity;
use FluentSecurity\Helpers\Arr;

class AuthService
{
    public static function doUserAuth($userData, $provider = '')
    {
        if (get_current_user_id()) {
            return new \WP_Error('already_logged_in', 'You are already logged in. Please refresh the page');
        }

        if (empty($userData['email']) || !is_email($userData['email'])) {
            return new \WP_Error('invalid_email', 'Valid Email address is required');
        }

        $email = $userData['email'];

        $userExist = get_user_by('email', $email);

        if ($userExist) {
            self::maybeUpdateUser($userExist, $userData);
            return self::makeLogin($userExist, $provider);
        }
        // let's create the user here
        $createUserData = [
            'email'    => $userData['email'],
            'password' => wp_generate_password(8),
            'username' => sanitize_user($userData['email'])
        ];

        if (!empty($userData['username'])) {
            if (username_exists($userData['username'])) {
                $createUserData['username'] = sanitize_user($userData['username']);
            }
        }

        $userId = wp_create_user($createUserData['username'], $createUserData['password'], $createUserData['email']);

        if (is_wp_error($userId)) {
            return $userId;
        }

        $user = get_user_by('ID', $userId);

        self::maybeUpdateUser($user, $userData);

        $defaultRole = get_option('default_role');
        if (!$defaultRole || $defaultRole == 'administrator') {
            $defaultRole = 'subscriber';
        }

        $setRole = apply_filters('fluent_auth/user_role', $defaultRole);
        $user->set_role($setRole);

        self::makeLogin($user, $provider);

        return get_user_by('ID', $userId);
    }

    private static function maybeUpdateUser($user, $userData)
    {
        $updateData = [];

        if (!empty($userData['user_url']) && !$user->user_url) {
            $updateData['user_url'] = $userData['user_url'];
        }

        if (!empty($userData['description']) && !$user->description) {
            $updateData['description'] = $userData['description'];
        }

        if (!$user->first_name || !$user->last_name) {
            if (!empty($userData['full_name'])) {
                // extract the names
                $fullNameArray = explode(' ', $userData['full_name']);
                $updateData['first_name'] = array_shift($fullNameArray);
                if ($fullNameArray) {
                    $updateData['last_name'] = implode(' ', $fullNameArray);
                } else {
                    $updateData['last_name'] = '';
                }
            }

            if (!empty($userData['first_name'])) {
                $updateData['first_name'] = $userData['first_name'];
            }

            if (!empty($userData['last_name'])) {
                $updateData['last_name'] = $userData['last_name'];
            }

        }

        if (!empty($userData)) {
            $updateData['ID'] = $user->ID;
            wp_update_user($updateData);
        }
    }

    public static function makeLogin($user, $provider = '')
    {
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);

        $user = get_user_by('ID', $user->ID);
        if($user) {
            (new LoginSecurity())->logAuthSuccess($user, $provider);
        }

        return $user;
    }

    public static function setStateToken()
    {
        $state = md5(wp_generate_uuid4());
        setcookie('fs_auth_state', $state, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);  /* expire in 1 hour */
        return $state;
    }

    public static function getStateToken()
    {
        return Arr::get($_COOKIE, 'fs_auth_state');
    }
}
