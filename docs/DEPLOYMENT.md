# Nexus Notes - Deployment Guide

## Overview
This guide covers deployment of Nexus Notes to InfinityFree and other shared hosting platforms.

## Prerequisites
- InfinityFree account (or similar PHP hosting)
- Domain configured (nexuss-notes.gt.tc)
- FTP client or file manager access
- PHP 8.0+ support

## Directory Structure
```
/
├── api.php              # Main API endpoint
├── api_notes.php        # Notes API controller
├── config.php           # Application configuration
├── database.php         # Database layer
├── index.php            # Main application entry
├── manifest.json        # PWA manifest
├── sw.js                # Service Worker
├── assets/
│   ├── css/
│   │   └── premium.css  # Premium styles
│   └── js/
│       ├── app.js       # Main application
│       ├── advanced.js  # Advanced features
│       ├── calendar.js  # Ethiopian calendar
│       ├── editor.js    # Rich text editor
│       └── utils.js     # Utilities
├── includes/
│   ├── analytics.php    # Analytics module
│   ├── categories.php   # Categories & tags
│   ├── export.php       # Export/Import
│   ├── search.php       # Search engine
│   ├── security.php     # Security module
│   └── settings.php     # Settings manager
├── cache/               # Cache directory (writable)
└── data/                # SQLite database (writable)
```

## Deployment Steps

### 1. Upload Files
1. Connect to your InfinityFree account via FTP
2. Navigate to `htdocs` directory
3. Upload all project files maintaining directory structure

### 2. Set Permissions
Ensure these directories are writable:
```
chmod 755 cache/
chmod 755 data/
```

### 3. Configure Domain
1. In InfinityFree control panel, go to "Domains"
2. Add your domain: nexuss-notes.gt.tc
3. Point it to the htdocs directory

### 4. Database Setup
The application uses SQLite and will automatically create the database on first run.
No MySQL configuration needed.

### 5. Enable HTTPS
InfinityFree provides free SSL:
1. Go to "SSL Certificates" in control panel
2. Request SSL for your domain
3. Wait for propagation (usually 15-30 minutes)

## Configuration

### Environment Variables (config.php)
Edit `config.php` to customize:
```php
define('APP_NAME', 'Nexus Notes');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', false);  // Set to false in production
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);
```

### Security Settings
The application includes built-in security:
- CSRF protection
- Input sanitization
- Rate limiting
- Security headers

## Performance Optimization

### Enable Caching
The app includes aggressive caching:
- Browser caching via Service Worker
- Server-side caching for API responses
- Static asset optimization

### CDN Integration
For better global performance, consider:
1. Upload static assets to a CDN
2. Update asset URLs in index.php
3. Configure CDN caching rules

## Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check PHP version (must be 8.0+)
- Verify file permissions
- Check error logs in control panel

**Database Errors**
- Ensure `data/` directory is writable
- Check disk space quota

**PWA Not Working**
- Verify HTTPS is enabled
- Check service worker registration
- Clear browser cache

### Debug Mode
Enable debug mode in config.php:
```php
define('DEBUG_MODE', true);
```

This will display detailed error messages.

## Backup Strategy

### Manual Backup
1. Download entire `data/` directory (contains SQLite database)
2. Export notes via Settings → Export All

### Automated Backups
Set up cron job (if available):
```bash
# Daily backup at 2 AM
0 2 * * * curl https://nexuss-notes.gt.tc/api/export?format=json -o backup.json
```

## Monitoring

### Uptime Monitoring
Use services like:
- UptimeRobot (free tier available)
- Pingdom
- StatusCake

### Analytics
The built-in analytics dashboard shows:
- Total notes count
- Daily activity
- Storage usage
- Popular tags/categories

## Updates

### Updating the Application
1. Backup your data folder
2. Download new version
3. Upload files (except data/ and config.php)
4. Clear cache folder
5. Test functionality

### Version History
Check CHANGELOG.md for update details.

## Support

### Documentation
- README.md - General overview
- docs/ - Technical documentation
- API documentation via /api endpoint

### Contact
For issues and feature requests, use the project repository.

## License
See LICENSE file for terms.

---

**Domain:** nexuss-notes.gt.tc  
**Version:** 1.0.0  
**Last Updated:** 2024
