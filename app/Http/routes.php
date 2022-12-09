<?php

$router = new \FluentAuth\App\Services\Router('fluent-auth');

$permissions = ['manage_options'];

$router->get('settings', ['\FluentAuth\App\Http\Controllers\SettingsController', 'getSettings'], $permissions)
    ->post('settings', ['\FluentAuth\App\Http\Controllers\SettingsController', 'updateSettings'], $permissions)
    ->get('auth-logs', ['\FluentAuth\App\Http\Controllers\LogsController', 'getLogs'], $permissions)
    ->get('quick-stats', ['\FluentAuth\App\Http\Controllers\LogsController', 'quickStats'], $permissions)
    ->post('delete-log/{id}', ['\FluentAuth\App\Http\Controllers\LogsController', 'deleteLog'], $permissions)
    ->post('truncate-auth-logs', ['\FluentAuth\App\Http\Controllers\LogsController', 'deleteAllLog'], $permissions)
    ->get('social-auth-settings', ['\FluentAuth\App\Http\Controllers\SocialAuthApiController', 'getSettings'], $permissions)
    ->post('social-auth-settings', ['\FluentAuth\App\Http\Controllers\SocialAuthApiController', 'saveSettings'], $permissions)
    ->get('auth-forms-settings', ['\FluentAuth\App\Http\Controllers\SettingsController', 'getAuthFormSettings'], $permissions)
    ->post('auth-forms-settings', ['\FluentAuth\App\Http\Controllers\SettingsController', 'saveAuthFormSettings'], $permissions);
