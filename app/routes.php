<?php

$router = new \FluentSecurity\Classes\Router('fluent-security');

$permissions = ['manage_options'];

$router->get('settings', ['\FluentSecurity\Classes\SettingsHandler', 'getSettings'], $permissions)
    ->post('settings', ['\FluentSecurity\Classes\SettingsHandler', 'updateSettings'], $permissions)
    ->get('auth-logs', ['\FluentSecurity\Classes\LogsHandler', 'getLogs'], $permissions);
