<?php

namespace FluentAuth\App\Services\ReCaptcha;

/**
 * @property array $white_label_domains
 * @property string $site_key
 * @property string $secret_key
 * @property string $enable_recaptcha
 */
class Settings
{
    private $settings = [];

    private $settingsKey = '__fls_auth_settings_recaptcha';

    public function __construct()
    {
        $this->settings = $this->getSettings();
    }

    public function get($key = null)
    {
        if (empty($this->settings)) {
            $this->settings = $this->getSettings();
        }

        if ($key) {
            return $this->settings[$key] ?: null;
        }

        return $this->settings;
    }

    public function set($key, $value)
    {
        $this->settings[$key] = $value;
        update_option($this->settingsKey, maybe_serialize($this->settings));
        $this->settings = $this->getSettings();
        return $this;
    }

    public function getSettings()
    {
        $savedSettings = get_option($this->settingsKey, []);
        $savedSettings = maybe_unserialize($savedSettings);

        $defaultSettings = [
            'white_label_domains'       => [],
            'site_key'                  => '',
            'secret_key'                => '',
            'enable_recaptcha'          => 'no',
            'enable_on_shortcode_login' => 'no',
        ];

        return wp_parse_args($savedSettings, $defaultSettings);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function save()
    {
        update_option($this->settingsKey, maybe_serialize($this->settings), false);
        return $this;
    }

    public function enabled()
    {
        return $this->settings['enable_recaptcha'] === 'yes' && (!empty($this->settings['site_key']));
    }

    public function update($data = [])
    {
        $settings = $this->getSettings();

        foreach ($data as $key => $value) {

            if (isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        update_option($this->settingsKey, maybe_serialize($settings));
        $this->settings = $this->getSettings();

        return $this->getSettings();
    }

    public function refreshData()
    {
        $this->settings = $this->getSettings();
        return $this;
    }
}
