# Developer Documentation — Sitemap

**Standard:** ISO/IEC/IEEE 26514:2022 — Developer Reference Documentation

---

## Overview

The sitemap feature extends WordPress core sitemaps (5.5+) by intercepting the sitemap filter chain. It allows site administrators to toggle post types, taxonomies, author pages, and individual documents in/out of the XML sitemap without writing code.

**SRS reference:** `docs/sitemap/srs.md` (requirements FR-01 through FR-29)

---

## API Reference

### 1. GG_Optimizer_DB (Shared Class)

The sitemap feature stores its configuration in the shared key-value settings table via `GG_Optimizer_DB`.

| Method | Signature | Description |
|---|---|---|
| `get` | `GG_Optimizer_DB::get( string $key, mixed $default = '' ) : string` | Retrieve a setting value by key |
| `set` | `GG_Optimizer_DB::set( string $key, string $value ) : int|false` | Insert or replace a setting value |

The sitemap feature uses the key `sitemap_settings` and stores a JSON-encoded associative array.

---

### 2. Hook Reference

All hooks are defined in `includes/sitemap.php`. Each hook function follows the same pattern: read the WordPress filter input, merge with DB overrides, return the modified value.

#### 2.1 `wp_sitemaps_enabled` → `gg_optimizer_sitemap_enabled` (filter)

**File:** `sitemap.php:368`

**Purpose:** Master gate for the entire sitemap feature. When disabled, all sitemap XML output is suppressed.

**Signature:**
```php
apply_filters( 'gg_optimizer_sitemap_enabled', bool $enabled ) : bool
```

| Parameter | Type | Description |
|---|---|---|
| `$enabled` | `bool` | Whether sitemaps are currently enabled. Default: value passed by WordPress core |

**Usage:**
```php
// Disable sitemaps entirely.
add_filter( 'gg_optimizer_sitemap_enabled', '__return_false' );

// Enable only on production.
add_filter( 'gg_optimizer_sitemap_enabled', function ( $enabled ) {
    return 'production' === wp_get_environment_type() ? $enabled : false;
} );
```

**SRS:** FR-01, FR-02

#### 2.2 `gg_optimizer_sitemap_disabled_taxonomies` (filter)

**File:** `sitemap.php:43`

**Purpose:** Specify which taxonomies should be disabled from the taxonomy sitemap by default (before DB overrides).

**Signature:**
```php
apply_filters( 'gg_optimizer_sitemap_disabled_taxonomies', array $disabled ) : array
```

| Parameter | Type | Description |
|---|---|---|
| `$disabled` | `array` | Array of taxonomy slugs to exclude |

**Usage:**
```php
// Disable specific taxonomies by default.
add_filter( 'gg_optimizer_sitemap_disabled_taxonomies', function () {
    return array( 'category', 'post_tag', 'product_cat' );
} );

// Clear default exclusions (include all taxonomies).
add_filter( 'gg_optimizer_sitemap_disabled_taxonomies', function () {
    return array();
} );
```

**SRS:** FR-07, FR-08, FR-09

#### 2.3 `gg_optimizer_sitemap_excluded_post_types` (filter)

**File:** `sitemap.php:78`

**Purpose:** Specify which post types should be excluded from the post sitemap by default (before DB overrides).

**Signature:**
```php
apply_filters( 'gg_optimizer_sitemap_excluded_post_types', array $excluded ) : array
```

| Parameter | Type | Description |
|---|---|---|
| `$excluded` | `array` | Array of post type slugs to exclude |

**Usage:**
```php
// Exclude a custom post type by default.
add_filter( 'gg_optimizer_sitemap_excluded_post_types', function () {
    return array( 'attachment' );
} );
```

**SRS:** FR-03, FR-04, FR-05

#### 2.4 `gg_optimizer_sitemap_excluded_terms` (filter)

**File:** `sitemap.php:115`

**Purpose:** Specify term IDs to exclude per taxonomy from the taxonomy sitemap.

**Signature:**
```php
apply_filters( 'gg_optimizer_sitemap_excluded_terms', array $excluded ) : array
```

