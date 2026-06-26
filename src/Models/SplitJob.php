<?php
namespace App\Models;

use App\Core\Database;

class SplitJob {
    /**
     * Insert a new split job into database.
     *
     * @param array $data
     * @return array|null The inserted row
     */
    public static function create(array $data): ?array {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);
        
        $sql = "INSERT INTO split_jobs (" . implode(', ', $fields) . ", created_at, updated_at) 
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                RETURNING *";

        return Database::fetch($sql, $data);
    }

    /**
     * Find a job by its public UUID.
     *
     * @param string $uuid
     * @return array|null
     */
    public static function findByUuid(string $uuid): ?array {
        $sql = "SELECT * FROM split_jobs WHERE uuid = :uuid";
        return Database::fetch($sql, ['uuid' => $uuid]);
    }

    /**
     * Update the status and processing results of a job.
     *
     * @param string $uuid
     * @param string $status pending | processing | completed | failed
     * @param string|null $errorMessage
     * @param int|null $totalOutputFiles
     * @param string|null $outputZipPath
     * @return bool
     */
    public static function updateStatus(
        string $uuid, 
        string $status, 
        ?string $errorMessage = null, 
        ?int $totalOutputFiles = null, 
        ?string $outputZipPath = null
    ): bool {
        $sql = "UPDATE split_jobs 
                SET status = :status, 
                    error_message = :error_message, 
                    total_output_files = COALESCE(:total_output_files, total_output_files),
                    output_zip_path = COALESCE(:output_zip_path, output_zip_path),
                    processed_at = CASE WHEN :status_check IN ('completed', 'failed') THEN CURRENT_TIMESTAMP ELSE processed_at END,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE uuid = :uuid";
        
        $stmt = Database::query($sql, [
            'uuid'               => $uuid,
            'status'             => $status,
            'status_check'       => $status,
            'error_message'      => $errorMessage,
            'total_output_files' => $totalOutputFiles,
            'output_zip_path'    => $outputZipPath
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Update chunk size configuration for an existing job.
     *
     * @param string $uuid
     * @param int $chunkSize
     * @return bool
     */
    public static function updateChunkSize(string $uuid, int $chunkSize): bool {
        $sql = "UPDATE split_jobs SET chunk_size = :chunk_size, updated_at = CURRENT_TIMESTAMP WHERE uuid = :uuid";
        $stmt = Database::query($sql, ['uuid' => $uuid, 'chunk_size' => $chunkSize]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update the total rows detected in a file.
     *
     * @param string $uuid
     * @param int $totalRows
     * @return bool
     */
    public static function updateRows(string $uuid, int $totalRows): bool {
        $sql = "UPDATE split_jobs SET total_rows = :total_rows, updated_at = CURRENT_TIMESTAMP WHERE uuid = :uuid";
        $stmt = Database::query($sql, ['uuid' => $uuid, 'total_rows' => $totalRows]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get recent split jobs.
     *
     * @param int $limit
     * @return array
     */
    public static function getRecentJobs(int $limit = 10): array {
        $cleanLimit = (int) $limit;
        $sql = "SELECT * FROM split_jobs ORDER BY created_at DESC LIMIT {$cleanLimit}";
        return Database::fetchAll($sql);
    }

    /**
     * Get aggregated statistics about split jobs.
     *
     * @return array
     */
    public static function getStats(): array {
        $sql = "SELECT 
                    COUNT(*) as total_jobs,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_jobs,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_jobs,
                    COALESCE(SUM(total_rows), 0) as total_rows_processed
                FROM split_jobs";
        
        $stats = Database::fetch($sql);
        
        return $stats ?: [
            'total_jobs'           => 0,
            'completed_jobs'       => 0,
            'failed_jobs'          => 0,
            'total_rows_processed' => 0
        ];
    }
}