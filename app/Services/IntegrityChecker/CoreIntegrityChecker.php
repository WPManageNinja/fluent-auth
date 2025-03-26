<?php

namespace FluentAuth\App\Services\IntegrityChecker;

use FluentAuth\App\Helpers\Arr;

class CoreIntegrityChecker
{
    private $hasIssues = false;

    private $result = [];

    private $remoteHashes = [];

    public function checkAll()
    {

        try {
            $remoteHashes = $this->getRemoteHashes();
            if (is_wp_error($remoteHashes)) {
                return $remoteHashes;
            }

            $this->checkAdminFolder();
            $this->checkIncFolder();
            $this->checkRootFolder();

            return $this->result;
        } catch (\Exception $exception) {
            return new \WP_Error('api_error', $exception->getMessage());
        }
    }

    public function checkAdminFolder()
    {
        $folder = ABSPATH . 'wp-admin';
        $localFileHashes = (new Hasher($folder))->generateBaselineHash()->getBaselineHases();
        $remoteAdminHashes = \FluentAuth\App\Helpers\Arr::get($this->remoteHashes, 'wp-admin.files', []);
        $diffs = (new Hasher($folder))->compareHashes($localFileHashes, $remoteAdminHashes);
        $diffs = array_filter($diffs);

        if ($diffs) {
            $this->hasIssues = true;
            $this->result['wp_admin'] = $diffs;
        }

        return $this;
    }

    public function checkIncFolder()
    {
        $folder = ABSPATH . WPINC;

        $localFileHashes = (new Hasher($folder))->generateBaselineHash()->getBaselineHases();
        $remoteIncHashes = \FluentAuth\App\Helpers\Arr::get($this->getRemoteHashes(), 'wp-includes.files', []);

        $diffs = (new Hasher($folder))->compareHashes($localFileHashes, $remoteIncHashes);

        $diffs = array_filter($diffs);

        if ($diffs) {
            $this->hasIssues = true;
            $this->result['wp_includes'] = $diffs;
        }

        return $this;
    }

    public function checkRootFolder()
    {
        $rootFolder = ABSPATH;

        // get the root files and folders
        $rootFiles = scandir($rootFolder);

        $ignores = array_unique([
            '.',
            '..',
            '.git',
            '.gitignore',
            '.DS_Store',
            '.idea',
            'wp-admin',
            'wp-includes',
            'wp-config.php',
            'wp-config-sample.php',
            '.htaccess',
            WPINC,
            'wp-content',
            basename(WP_CONTENT_DIR)
        ]);

        $rootFiles = array_diff($rootFiles, $ignores);

        $files = [];
        $extraFolders = [];
        foreach ($rootFiles as $file) {
            if (is_file($rootFolder . '/' . $file)) {
                $files[] = $file;
            } elseif (is_dir($rootFolder . '/' . $file)) {
                $xcloudDirs = ['before', 'after', 'server'];
                if (in_array($file, $xcloudDirs)) {
                    if ($this->isConfFolder($rootFolder . '/' . $file)) {
                        continue;
                    }
                }

                $extraFolders[] = $file;
            }
        }

        $localFileHashes = [];

        $hasher = new Hasher();
        foreach ($files as $file) {
            if (preg_match('/^(file-manager-|adminer-).*\.php$|\.conf$/i', $file)) {
                continue; // we are ignoring known useful files
            }
            $localFileHashes[$file] = $hasher->getFileHash(ABSPATH . $file);
        }

        $remoteRootHashes = Arr::get($this->getRemoteHashes(), 'root_files', []);

        if (!$remoteRootHashes) {
            throw new \Exception('Remote root hashes not found');
        }

        if (!$localFileHashes) {
            throw new \Exception('Local root hashes not found');
        }

        $diffs = (new Hasher())->compareHashes($localFileHashes, $remoteRootHashes);

        $diffs = array_filter($diffs);

        if ($diffs) {
            $this->hasIssues = true;
            $this->result['root'] = $diffs;
            $this->result['extra_root_folders'] = $extraFolders;
        }

        return $this;
    }

    public function getRemoteRootFileHashes()
    {
        $remoteSummary = $this->getRemoteSummary();

        if (!$remoteSummary) {
            return;
        }

        $remoteFileHashes = \FluentAuth\App\Helpers\Arr::get($remoteSummary, 'data.root_hashes', []);

        $remoteFileHashes = array_filter($remoteFileHashes, function ($key) {
            return !in_array($key, ['wp-admin', 'wp-includes']);
        }, ARRAY_FILTER_USE_KEY);

        return $remoteFileHashes;
    }

    public function getRemoteSummary()
    {
        static $remoteSummary = null;

        if ($remoteSummary === null) {
            $remoteSummary = Api::getRemoteHashes();
        }

        return $remoteSummary;
    }

    public function getRemoteHashes()
    {
        if (!$this->remoteHashes) {
            $this->setRemoteHashes();
        }

        return $this->remoteHashes;
    }

    public function setRemoteHashes()
    {
        $remoteHashes = Api::getRemoteHashes(true);

        if (is_wp_error($remoteHashes)) {
            throw new \Exception($remoteHashes->get_error_message(), '500');
        }

        $this->remoteHashes = Arr::get($remoteHashes, 'data.hashes', []);
        return $this;
    }

    private function isConfFolder($folderPath)
    {
        // Check if directory exists
        if (!is_dir($folderPath)) {
            return true;
        }

        // Get all files in directory
        $files = scandir($folderPath);

        // Remove . and .. from the list
        $files = array_diff($files, array('.', '..'));

        // If directory is empty, return true
        if (empty($files)) {
            return true;
        }

        // Check each file
        foreach ($files as $file) {
            $fullPath = $folderPath . DIRECTORY_SEPARATOR . $file;

            // If it's a directory, return false
            if (is_dir($fullPath)) {
                return false;
            }

            // If it's not a .conf file, return false
            if (!preg_match('/\.conf$/i', $file)) {
                return false;
            }
        }

        // If we made it through all checks, return true
        return true;
    }

}
