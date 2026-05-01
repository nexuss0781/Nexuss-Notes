/**
 * Nexus Notes - Main Application Logic
 * Handles UI interactions and API communication
 */

// Application State
const App = {
    currentFilter: 'all',
    currentFolderId: null,
    currentTagId: null,
    searchQuery: '',
    notes: [],
    folders: [],
    tags: []
};

// Initialize application on DOM load
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
    initializeMobileMenu();
    loadStats();
    loadFolders();
    loadTags();
    loadNotes();
    initializeSearch();
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

/**
 * Theme Management
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Check for saved theme preference or default to light
    const savedTheme = Utils.storage.get('theme', 'light');
    if (savedTheme === 'dark') {
        html.classList.add('dark');
    }
    
    themeToggle?.addEventListener('click', () => {
        html.classList.toggle('dark');
        const newTheme = html.classList.contains('dark') ? 'dark' : 'light';
        Utils.storage.set('theme', newTheme);
    });
}

/**
 * Mobile Menu Toggle
 */
function initializeMobileMenu() {
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    
    menuBtn?.addEventListener('click', () => {
        mobileMenu?.classList.toggle('hidden');
    });
}

/**
 * Load Dashboard Statistics
 */
async function loadStats() {
    try {
        const response = await fetch('api.php?action=get_stats');
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data;
            document.getElementById('totalNotes').textContent = stats.total_notes;
            document.getElementById('pinnedNotes').textContent = stats.pinned_notes;
            document.getElementById('totalFolders').textContent = stats.total_folders;
            document.getElementById('totalTags').textContent = stats.total_tags;
        }
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

/**
 * Load Folders
 */
async function loadFolders() {
    try {
        const response = await fetch('api.php?action=get_folders');
        const result = await response.json();
        
        if (result.success) {
            App.folders = result.data;
            renderFolders();
        }
    } catch (error) {
        console.error('Failed to load folders:', error);
    }
}

/**
 * Render Folders List
 */
function renderFolders() {
    const container = document.getElementById('foldersList');
    if (!container) return;
    
    container.innerHTML = App.folders.map(folder => `
        <button onclick="filterByFolder(${folder.id})" 
                class="btn-ghost w-full justify-start text-sm">
            <i data-lucide="${folder.icon || 'folder'}" class="w-4 h-4 mr-2" style="color: ${folder.color}"></i>
            <span class="truncate">${Utils.escapeHtml(folder.name)}</span>
            <span class="ml-auto text-xs text-gray-400">${folder.note_count}</span>
        </button>
    `).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Load Tags
 */
async function loadTags() {
    try {
        const response = await fetch('api.php?action=get_tags');
        const result = await response.json();
        
        if (result.success) {
            App.tags = result.data;
            renderTags();
        }
    } catch (error) {
        console.error('Failed to load tags:', error);
    }
}

/**
 * Render Tags
 */
function renderTags() {
    const container = document.getElementById('tagsList');
    if (!container) return;
    
    container.innerHTML = App.tags.map(tag => `
        <button onclick="filterByTag(${tag.id})" 
                class="badge hover:opacity-80 transition-opacity cursor-pointer">
            <span class="w-2 h-2 rounded-full mr-1.5" style="background-color: ${tag.color}"></span>
            ${Utils.escapeHtml(tag.name)}
            <span class="ml-1.5 opacity-60">${tag.note_count}</span>
        </button>
    `).join('');
}

/**
 * Load Notes
 */
async function loadNotes() {
    const grid = document.getElementById('notesGrid');
    const emptyState = document.getElementById('emptyState');
    const loadingState = document.getElementById('loadingState');
    
    // Show loading state
    grid.innerHTML = '';
    emptyState?.classList.add('hidden');
    loadingState?.classList.remove('hidden');
    
    try {
        let url = 'api.php?action=get_notes';
        const params = new URLSearchParams();
        
        if (App.currentFolderId) params.append('folder_id', App.currentFolderId);
        if (App.currentTagId) params.append('tag_id', App.currentTagId);
        if (App.searchQuery) params.append('search', App.searchQuery);
        if (App.currentFilter === 'pinned') params.append('archived', 0);
        
        if (params.toString()) url += '&' + params.toString();
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            App.notes = result.data;
            
            // Filter pinned if needed
            if (App.currentFilter === 'pinned') {
                App.notes = App.notes.filter(n => n.is_pinned == 1);
            }
            
            renderNotes();
        }
    } catch (error) {
        console.error('Failed to load notes:', error);
        Utils.toast('Failed to load notes', 'error');
    } finally {
        loadingState?.classList.add('hidden');
    }
}

/**
 * Render Notes Grid
 */
function renderNotes() {
    const grid = document.getElementById('notesGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (!grid) return;
    
    if (App.notes.length === 0) {
        grid.innerHTML = '';
        emptyState?.classList.remove('hidden');
        return;
    }
    
    emptyState?.classList.add('hidden');
    
    grid.innerHTML = App.notes.map(note => `
        <div class="note-card" onclick="openNote(${note.id})">
            <div class="flex items-start justify-between mb-2">
                <h3 class="note-card-title">${Utils.escapeHtml(note.title) || 'Untitled Note'}</h3>
                ${note.is_pinned == 1 ? '<i data-lucide="pin" class="w-4 h-4 text-yellow-500 flex-shrink-0"></i>' : ''}
            </div>
            <p class="note-card-excerpt">${Utils.escapeHtml(Utils.stripHtml(note.content)) || 'No content'}</p>
            <div class="note-card-meta">
                <span>${Utils.timeAgo(new Date(note.updated_at).getTime() / 1000)}</span>
                <div class="flex items-center gap-2">
                    ${note.folder_name ? `<span class="text-xs" style="color: ${note.folder_color}">${Utils.escapeHtml(note.folder_name)}</span>` : ''}
                </div>
            </div>
            ${note.tags && note.tags.length > 0 ? `
                <div class="flex flex-wrap gap-1 mt-3">
                    ${note.tags.slice(0, 3).map(tag => `
                        <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: ${tag.color}20; color: ${tag.color}">
                            ${Utils.escapeHtml(tag.name)}
                        </span>
                    `).join('')}
                    ${note.tags.length > 3 ? `<span class="text-xs text-gray-400">+${note.tags.length - 3}</span>` : ''}
                </div>
            ` : ''}
        </div>
    `).join('');
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Initialize Search
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    
    // Debounced search
    const debouncedSearch = Utils.debounce((query) => {
        App.searchQuery = query;
        loadNotes();
    }, 300);
    
    searchInput?.addEventListener('input', (e) => {
        debouncedSearch(e.target.value);
    });
}

/**
 * Filter Notes
 */
function filterNotes(filter) {
    App.currentFilter = filter;
    App.currentFolderId = null;
    App.currentTagId = null;
    loadNotes();
}

/**
 * Filter by Folder
 */
function filterByFolder(folderId) {
    App.currentFilter = 'folder';
    App.currentFolderId = folderId;
    App.currentTagId = null;
    loadNotes();
}

/**
 * Filter by Tag
 */
function filterByTag(tagId) {
    App.currentFilter = 'tag';
    App.currentTagId = tagId;
    App.currentFolderId = null;
    loadNotes();
}

/**
 * Create New Note
 */
async function createNewNote() {
    try {
        const response = await fetch('api.php?action=create_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title: '', content: '' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            openNote(result.data.id);
            loadStats();
        }
    } catch (error) {
        console.error('Failed to create note:', error);
        Utils.toast('Failed to create note', 'error');
    }
}

/**
 * Open Note in Modal
 */
async function openNote(noteId) {
    try {
        const response = await fetch(`api.php?action=get_note&id=${noteId}`);
        const result = await response.json();
        
        if (result.success) {
            const note = result.data;
            
            // Initialize editor
            Editor.init('#editorContainer', { noteId: note.id });
            Editor.loadNote(note);
            
            // Show modal
            document.getElementById('noteModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Focus title
            setTimeout(() => {
                document.getElementById('noteTitle')?.focus();
            }, 100);
        }
    } catch (error) {
        console.error('Failed to open note:', error);
        Utils.toast('Failed to open note', 'error');
    }
}

/**
 * Close Note Modal
 */
function closeNoteModal(event) {
    if (event && event.target !== event.currentTarget) return;
    
    document.getElementById('noteModal').classList.add('hidden');
    document.body.style.overflow = '';
    Editor.clear();
}

/**
 * Save Current Note
 */
async function saveCurrentNote() {
    const note = {
        id: Editor.currentNoteId,
        title: Editor.getTitle(),
        content: Editor.getContent()
    };
    
    try {
        const response = await fetch('api.php?action=update_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(note)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.toast('Note saved successfully', 'success');
            loadNotes();
            loadStats();
            closeNoteModal();
        } else {
            Utils.toast('Failed to save note', 'error');
        }
    } catch (error) {
        console.error('Failed to save note:', error);
        Utils.toast('Network error', 'error');
    }
}

/**
 * Delete Current Note
 */
async function deleteCurrentNote() {
    const confirmed = await Utils.confirm('Are you sure you want to delete this note?', 'Delete Note');
    
    if (!confirmed) return;
    
    try {
        const response = await fetch(`api.php?action=delete_note&id=${Editor.currentNoteId}`, {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.toast('Note deleted', 'success');
            closeNoteModal();
            loadNotes();
            loadStats();
        }
    } catch (error) {
        console.error('Failed to delete note:', error);
        Utils.toast('Failed to delete note', 'error');
    }
}

/**
 * Create New Folder
 */
async function createFolder() {
    const name = prompt('Enter folder name:');
    if (!name) return;
    
    try {
        const response = await fetch('api.php?action=create_folder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, color: '#6B7280', icon: 'folder' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.toast('Folder created', 'success');
            loadFolders();
            loadStats();
        }
    } catch (error) {
        console.error('Failed to create folder:', error);
        Utils.toast('Failed to create folder', 'error');
    }
}

/**
 * Create New Tag
 */
async function createTag() {
    const name = prompt('Enter tag name:');
    if (!name) return;
    
    // Simple implementation - would need API endpoint
    Utils.toast('Tag creation coming soon', 'info');
}

// Make functions available globally
window.createNote = createNewNote;
window.openNote = openNote;
window.closeNoteModal = closeNoteModal;
window.saveCurrentNote = saveCurrentNote;
window.deleteCurrentNote = deleteCurrentNote;
window.filterNotes = filterNotes;
window.filterByFolder = filterByFolder;
window.filterByTag = filterByTag;
window.createFolder = createFolder;
window.createTag = createTag;
