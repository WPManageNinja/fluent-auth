<?php

namespace FluentAuth\App\Hooks\Handlers;

use FluentAuth\App\Helpers\Arr;
use FluentAuth\App\Helpers\Helper;
use FluentAuth\App\Services\Libs\Emogrifier\Emogrifier;
use FluentAuth\App\Services\SmartCodeParser;
use FluentAuth\App\Services\SystemEmailService;

class WPSystemEmailHandler
{

    private $tempEmailSubjectForEmailChange = '';

    public function register()
    {
        add_filter('fluent_auth/parse_smartcode', function ($code, $user) {
            return (new SmartCodeParser())->parse($code, $user);
        });

        add_filter('retrieve_password_notification_email', [$this, 'maybeAlterPasswordResetEmail'], 99, 4);

        add_filter('wp_new_user_notification_email', [$this, 'maybeAlterUserRegistrationEmail'], 99, 3);

        add_filter('new_user_email_content', [$this, 'maybeAlterEmailChangeNotificationEmailToUser'], 99, 2);
    }


    public function maybeAlterPasswordResetEmail($defaults, $key, $user_login, $user_data)
    {
        $setting = SystemEmailService::getEmailSettingsByType('password_reset_to_user');

        if (!$setting || Arr::get($setting, 'status', '') !== 'active') {
            return $defaults;
        }

        $user_data->_password_reset_key_ = $key;

        // Let's change these now
        $email = Arr::get($setting, 'email', []);

        $defaults['subject'] = $this->parseCode(Arr::get($email, 'subject', $defaults['subject']), $user_data);

        $defaults['message'] = $this->withHtmlTemplate($this->parseCode(Arr::get($email, 'body', $defaults['message']), $user_data), '', $user_data);

        if (!is_array($defaults['headers'])) {
            $defaults['headers'] = [];
        }

        $defaults['headers'][] = 'Content-Type: text/html; charset=UTF-8';

        return $defaults;
    }

    public function maybeAlterUserRegistrationEmail($defaults, $user, $blogname)
    {
        $setting = SystemEmailService::getEmailSettingsByType('user_registration_to_user');

        if (!$setting || Arr::get($setting, 'status', '') !== 'active') {
            return $defaults;
        }

        // Let's change these now
        $email = Arr::get($setting, 'email', []);
        $defaults['subject'] = $this->parseCode(Arr::get($email, 'subject', $defaults['subject']), $user);
        $defaults['message'] = $this->withHtmlTemplate($this->parseCode(Arr::get($email, 'body', $defaults['message']), $user), '', $user);

        if (!is_array($defaults['headers'])) {
            $defaults['headers'] = [];
        }

        $defaults['headers'][] = 'Content-Type: text/html; charset=UTF-8';

        return $defaults;
    }

    public function maybeAlterEmailChangeNotificationEmailToUser($emailBody, $newEmail)
    {
        $wpUser = wp_get_current_user();
        $setting = SystemEmailService::getEmailSettingsByType('email_change_notification_to_user');

        if (!$setting || Arr::get($setting, 'status', '') !== 'active') {
            return $emailBody;
        }

        // Let's change these now
        $email = Arr::get($setting, 'email', []);
        $emailSubject = $this->parseCode(Arr::get($email, 'subject', ''), $wpUser);
        $newEmailBody = $this->withHtmlTemplate($this->parseCode(Arr::get($email, 'body', ''), $wpUser), '', $wpUser);

        if (!$emailBody) {
            return $emailBody;
        }

        $this->tempEmailSubjectForEmailChange = $emailSubject;

        // we have to hook into wp_mail and alter the subject and headers and after done, we have to remove the hook
        add_filter('wp_mail', [$this, 'alterEmailChangeNotificationEmailSubjectHeader'], 99, 1);

        return $newEmailBody;
    }

    public function alterEmailChangeNotificationEmailSubjectHeader($atts)
    {
        if (!$this->tempEmailSubjectForEmailChange) {
            return $atts;
        }

        $atts['subject'] = $this->tempEmailSubjectForEmailChange;

        if (!is_array($atts['headers'])) {
            $atts['headers'] = [];
        }

        $atts['headers'][] = 'Content-Type: text/html; charset=UTF-8';

        $this->tempEmailSubjectForEmailChange = '';
        remove_filter('wp_mail', [$this, 'alterEmailChangeNotificationEmailSubjectHeader'], 99);

        return $atts;

    }

    protected function parseCode($code, $wpUser)
    {
        return (new SmartCodeParser())->parse($code, $wpUser);
    }

    protected function withHtmlTemplate($body, $preHeader = '', $wpUser)
    {

        $html = (string)Helper::loadView('email_template', [
            'body'       => $body,
            'pre_header' => $preHeader
        ]);


        return (string)(new Emogrifier($html))->emogrify();
    }

}
