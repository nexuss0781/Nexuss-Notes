# Nexus Notes - System Architecture

## Waterfall SDLC Phases

### Phase 1: Requirements Analysis (Complete)
- Functional requirements documented
- Non-functional requirements defined
- Technical constraints identified

### Phase 2: System Design
#### Directory Structure
```
/
├── index.php          # Main entry point
├── config.php         # Configuration settings
├── database.php       # Database connection & queries
├── api.php            # REST API endpoints
├── assets/
│   ├── css/
│   │   └── style.css  # Custom styles + Tailwind
│   ├── js/
│   │   ├── app.js     # Main application logic
│   │   ├── calendar.js # Ethiopian calendar logic
│   │   ├── editor.js  # Rich text editor
│   │   └── utils.js   # Utility functions
│   └── icons/         # Lucide icons
├── includes/
│   ├── header.php     # Common header
│   ├── footer.php     # Common footer
│   └── functions.php  # Helper functions
├── data/              # SQLite database storage
├── cache/             # Aggressive caching layer
└── docs/              # Documentation
```

#### Database Schema (SQLite)
- notes: id, title, content, folder_id, created_at, updated_at
- folders: id, name, parent_id, color, icon
- tags: id, name, color
- note_tags: note_id, tag_id
- settings: key, value

### Phase 3: Implementation Plan
1. Core infrastructure setup
2. Database layer implementation
3. API endpoints
4. Frontend components
5. Calendar integration
6. Caching layer
7. PWA features

### Phase 4: Testing Strategy
- Unit tests for PHP functions
- Integration tests for API
- UI/UX testing
- Performance benchmarks

### Phase 5: Deployment
- InfinityFree configuration
- Domain setup
- SSL certificate
- Cache optimization
