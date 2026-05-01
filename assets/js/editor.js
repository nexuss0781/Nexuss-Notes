/**
 * Nexus Notes - Rich Text Editor Module
 * Premium WYSIWYG editor with markdown support
 */

const Editor = {
    instance: null,
    currentNoteId: null,
    autoSaveTimer: null,
    
    // Toolbar configuration
    toolbarButtons: [
        { command: 'bold', icon: 'bold', title: 'Bold (Ctrl+B)' },
        { command: 'italic', icon: 'italic', title: 'Italic (Ctrl+I)' },
        { command: 'underline', icon: 'underline', title: 'Underline (Ctrl+U)' },
        { type: 'separator' },
        { command: 'formatBlock', value: 'h1', icon: 'heading1', title: 'Heading 1' },
        { command: 'formatBlock', value: 'h2', icon: 'heading2', title: 'Heading 2' },
        { command: 'formatBlock', value: 'h3', icon: 'heading3', title: 'Heading 3' },
        { type: 'separator' },
        { command: 'insertUnorderedList', icon: 'list', title: 'Bullet List' },
        { command: 'insertOrderedList', icon: 'list-ordered', title: 'Numbered List' },
        { type: 'separator' },
        { command: 'createLink', icon: 'link', title: 'Insert Link' },
        { command: 'unlink', icon: 'unlink', title: 'Remove Link' },
        { type: 'separator' },
        { command: 'quote', icon: 'quote', title: 'Quote' },
        { command: 'code', icon: 'code', title: 'Code' },
        { type: 'separator' },
        { command: 'undo', icon: 'undo', title: 'Undo (Ctrl+Z)' },
        { command: 'redo', icon: 'redo', title: 'Redo (Ctrl+Y)' }
    ],

    /**
     * Initialize editor
     */
    init(containerSelector, options = {}) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        
        this.currentNoteId = options.noteId || null;
        
        container.innerHTML = `
            <div class="editor-container">
                <div class="editor-toolbar" role="toolbar" aria-label="Editor toolbar"></div>
                <div class="editor-content" contenteditable="true" spellcheck="true"></div>
            </div>
        `;
        
        this.buildToolbar(container.querySelector('.editor-toolbar'));
        this.bindEvents(container.querySelector('.editor-content'));
        this.instance = container;
        
        return this;
    },

    /**
     * Build toolbar
     */
    buildToolbar(toolbarContainer) {
        this.toolbarButtons.forEach(btn => {
            if (btn.type === 'separator') {
                toolbarContainer.appendChild(this.createSeparator());
                return;
            }
            
            const button = document.createElement('button');
            button.className = 'btn-ghost';
            button.type = 'button';
            button.title = btn.title;
            button.dataset.command = btn.command;
            if (btn.value) button.dataset.value = btn.value;
            
            button.innerHTML = `<i data-lucide="${btn.icon}" class="w-4 h-4"></i>`;
            
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.executeCommand(btn.command, btn.value);
            });
            
            toolbarContainer.appendChild(button);
        });
        
        // Initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },

    /**
     * Create toolbar separator
     */
    createSeparator() {
        const sep = document.createElement('div');
        sep.className = 'w-px h-6 bg-gray-200 dark:bg-dark-border mx-1';
        return sep;
    },

    /**
     * Execute formatting command
     */
    executeCommand(command, value = null) {
        if (command === 'quote') {
            document.execCommand('formatBlock', false, 'blockquote');
        } else if (command === 'code') {
            document.execCommand('formatBlock', false, 'pre');
        } else {
            document.execCommand(command, false, value);
        }
        this.getContentElement()?.focus();
    },

    /**
     * Get content editable element
     */
    getContentElement() {
        return this.instance?.querySelector('.editor-content');
    },

    /**
     * Bind editor events
     */
    bindEvents(contentElement) {
        if (!contentElement) return;
        
        // Input handling with debounce for auto-save
        contentElement.addEventListener('input', () => {
            this.updateWordCount();
            this.scheduleAutoSave();
        });
        
        // Keyboard shortcuts
        contentElement.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                document.execCommand('insertText', false, '    ');
            }
        });
        
        // Paste handling - clean formatting
        contentElement.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = e.clipboardData.getData('text/plain');
            document.execCommand('insertText', false, text);
        });
    },

    /**
     * Update word/char count display
     */
    updateWordCount() {
        const content = this.getContent();
        const wordCount = content.trim().split(/\s+/).filter(w => w.length > 0).length;
        const charCount = content.length;
        
        const counterEl = document.getElementById('editorStats');
        if (counterEl) {
            counterEl.textContent = `${wordCount} words | ${charCount} characters`;
        }
    },

    /**
     * Schedule auto-save
     */
    scheduleAutoSave() {
        clearTimeout(this.autoSaveTimer);
        this.autoSaveTimer = setTimeout(() => {
            this.save();
        }, 30000); // 30 seconds
    },

    /**
     * Get editor content
     */
    getContent() {
        return this.getContentElement()?.innerHTML || '';
    },

    /**
     * Set editor content
     */
    setContent(html) {
        const contentEl = this.getContentElement();
        if (contentEl) {
            contentEl.innerHTML = html || '';
        }
    },

    /**
     * Get plain text content
     */
    getPlainText() {
        return this.getContentElement()?.innerText || '';
    },

    /**
     * Set note title
     */
    setTitle(title) {
        const titleEl = document.getElementById('noteTitle');
        if (titleEl) {
            titleEl.value = title || '';
        }
    },

    /**
     * Get note title
     */
    getTitle() {
        const titleEl = document.getElementById('noteTitle');
        return titleEl?.value || '';
    },

    /**
     * Save note
     */
    async save() {
        if (!this.currentNoteId) return null;
        
        const data = {
            id: this.currentNoteId,
            title: this.getTitle(),
            content: this.getContent()
        };
        
        try {
            const response = await fetch('api.php?action=update_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                Utils.toast('Note saved successfully', 'success');
                return result.data;
            } else {
                Utils.toast('Failed to save note', 'error');
                return null;
            }
        } catch (error) {
            console.error('Save error:', error);
            Utils.toast('Network error while saving', 'error');
            return null;
        }
    },

    /**
     * Load note into editor
     */
    loadNote(note) {
        if (!note) return;
        
        this.currentNoteId = note.id;
        this.setTitle(note.title);
        this.setContent(note.content);
        this.updateWordCount();
    },

    /**
     * Create new note
     */
    async createNewNote() {
        try {
            const response = await fetch('api.php?action=create_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: '', content: '' })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.loadNote(result.data);
                Utils.toast('New note created', 'success');
                return result.data;
            }
        } catch (error) {
            console.error('Create error:', error);
            Utils.toast('Failed to create note', 'error');
        }
        return null;
    },

    /**
     * Export note as Markdown
     */
    exportAsMarkdown(filename = 'note.md') {
        const html = this.getContent();
        const markdown = this.htmlToMarkdown(html);
        Utils.downloadFile(markdown, filename, 'text/markdown');
        Utils.toast('Exported as Markdown', 'success');
    },

    /**
     * Export note as PDF (print)
     */
    exportAsPDF() {
        window.print();
    },

    /**
     * Simple HTML to Markdown conversion
     */
    htmlToMarkdown(html) {
        let md = html;
        md = md.replace(/<h1[^>]*>(.*?)<\/h1>/gi, '# $1\n\n');
        md = md.replace(/<h2[^>]*>(.*?)<\/h2>/gi, '## $1\n\n');
        md = md.replace(/<h3[^>]*>(.*?)<\/h3>/gi, '### $1\n\n');
        md = md.replace(/<strong[^>]*>(.*?)<\/strong>/gi, '**$1**');
        md = md.replace(/<b[^>]*>(.*?)<\/b>/gi, '**$1**');
        md = md.replace(/<em[^>]*>(.*?)<\/em>/gi, '*$1*');
        md = md.replace(/<i[^>]*>(.*?)<\/i>/gi, '*$1*');
        md = md.replace(/<ul[^>]*>(.*?)<\/ul>/gis, (m, p1) => {
            return p1.replace(/<li[^>]*>(.*?)<\/li>/gi, '- $1\n') + '\n';
        });
        md = md.replace(/<ol[^>]*>(.*?)<\/ol>/gis, (m, p1) => {
            let i = 1;
            return p1.replace(/<li[^>]*>(.*?)<\/li>/gi, () => `${i++}. $1\n`) + '\n';
        });
        md = md.replace(/<blockquote[^>]*>(.*?)<\/blockquote>/gis, '> $1\n\n');
        md = md.replace(/<code[^>]*>(.*?)<\/code>/gi, '`$1`');
        md = md.replace(/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/gis, '```\n$1\n```');
        md = md.replace(/<a[^>]*href="(.*?)"[^>]*>(.*?)<\/a>/gi, '[$2]($1)');
        md = md.replace(/<p[^>]*>(.*?)<\/p>/gi, '$1\n\n');
        md = md.replace(/<br\s*\/?>/gi, '\n');
        md = md.replace(/<[^>]+>/g, '');
        md = md.replace(/\n{3,}/g, '\n\n');
        return md.trim();
    },

    /**
     * Clear editor
     */
    clear() {
        this.currentNoteId = null;
        this.setTitle('');
        this.setContent('');
        this.updateWordCount();
    }
};

// Make available globally
window.Editor = Editor;
