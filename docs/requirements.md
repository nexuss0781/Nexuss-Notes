# Nexus Notes - Requirements Specification

## Project Overview
Premium note-taking application with Ethiopian calendar support, dual timezone display, and modern UI.

## Core Features
1. **Note Management**
   - Create, edit, delete notes
   - Organize with folders/categories
   - Rich text editing with markdown support
   - Auto-save functionality

2. **Date & Time Display**
   - Ethiopian Calendar (ዓመተ ምሕረት)
   - Gregorian Calendar
   - UTC+9 (East Africa Time)
   - UTC+3 (Arabia Standard Time)

3. **Advanced Features**
   - Full-text search
   - Note tagging system
   - Export to PDF/Markdown
   - Dark/Light theme toggle
   - Responsive design
   - Offline support (PWA)
   - Aggressive caching strategy

## Technical Stack
- Pure PHP 8.x (no frameworks)
- Vanilla JavaScript ES6+
- Tailwind CSS via CDN
- Lucide Icons
- SQLite for data persistence
- Service Workers for caching

## Deployment Target
- InfinityFree hosting
- Domain: nexuss-notes.gt.tc
