<?php

namespace FluentSecurity\Classes;

use FluentSecurity\Helpers\Helper;

class LogsHandler
{
    public static function getLogs(\WP_REST_Request $request)
    {

        $limit = (int) $request->get_param('per_page');
        if(!$limit) {
            $limit = 15;
        }
        $page = (int) $request->get_param('page');
        if(!$page) {
            $page = 1;
        }

        $orderByColumn = sanitize_sql_orderby($request->get_param('sortBy'));
        $orderBy = sanitize_sql_orderby($request->get_param('sortType'));

        if(!$orderByColumn) {
            $orderByColumn = 'id';
        }
        if(!$orderBy) {
            $orderBy = 'DESC';
        }

        $query = flsDb()->table('fls_auth_logs')->orderBy($orderByColumn, $orderBy);

        if($statuses = $request->get_param('statuses')) {
            $statuses = array_filter(map_deep($statuses, 'sanitize_text_field'));
            if($statuses) {
                $query->whereIn('status', $statuses);
            }
        }

        $logs = $query->paginate();

        $currentTimeStamp = current_time('timestamp');
        foreach ($logs['data'] as $log) {
            $log->human_time_diff = human_time_diff(strtotime($log->created_at, $currentTimeStamp), $currentTimeStamp) . ' ago';
        }

        return [
            'logs' => $logs
        ];
    }

}
