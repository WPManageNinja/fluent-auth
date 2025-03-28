<?php

/*
 * Init Direct Classes Here
 */

use FluentAuth\App\Services\IntegrityChecker\IntegrityHelper;

(new \FluentAuth\App\Hooks\Handlers\AdminMenuHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\CustomAuthHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\LoginSecurityHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\MagicLoginHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\SocialAuthHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\TwoFaHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\BasicTasksHandler())->register();
(new \FluentAuth\App\Hooks\Handlers\WPSystemEmailHandler())->register();

add_action('init', function () {
    if (!isset($_REQUEST['hash'])) {
        return;
    }

    $localResult = IntegrityHelper::getCoreFilesHashes();
    global $wp_version;
    require_once ABSPATH . 'wp-admin/includes/update.php';
    $remoteHashes = get_core_checksums($wp_version, get_locale());
    foreach ($remoteHashes as $file => $hash) {
        // if file has wp-content at the start, remove it
        if (strpos($file, 'wp-content') === 0) {
            unset($remoteHashes[$file]);
        }
    }
    unset($remoteHashes['wp-config-sample.php']);

    $localHashes = $localResult['all_files'];

    $modifiedFiles = [];
    foreach ($localHashes as $file => $localHash) {
        $remoteHash = isset($remoteHashes[$file]) ? $remoteHashes[$file] : null;
        if (!$remoteHash) {
            // the file is deleted
            $modifiedFiles[$file] = [
                'status'      => 'new',
                'modified_at' => gmdate('Y-m-d H:i:s', filemtime(ABSPATH . $file))
            ];
        } else if ($localHash !== $remoteHash) {
            // the file is modified
            $modifiedFiles[$file] = [
                'status'      => 'modified',
                'modified_at' => gmdate('Y-m-d H:i:s', filemtime(ABSPATH . $file))
            ];
        }
    }

    $deletedFiles = array_values(array_diff(array_keys($remoteHashes), array_keys($localHashes)));

    foreach ($deletedFiles as $file) {
        $modifiedFiles[$file] = [
            'status'      => 'deleted',
            'modified_at' => ''
        ];
    }

    // let's grouped the files by folders
    $groupedFiles = [];

    foreach ($modifiedFiles as $file => $data) {
        $folder = dirname($file);

        $relativePath = $file;
        if ($folder === '.') {
            $folder = 'root';
        } else {
            // Replace the first occurrence of the folder with an empty string
            $relativePath = preg_replace('/^' . preg_quote($folder, '/') . '\//', '', $file);
        }

        if (!isset($groupedFiles[$folder])) {
            $groupedFiles[$folder] = [];
        }
        $groupedFiles[$folder][$relativePath] = $data;
    }

    dd($groupedFiles);


    dd($modifiedFiles, $localResult['extra_root_folders']);

    $modifiedFiles = array_diff($remoteHashes);


    dd($modifiedFiles);
});