| Parameter | Type | Description |
|---|---|---|
| `$excluded` | `array` | Associative array: `taxonomy_slug => [term_id, ...]` |

**Usage:**
```php
// Exclude specific terms from the sitemap.
add_filter( 'gg_optimizer_sitemap_excluded_terms', function () {
    return array(
        'category' => array( 5, 12 ),        // Exclude categories with IDs 5 and 12
        'product_cat' => array( 99 ),          // Exclude product category ID 99
    );
} );
```

**SRS:** FR-07

#### 2.5 `gg_optimizer_sitemap_excluded_users` (filter)

**File:** `sitemap.php:175`

**Purpose:** Specify user IDs to exclude from the author (users) sitemap by default.

**Signature:**
```php
apply_filters( 'gg_optimizer_sitemap_excluded_users', array $excluded ) : array
```

| Parameter | Type | Description |
|---|---|---|
| `$excluded` | `array` | Array of user IDs |

**Usage:**
```php
// Exclude specific users from the author sitemap.
add_filter( 'gg_optimizer_sitemap_excluded_users', function () {
    return array( 1, 7 );   // Exclude user IDs 1 and 7
} );
```

**SRS:** FR-12

#### 2.6 `gg_optimizer_sitemap_enable_users_provider` (filter)

**File:** `sitemap.php:150`

**Purpose:** Enable or disable the author (users) sitemap provider by default.

**Signature:**
```php
apply_filters( 'gg_optimizer_sitemap_enable_users_provider', bool $enabled ) : bool
```

| Parameter | Type | Description |
|---|---|---|
| `$enabled` | `bool` | Whether the users provider is enabled. Default: `false` |

**Usage:**
```php
// Enable the author sitemap by default.
add_filter( 'gg_optimizer_sitemap_enable_users_provider', '__return_true' );
```

**SRS:** FR-10, FR-11

---

### 3. REST API Reference

#### 3.1 `GET /gg-optimizer/v1/sitemap-settings`

**Permission:** `manage_options`

**Response shape:**
```json
{
  "settings": {
    "post_types": { "post": true, "page": true, "product": false },
    "taxonomies": { "category": false, "post_tag": false, "product_cat": true },
    "authors": true,
    "excluded_users": [3, 7]
  },
  "post_types": [
    { "slug": "post", "label": "Posts" },
    { "slug": "page", "label": "Pages" }
  ],
  "taxonomies": [
    { "slug": "category", "label": "Categories" },
    { "slug": "post_tag", "label": "Tags" }
  ],
  "users": [
    { "id": 1, "display_name": "admin" },
    { "id": 2, "display_name": "editor" }
  ],
  "sitemap_url": "https://example.com/wp-sitemap.xml"
}
```

| Field | Type | Description |
|---|---|---|
| `settings` | `object` | Saved override values. Empty `{}` when no overrides exist |
| `settings.post_types` | `object` | Map of `post_type_slug => bool` inclusion |
| `settings.taxonomies` | `object` | Map of `taxonomy_slug => bool` inclusion |
| `settings.authors` | `boolean` | Whether author sitemap is enabled |
| `settings.excluded_users` | `array` | Array of excluded user IDs |
| `post_types` | `array` | Available public post types (excludes `attachment`, `customize_changeset`, `nav_menu_item`) |
| `taxonomies` | `array` | Available public taxonomies (excludes `post_format`) |
| `users` | `array` | Users with `publish_posts` capability |
| `sitemap_url` | `string` | Full URL to the site's sitemap index |

#### 3.2 `POST /gg-optimizer/v1/sitemap-settings`

**Permission:** `manage_options`

**Request shape:**
```json
{
  "settings": {
    "post_types": { "post": true, "page": false },
    "taxonomies": { "category": true },
    "authors": true,
    "excluded_users": [3]
  }
}
```

All fields are optional. The endpoint validates:
- Post type slugs against the list of registered public post types
- Taxonomy slugs against registered public taxonomies
- User IDs against existing users with `publish_posts` capability

**Response shape:**
```json
{ "success": true }
```

**Error responses:**

