<?php

namespace FluentAuth\App\Services;

use FluentAuth\App\Helpers\Arr;

class SystemEmailService
{
    public static function getEmailIndexes()
    {
        $systemEmails = [
            // User Account Management Emails
            'user_registration_to_user'          => [
                'name'        => 'user_registration_to_user',
                'title'       => 'New User Registration Notification',
                'description' => 'An essential email sent to new users upon account signup.',
                'recipient'   => 'user',
                'hook'        => 'wp_new_user_notification'
            ],
            'password_reset_to_user'             => [
                'name'        => 'password_reset_to_user',
                'title'       => 'Password Reset Request Email',
                'description' => 'A security-critical email sent when a user requests to reset their password, containing a unique reset link with time-limited access.',
                'hook'        => 'retrieve_password',
                'recipient'   => 'user',
            ],
            'email_change_notification_to_user'  => [
                'name'        => 'email_change_notification_to_user',
                'title'       => 'Email Address Change Confirmation',
                'description' => 'Sent to the new email addresses to confirm and validate an email address change, providing security against unauthorized modifications.',
                'hook'        => 'wp_email_change_notification',
                'recipient'   => 'user',
            ],
            'user_registration_to_admin'         => [
                'name'        => 'user_registration_to_admin',
                'title'       => 'New User Registration Notification',
                'description' => 'An essential email sent to the admin when someone signup.',
                'recipient'   => 'site_admin',
                'hook'        => 'wp_new_user_notification'
            ],
            'email_change_notification_to_admin' => [
                'name'        => 'email_change_notification_to_admin',
                'title'       => 'Email Address Change Confirmation',
                'description' => 'Send email notification to admin when a user changes their email address.',
                'hook'        => 'wp_email_change_notification',
                'recipient'   => 'site_admin',
            ],
        ];

        $globalSettings = self::getGlobalSettings();

        foreach ($systemEmails as $key => $value) {
            $systemEmails[$key]['status'] = $globalSettings['emails'][$key]['status'] ?? 'system';
        }

        return $systemEmails;
    }

    public static function getGlobalSettings($cached = true)
    {
        static $formattedSettings = null;

        if ($cached && $formattedSettings) {
            return $formattedSettings;
        }

        $emailsDefault = [
            'user_registration_to_user'          => [
                'status' => 'system',
                'email'  => [
                    'subject' => '[{site.title}] - Set Up Your Password',
                    'body'    => self::getDefaultEmailBody('user_registration_to_user')
                ]
            ],
            'password_reset_to_user'             => [
                'status' => 'system',
                'email'  => [
                    'subject' => '[{{site.title}}] Password Reset',
                    'body'    => self::getDefaultEmailBody('password_reset_to_user'),
                ]
            ],
            'email_change_notification_to_user'  => [
                'status' => 'system',
                'email'  => [
                    'subject' => '[{{site.name}}] Email Change Request',
                    'body'    => self::getDefaultEmailBody('email_change_notification_to_user'),
                ]
            ],
            'user_registration_to_admin'         => [
                'status' => 'system',
                'email'  => [
                    'subject' => '',
                    'body'    => '',
                ]
            ],
            'email_change_notification_to_admin' => [
                'status' => 'system',
                'email'  => [
                    'subject' => '',
                    'body'    => '',
                ]
            ],
        ];

        $emailConfig = [
            'logo'            => '',
            'primary_color'   => '#0073aa',
            'secondary_color' => '#ffffff',
            'font_family'     => 'Arial, sans-serif',
            'template'        => '',
            'email_footer'    => '',
            'from_name'       => '',
            'from_email'      => '',
            'reply_to_name'   => '',
            'reply_to_email'  => ''
        ];

        $settings = get_option('fa_system_email_settings', []);

        if (empty($settings)) {
            $formattedSettings = [
                'emails'          => $emailsDefault,
                'global_settings' => $emailConfig
            ];

            return $formattedSettings;
        }

        $emails = $settings['emails'] ?? [];
        $globalSettings = $settings['global_settings'] ?? [];

        $emails = wp_parse_args($emails, $emailsDefault);
        $globalSettings = wp_parse_args($globalSettings, $emailConfig);

        $formattedSettings = [
            'emails'          => $emails,
            'global_settings' => $globalSettings
        ];

        return $formattedSettings;
    }

