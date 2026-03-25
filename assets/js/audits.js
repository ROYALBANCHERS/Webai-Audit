/**
 * Audits Page - Display audit history
 */

const API_BASE = 'http://localhost:8000/api';

let currentPage = 1;
let totalPages = 1;
let currentFilters = {
    status: '',
    url: '',
};

document.addEventListener('DOMContentLoaded', () => {
    loadAudits();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('statusFilter').addEventListener('change', (e) => {
        currentFilters.status = e.target.value;
        currentPage = 1;
        loadAudits();
    });

    document.getElementById('searchInput').addEventListener('input', debounce((e) => {
        currentFilters.url = e.target.value;
        currentPage = 1;
        loadAudits();
    }, 500));

    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadAudits();
        }
    });

    document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            loadAudits();
        }
    });
}

async function loadAudits() {
    const container = document.getElementById('auditsList');
    container.innerHTML = `
        <div class="text-center py-12">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-400">Loading audits...</p>
        </div>
    `;

    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters,
        });

        const response = await fetch(`${API_BASE}/audits?${params}`);
        const data = await response.json();

        if (data.success) {
            displayAudits(data.data.data);
            updatePagination(data.data);
        } else {
            throw new Error(data.error || 'Failed to load audits');
        }
    } catch (error) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-5xl text-red-400 mb-4"></i>
                <p class="text-gray-400">Failed to load audits</p>
                <p class="text-gray-500 text-sm">${error.message}</p>
            </div>
        `;
    }
}

function displayAudits(audits) {
    const container = document.getElementById('auditsList');

    if (audits.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-folder-open text-5xl text-gray-600 mb-4"></i>
                <p class="text-gray-400">No audits found</p>
            </div>
        `;
        return;
    }

    container.innerHTML = audits.map(audit => createAuditCard(audit)).join('');
}

function createAuditCard(audit) {
    const statusColors = {
        completed: 'bg-green-500',
        running: 'bg-yellow-500',
        failed: 'bg-red-500',
        pending: 'bg-gray-500',
    };

    const scoreClass = audit.overall_score >= 80 ? 'text-green-400' :
                       audit.overall_score >= 60 ? 'text-yellow-400' :
                       audit.overall_score >= 40 ? 'text-orange-400' :
                       'text-red-400';

    const createdAt = new Date(audit.created_at).toLocaleString();

    return `
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 hover:border-gray-600 transition">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="w-3 h-3 rounded-full ${statusColors[audit.status]}"></span>
                        <a href="../index.html?audit=${audit.id}" class="text-blue-400 hover:underline font-medium">
                            ${audit.url}
                        </a>
                    </div>
                    <p class="text-gray-400 text-sm mb-3">Created: ${createdAt}</p>
                    <div class="flex items-center space-x-6 text-sm">
                        ${audit.overall_score !== null ? `
                            <div>
                                <span class="text-gray-400">Score:</span>
                                <span class="${scoreClass} font-bold">${audit.overall_score}</span>
                            </div>
                        ` : ''}
                        ${audit.seo_score !== null ? `
                            <div>
                                <span class="text-gray-400">SEO:</span>
                                <span class="text-blue-400 font-bold">${audit.seo_score}</span>
                            </div>
                        ` : ''}
                        ${audit.pages_count ? `
                            <div>
                                <span class="text-gray-400">Pages:</span>
                                <span class="text-white">${audit.pages_count}</span>
                            </div>
                        ` : ''}
                        ${audit.load_time ? `
                            <div>
                                <span class="text-gray-400">Time:</span>
                                <span class="text-white">${audit.load_time}s</span>
                            </div>
                        ` : ''}
                    </div>
                    ${audit.error_message ? `
                        <p class="text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>${audit.error_message}</p>
                    ` : ''}
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="viewAudit(${audit.id})" class="p-2 text-gray-400 hover:text-white transition" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="deleteAudit(${audit.id})" class="p-2 text-gray-400 hover:text-red-400 transition" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function updatePagination(paginationData) {
    totalPages = paginationData.last_page;

    document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;

    document.getElementById('pagination').classList.toggle('hidden', totalPages <= 1);
}

async function viewAudit(id) {
    // Store in localStorage and redirect to main page
    localStorage.setItem('viewAuditId', id);
    window.location.href = '../index.html';
}

async function deleteAudit(id) {
    if (!confirm('Are you sure you want to delete this audit?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/audits/${id}`, {
            method: 'DELETE',
        });

        const data = await response.json();

        if (data.success) {
            UI.showToast('Audit deleted successfully', 'success');
            loadAudits();
        } else {
            throw new Error(data.error || 'Failed to delete audit');
        }
    } catch (error) {
        UI.showToast(error.message, 'error');
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
