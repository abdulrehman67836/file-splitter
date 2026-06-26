<!-- Dashboard Metrics Row -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrapper indigo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Jobs</span>
            <h3 class="stat-value"><?= number_format($stats['total_jobs']) ?></h3>
            <p class="stat-subtext">Overall split tasks registered</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Completed Jobs</span>
            <h3 class="stat-value"><?= number_format($stats['completed_jobs']) ?></h3>
            <p class="stat-subtext">Successfully split & archived</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper red">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Failed Jobs</span>
            <h3 class="stat-value"><?= number_format($stats['failed_jobs']) ?></h3>
            <p class="stat-subtext">Failed uploads or conversions</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper violet">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Rows Processed</span>
            <h3 class="stat-value"><?= number_format($stats['total_rows_processed']) ?></h3>
            <p class="stat-subtext">Total data records handled</p>
        </div>
    </div>
</div>

<!-- Core Splitter Panel Grid -->
<div class="workspace-grid">
    <!-- Left Panel: Split Process Wizard (Dynamic States) -->
    <div class="glass-card splitter-card">
        <div class="card-header">
            <h3>Split Wizard</h3>
            <p>Perform memory-efficient spreadsheet division</p>
        </div>
        
        <!-- State 1: Upload Dropzone -->
        <div id="state-upload" class="wizard-state active">
            <div class="dropzone" id="dropzone">
                <input type="file" id="file-input" accept=".xlsx,.csv" style="display: none;">
                <div class="dropzone-inner">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="upload-icon">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <h4>Drag and drop spreadsheet here</h4>
                    <p>Supports <strong>.xlsx</strong> and <strong>.csv</strong> files (Max 10 MB)</p>
                    <button class="btn btn-secondary" id="browse-btn">Browse Files</button>
                </div>
            </div>

            <div class="options-bar">
                <label class="custom-checkbox">
                    <input type="checkbox" id="header-checkbox" checked>
                    <span class="checkbox-box"></span>
                    <span class="checkbox-label">First row is a header row (replicated in output chunks)</span>
                </label>
            </div>

            <button class="btn btn-gradient btn-full" id="upload-submit-btn" disabled>
                Upload and Analyze File
            </button>
        </div>

        <!-- State 2: Analysis & Configuration -->
        <div id="state-config" class="wizard-state">
            <div class="file-details-box">
                <div class="file-meta">
                    <span class="meta-label">Selected File:</span>
                    <strong class="meta-val" id="config-filename">sample.xlsx</strong>
                </div>
                <div class="file-meta">
                    <span class="meta-label">Total Data Rows Detected:</span>
                    <strong class="meta-val highlight" id="config-total-rows">0</strong>
                </div>
            </div>

            <!-- Pre-calculated Recommendations Grid (FR-5) -->
            <div class="recommendations-section">
                <h4>Suggested Row Splits</h4>
                <div class="recommendations-grid">
                    <div class="recommendation-card" data-chunk="50">
                        <span class="chunk-rows">50 rows</span>
                        <span class="chunk-files" id="rec-50-files">0 files</span>
                    </div>
                    <div class="recommendation-card" data-chunk="100">
                        <span class="chunk-rows">100 rows</span>
                        <span class="chunk-files" id="rec-100-files">0 files</span>
                    </div>
                    <div class="recommendation-card" data-chunk="200">
                        <span class="chunk-rows">200 rows</span>
                        <span class="chunk-files" id="rec-200-files">0 files</span>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label for="chunk-size-input">Custom Rows Per File (Chunk Size)</label>
                <input type="number" id="chunk-size-input" class="form-input" placeholder="e.g. 500" min="1" step="1">
                <p class="input-error" id="chunk-size-error"></p>
            </div>

            <div class="btn-row">
                <button class="btn btn-secondary" id="config-back-btn">Go Back</button>
                <button class="btn btn-gradient" id="split-execute-btn">Execute Split</button>
            </div>
        </div>

        <!-- State 3: Async Processing State -->
        <div id="state-process" class="wizard-state">
            <div class="process-loader-section">
                <div class="glowing-spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-core"></div>
                </div>
                <h4 id="process-title">Processing File...</h4>
                <p id="process-subtitle">Do not close this tab. The file is being processed asynchronously.</p>
            </div>

            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progress-fill" style="width: 25%;"></div>
            </div>
            
            <div class="process-log" id="process-log-console">
                <div class="log-line info">Job created in database queue.</div>
            </div>
        </div>

        <!-- State 4: Completion & Download State -->
        <div id="state-result" class="wizard-state">
            <div class="result-success-section">
                <div class="success-ring-glow">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="success-check-icon">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h4>File Split Successfully!</h4>
                <p>All chunks have been generated and packaged into a ZIP archive.</p>
            </div>

            <div class="result-stats-box">
                <div class="res-meta">
                    <span>Generated Chunks:</span>
                    <strong id="result-total-files">0 files</strong>
                </div>
                <div class="res-meta">
                    <span>Rows per Chunk:</span>
                    <strong id="result-chunk-size">0</strong>
                </div>
            </div>

            <div class="btn-row-stacked">
                <a href="#" class="btn btn-gradient btn-full text-center" id="result-download-btn" download>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: middle;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Download ZIP Archive
                </a>
                <button class="btn btn-secondary btn-full" id="result-restart-btn">Split Another File</button>
            </div>
        </div>
    </div>

    <!-- Right Panel: Database History Log (PostgreSQL Integration) -->
    <div class="glass-card history-card">
        <div class="card-header">
            <h3>Recent Job History</h3>
            <p>Direct tracking from PostgreSQL database</p>
        </div>

        <div class="history-table-container">
            <?php if (empty($recentJobs)): ?>
                <div class="empty-history">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <circle cx="11.5" cy="14.5" r="2.5"></circle>
                        <path d="M16 20h-9"></path>
                    </svg>
                    <p>No split jobs logged in database yet.</p>
                </div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Total Rows</th>
                            <th>Chunk Size</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentJobs as $job): ?>
                            <tr>
                                <td class="file-name-cell" title="<?= htmlspecialchars($job['original_filename']) ?>">
                                    <?= htmlspecialchars(strlen($job['original_filename']) > 24 ? substr($job['original_filename'], 0, 22) . '...' : $job['original_filename']) ?>
                                </td>
                                <td><?= number_format($job['total_rows']) ?></td>
                                <td><?= $job['chunk_size'] > 0 ? number_format($job['chunk_size']) : '-' ?></td>
                                <td>
                                    <span class="status-badge <?= htmlspecialchars($job['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($job['status'])) ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <?= date('M d, Y H:i', strtotime($job['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($job['status'] === 'completed'): ?>
                                        <a href="/jobs/<?= htmlspecialchars($job['uuid']) ?>/download" class="action-btn-link" title="Download Archive">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="7 10 12 15 17 10"></polyline>
                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                            </svg>
                                        </a>
                                    <?php else: ?>
                                        <span class="action-btn-disabled" title="No download available">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
