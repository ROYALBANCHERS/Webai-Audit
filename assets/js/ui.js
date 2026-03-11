/**
 * UI Module - Handles UI interactions
 */

const UI = (() => {
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        // Remove existing toast
        const existing = document.querySelector('.toast');
        if (existing) {
            existing.remove();
        }

        // Create new toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="flex items-center space-x-3">
                <i class="fas fa-${getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto hide
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'times-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Update progress bar
     */
    function updateProgress(percent, status) {
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressStatus = document.getElementById('progressStatus');

        if (progressBar) {
            progressBar.style.width = `${percent}%`;
        }
        if (progressPercent) {
            progressPercent.textContent = `${Math.round(percent)}%`;
        }
        if (progressStatus) {
            progressStatus.textContent = status;
        }
    }

    /**
     * Show/hide section
     */
    function showSection(sectionId) {
        document.getElementById('hero').classList.add('hidden');
        document.getElementById('progressSection').classList.add('hidden');
        document.getElementById('resultsSection').classList.add('hidden');

        if (sectionId === 'hero') {
            document.getElementById('hero').classList.remove('hidden');
        } else if (sectionId === 'progress') {
            document.getElementById('progressSection').classList.remove('hidden');
        } else if (sectionId === 'results') {
            document.getElementById('resultsSection').classList.remove('hidden');
        }
    }

    /**
     * Switch tab
     */
    function switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        // Update tab panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `tab-${tabName}`);
        });
    }

    /**
     * Create badge element
     */
    function createBadge(text, type = 'primary') {
        const badge = document.createElement('span');
        badge.className = `badge badge-${type}`;
        badge.textContent = text;
        return badge;
    }

    /**
     * Create severity indicator
     */
    function getSeverityColor(severity) {
        const colors = {
            critical: '#dc2626',
            high: '#ea580c',
            medium: '#ca8a04',
            low: '#6b7280',
            info: '#06b6d4',
        };
        return colors[severity] || colors.info;
    }

    /**
     * Format score with color
     */
    function formatScore(score) {
        if (score >= 80) return { color: 'text-green-400', class: 'success' };
        if (score >= 60) return { color: 'text-yellow-400', class: 'warning' };
        return { color: 'text-red-400', class: 'danger' };
    }

    /**
     * Truncate text
     */
    function truncate(text, length = 100) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    /**
     * Format bytes
     */
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Format duration
     */
    function formatDuration(seconds) {
        if (seconds < 1) return `${Math.round(seconds * 1000)}ms`;
        return `${seconds.toFixed(2)}s`;
    }

    /**
     * Copy to clipboard
     */
    async function copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            showToast('Copied to clipboard', 'success');
        } catch (err) {
            showToast('Failed to copy', 'error');
        }
    }

    /**
     * Export data as JSON
     */
    function exportJSON(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        downloadBlob(blob, filename || 'audit-results.json');
    }

    /**
     * Download blob as file
     */
    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Initialize event listeners
     */
    function init() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });

        // Options toggle
        const optionsToggle = document.getElementById('optionsToggle');
        const optionsPanel = document.getElementById('optionsPanel');
        const optionsChevron = document.getElementById('optionsChevron');

        if (optionsToggle && optionsPanel) {
            optionsToggle.addEventListener('click', () => {
                optionsPanel.classList.toggle('hidden');
                optionsChevron.classList.toggle('rotate-180');
            });
        }

        // Issue filter buttons
        document.querySelectorAll('.issue-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.issue-filter').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterIssues(btn.dataset.severity);
            });
        });

        // Export buttons
        document.getElementById('exportJson')?.addEventListener('click', () => {
            if (window.currentAuditData) {
                exportJSON(window.currentAuditData, 'audit-results.json');
            }
        });
    }

    /**
     * Filter issues by severity
     */
    function filterIssues(severity) {
        document.querySelectorAll('.issue-card').forEach(card => {
            if (severity === 'all' || card.dataset.severity === severity) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    return {
        showToast,
        updateProgress,
        showSection,
        switchTab,
        createBadge,
        getSeverityColor,
        formatScore,
        truncate,
        formatBytes,
        formatDuration,
        copyToClipboard,
        exportJSON,
        init,
    };
})();