| Status | Condition |
|---|---|
| 400 | `settings` is not an object |
| 400 | Invalid post type slug |
| 400 | Invalid taxonomy slug |
| 400 | Invalid user ID |

---

### 4. Post Meta Reference

| Meta Key | Type | Default | Post Types |
|---|---|---|---|
| `_gg_optimizer_hide_from_search` | `boolean` | `false` | All public post types |

When `true`, the post:
- Outputs `<meta name="robots" content="noindex">` on its frontend page
- Is excluded from all sitemap providers (posts, taxonomies, users)
- Is registered via `GG_Optimizer_Custom_Meta_Field` with `revisions_enabled => true`

---

## Integration Guide

### Installation

The sitemap feature is part of the `gregius-optimizer` plugin. Ensure the main plugin file is loaded:

```php
require_once WP_PLUGIN_DIR . '/gregius-optimizer/gregius-optimizer.php';
```

No additional setup is required. The sitemap filters are registered automatically.

### Extending with Custom Post Types

Custom post types registered via `register_post_type()` with `public => true` and `show_in_rest => true` will automatically appear in the Sitemap modal's post type toggle list. No additional configuration is needed.

### Disabling the Entire Sitemap Feature

```php
add_filter( 'gg_optimizer_sitemap_enabled', '__return_false' );
```

### Adding Custom Taxonomy Exclusions

```php
add_filter( 'gg_optimizer_sitemap_excluded_terms', function () {
    return array(
        'my_taxonomy' => array( 42, 99 ),
    );
} );
```

---

## Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| Post type does not appear in toggle list | Post type is not registered as `public => true` | Check `register_post_type()` arguments |
| Taxonomy does not appear in toggle list | Taxonomy is `post_format` or not public | Review taxonomy registration |
| Changes to sitemap settings not reflected in XML | WordPress sitemap cache; browser cache | Clear permalinks (Settings → Permalinks → Save); hard refresh |
| `gg_optimizer_sitemap_enabled` has no effect | Filter applied too early or too late | Ensure the filter is added before `wp_sitemaps_enabled` fires |
| Author sitemap toggle does nothing | `gg_optimizer_sitemap_enable_users_provider` filter is blocking | Check for `add_filter('gg_optimizer_sitemap_enable_users_provider', '__return_false')` |

---

## Extension Points

| Hook | Type | When to Use |
|---|---|---|
| `gg_optimizer_sitemap_enabled` | filter | To disable the entire sitemap feature |
| `gg_optimizer_sitemap_disabled_taxonomies` | filter | To set default taxonomy exclusions |
| `gg_optimizer_sitemap_excluded_post_types` | filter | To set default post type exclusions |
| `gg_optimizer_sitemap_excluded_terms` | filter | To exclude specific term IDs per taxonomy |
| `gg_optimizer_sitemap_excluded_users` | filter | To exclude specific users from the author sitemap |
| `gg_optimizer_sitemap_enable_users_provider` | filter | To enable/disable the users provider by default |

---

## Traceability

| Hook / Endpoint / Meta | SRS Requirement |
|---|---|
| `gg_optimizer_sitemap_enabled` | FR-01, FR-02 |
| `gg_optimizer_sitemap_disabled_taxonomies` | FR-07, FR-08, FR-09 |
| `gg_optimizer_sitemap_excluded_post_types` | FR-03, FR-04, FR-05 |
| `gg_optimizer_sitemap_excluded_terms` | FR-07 |
| `gg_optimizer_sitemap_excluded_users` | FR-12 |
| `gg_optimizer_sitemap_enable_users_provider` | FR-10, FR-11 |
| `GET /gg-optimizer/v1/sitemap-settings` | FR-16 |
| `POST /gg-optimizer/v1/sitemap-settings` | FR-17, FR-18, FR-19 |
| `_gg_optimizer_hide_from_search` | FR-13, FR-14, FR-15 |
| PluginDocumentSettingPanel "Sitemap" | FR-21, FR-22, FR-23, FR-24 |
| Sitemap modal UI | FR-25, FR-26, FR-27, FR-28, FR-29 |
