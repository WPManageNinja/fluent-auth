<?php

/*
 * Init Direct Classes Here
 */
(new \FluentAuth\App\Hooks\Handlers\AdminMenuHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\CustomAuthHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\LoginSecurityHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\MagicLoginHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\SocialAuthHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\TwoFaHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\BasicTasksHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\WPSystemEmailHandler())->register();


add_action('init', function () {
    if(!isset($_REQUEST['scan'])) {
        return;
    }

    \FluentAuth\App\Services\IntegrityChecker\IntegrityHelper::maybeSendScanReport();

});
