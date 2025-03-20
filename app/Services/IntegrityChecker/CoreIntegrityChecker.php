<?php

namespace FluentAuth\App\Services\IntegrityChecker;

class CoreIntegrityChecker
{
    private $hasIssues = false;

    private $result = [];

    public function checkAll()
    {
        $this->checkRootFolder();
        $this->checkAdminFolder();
        $this->checkIncFolder();

        return $this->result;
    }

    public function checkAdminFolder()
    {

    }

    public function checkIncFolder()
    {

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
                $extraFolders[] = $file;
            }
        }

        $localFileHashes = [];

        $hasher = new Hasher();
        foreach ($files as $file) {
            $localFileHashes[$file] = $hasher->getFileHash(ABSPATH . $file);
        }

        $remoteRootHashes = $this->getRemoteRootFileHashes();

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
        }

        return $this;
    }

    public function getRemoteRootFileHashes()
    {
        $remoteSummary = $this->getRemoteSummary();

        if (!$remoteSummary) {
            return;
        }

        $remoteFileHashes = \FluentAuth\App\Helpers\Arr::get($remoteSummary, 'hashes', []);

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
        static $remoteHashes = null;

        if ($remoteHashes === null) {
            $remoteHashes = Api::getRemoteHashes(true);
        }

        return $remoteHashes;
    }
}
