<?php

namespace FluentAuth\App\Services\ReCaptcha;

class MenuPage
{
    private $permission;
    private $menuSlug;
    private $cb;
    public function __construct($permission, $menuSlug, $cb)
    {
        $this->permission = $permission;
        $this->menuSlug = $menuSlug;
        $this->cb = $cb;
    }

    public function registerSubmenuPage($pageTitle, $menuTitle, $menuSlug, $cb = null)
    {
        add_submenu_page(
            $this->menuSlug,
            $pageTitle,
            $menuTitle,
            $this->permission,
            $menuSlug,
            $cb ?: $this->cb
        );
    }

    public function registerMenuPage($pageTitle, $menuTitle, $menuSlug, $cb = null)
    {
        add_menu_page(
            $pageTitle,
            $menuTitle,
            $this->permission,
            $menuSlug,
            $cb ?: $this->cb
        );
    }

}
