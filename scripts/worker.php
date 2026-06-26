<?php
// CLI Worker process to handle spreadsheet splitting asynchronously.

if (php_sapi_name() !== 'cli') {
    die("This script can only be run via command line interface (CLI).\n");
}

if ($argc < 2) {
    die("Usage: php worker.php {job_uuid}\n");
}

$uuid = trim($argv[1]);

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Database;
use App\Models\SplitJob;
use App\Models\SplitJobFile;
use App\Services\SplitService;

Env::load($root . '/.env');

echo "[" . date('Y-m-d H:i:s') . "] Starting background worker for job UUID: {$uuid}...\n";

// 1. Retrieve the pending job
try {
    $job = SplitJob::findByUuid($uuid);
    if (!$job) {
        throw new Exception("Job UUID '{$uuid}' not found in database.");
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Update status to 'processing' (FR-14)
try {
    SplitJob::updateStatus($uuid, 'processing');
    echo "[" . date('Y-m-d H:i:s') . "] Status updated to 'processing'.\n";
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to transition status to processing: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Execute splitting operations
try {
    $storageDir = Env::get('STORAGE_DIR', 'storage');
    $outDir = $root . '/' . $storageDir . '/outputs/' . $uuid;
    $archivePath = $root . '/' . $storageDir . '/archives/' . $uuid . '.zip';
    
    // Parse boolean condition
    $hasHeader = ($job['has_header'] === 'true' || $job['has_header'] === true || $job['has_header'] === 1 || $job['has_header'] === '1');

    echo "[" . date('Y-m-d H:i:s') . "] Splitting file '{$job['original_filename']}' (Type: {$job['file_type']}, Chunk Size: {$job['chunk_size']})...\n";

    // Run core streaming split
    $splitFiles = SplitService::splitFile(
        $job['stored_path'],
        $job['file_type'],
        (int) $job['chunk_size'],
        $hasHeader,
        $outDir,
        $archivePath
    );

    echo "[" . date('Y-m-d H:i:s') . "] Splitting completed. Inserting chunk metadata into database...\n";

    // 4. Save results to split_job_files in a Transaction
    Database::beginTransaction();
    foreach ($splitFiles as $file) {
        SplitJobFile::create([
            'split_job_id' => $job['id'],
            'file_name'    => $file['name'],
            'row_count'    => $file['rows'],
            'file_path'    => $file['path']
        ]);
    }
    Database::commit();

    // 5. Update job status to 'completed'
    SplitJob::updateStatus($uuid, 'completed', null, count($splitFiles), $archivePath);
    echo "[" . date('Y-m-d H:i:s') . "] SUCCESS: Job {$uuid} completed. Produced " . count($splitFiles) . " chunk files.\n";

} catch (Exception $e) {
    // Rollback any active database transactions
    try {
        if (Database::getConnection()->inTransaction()) {
            Database::rollback();
        }
    } catch (Exception $ex) {
        // Suppress rollback errors
    }

    // Update job status to 'failed' and log error details (FR-16)
    SplitJob::updateStatus($uuid, 'failed', $e->getMessage());
    echo "[" . date('Y-m-d H:i:s') . "] FAILED: Job {$uuid} failed. Reason: " . $e->getMessage() . "\n";
    exit(1);
}
