<?php defined('ABSPATH') or die;


if (! function_exists('flsDb')) {
    /**
     * @return \FluentAuth\App\Services\DB\QueryBuilder\QueryBuilderHandler
     */
    function flsDb()
    {
        static $FluentAuthDb;

        if (! $FluentAuthDb) {
            global $wpdb;

            $connection = new \FluentAuth\App\Services\DB\Connection($wpdb, ['prefix' => $wpdb->prefix]);

            $FluentAuthDb = new \FluentAuth\App\Services\DB\QueryBuilder\QueryBuilderHandler($connection);
        }

        return $FluentAuthDb;
    }
}
