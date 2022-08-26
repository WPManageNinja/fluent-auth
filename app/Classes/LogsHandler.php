<?php

namespace FluentSecurity\Classes;

class LogsHandler
{
    public static function getLogs(\WP_REST_Request $request)
    {
        $orderByColumn = sanitize_sql_orderby($request->get_param('sortBy'));
        $orderBy = sanitize_sql_orderby($request->get_param('sortType'));

        if (!$orderByColumn) {
            $orderByColumn = 'id';
        }
        if (!$orderBy) {
            $orderBy = 'DESC';
        }

        $query = flsDb()->table('fls_auth_logs')->orderBy($orderByColumn, $orderBy);

        if ($statuses = $request->get_param('statuses')) {
            $statuses = array_filter(map_deep($statuses, 'sanitize_text_field'));
            if ($statuses && !in_array('all', $statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($request->get_param('search')) {
            $search = sanitize_text_field($request->get_param('search'));
            $query->where('username', 'LIKE', '%'.$search.'%');
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

    public static function deleteLog(\WP_REST_Request $request)
    {
        $id = $request->get_param('id');
        wpFluent()->table('fls_auth_logs')->where('id', $id)->delete();

        return [
            'message' => __('Log has been deleted', 'fluent-security')
        ];
    }

    public static function deleteAllLog(\WP_REST_Request $request)
    {
        global $wpdb;

        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}fls_auth_logs");

        return [
            'message' => __('All Logs has been deleted', 'fluent-security')
        ];

    }

    public static function quickStats(\WP_REST_Request $request)
    {
        $fromRange = sanitize_text_field($request->get_param('day_range'));

        if (!$fromRange) {
            $fromRange = '-0 days';
        }

        if ($fromRange == 'this_month') {
            $fromDate = date('Y-m-01 00:00:00');
        } else if ($fromRange == 'all_time') {
            $fromDate = false;
            $compare = false;
        } else {
            $fromDate = date('Y-m-d 00:00:00', strtotime($fromRange));
        }

        $toDate = date('Y-m-d 23:59:59', current_time('timestamp'));

        $counts = wpFluent()->table('fls_auth_logs')
            ->select('status', wpFluent()->raw('count(*) as total'))
            ->whereBetween('created_at', $fromDate, $toDate)
            ->groupBy('status')
            ->get();

        $items = [
            'failed'  => [
                'count' => 0,
                'title' => 'Failed Logins'
            ],
            'blocked' => [
                'count' => 0,
                'title' => 'Blocked Logins'
            ],
            'success' => [
                'count' => 0,
                'title' => 'Successful Logins'
            ]
        ];

        foreach ($counts as $countItem) {
            if (isset($items[$countItem->status])) {
                $items[$countItem->status]['count'] = $countItem->total;
            }
        }

        return [
            'stats' => $items
        ];
    }

}
