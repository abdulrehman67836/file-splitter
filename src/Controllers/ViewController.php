<?php
namespace App\Controllers;

use App\Core\Response;
use App\Models\SplitJob;

class ViewController {
    /**
     * Renders the main dashboard index view.
     *
     * @return void
     */
    public function index(): void {
        try {
            $stats = SplitJob::getStats();
            $recentJobs = SplitJob::getRecentJobs(10);
        } catch (\Exception $e) {
            // Fallback empty metrics in case of DB connection issues before setup
            $stats = [
                'total_jobs'           => 0,
                'completed_jobs'       => 0,
                'failed_jobs'          => 0,
                'total_rows_processed' => 0
            ];
            $recentJobs = [];
        }

        Response::render('index', [
            'stats'      => $stats,
            'recentJobs' => $recentJobs,
            'title'      => 'Excel & CSV File Splitter Hub'
        ]);
    }
}
