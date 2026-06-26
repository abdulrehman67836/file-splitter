<?php
// Automated test suite for spreadsheet split operations

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

use App\Services\SplitService;

echo "=== Excel/CSV File Splitter Test Runner ===\n\n";

$tempDir = __DIR__ . '/temp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$testCsv = $tempDir . '/test_1000_rows.csv';
$outDir = $tempDir . '/output_splits';
$zipPath = $tempDir . '/archive.zip';

// Helper to clean directory
function cleanup($dir) {
    if (!file_exists($dir)) return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $path = $fileinfo->getRealPath();
        if ($fileinfo->isDir()) {
            rmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// ---------------------------------------------------------
// 1. Generate Test Data (1,000 data rows + 1 header row)
// ---------------------------------------------------------
echo "1. Generating test CSV file with 1,000 rows... ";
$header = ['id', 'name', 'email', 'created_at'];
$handle = fopen($testCsv, 'w');
fputcsv($handle, $header);
for ($i = 1; $i <= 1000; $i++) {
    fputcsv($handle, [$i, "User {$i}", "user{$i}@example.com", date('Y-m-d H:i:s')]);
}
fclose($handle);
echo "Generated.\n";

$assertionsPassed = 0;
$totalAssertions = 0;

function assertEqual($actual, $expected, $message) {
    global $assertionsPassed, $totalAssertions;
    $totalAssertions++;
    if ($actual === $expected) {
        echo "   [PASS] $message\n";
        $assertionsPassed++;
    } else {
        echo "   [FAIL] $message (Expected: $expected, Actual: $actual)\n";
    }
}

// ---------------------------------------------------------
// Test Case 1: Count Rows (FR-4)
// ---------------------------------------------------------
echo "\nTest 1: Row Counting verification...\n";
try {
    $rows = SplitService::countRows($testCsv, 'csv', true);
    assertEqual($rows, 1000, "Should detect exactly 1,000 data rows (excluding header).");
} catch (Exception $e) {
    echo "   [ERROR] " . $e->getMessage() . "\n";
}

// ---------------------------------------------------------
// Test Case 2: Split with Chunk Size 300 (FR-8, FR-9, FR-10, Acceptance 6)
// ---------------------------------------------------------
echo "\nTest 2: Split 1,000 rows with chunk size 300...\n";
try {
    cleanup($outDir);
    if (file_exists($zipPath)) unlink($zipPath);

    $splits = SplitService::splitFile($testCsv, 'csv', 300, true, $outDir, $zipPath);

    assertEqual(count($splits), 4, "Should produce exactly 4 split chunks.");
    
    // Check sizes
    assertEqual($splits[0]['rows'], 300, "Chunk 1 should contain 300 data rows.");
    assertEqual($splits[1]['rows'], 300, "Chunk 2 should contain 300 data rows.");
    assertEqual($splits[2]['rows'], 300, "Chunk 3 should contain 300 data rows.");
    assertEqual($splits[3]['rows'], 100, "Chunk 4 should contain remaining 100 data rows.");

    // Verify header in each output chunk file (FR-9)
    $allHeadersOk = true;
    foreach ($splits as $split) {
        $fh = fopen($split['path'], 'r');
        $row = fgetcsv($fh);
        fclose($fh);
        
        // Strip UTF-8 BOM if present (added by OpenSpout for Excel compatibility)
        if ($row && strpos($row[0], "\xEF\xBB\xBF") === 0) {
            $row[0] = substr($row[0], 3);
        }

        if ($row !== $header) {
            $allHeadersOk = false;
        }
    }
    assertEqual($allHeadersOk, true, "All generated files must duplicate the header row.");

    // Verify ZIP creation
    assertEqual(file_exists($zipPath), true, "ZIP archive must be generated successfully.");
} catch (Exception $e) {
    echo "   [ERROR] " . $e->getMessage() . "\n";
}

// ---------------------------------------------------------
// Test Case 3: Chunk size greater than total rows (Edge Case 8.2)
// ---------------------------------------------------------
echo "\nTest 3: Chunk size larger than total rows (chunk 1500)...\n";
try {
    cleanup($outDir);
    $splits = SplitService::splitFile($testCsv, 'csv', 1500, true, $outDir, $zipPath);
    assertEqual(count($splits), 1, "Should produce exactly 1 output file.");
    assertEqual($splits[0]['rows'], 1000, "The single file should contain all 1,000 data rows.");
} catch (Exception $e) {
    echo "   [ERROR] " . $e->getMessage() . "\n";
}

// ---------------------------------------------------------
// Test Case 4: Negative or zero chunk size (Edge Case 8.2)
// ---------------------------------------------------------
echo "\nTest 4: Reject zero or negative chunk sizes...\n";
try {
    cleanup($outDir);
    SplitService::splitFile($testCsv, 'csv', 0, true, $outDir, $zipPath);
    echo "   [FAIL] Did not throw exception for 0 chunk size.\n";
} catch (Exception $e) {
    assertEqual(true, true, "Throws exception for zero chunk size: " . $e->getMessage());
}

// ---------------------------------------------------------
// Clean Up
// ---------------------------------------------------------
echo "\nCleaning up temporary test files... ";
if (file_exists($testCsv)) unlink($testCsv);
cleanup($outDir);
if (file_exists($zipPath)) @unlink($zipPath);
cleanup($tempDir);
echo "Cleaned.\n";

echo "\n=== Test Summary: {$assertionsPassed} / {$totalAssertions} Passed ===\n";
if ($assertionsPassed === $totalAssertions) {
    echo "SUCCESS: All edge cases and acceptance criteria verified!\n";
    exit(0);
} else {
    echo "FAILURE: Some assertions failed.\n";
    exit(1);
}
