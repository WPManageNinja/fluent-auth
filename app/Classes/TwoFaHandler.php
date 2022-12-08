<?php

namespace FluentSecurity\Classes;

use FluentSecurity\Helpers\Arr;
use FluentSecurity\Helpers\Helper;

class TwoFaHandler
{
    public function register()
    {
        add_action('fluent_security/login_attempts_checked', [$this, 'maybe2FaRedirect'], 1, 1);
        add_action('login_form_fls_2fa_email', [$this, 'render2FaForm'], 1);
        add_action('wp_ajax_nopriv_fluent_security_2fa_email', [$this, 'verify2FaEmailCode']);
    }

    public function render2FaForm()
    {
        if (!isset($_GET['fls_2fa']) || $_GET['fls_2fa'] != 'email') {
            return;
        }

        login_header(__('Provide Login Code'), '', false);
        do_action('fls_load_login_helper');
        ?>
        <form
            style="margin-top: 20px;margin-left: 0;padding: 26px 24px 34px;font-weight: 400;overflow: hidden;background: #fff;border: 1px solid #c3c4c7;box-shadow: 0 1px 3px rgb(0 0 0 / 4%);"
            class="fls_2fs" id="fls_2fa_form">
            <input type="hidden" name="login_hash" value="<?php echo esc_attr(Arr::get($_REQUEST, 'login_hash')); ?>"/>
            <input type="hidden" name="redirect_to"
                   value="<?php echo esc_url_raw(Arr::get($_REQUEST, 'redirect_to')); ?>"/>
            <div class="user-pass-wrap">
                <p style="margin-bottom: 20px;"><?php _e('Please check your email inbox and get the 2 factor Authentication code and Provide here to login', 'fluent-security'); ?></p>
                <label for="login_passcode"><?php _e('Two-Factor Authentication Code', 'fluent-security'); ?></label>
                <div class="wp-pwd">
                    <input style="font-size: 14px;" placeholder="<?php _e('Login Code', 'fluent-security'); ?>"
                           type="text"
                           value="<?php echo (isset($_REQUEST['auto_code'])) ? esc_attr($_REQUEST['auto_code']) : ''; ?>"
                           name="login_passcode" id="login_passcode" class="input" size="20"/>
                </div>
                <div>
                    <button
                        style="display: block; cursor: pointer; width: 100%;border: 1px solid #2271b1;background: #2271b1;color: #fff;text-decoration: none;text-shadow: none;min-height: 32px;line-height: 2.30769231;padding: 4px 12px;font-size: 13px;border-radius: 3px;"
                        type="submit">
                        <?php _e('Login', ''); ?>
                    </button>
                </div>
            </div>
        </form>
        <?php
        login_footer();
        exit();
    }

    public function maybe2FaRedirect($user)
    {
        // If it's an ajax call and not our own ajax calls then we will just return it
        // Until we get a better work-around for other plugins
        if(wp_doing_ajax() && empty($_REQUEST['_is_fls_form'])) {
            return;
        }

        $twoFaCode = mt_rand(100123, 900987);

        $string = $user->ID . '-' . wp_generate_uuid4() . mt_rand(1, 99999999);
        $hash = wp_hash_password($string);
        $hash = sanitize_title($hash, '', 'display');
        $hash .= $user->ID . '-' . time();

        $redirectIntend = '';
        if (isset($_GET['redirect_to'])) {
            $redirectIntend = esc_url($_GET['redirect_to']);
        }

        $data = array(
            'login_hash'      => $hash,
            'user_id'         => $user->ID,
            'status'          => 'issued',
            'ip_address'      => Helper::getIp(),
            'redirect_intend' => $redirectIntend,
            'use_type'        => 'email_2_fa',
            'two_fa_code'     => $twoFaCode,
            'valid_till'      => date('Y-m-d H:i:s', current_time('timestamp') + 10 * 60),
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql')
        );

        flsDb()->table('fls_login_hashes')
            ->insert($data);

        $autoLoginUrl = add_query_arg([
            'fls_2fa'    => 'email',
            'login_hash' => $hash,
            'action'     => 'fls_2fa_email',
            'auto_code'  => $data['two_fa_code']
        ], wp_login_url());

        $this->send2FaEmail($data, $user, $autoLoginUrl);

        $loginUrl = add_query_arg([
            'fls_2fa'    => 'email',
            'login_hash' => $hash,
            'action'     => 'fls_2fa_email'
        ], wp_login_url());

        if(wp_doing_ajax()) {
            wp_send_json([
                'redirect' => $loginUrl
            ]);
        }

        wp_redirect($loginUrl);
        exit();
    }

