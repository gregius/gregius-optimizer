# Developer Documentation — Robots

**Standard:** ISO/IEC/IEEE 26514:2022 — Developer Reference Documentation

---

## Overview

The robots feature manages two outputs: the site's `robots.txt` file (via the WordPress `robots_txt` filter) and per-page robots meta tags (via `wp_head`). Site administrators can override the robots.txt content from the Block Editor sidebar and control noindex behavior per post.

**SRS reference:** `docs/robots/srs.md` (FR-01 through FR-22)

---

## API Reference

### 1. Functions Reference

All functions are defined in `includes/robots.php` and prefixed with `gg_optimizer_`.

#### 1.1 `gg_optimizer_is_hidden_from_search`

```php
gg_optimizer_is_hidden_from_search( int $post_id ) : bool
```

Checks whether a post is flagged with `_gg_optimizer_hide_from_search` meta. Normalizes truthy values including `'1'`, `'true'`, `'yes'`, `'on'`.

| Parameter | Type | Description |
|---|---|---|
| `$post_id` | `int` | Post ID |

| Returns | Type | Description |
|---|---|---|
| `true` | `bool` | Post is hidden from search |
| `false` | `bool` | Post is visible to search |

**Usage:**
```php
if ( gg_optimizer_is_hidden_from_search( $post->ID ) ) {
    // Apply noindex behavior.
}
```

#### 1.2 `gg_optimizer_output_robots_meta`

```php
gg_optimizer_output_robots_meta() : void
```

Hooked to `wp_head`. Outputs `<meta name="robots" content="...">` with context-appropriate directives.

**Default behavior:**
- All pages: `index, follow`
- Search results (`is_search()`): `noindex, follow`
- 404 pages: `noindex, follow`
- Singular posts with `hide_from_search`: `noindex, follow`

**Filters:**

```php
// Disable robots meta entirely.
add_filter( 'gg_optimizer_robots_meta_enabled', '__return_false' );

// Override content for all pages.
add_filter( 'gg_optimizer_robots_meta_content', function ( $content ) {
    return 'noindex, nofollow';
} );
```

#### 1.3 `gg_optimizer_get_default_robots_txt`

```php
gg_optimizer_get_default_robots_txt() : string
```

Returns the built-in default robots.txt content. Includes:

- `User-agent: *` with `Disallow: /wp-admin/` and `Allow: /wp-admin/admin-ajax.php`
- Sitemap URL
- Traditional search bots: `Googlebot`, `Bingbot` — `Allow: /`
- AI answer engines: `OAI-SearchBot`, `ChatGPT-User`, `PerplexityBot`, `ClaudeBot`, `Claude-Web` — `Allow: /`
- AI model trainers: `Google-Extended`, `GPTBot`, `Applebot-Extended`, `Cohere-ai` — `Allow: /`

**Usage:**
```php
$default_rules = gg_optimizer_get_default_robots_txt();
```

#### 1.4 `gg_optimizer_output_robots_txt`

```php
gg_optimizer_output_robots_txt( string $output, string $is_public ) : string
```

Hooked to `robots_txt`. Determines the final robots.txt output.

**Merge order:**
1. If `blog_public` is `'0'`, return original WordPress output unchanged
2. If `gg_optimizer_robots_txt_enabled` returns `false`, return original output
3. If DB override exists (`robots_txt_content`), return it
4. Otherwise, return `gg_optimizer_get_default_robots_txt()`

**Filter:**
```php
add_filter( 'gg_optimizer_robots_txt_enabled', '__return_false' );
```

---

### 2. Hook Reference

| Hook | Type | File | Description |
|---|---|---|---|
| `robots_txt` | filter (core) | `robots.php:159` | Intercepted to provide custom/default robots.txt |
| `gg_optimizer_robots_meta_enabled` | filter | `robots.php:46` | Disable robots meta output. Default: `true` |
| `gg_optimizer_robots_meta_content` | filter | `robots.php:64` | Override robots meta content string |
| `gg_optimizer_robots_txt_enabled` | filter | `robots.php:147` | Disable robots.txt override. Default: `true` |

---

### 3. REST API Reference

#### 3.1 `GET /gg-optimizer/v1/robots-txt`

**Permission:** `manage_options`

**Response:**
```json
{
  "content": "User-agent: *\nDisallow: /wp-admin/\n...",
  "has_custom": false,
  "robots_txt_url": "https://example.com/robots.txt"
}
```

| Field | Type | Description |
|---|---|---|
| `content` | `string` | Current robots.txt content (custom override or defaults) |
| `has_custom` | `bool` | Whether a custom override exists in the database |
| `robots_txt_url` | `string` | Full URL to the site's `/robots.txt` |

#### 3.2 `POST /gg-optimizer/v1/robots-txt`

**Permission:** `manage_options`

**Request:**
```json
{ "content": "User-agent: *\nDisallow: /private/\n..." }
```

**Response:**
```json
{ "success": true }
```

Passing `{ "content": "" }` resets the override. The next GET will return defaults with `has_custom: false`.

| Error | Status | Condition |
|---|---|---|
| Invalid data | 400 | `content` is not a string |

---

### 4. Post Meta Reference

| Meta Key | Type | Used By |
|---|---|---|
| `_gg_optimizer_hide_from_search` | `boolean` | `gg_optimizer_is_hidden_from_search()` → noindex in meta tag + sitemap exclusion |

---

## Integration Guide

### Disabling the Robots.txt Override

```php
add_filter( 'gg_optimizer_robots_txt_enabled', '__return_false' );
```

### Disabling Robots Meta Output

```php
add_filter( 'gg_optimizer_robots_meta_enabled', '__return_false' );
```

### Customizing Robots Meta Content

```php
add_filter( 'gg_optimizer_robots_meta_content', function ( $content ) {
    if ( is_page( 'members-only' ) ) {
        return 'noindex, nofollow';
    }
    return $content;
} );
```

### Extending Default Robots.txt

```php
add_filter( 'gg_optimizer_robots_txt_enabled', function () {
    // Cannot directly extend defaults, but can use a mu-plugin to filter
    // the WordPress robots_txt filter priority.
    return true; // Keep the feature enabled.
} );
```

---

## Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| Robots.txt shows WordPress default | `blog_public` is unchecked | Check Settings → Reading → "Discourage search engines" |
| Robots meta appears twice | Both `gg_optimizer` and WordPress `wp_robots` are active | Check for conflicting SEO plugins |
| Custom robots.txt not saved | JavaScript error in editor | Check browser console; verify REST endpoint is accessible |
| Changes not reflected immediately | Cache or CDN | Clear cache; verify via direct `/robots.txt` request |

---

## Traceability

| Hook / Function / Endpoint | SRS Requirement |
|---|---|
| `gg_optimizer_output_robots_txt` (robots_txt) | FR-01, FR-02, FR-03, FR-04, FR-05, FR-06 |
| `gg_optimizer_output_robots_meta` (wp_head) | FR-07, FR-08, FR-09, FR-10, FR-11, FR-12 |
| `GET /gg-optimizer/v1/robots-txt` | FR-13 |
| `POST /gg-optimizer/v1/robots-txt` | FR-14, FR-15 |
| PluginDocumentSettingPanel "Robots" | FR-16, FR-17, FR-18 |
| Robots modal | FR-19, FR-20, FR-21, FR-22 |
