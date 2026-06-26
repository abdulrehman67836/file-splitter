<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Env;
use App\Services\FileService;
use App\Services\SplitService;
use App\Models\SplitJob;
use Exception;

class FileUploadController {
    /**
     * Handles file upload and streaming row detection.
     * POST /uploads
     *
     * @return void
     */
    public function upload(): void {
        $file = Request::file('file');
        // Treat as header row by default
        $hasHeaderInput = Request::input('has_header', '1');
        $hasHeader = ($hasHeaderInput === '1' || $hasHeaderInput === 'true' || $hasHeaderInput === true);

        if (!$file) {
            Response::json(['error' => 'No file was uploaded. Please drop or select a file.'], 400);
        }

        // 1. Validate file (FR-1, FR-2, FR-3, NFR-5)
        $validation = FileService::validateFile($file);
        if (!$validation['valid']) {
            Response::json(['error' => $validation['error']], 400);
        }

        $extension = $validation['extension'];
        $sanitizedName = $validation['sanitized_name'];

        // 2. Generate unique UUID
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $root = dirname(dirname(__DIR__));
        $storageDir = Env::get('STORAGE_DIR', 'storage');
        $uploadDir = $root . '/' . $storageDir . '/uploads';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $storedPath = $uploadDir . '/' . $uuid . '.' . $extension;

        // 3. Move file to secure storage outside public web root (NFR-4)
        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            Response::json(['error' => 'Could not save the uploaded file on the server.'], 500);
        }

        try {
            // 4. Count total data rows in a streaming manner (FR-4, NFR-2)
            $totalRows = SplitService::countRows($storedPath, $extension, $hasHeader);
        } catch (Exception $e) {
            @unlink($storedPath); // Cleanup
            Response::json(['error' => 'Spreadsheet processing error: ' . $e->getMessage()], 400);
        }

        // 5. Handle empty file edge case (FR-4 / Edge Case 8.2)
        if ($totalRows <= 0) {
            @unlink($storedPath);
            Response::json(['error' => 'The uploaded file has no data rows to split. Only a header row or empty content was found.'], 400);
        }

        // 6. Save split job in PostgreSQL
        try {
            $jobData = [
                'uuid'              => $uuid,
                'original_filename' => $sanitizedName,
                'stored_path'       => $storedPath,
                'file_type'         => $extension,
                'total_rows'        => $totalRows,
                'chunk_size'        => 0, // Placeholder, updated in split stage
                'has_header'        => $hasHeader ? 'true' : 'false',
                'status'            => 'pending'
            ];

            $job = SplitJob::create($jobData);
            if (!$job) {
                throw new Exception("Database INSERT failed.");
            }
        } catch (Exception $e) {
            @unlink($storedPath);
            Response::json(['error' => 'Could not register split job in the database. details: ' . $e->getMessage()], 500);
        }

        // 7. Perform housekeeping during idle upload times (NFR-8)
        FileService::cleanExpiredFiles();

        // 8. Generate chunk recommendations (FR-5)
        $recommendations = [
            '50'  => ceil($totalRows / 50),
            '100' => ceil($totalRows / 100),
            '200' => ceil($totalRows / 200),
        ];

        Response::json([
            'uuid'            => $uuid,
            'filename'        => $sanitizedName,
            'total_rows'      => $totalRows,
            'recommendations' => $recommendations
        ]);
    }
}