    public static function getEmailSettingsByType($emailType)
    {
        $settings = get_option('fa_system_email_settings', []);

        if (!$settings) {
            return [];
        }

        return Arr::get($settings, 'emails.' . $emailType, []);
    }

    public static function getDefaultEmailBody($type = '')
    {
        if ($type == 'user_registration_to_user') {
            ob_start();
            ?>
            <p>Hello<strong>{{user.display_name}}</strong>,</p>
            <p>Your account has been created on<strong>{{site.title}}</strong>. To set up your password and complete
                your registration, please click the button below:</p>
            <p>&nbsp;</p>
            <p class="align-center" style="text-align: center;" align="center"><a
                    style="color: #ffffff; background-color: #0072ff; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: bold; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;"
                    href="#user.password_set_url#">Set Your Password</a></p>
            <p>&nbsp;</p>
            <p>If the button above doesn't work, you can copy and paste this URL into your browser:</p>
            <p>##user.password_set_url##</p>
            <p>This password reset link will expire in 24 hours for security reasons.</p>
            <p>Here's your login information:</p>
            <table role="presentation" border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
                <tbody>
                <tr>
                    <td>
                        <p><strong>Username:</strong> {{user.username}}</p>
                        <p><strong>Login URL:</strong> {{site.login_url}}</p>
                    </td>
                </tr>
                </tbody>
            </table>
            <p>&nbsp;</p>
            <hr/>
            <p>If you didn't request this email, please contact the site administrator.</p>
            <p>&nbsp;</p>
            <?php
            return ob_get_clean();
        }

        if ($type == 'password_reset_to_user') {
            ob_start();
            ?>
            <p>Hello<strong>{{user.display_name}}</strong>,</p>
            <p>A password reset has been requested for the following administrator account:</p>
            <blockquote>
                <p>Your Account Username: {{user.username}}</p>
                <p>Your Account Email: {{user.email}}</p>
            </blockquote>
            <p>If you did not request this password reset, please disregard this email and no changes will be made to
                your account.</p>
            <p>To proceed with resetting your password, please click the button below:</p>
            <p>&nbsp;</p>
            <p class="align-center" style="text-align: center;" align="center"><a
                    style="color: #ffffff; background-color: #0072ff; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: bold; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;"
                    href="##user.password_reset_url##">Reset Your Password</a></p>
            <p>&nbsp;</p>
            <p>This password reset link will expire in 24 hours for security reasons.</p>
            <p>If you're having trouble with the button above, copy and paste the URL below into your web browser:</p>
            <blockquote>
                <p>{{##user.password_reset_url##}}</p>
            </blockquote>
            <hr/>
            <p>If you did not initiate this request, please review your account security and consider changing your
                password.</p>
            <?php
            return ob_get_clean();
        }

        if ($type == 'email_change_notification_to_user') {
            ob_start();
            ?>
            <p>Hello<b> {{user.display_name}}</b>,</p>
            <p>We received a request to change the email address associated with your <strong>{{site.name}}</strong>
                account.</p>
            <p><span style="text-decoration: underline;"><strong>Your account change details:</strong></span></p>
            <blockquote>
                <p><strong>Current Email:</strong> {{user.user_email}}</p>
                <p><strong>New Email:</strong> {{user.new_changing_email_id}} <em>(will take effect after
                        confirmation)</em></p>
            </blockquote>
            <p>To complete this process and verify your new email address, please click the confirmation button
                below.</p>
            <p>&nbsp;</p>
            <p class="align-center" style="text-align: center;" align="center"><a
                    style="color: #ffffff; background-color: #0072ff; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: bold; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;"
                    href="##user.confirm_email_change_url##">Confirm Email Change</a></p>
            <p>&nbsp;</p>
            <p>If the button above doesn't work, you can copy and paste this URL into your browser:</p>
            <blockquote>
                <p>{{user.confirm_email_change_url}}</p>
            </blockquote>
            <p>This confirmation link will expire in 24 hours for security reasons. If you don't confirm within this
                timeframe, you'll need to submit a new email change request.</p>
            <hr/>
            <p>This email has been sent to: {{user.meta._new_email}}</p>
            <p>Regards</p>
            <p>All at {{site.name}}<br/>{{site.url}}</p>
            <?php
            return ob_get_clean();
        }

    }

}
