<?php
namespace App\Services;

use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row;
use Exception;
use ZipArchive;

class SplitService {
    /**
     * Instantiates the correct OpenSpout Reader based on file extension.
     *
     * @param string $extension
     * @return mixed CsvReader|XlsxReader
     */
    private static function createReader(string $extension): mixed {
        if ($extension === 'csv') {
            return new CsvReader();
        } elseif ($extension === 'xlsx') {
            return new XlsxReader();
        }
        throw new Exception("Unsupported reader extension: .{$extension}");
    }

    /**
     * Instantiates the correct OpenSpout Writer based on file extension.
     *
     * @param string $extension
     * @return mixed CsvWriter|XlsxWriter
     */
    private static function createWriter(string $extension): mixed {
        if ($extension === 'csv') {
            return new CsvWriter();
        } elseif ($extension === 'xlsx') {
            return new XlsxWriter();
        }
        throw new Exception("Unsupported writer extension: .{$extension}");
    }

    /**
     * Count total data rows in a spreadsheet in a streaming manner.
     *
     * @param string $filePath
     * @param string $extension
     * @param bool $hasHeader
     * @return int Total number of records (excluding header)
     */
    public static function countRows(string $filePath, string $extension, bool $hasHeader = true): int {
        $reader = self::createReader($extension);
        $reader->open($filePath);

        $rowCount = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                // Skip completely empty rows if needed (default is to count them)
                $rowCount++;
            }
            // Only process the first worksheet (Assumption 2.5)
            break; 
        }
        $reader->close();

        if ($hasHeader && $rowCount > 0) {
            return $rowCount - 1; // Exclude header row
        }
        
        return $rowCount;
    }

    /**
     * Splits a large spreadsheet into multiple smaller files and packages them into a ZIP.
     *
     * @param string $filePath Original uploaded file path
     * @param string $extension csv or xlsx
     * @param int $chunkSize Number of rows per split file
     * @param bool $hasHeader
     * @param string $outDir Output folder path for chunks
     * @param string $archivePath Output file path for final ZIP archive
     * @return array Metadata list of generated files
     * @throws Exception
     */
    public static function splitFile(
        string $filePath, 
        string $extension, 
        int $chunkSize, 
        bool $hasHeader, 
        string $outDir, 
        string $archivePath
    ): array {
        if ($chunkSize <= 0) {
            throw new Exception("Chunk size must be a positive integer.");
        }

        if (!file_exists($outDir)) {
            mkdir($outDir, 0777, true);
        }

        $reader = self::createReader($extension);
        $reader->open($filePath);

        $headerRow = null;
        $splitFiles = [];
        $currentChunkIndex = 0;
        $rowsInCurrentFile = 0;
        $writer = null;
        $currentChunkPath = '';
        $currentChunkName = '';

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                // 1. Handle header row extraction
                if ($hasHeader && $headerRow === null) {
                    $headerRow = $row;
                    continue; // Skip writing header as a data row
                }

                // 2. Initialize writer if null (start of a new chunk)
                if ($writer === null) {
                    $currentChunkIndex++;
                    $currentChunkName = "part_" . sprintf("%03d", $currentChunkIndex) . "." . $extension;
                    $currentChunkPath = $outDir . '/' . $currentChunkName;
                    
                    $writer = self::createWriter($extension);
                    $writer->openToFile($currentChunkPath);

                    // Repeat header row in every output file (FR-9)
                    if ($hasHeader && $headerRow !== null) {
                        $writer->addRow($headerRow);
                    }
                    
                    $rowsInCurrentFile = 0;
                }

                // 3. Write data row to active writer
                $writer->addRow($row);
                $rowsInCurrentFile++;

                // 4. Close chunk writer if target size is reached
                if ($rowsInCurrentFile === $chunkSize) {
                    $writer->close();
                    $splitFiles[] = [
                        'name' => $currentChunkName,
                        'path' => $currentChunkPath,
                        'rows' => $rowsInCurrentFile
                    ];
                    $writer = null;
                }
            }
            break; // Process only the first sheet
        }

        // 5. Close any trailing open writer (FR-10: remaining rows in final smaller file)
        if ($writer !== null) {
            $writer->close();
            $splitFiles[] = [
                'name' => $currentChunkName,
                'path' => $currentChunkPath,
                'rows' => $rowsInCurrentFile
            ];
        }

        $reader->close();

        // 6. Handle edge case: empty spreadsheet file (0 data rows)
        if (empty($splitFiles)) {
            throw new Exception("The uploaded file has no data rows to split.");
        }

        // 7. Package generated chunk files into a single ZIP archive (FR-11)
        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($splitFiles as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();
        } else {
            throw new Exception("Failed to package split files into a ZIP archive.");
        }

        return $splitFiles;
    }
}
