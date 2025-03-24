<?php

namespace FluentAuth\App\Services\IntegrityChecker;

class Hasher
{
    private $folderPath;
    private $baselineHashes = [];

    public function __construct($folderPath = null)
    {
        // Default to standard WordPress path if not provided
        $this->folderPath = $folderPath;
    }

    /**
     * Generate baseline hash of wp-admin directory in memory
     * @return $this
     */
    public function generateBaselineHash()
    {
        if (!$this->folderPath) {
            throw new \Exception('Folder path is not set.');
        }

        $files = $this->getAllFiles($this->folderPath);
        $hashData = [];

        foreach ($files as $file) {
            $relativePath = str_replace($this->folderPath . '/', '', $file);
            $hashData[$relativePath] = $this->getFileHash($file);
        }

        // Sort to ensure consistent hashing
        ksort($hashData);

        // Store in memory
        $this->baselineHashes = $hashData;

        return $this;
    }

    public function getTotalHash(): string
    {
        return hash('md5', json_encode($this->baselineHashes));
    }

    /**
     * Get all files in wp-admin directory recursively
     * @param string $directory Directory to scan
     * @return array List of file paths
     */
    private function getAllFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                // Exclude certain system files or large files to improve performance
                $excludePatterns = [
                    '\.DS_Store$',
                    '\.log$',
                    '\.tmp$'
                ];

                $relativePath = $file->getPathname();
                $shouldExclude = array_reduce($excludePatterns, function ($carry, $pattern) use ($relativePath) {
                    return $carry || preg_match('/' . $pattern . '/i', $relativePath);
                }, false);

                if (!$shouldExclude) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Get file hash with configurable method
     * @param string $filepath Path to the file
     * @param string $algo Hashing algorithm
     * @return string Hash of the file
     */
    public function getFileHash(string $filepath, string $algo = 'md5'): string
    {
        // For files up to 1MB, hash the entire content
        $maxFileSize = 1 * 1024 * 1024; // 1MB
        if (filesize($filepath) <= $maxFileSize) {
            $hash = hash_file($algo, $filepath);
            if ($hash === false) {
                throw new \RuntimeException("Failed to generate hash for file: {$filepath}");
            }
            return $hash;
        }

        // For files larger than 1MB, hash only the first 1MB
        try {
            $handle = fopen($filepath, 'rb');
            if ($handle === false) {
                throw new \RuntimeException("Failed to open file: {$filepath}");
            }

            $content = fread($handle, $maxFileSize);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file: {$filepath}");
            }

            return hash($algo, $content);
        } catch (\Exception $e) {
            throw new \RuntimeException("Error processing large file: " . $e->getMessage());
        } finally {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    public function getBaselineHases()
    {
        return $this->baselineHashes;
    }

    public function compareHashes($currentHashes, $referenceHases)
    {
        $fileLists = [];

        // Compare hashes
        foreach ($currentHashes as $file => $hash) {
            if (!isset($referenceHases[$file])) {
                $fileLists[$file] = 'new';
            } elseif ($referenceHases[$file] !== $hash) {
                $fileLists[$file] = 'modified';
            }
        }

        // Check for deleted files
        foreach ($referenceHases as $file => $hash) {
            if (!isset($currentHashes[$file])) {
                $fileLists[$file] = 'deleted';
            }
        }

        return $fileLists;
    }
}
