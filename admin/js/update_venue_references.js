// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll to bottom of migration log
    const migrationLog = document.querySelector('.migration-log');
    if (migrationLog) {
        migrationLog.scrollTop = migrationLog.scrollHeight;
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add copy functionality for log entries
    const copyButtons = document.querySelectorAll('.copy-log');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const logText = Array.from(document.querySelectorAll('.log-entry'))
                .map(entry => entry.textContent.trim())
                .join('\n');
            
            navigator.clipboard.writeText(logText).then(() => {
                // Show success message
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check"></i> Copied!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        });
    });

    // Add confirmation for navigation away from page
    const backButton = document.querySelector('a[href="manage_venues.php"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            const migrationInProgress = document.querySelector('.alert-warning');
            if (migrationInProgress) {
                if (!confirm('Migration may still be in progress. Are you sure you want to leave this page?')) {
                    e.preventDefault();
                }
            }
        });
    }
}); 