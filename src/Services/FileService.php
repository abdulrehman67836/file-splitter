<?php
namespace App\Services;

use App\Core\Env;

class FileService {
    /**
     * Validate the uploaded file properties.
     *
     * @param array $file $_FILES['key'] array
     * @return array Array containing validation status and errors/extension/sanitized name
     */
    public static function validateFile(array $file): array {
        $allowedExtensions = ['csv', 'xlsx'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $tmpPath = $file['tmp_name'];
        $error = $file['error'];

        if ($error !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload failed with error code: ' . $error];
        }

        // Check file size (FR-3: default max size 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($fileSize > $maxSize) {
            return ['valid' => false, 'error' => 'File size exceeds maximum limit of 10MB.'];
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Handle legacy Excel .xls files gracefully
        if ($extension === 'xls') {
            return [
                'valid' => false, 
                'error' => 'Legacy Excel binary format (.xls) is not supported for memory-efficient streaming. Please open the file and save it as modern Excel (.xlsx) or CSV, then try again.'
            ];
        }

        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Unsupported file format. Only .xlsx and .csv are supported.'];
        }

        // Validate MIME type (NFR-5)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'text/csv',
            'text/plain',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip', // XLSX is technically a zipped XML structure
            'application/octet-stream'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return ['valid' => false, 'error' => 'Invalid file MIME type: ' . $mimeType];
        }

        return [
            'valid'          => true,
            'extension'      => $extension,
            'sanitized_name' => self::sanitizeFilename($fileName)
        ];
    }

    /**
     * Sanitize a filename to prevent path traversal or special character execution.
     *
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string {
        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = $info['extension'];
        
        // Remove anything that is not alphanumeric, dash, or underscore
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        
        // Restrict name length to prevent filesystem limits
        $name = substr($name, 0, 100);
        
        return $name . '.' . $ext;
    }

    /**
     * Housekeeping: delete temporary and generated files older than retention hours (NFR-8).
     *
     * @return int Number of deleted files/folders
     */
    public static function cleanExpiredFiles(): int {
        $root = dirname(dirname(__DIR__));
        $storageDir = Env::get('STORAGE_DIR', 'storage');
        $retentionHours = (int) Env::get('RETENTION_HOURS', 24);
        $thresholdTime = time() - ($retentionHours * 3600);

        $dirs = [
            $root . '/' . $storageDir . '/uploads',
            $root . '/' . $storageDir . '/outputs',
            $root . '/' . $storageDir . '/archives',
        ];

        $deletedCount = 0;
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                continue;
            }
            
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                // If the file modification time is older than the retention threshold
                if ($fileinfo->getMTime() < $thresholdTime) {
                    $path = $fileinfo->getRealPath();
                    if ($fileinfo->isDir()) {
                        @rmdir($path);
                    } else {
                        @unlink($path);
                    }
                    $deletedCount++;
                }
            }
        }
        return $deletedCount;
    }
}
