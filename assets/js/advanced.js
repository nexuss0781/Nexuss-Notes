/**
 * Nexus Notes - Advanced Features Module
 * Analytics dashboard, export/import, settings management
 */

// Analytics Dashboard
const Analytics = {
    async loadOverview() {
        try {
            const response = await fetch('/api/analytics?type=overview');
            const data = await response.json();
            if (data.success) {
                this.renderOverview(data.data);
            }
        } catch (error) {
            console.error('Failed to load analytics:', error);
        }
    },
    
    renderOverview(stats) {
        const elements = {
            'total-notes': stats.total_notes || 0,
            'active-notes': stats.active_notes || 0,
            'favorites-count': stats.favorites || 0,
            'archived-count': stats.archived_notes || 0,
            'categories-count': stats.total_categories || 0,
            'tags-count': stats.total_tags || 0,
            'created-today': stats.created_today || 0,
            'created-week': stats.created_this_week || 0
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });
        
        // Render chart if container exists
        this.renderActivityChart();
    },
    
    async renderActivityChart() {
        try {
            const response = await fetch('/api/analytics?type=daily');
            const data = await response.json();
            if (data.success && data.data.length > 0) {
                this.drawBarChart(data.data);
            }
        } catch (error) {
            console.error('Failed to load activity data:', error);
        }
    },
    
    drawBarChart(data) {
        const canvas = document.getElementById('activity-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const padding = 40;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);
        
        // Calculate bar dimensions
        const barWidth = (width - padding * 2) / data.length - 5;
        const maxValue = Math.max(...data.map(d => d.count));
        
        // Draw bars
        data.forEach((item, index) => {
            const barHeight = (item.count / maxValue) * (height - padding * 2);
            const x = padding + index * (barWidth + 5);
            const y = height - padding - barHeight;
            
            // Draw bar with gradient
            const gradient = ctx.createLinearGradient(x, y, x, height - padding);
            gradient.addColorStop(0, '#3b82f6');
            gradient.addColorStop(1, '#1d4ed8');
            
            ctx.fillStyle = gradient;
            ctx.fillRect(x, y, barWidth, barHeight);
            
            // Draw label
            ctx.fillStyle = '#666';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(item.date.slice(-5), x + barWidth / 2, height - padding + 15);
            
            // Draw value
            ctx.fillStyle = '#333';
            ctx.fillText(item.count, x + barWidth / 2, y - 5);
        });
    },
    
    async getStorageUsage() {
        try {
            const response = await fetch('/api/analytics?type=storage');
            const data = await response.json();
            if (data.success) {
                const el = document.getElementById('storage-usage');
                if (el) {
                    el.textContent = `${data.data.megabytes} MB`;
                }
            }
        } catch (error) {
            console.error('Failed to load storage usage:', error);
        }
    }
};

// Export/Import Manager
const ExportImport = {
    async exportNote(noteId, format = 'json') {
        const formats = {
            json: { mime: 'application/json', ext: 'json' },
            markdown: { mime: 'text/markdown', ext: 'md' },
            html: { mime: 'text/html', ext: 'html' },
            text: { mime: 'text/plain', ext: 'txt' }
        };
        
        const config = formats[format];
        if (!config) return;
        
        window.open(`/api/export?format=${format}&id=${noteId}`, '_blank');
    },
    
    async exportAllNotes() {
        window.open('/api/export?format=json', '_blank');
    },
    
    async importNotes(fileInput) {
        const file = fileInput.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const response = await fetch('/api/import', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: e.target.result
                });
                
                const result = await response.json();
                if (result.success) {
                    alert(`Successfully imported ${result.imported} notes!`);
                    location.reload();
                } else {
                    alert('Import failed: ' + result.error);
                }
            } catch (error) {
                alert('Import failed: ' + error.message);
            }
        };
        reader.readAsText(file);
    },
    
    downloadBackup() {
        this.exportAllNotes();
    }
};

// Settings Manager
const SettingsManager = {
    async loadSettings() {
        try {
            const response = await fetch('/api/settings');
            const data = await response.json();
            if (data.success) {
                this.applySettings(data.data);
                this.populateSettingsForm(data.data);
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    },
    
    applySettings(settings) {
        // Theme
        if (settings.theme_mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (settings.theme_mode === 'light') {
            document.documentElement.classList.remove('dark');
        }
        
        // Auto-save interval
        if (settings.auto_save_interval) {
            App.autoSaveInterval = settings.auto_save_interval * 1000;
        }
    },
    
    populateSettingsForm(settings) {
        Object.entries(settings).forEach(([key, value]) => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = value === true || value === '1';
                } else {
                    input.value = value;
                }
            }
        });
    },
    
    async saveSettings(formData) {
        try {
            const settings = {};
            formData.forEach((value, key) => {
                settings[key] = value;
            });
            
            const response = await fetch('/api/settings', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(settings)
            });
            
            const result = await response.json();
            if (result.success) {
                this.loadSettings();
                showToast('Settings saved successfully');
            }
        } catch (error) {
            showToast('Failed to save settings: ' + error.message, 'error');
        }
    },
    
    resetToDefaults() {
        if (confirm('Reset all settings to defaults?')) {
            localStorage.clear();
            location.reload();
        }
    }
};

// Modal Manager
const ModalManager = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },
    
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },
    
    init() {
        // Close modals on backdrop click
        document.querySelectorAll('[data-modal-backdrop]').forEach(backdrop => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    const modal = backdrop.closest('[id$="-modal"]');
                    if (modal) {
                        this.close(modal.id);
                    }
                }
            });
        });
    }
};

// Toast Notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 z-50 ${
        type === 'error' ? 'bg-red-500' : 'bg-green-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    requestAnimationFrame(() => {
        toast.classList.add('translate-y-0', 'opacity-100');
    });
    
    // Remove after delay
    setTimeout(() => {
        toast.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Keyboard Shortcuts
const KeyboardShortcuts = {
    init() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + N: New note
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                document.getElementById('new-note-btn')?.click();
            }
            
            // Ctrl/Cmd + S: Save note
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                document.getElementById('save-note-btn')?.click();
            }
            
            // Ctrl/Cmd + F: Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('search-input')?.focus();
            }
            
            // Escape: Close modals
            if (e.key === 'Escape') {
                document.querySelectorAll('[id$="-modal"]').forEach(modal => {
                    ModalManager.close(modal.id);
                });
            }
        });
    }
};

// Initialize advanced features
document.addEventListener('DOMContentLoaded', () => {
    ModalManager.init();
    KeyboardShortcuts.init();
    
    // Load analytics if on dashboard
    if (document.getElementById('total-notes')) {
        Analytics.loadOverview();
        Analytics.getStorageUsage();
    }
    
    // Setup settings form
    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        SettingsManager.loadSettings();
        settingsForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(settingsForm);
            SettingsManager.saveSettings(formData);
        });
    }
    
    // Setup export buttons
    document.getElementById('export-all-btn')?.addEventListener('click', () => {
        ExportImport.exportAllNotes();
    });
    
    // Setup import file input
    document.getElementById('import-file')?.addEventListener('change', (e) => {
        ExportImport.importNotes(e.target);
    });
});

// Export for global access
window.Analytics = Analytics;
window.ExportImport = ExportImport;
window.SettingsManager = SettingsManager;
window.ModalManager = ModalManager;
window.showToast = showToast;
