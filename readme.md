# EM REST API CPT Plugin

A WordPress plugin that registers a custom post type (**API Data**) and exposes a set of authenticated REST API endpoints for creating, retrieving, and deleting entries programmatically.

**Project:** [GitHub Repository](https://github.com/Esmond-M/em-rest-api-cpt)  
**Author:** [esmondmccain.com](https://esmondmccain.com/)

---

## Features

- Registers a custom post type: **API Data**
- Three REST API endpoints: `POST`, `GET`, and `DELETE`
- Plugin-managed **API key authentication** via `X-API-Key` header
- Custom **meta fields** per entry: `source`, `received_at`, `external_id`
- Custom **admin list columns** for source, external ID, and received timestamp
- **Admin settings page** under *API Data → Settings* — view/regenerate the API key and see live endpoint reference with example cURL commands
- API key auto-generated on plugin activation
- Proper HTTP status codes and `WP_Error` responses
- Input sanitization on all fields (`sanitize_text_field`, `wp_kses_post`)
- Full REST route argument schema with types, defaults, and validation callbacks

---

## Requirements

- WordPress 6.0+
- PHP 8.0+

---

## Installation

1. [Download the latest release](https://github.com/Esmond-M/em-rest-api-cpt/blob/main/build/em-rest-api-cpt.zip)
2. Upload and extract `em-rest-api-cpt.zip` into `/wp-content/plugins/`.
3. Activate via **Plugins → Installed Plugins**.
4. Navigate to **API Data → Settings** to find your auto-generated API key.

---

## Authentication

All endpoints require an `X-API-Key` header. The key is generated on activation and can be regenerated at any time from the settings page.

```
X-API-Key: <your-api-key>
```

---

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/wp-json/esmond-api/v1/receive` | Create a new API Data entry |
| `GET` | `/wp-json/esmond-api/v1/entries` | List entries (filterable, paginated) |
| `DELETE` | `/wp-json/esmond-api/v1/entries/{id}` | Permanently delete an entry |

---

## Usage

### Create an entry (POST)

**Body (JSON):**

```json
{
  "title": "Entry title",
  "body": "Entry content here.",
  "source": "my-app",
  "external_id": "abc-123"
}
```

- `title` *(required)* — Post title
- `body` *(required)* — Post content (HTML allowed via `wp_kses_post`)
- `source` *(optional)* — Origin identifier, e.g. `"crm"`, `"mobile-app"`
- `external_id` *(optional)* — ID from the external system for cross-referencing

**Example cURL:**

```sh
curl -X POST https://yoursite.domain/wp-json/esmond-api/v1/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{"title":"My Entry","body":"Hello world.","source":"demo","external_id":"ext-001"}'
```

**Response (201):**

```json
{
  "success": true,
  "message": "Entry created successfully.",
  "data": {
    "id": 42,
    "title": "My Entry",
    "source": "demo",
    "external_id": "ext-001",
    "received_at": "2026-05-07 14:30:00"
  }
}
```

---

### List entries (GET)

**Query params (all optional):**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `source` | string | — | Filter by source identifier |
| `per_page` | int | 10 | Results per page (max 100) |
| `page` | int | 1 | Page number |

```sh
curl "https://yoursite.domain/wp-json/esmond-api/v1/entries?source=demo&per_page=5" \
  -H "X-API-Key: YOUR_API_KEY"
```

**Response (200):**

```json
{
  "success": true,
  "total": 12,
  "total_pages": 3,
  "page": 1,
  "data": [
    {
      "id": 42,
      "title": "My Entry",
      "body": "Hello world.",
      "source": "demo",
      "external_id": "ext-001",
      "received_at": "2026-05-07 14:30:00"
    }
  ]
}
```

---

### Delete an entry (DELETE)

```sh
curl -X DELETE https://yoursite.domain/wp-json/esmond-api/v1/entries/42 \
  -H "X-API-Key: YOUR_API_KEY"
```

**Response (200):**

```json
{
  "success": true,
  "message": "Entry #42 has been permanently deleted."
}
```

---

## File Structure

```
em-rest-api-cpt/
├── em-rest-api-cpt.php       # Plugin bootstrap, singleton, activation hook
├── classes/
│   ├── register-cpt.php      # CPT registration, meta fields, admin columns
│   ├── admin-settings.php    # Settings page, API key management
│   └── make-endpoint.php     # REST routes: POST /receive, GET /entries, DELETE /entries/{id}
├── build/
└── package.json
```

---

## Error Responses

All errors return a standard WP REST error shape with an appropriate HTTP status code:

```json
{
  "code": "rest_forbidden",
  "message": "Invalid or missing API key. Pass your key in the X-API-Key header.",
  "data": { "status": 401 }
}
```

| Status | Code | Meaning |
|--------|------|---------|
| 401 | `rest_forbidden` | Missing or invalid API key |
| 404 | `rest_not_found` | Entry ID does not exist |
| 500 | `rest_cannot_create` | Post insert failed |
| 500 | `rest_api_key_not_configured` | No API key set in options |

---

*Maintained by [Esmond McCain](https://esmondmccain.com/).*
