<?php

/*
 * Init Direct Classes Here
 */
(new \FluentSecurity\App\Hooks\Handlers\AdminMenuHandler())->register();
(new \FluentSecurity\App\Hooks\Handlers\CustomAuthHandler())->register();
(new \FluentSecurity\App\Hooks\Handlers\LoginSecurityHandler())->register();
(new \FluentSecurity\App\Hooks\Handlers\MagicLoginHandler())->register();
(new \FluentSecurity\App\Hooks\Handlers\SocialAuthHandler())->register();
(new \FluentSecurity\App\Hooks\Handlers\TwoFaHandler())->register();
(new \FluentSecurity\App\Hooks\Handlers\BasicTasksHandler())->register();

