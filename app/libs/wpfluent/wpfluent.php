<?php defined('ABSPATH') or die;

// Autoload plugin.
require_once(__DIR__.'/autoload.php');

if (! function_exists('flsDb')) {
    /**
     * @return \FluentAuthDb\QueryBuilder\QueryBuilderHandler
     */
    function flsDb()
    {
        static $FluentAuthDb;

        if (! $FluentAuthDb) {
            global $wpdb;

            $connection = new \FluentAuthDb\Connection($wpdb, ['prefix' => $wpdb->prefix]);

            $FluentAuthDb = new \FluentAuthDb\QueryBuilder\QueryBuilderHandler($connection);
        }

        return $FluentAuthDb;
    }
}
