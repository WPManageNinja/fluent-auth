<?php

$router = new \FluentSecurity\App\Services\Router('fluent-security');

$permissions = ['manage_options'];

$router->get('settings', ['\FluentSecurity\App\Http\Controllers\SettingsController', 'getSettings'], $permissions)
    ->post('settings', ['\FluentSecurity\App\Http\Controllers\SettingsController', 'updateSettings'], $permissions)
    ->get('auth-logs', ['\FluentSecurity\App\Http\Controllers\LogsController', 'getLogs'], $permissions)
    ->get('quick-stats', ['\FluentSecurity\App\Http\Controllers\LogsController', 'quickStats'], $permissions)
    ->post('delete-log/{id}', ['\FluentSecurity\App\Http\Controllers\LogsController', 'deleteLog'], $permissions)
    ->post('truncate-auth-logs', ['\FluentSecurity\App\Http\Controllers\LogsController', 'deleteAllLog'], $permissions)
    ->get('social-auth-settings', ['\FluentSecurity\App\Http\Controllers\SocialAuthApiController', 'getSettings'], $permissions)
    ->post('social-auth-settings', ['\FluentSecurity\App\Http\Controllers\ocialAuthApiController', 'saveSettings'], $permissions)
    ->get('auth-forms-settings', ['\FluentSecurity\App\Http\Controllers\SettingsController', 'getAuthFormSettings'], $permissions)
    ->post('auth-forms-settings', ['\FluentSecurity\App\Http\Controllers\SettingsController', 'saveAuthFormSettings'], $permissions);
