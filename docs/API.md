# Nexus Notes - API Documentation

## Base URL
```
/api
```

## Authentication
Currently, the API uses CSRF token protection for state-changing operations.

### CSRF Token
Include in POST/PUT/DELETE requests:
```json
{
    "csrf_token": "your_csrf_token_here"
}
```

## Endpoints

### Notes

#### GET /api/notes
Retrieve all notes with pagination and filtering.

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `limit` (integer): Items per page (default: 20)
- `filter` (string): Filter type - 'all', 'favorites', 'archived'

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Note Title",
            "content": "Note content...",
            "created_at": "2024-01-01 12:00:00",
            "updated_at": "2024-01-01 12:00:00",
            "is_favorite": false,
            "is_archived": false,
            "category_id": null,
            "tags": [
                {"id": 1, "name": "important"}
            ]
        }
    ]
}
```

#### GET /api/notes/:id
Retrieve a single note by ID.

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Note Title",
        "content": "Note content...",
        "created_at": "2024-01-01 12:00:00",
        "updated_at": "2024-01-01 12:00:00",
        "is_favorite": false,
        "is_archived": false,
        "category_id": null,
        "tags": []
    }
}
```

#### POST /api/notes
Create a new note.

**Request Body:**
```json
{
    "csrf_token": "token",
    "title": "New Note",
    "content": "Note content...",
    "category_id": 1,
    "tags": ["tag1", "tag2"]
}
```

**Response:**
```json
{
    "success": true,
    "id": 123
}
```

#### PUT /api/notes/:id
Update an existing note.

**Request Body:**
```json
{
    "csrf_token": "token",
    "title": "Updated Title",
    "content": "Updated content...",
    "category_id": 2,
    "tags": ["new-tag"]
}
```

#### DELETE /api/notes/:id
Delete a note.

**Response:**
```json
{
    "success": true
}
```

#### POST /api/notes/:id/favorite
Toggle favorite status.

**Response:**
```json
{
    "success": true,
    "is_favorite": true
}
```

#### POST /api/notes/:id/archive
Toggle archive status.

**Response:**
```json
{
    "success": true,
    "is_archived": true
}
```

### Categories

#### GET /api/categories
Get all categories.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Work",
            "color": "#3b82f6",
            "icon": "folder",
            "created_at": "2024-01-01 12:00:00"
        }
    ]
}
```

#### POST /api/categories
Create a new category.

**Request Body:**
```json
{
    "csrf_token": "token",
    "name": "Personal",
    "color": "#10b981",
    "icon": "user"
}
```

### Tags

#### GET /api/tags
Get all tags with usage count.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "important",
            "note_count": 5
        }
    ]
}
```

### Search

#### GET /api/search
Search notes by query.

**Query Parameters:**
- `q` (string): Search query
- `advanced` (boolean): Enable advanced search
- `title` (string): Title filter (advanced)
- `content` (string): Content filter (advanced)
- `category_id` (integer): Category filter (advanced)
- `tag_id` (integer): Tag filter (advanced)
- `date_from` (string): Start date (YYYY-MM-DD)
- `date_to` (string): End date (YYYY-MM-DD)
- `favorites` (boolean): Filter favorites only
- `archived` (boolean): Filter archived only

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Matching Note",
            "content": "...",
            "relevance": 3
        }
    ]
}
```

### Export

#### GET /api/export
Export notes in various formats.

**Query Parameters:**
- `format` (string): Export format - 'json', 'markdown', 'html', 'text'
- `id` (integer, optional): Note ID (exports all if not provided)

**Returns:** File download

### Import

#### POST /api/import
Import notes from JSON backup.

**Request Body:** Raw JSON data from export

**Response:**
```json
{
    "success": true,
    "imported": 10
}
```

### Analytics

#### GET /api/analytics
Get analytics data.

**Query Parameters:**
- `type` (string): Analytics type
  - 'overview' - General statistics
  - 'daily' - Daily activity (last 30 days)
  - 'tags' - Most used tags
  - 'categories' - Category distribution
  - 'storage' - Storage usage

**Response (overview):**
```json
{
    "success": true,
    "data": {
        "total_notes": 150,
        "active_notes": 140,
        "archived_notes": 10,
        "favorites": 25,
        "total_categories": 5,
        "total_tags": 20,
        "created_today": 5,
        "created_this_week": 15,
        "created_this_month": 45
    }
}
```

### Settings

#### GET /api/settings
Get all application settings.

**Response:**
```json
{
    "success": true,
    "data": {
        "app_name": "Nexus Notes",
        "theme_mode": "auto",
        "calendar_primary": "ethiopian",
        "auto_save_interval": 30,
        ...
    }
}
```

#### PUT /api/settings
Update settings.

**Request Body:**
```json
{
    "csrf_token": "token",
    "theme_mode": "dark",
    "auto_save_interval": 60
}
```

## Error Responses

### Standard Error Format
```json
{
    "error": "Error message description"
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `403` - Forbidden (CSRF failure)
- `404` - Not Found
- `405` - Method Not Allowed
- `429` - Rate Limit Exceeded
- `500` - Internal Server Error

## Rate Limiting
- Maximum 5 requests per 5 minutes per IP
- Headers included in response:
  - `X-RateLimit-Limit`: Maximum requests
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Reset timestamp

## Caching
- Static assets: 1 year
- API responses: Configurable TTL (default: 1 hour)
- Service Worker: Aggressive caching for offline support

## Examples

### JavaScript Fetch Example
```javascript
// Get all notes
const response = await fetch('/api/notes');
const data = await response.json();

// Create a note
const newNote = await fetch('/api/notes', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        csrf_token: getCsrfToken(),
        title: 'My Note',
        content: 'Content here'
    })
});

// Search notes
const results = await fetch('/api/search?q=keyword');
```

### cURL Examples
```bash
# Get all notes
curl https://nexuss-notes.gt.tc/api/notes

# Create a note
curl -X POST https://nexuss-notes.gt.tc/api/notes \
  -H "Content-Type: application/json" \
  -d '{"csrf_token":"token","title":"Test","content":"Content"}'

# Export all notes
curl -O https://nexuss-notes.gt.tc/api/export?format=json
```

---

**Version:** 1.0.0  
**Last Updated:** 2024
