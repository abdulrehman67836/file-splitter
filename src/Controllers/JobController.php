<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\SplitJob;

class JobController {
    /**
     * Start the split job and spawn the background worker.
     * POST /jobs/{uuid}/split
     *
     * @param string $uuid
     * @return void
     */
    public function startSplit(string $uuid): void {
        $job = SplitJob::findByUuid($uuid);
        if (!$job) {
            Response::json(['error' => 'Job not found.'], 404);
        }

        $chunkSizeInput = Request::input('chunk_size');

        // Validation (FR-6, FR-7)
        if ($chunkSizeInput === null || $chunkSizeInput === '') {
            Response::json(['error' => 'Chunk size is required.'], 400);
        }

        if (!is_numeric($chunkSizeInput)) {
            Response::json(['error' => 'Chunk size must be a numeric value.'], 400);
        }

        $chunkSize = (int) $chunkSizeInput;
        if ($chunkSize <= 0) {
            Response::json(['error' => 'Chunk size must be a positive integer greater than zero.'], 400);
        }

        // Update database with selected configuration and reset to pending
        try {
            SplitJob::updateChunkSize($uuid, $chunkSize);
            SplitJob::updateStatus($uuid, 'pending');
        } catch (\Exception $e) {
            Response::json(['error' => 'Failed to initialize split configuration: ' . $e->getMessage()], 500);
        }

        // Spawn background worker process asynchronously on Windows (FR-13)
        $root = dirname(dirname(__DIR__));
        $workerScript = $root . '/scripts/worker.php';
        
        // Execute background cmd command that returns immediately (doesn't block the request)
        $cmd = "cmd /c start /B php " . escapeshellarg($workerScript) . " " . escapeshellarg($uuid) . " > NUL 2>&1";
        @exec($cmd);

        Response::json([
            'status'  => 'pending',
            'uuid'    => $uuid,
            'message' => 'The file splitter process has been queued in the background.'
        ]);
    }

    /**
     * Get JSON status for a job (polled by frontend).
     * GET /jobs/{uuid}/status
     *
     * @param string $uuid
     * @return void
     */
    public function status(string $uuid): void {
        $job = SplitJob::findByUuid($uuid);
        if (!$job) {
            Response::json(['error' => 'Job not found.'], 404);
        }

        Response::json([
            'uuid'               => $job['uuid'],
            'status'             => $job['status'],
            'total_rows'         => (int) $job['total_rows'],
            'chunk_size'         => (int) $job['chunk_size'],
            'total_output_files' => $job['total_output_files'] ? (int) $job['total_output_files'] : null,
            'error_message'      => $job['error_message'],
            'download_url'       => $job['status'] === 'completed' ? "/jobs/{$uuid}/download" : null
        ]);
    }

    /**
     * Download the completed split ZIP archive.
     * GET /jobs/{uuid}/download
     *
     * @param string $uuid
     * @return void
     */
    public function download(string $uuid): void {
        $job = SplitJob::findByUuid($uuid);
        if (!$job) {
            Response::error('Job not found.', 404);
        }

        if ($job['status'] !== 'completed') {
            Response::error('The requested split process has not finished or failed. Status: ' . $job['status'], 400);
        }

        $zipPath = $job['output_zip_path'];
        if (!$zipPath || !file_exists($zipPath)) {
            Response::error('The generated ZIP archive was not found on the server.', 404);
        }

        // Format clean download filename
        $originalBase = pathinfo($job['original_filename'], PATHINFO_FILENAME);
        $downloadName = $originalBase . '_split.zip';

        // Clear output buffering to prevent file corruption
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Send headers for file streaming download
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zipPath));
        
        // Read and stream file content
        readfile($zipPath);
        exit;
    }
}
