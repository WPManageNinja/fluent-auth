<?php
namespace FluentAuth\App\Services\DB\QueryBuilder\Adapters;

class Mysql extends BaseAdapter
{
    /**
     * @var string
     */
    protected $sanitizer = '`';
}
