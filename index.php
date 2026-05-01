<?php
/**
 * Nexus Notes - Main Application
 * Premium Note-Taking with Ethiopian Calendar Support
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My Notes</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Organize your thoughts, ideas, and tasks
        </p>
    </div>
    <div class="flex items-center gap-3">
        <!-- Search -->
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input 
                type="text" 
                id="searchInput"
                placeholder="Search notes..." 
                class="input-field pl-10 w-full sm:w-64"
            >
        </div>
        <!-- New Note Button -->
        <button onclick="createNewNote()" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span class="hidden sm:inline">New Note</span>
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8" id="statsContainer">
    <div class="card p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                <i data-lucide="file-text" class="w-5 h-5 text-primary-600 dark:text-primary-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="totalNotes">-</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Notes</p>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                <i data-lucide="pin" class="w-5 h-5 text-yellow-600 dark:text-yellow-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="pinnedNotes">-</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Pinned</p>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <i data-lucide="folder" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="totalFolders">-</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Folders</p>
            </div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <i data-lucide="tags" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="totalTags">-</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tags</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid lg:grid-cols-4 gap-6">
    <!-- Sidebar -->
    <aside class="lg:col-span-1 space-y-4">
        <!-- Quick Filters -->
        <div class="card p-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Quick Filters</h3>
            <nav class="space-y-1">
                <button onclick="filterNotes('all')" class="btn-ghost w-full justify-start">
                    <i data-lucide="layout-grid" class="w-4 h-4 mr-2"></i>
                    All Notes
                </button>
                <button onclick="filterNotes('pinned')" class="btn-ghost w-full justify-start">
                    <i data-lucide="pin" class="w-4 h-4 mr-2"></i>
                    Pinned
                </button>
                <button onclick="filterNotes('recent')" class="btn-ghost w-full justify-start">
                    <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                    Recent
                </button>
            </nav>
        </div>
        
        <!-- Folders -->
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Folders</h3>
                <button onclick="createFolder()" class="btn-ghost p-1">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                </button>
            </div>
            <nav class="space-y-1" id="foldersList"></nav>
        </div>
        
        <!-- Tags -->
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tags</h3>
                <button onclick="createTag()" class="btn-ghost p-1">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="flex flex-wrap gap-2" id="tagsList"></div>
        </div>
    </aside>
    
    <!-- Notes Grid -->
    <div class="lg:col-span-3">
        <!-- Notes Container -->
        <div id="notesGrid" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <!-- Notes will be loaded here -->
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" class="empty-state hidden">
            <i data-lucide="notebook" class="empty-state-icon"></i>
            <h3 class="empty-state-title">No notes yet</h3>
            <p class="empty-state-description mb-4">
                Create your first note to get started organizing your thoughts.
            </p>
            <button onclick="createNewNote()" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Create Note
            </button>
        </div>
        
        <!-- Loading State -->
        <div id="loadingState" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div class="card p-5"><div class="skeleton h-5 w-3/4 mb-3"></div><div class="skeleton h-4 w-full mb-2"></div><div class="skeleton h-4 w-2/3"></div></div>
            <div class="card p-5"><div class="skeleton h-5 w-3/4 mb-3"></div><div class="skeleton h-4 w-full mb-2"></div><div class="skeleton h-4 w-2/3"></div></div>
            <div class="card p-5"><div class="skeleton h-5 w-3/4 mb-3"></div><div class="skeleton h-4 w-full mb-2"></div><div class="skeleton h-4 w-2/3"></div></div>
        </div>
    </div>
</div>

<!-- Note Editor Modal -->
<div id="noteModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-overlay" onclick="closeNoteModal(event)">
        <div class="modal-content max-w-4xl" onclick="event.stopPropagation()">
            <div class="modal-header">
                <input 
                    type="text" 
                    id="noteTitle" 
                    placeholder="Note title..." 
                    class="text-lg font-semibold border-none focus:ring-0 p-0 w-full bg-transparent"
                >
                <div class="flex items-center gap-2">
                    <span id="editorStats" class="text-xs text-gray-400 mr-2"></span>
                    <button onclick="Editor.exportAsMarkdown()" class="btn-ghost" title="Export as Markdown">
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deleteCurrentNote()" class="btn-ghost text-red-500 hover:text-red-600" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    <button onclick="closeNoteModal()" class="btn-ghost">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div id="editorContainer"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeNoteModal()" class="btn-secondary">Cancel</button>
                <button onclick="saveCurrentNote()" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Note
                </button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
