<div class="glass-card error-card" style="max-width: 600px; margin: 80px auto; text-align: center; padding: 48px;">
    <div class="error-icon" style="color: var(--accent-red); margin-bottom: 24px;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>
    <h1 style="font-size: 2.2rem; margin-bottom: 16px; font-weight: 700; background: linear-gradient(135deg, #ef4444, #f87171); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        System Alert (<?= (int) ($status ?? 400) ?>)
    </h1>
    <p style="color: var(--text-secondary); font-size: 1.1rem; line-height: 1.6; margin-bottom: 32px;">
        <?= htmlspecialchars($message ?? 'An unexpected exception has occurred while processing your request.') ?>
    </p>
    <div class="card-actions">
        <a href="/" class="btn btn-gradient">Return to Dashboard</a>
    </div>
</div>
