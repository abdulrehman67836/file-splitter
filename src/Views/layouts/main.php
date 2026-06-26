<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Excel & CSV File Splitter Hub') ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS Styling -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation Drawer -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM5 5H11V11H5V5ZM5 19V13H11V19H5ZM19 19H13V13H19V19ZM19 11H13V5H19V11Z" fill="url(#logo-grad)"/>
                    <defs>
                        <linearGradient id="logo-grad" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#6366f1"/>
                            <stop offset="1" stop-color="#a855f7"/>
                        </linearGradient>
                    </defs>
                </svg>
                <span class="brand-text">Splitter Hub</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="/" class="menu-item active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                        <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                        <rect x="3" y="16" width="7" height="5" rx="1"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="version">Version 1.0.0 (Draft)</div>
                <div class="status-indicator">
                    <span class="status-dot green"></span>
                    <span>PostgreSQL Online</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="page-title-section">
                    <h2>Spreadsheet Processing</h2>
                </div>
                <div class="user-profile-section">
                    <div class="environment-badge">Development</div>
                    <div class="user-avatar">
                        <span>AR</span>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <?= $content ?>
            </div>
        </main>
    </div>

    <!-- Notification Toast Alerts Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Global Application script -->
    <script src="/js/app.js"></script>
</body>
</html>