    public function verify2FaEmailCode()
    {
        $code = sanitize_text_field(Arr::get($_REQUEST, 'login_passcode'));
        $hash = sanitize_text_field(Arr::get($_REQUEST, 'login_hash'));

        $logHash = flsDb()->table('fls_login_hashes')
            ->where('login_hash', $hash)
            ->where('use_type', 'email_2_fa')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$logHash) {
            wp_send_json_error([
                'message' => __('Your provided code or url is not valid', 'fluent-security')
            ], 423);
        }

        if ($logHash->two_fa_code != $code) {
            flsDb()->table('fls_login_hashes')
                ->where('id', $logHash->id)
                ->update([
                    'used_count' => $logHash->used_count + 1
                ]);
            wp_send_json_error([
                'message' => __('Your provided code is not valid. Please try again', 'fluent-security')
            ], 423);
        }

        $user = get_user_by('ID', $logHash->user_id);

        if (strtotime($logHash->created_at) < current_time('timestamp') - 600 || !$user || $logHash->status != 'issued' || $logHash->used_count > 5) {
            wp_send_json_error([
                'message' => __('Sorry, your login code has been expired. Please try to login again', 'fluent-security')
            ], 423);
        }

        remove_action('fluent_security/login_attempts_checked', [$this, 'maybe2FaRedirect'], 1);

        add_filter('authenticate', array($this, 'allowProgrammaticLogin'), 10, 3);    // hook in earlier than other callbacks to short-circuit them
        $user = wp_signon(array('user_login' => $user->user_login));
        remove_filter('authenticate', array($this, 'allowProgrammaticLogin'), 10, 3);

        if ($user instanceof \WP_User) {
            wp_set_current_user($user->ID, $user->user_login);
            if (is_user_logged_in()) {
                flsDb()->table('fls_login_hashes')
                    ->where('id', $logHash->id)
                    ->update([
                        'status'             => 'used',
                        'success_ip_address' => Helper::getIp()
                    ]);

                $redirectTo = $logHash->redirect_intend;
                if (!$redirectTo) {
                    $redirectTo = admin_url();
                }

                $redirectTo = apply_filters('login_redirect', $redirectTo, $logHash->redirect_intend, $user);
                wp_send_json([
                    'redirect' => $redirectTo
                ]);
            }
        }

        wp_send_json_error([
            'message' => __('There has an error when log you in. Please try to login again', 'fluent-security')
        ]);
    }


    private function send2FaEmail($data, $user, $autoLoginUrl = false)
    {
        $emailSubject = sprintf(__('Your Login code for %s', 'fluent-security'), get_bloginfo('name'));

        $emailLines = [
            sprintf(__('Hello %s,', 'fluent-security'), $user->display_name),
            sprintf(__('Someone requested to login to %s and here is the Login code that you can use in the login form', 'fluent-security'), get_bloginfo('name')),
            '<b>' . __('Your Login Code: ', 'fluent-security') . '</b>',
            '<p style="font-size: 22px;border: 1px dashed #555454;padding: 5px 10px;text-align: center;background: #fffaca;letter-spacing: 7px;color: #555454;display:block;">' . $data['two_fa_code'] . '</p>',
            sprintf(__('This code will expire in %d minutes and can only be used once.', 'fluent-security'), 10),
            ' ',
            '<hr />'
        ];

        $callToAction = false;

        if ($autoLoginUrl) {
            $emailLines[] = ' ';
            $emailLines[] = __('You can also login by clicking the following button', 'fluent-security');
            $callToAction = [
                'btn_text' => sprintf(__('Sign in to %s', 'fluent-security'), get_bloginfo('name')),
                'url'      => $autoLoginUrl
            ];
        }


        $footerLines = [
            ' ',
            __('If you did not make this request, you can safely ignore this email.', 'fluent-security')
        ];

        $emailBody = '';
        $emailBody .= Helper::loadView('magic_login.header', [
            'pre_header' => $emailSubject
        ]);

        $emailBody .= Helper::loadView('magic_login.line_block', [
            'lines' => $emailLines
        ]);

        if ($callToAction) {
            $emailBody .= Helper::loadView('magic_login.call_to_action', $callToAction);
        }

        $emailBody .= Helper::loadView('magic_login.line_block', [
            'lines' => $footerLines
        ]);

        $emailBody .= Helper::loadView('magic_login.footer', []);

        return wp_mail($user->user_email, $emailSubject, $emailBody, array(
            'Content-Type: text/html; charset=UTF-8'
        ));

    }

    public function allowProgrammaticLogin($user, $username, $password)
    {
        return get_user_by('login', $username);
    }
}