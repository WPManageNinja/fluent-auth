<?php

namespace FluentAuth\App\Services;


use FluentAuth\App\Helpers\Helper;

class TotpService
{
    private $userId = false;

    public function __construct($userId = false)
    {
        $this->userId = $userId;
    }

    public function generateSecret()
    {
        return \FluentAuth\App\Services\Totp\Totp::GenerateSecret(32);
    }

    public function getSecret()
    {
        $secret = get_user_meta($this->userId, '_fluentauth_totp_secret', true);

        if (!$secret) {
            return null;
        }

        return Helper::decryptString($secret);
    }

    public function setSecret($secret)
    {
        $secret = Helper::encryptString($secret);
        return update_user_meta($this->userId, '_fluentauth_totp_secret', $secret);
    }

    public function verifyCode($code)
    {
        $secret = $this->getSecret();

        if (!$secret) {
            return false;
        }

        $key = (new \FluentAuth\App\Services\Totp\Totp())->GenerateToken(\FluentAuth\App\Services\Totp\Base32::decode($secret));

        return $key === $code;
    }

    public function isEnabled()
    {
        return !!$this->getSecret($this->userId);
    }

}
