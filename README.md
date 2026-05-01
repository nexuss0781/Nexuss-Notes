# Nexus Notes

Premium note-taking application with Ethiopian calendar support, dual timezone display, and modern UI.

## Features

### Core Functionality
- **Note Management**: Create, edit, delete, and organize notes
- **Rich Text Editor**: WYSIWYG editing with formatting toolbar
- **Folder Organization**: Categorize notes into folders
- **Tagging System**: Add tags for better organization
- **Full-Text Search**: Quickly find notes by content
- **Pin Notes**: Keep important notes at the top

### Unique Features
- **Ethiopian Calendar**: Display dates in Ethiopian calendar (ዓመተ ምሕረት)
- **Dual Timezone**: Show UTC+9 (East Africa) and UTC+3 (Arabia) times
- **Gregorian Calendar**: Standard calendar display alongside Ethiopian

### Advanced Features
- **Dark/Light Theme**: Toggle between themes with persistence
- **PWA Support**: Install as app, works offline
- **Export Options**: Export notes as Markdown or PDF
- **Auto-Save**: Automatic saving every 30 seconds
- **Responsive Design**: Works on all devices
- **Aggressive Caching**: Fast loading with service worker

## Tech Stack

- **Backend**: Pure PHP 8.x (no frameworks)
- **Database**: SQLite with WAL mode
- **Frontend**: Vanilla JavaScript ES6+
- **Styling**: Tailwind CSS + Custom CSS3
- **Icons**: Lucide Icons
- **Fonts**: Inter, JetBrains Mono

## Project Structure

```
/
├── index.php           # Main dashboard
├── config.php          # Configuration settings
├── database.php        # SQLite database layer
├── api.php             # REST API endpoints
├── manifest.json       # PWA manifest
├── sw.js               # Service Worker
├── assets/
│   ├── css/
│   │   └── style.css   # Custom styles
│   └── js/
│       ├── app.js      # Main application logic
│       ├── editor.js   # Rich text editor
│       ├── calendar.js # Ethiopian calendar
│       └── utils.js    # Utility functions
├── includes/
│   ├── header.php      # Common header
│   ├── footer.php      # Common footer
│   └── functions.php   # Helper functions
├── data/               # SQLite database storage
├── cache/              # Server-side caching
└── docs/               # Documentation
```

## Installation

### InfinityFree Deployment

1. Upload all files to your InfinityFree account
2. Ensure `data/` and `cache/` directories are writable (755 permissions)
3. Access your domain (nexuss-notes.gt.tc)

### Local Development

```bash
# Clone repository
git clone <repo-url>
cd nexus-notes

# Start PHP built-in server
php -S localhost:8000

# Open browser
http://localhost:8000
```

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `api.php?action=get_notes` | GET | Fetch all notes with filters |
| `api.php?action=get_note&id=X` | GET | Fetch single note |
| `api.php?action=create_note` | POST | Create new note |
| `api.php?action=update_note` | POST | Update existing note |
| `api.php?action=delete_note&id=X` | POST | Delete note |
| `api.php?action=toggle_pin&id=X` | POST | Pin/unpin note |
| `api.php?action=get_folders` | GET | List folders |
| `api.php?action=create_folder` | POST | Create folder |
| `api.php?action=get_tags` | GET | List tags |
| `api.php?action=get_stats` | GET | Dashboard statistics |

## Configuration

Edit `config.php` to customize:

- Application name and URL
- Cache settings (TTL, compression)
- Feature flags
- Timezone configurations
- Performance optimizations

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## License

MIT License

## Author

Elite Developer - Nexus Notes Team
