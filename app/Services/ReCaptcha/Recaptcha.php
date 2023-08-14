<?php

namespace FluentAuth\App\Services\ReCaptcha;


/**
 * Class Recaptcha
 * @package FluentAuth\App\Services\ReCaptcha
 * @method static void register()
 * @method static void settings()
 * @method static void renderMenuPage($permission, $menuSlug, $cb)
 */
class Recaptcha
{
    public static $service = null;

    public function __construct()
    {
        if (is_null(self::$service)) {
            self::$service = new RecaptchaService();
        }

        return self::$service;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([self::$service, $name], $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        if (is_null(self::$service)) {
            self::$service = new RecaptchaService();
        }

        return call_user_func_array([self::$service, $name], $arguments);
    }

    public function __get($name)
    {
        return self::$service->{$name};
    }

    public function __set($name, $value)
    {
        self::$service->{$name} = $value;
    }

    public function __isset($name)
    {
        return isset(self::$service->{$name});
    }

    public function __unset($name)
    {
        unset(self::$service->{$name});
    }
}
