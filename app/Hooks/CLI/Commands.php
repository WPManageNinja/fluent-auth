<?php

namespace FluentAuth\App\Hooks\CLI;

use FluentAuth\App\Helpers\Arr;
use FluentAuth\App\Services\IntegrityChecker\IntegrityHelper;

class Commands
{
    public function scan_site()
    {
        $scanningSettings = IntegrityHelper::getSettings();

        if (Arr::get($scanningSettings, 'status') != 'active') {
            \WP_CLI::error('Integrity scan is not active. Please register your site first.');
        }

        $result = (new \FluentAuth\App\Services\IntegrityChecker\CoreIntegrityChecker())->checkAll();
        if (is_wp_error($result) || empty($result)) {
            \WP_CLI::error('Error when scanning: ' . $result ? $result->get_error_message() : 'Unknown Errors.');
        }

        $activeChnages = IntegrityHelper::getActiveModifiedFilesFolders($result, true);

        $modifiedFiles = Arr::get($activeChnages, 'files', []);
        $modifiedFolders = Arr::get($activeChnages, 'folders', []);

        if (!$modifiedFolders && !$modifiedFiles) {
            \WP_CLI::success('All good! No changes found.');
            return;
        }

        // show the modified files and folders as table WP_CLI Table please
        $items = [];

        if ($modifiedFolders) {
            foreach ($modifiedFolders as $folder => $fileData) {
                $items[] = [
                    'type'   => 'folder',
                    'path'   => $folder,
                    'status' => $fileData['status'],
                    'modified_at' => '--'
                ];
            }
        }

        if ($modifiedFiles) {
            foreach ($modifiedFiles as $file => $fileData) {
                $items[] = [
                    'type'        => 'file',
                    'path'        => $file,
                    'status'      => $fileData['status'],
                    'modified_at' => $fileData['modified_at']
                ];
            }
        }

        if (empty($items)) {
            WP_CLI::success('All good. No changes found.');
            return;
        }


        \WP_CLI\Utils\format_items('table', $items, array('type', 'path', 'status', 'modified_at'));

    }

    public function ignored_scan_files()
    {
        $ignores = IntegrityHelper::getIgnoreLists();

        $lists = [];

        foreach ($ignores['folders'] as $ignore) {
            $lists[] = [
                'path' => $ignore,
                'type' => 'folder'
            ];
        }

        foreach ($ignores['files'] as $ignore) {
            $lists[] = [
                'path' => $ignore,
                'type' => 'file'
            ];
        }


        \WP_CLI\Utils\format_items('table', $lists, array('type', 'path'));
    }
}
