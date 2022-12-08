<?php defined('ABSPATH') or die;

// Autoload plugin.
require_once(__DIR__.'/autoload.php');

if (! function_exists('flsDb')) {
    /**
     * @return \FluentSecurityDb\QueryBuilder\QueryBuilderHandler
     */
    function flsDb()
    {
        static $FluentSecurityDb;

        if (! $FluentSecurityDb) {
            global $wpdb;

            $connection = new \FluentSecurityDb\Connection($wpdb, ['prefix' => $wpdb->prefix]);

            $FluentSecurityDb = new \FluentSecurityDb\QueryBuilder\QueryBuilderHandler($connection);
        }

        return $FluentSecurityDb;
    }
}
