# Developer Documentation — Schema

**Standard:** ISO/IEC/IEEE 26514:2022

---

## Overview

The schema feature generates JSON-LD structured data for Organization, WebSite, BreadcrumbList, and content pages. It includes a full schema.org type map (9 categories, 176 subtypes), configurable defaults per post type, and per-post subtype override.

**SRS reference:** `docs/schema/srs.md` (FR-01 through FR-30)

---

## API Reference

### 1. Functions — includes/schema.php

#### 1.1 `gg_optimizer_schema_output_json_ld`

```php
gg_optimizer_schema_output_json_ld() : void
```

Master output function hooked to `wp_head`. Calls individual output functions for Organization, WebSite, BreadcrumbList, and article/page schema. Wraps all nodes in a `@graph` when multiple are present.

#### 1.2 `gg_optimizer_schema_output_organization_json_ld`

```php
gg_optimizer_schema_output_organization_json_ld() : void
```

Outputs Organization JSON-LD. Resolves the organization type via `gg_optimizer_schema_organization_type` filter. Includes `name`, `url`, `logo` (from site logo or block content), and `sameAs` (from social links blocks).

#### 1.3 `gg_optimizer_schema_output_website_json_ld`

```php
gg_optimizer_schema_output_website_json_ld() : void
```

Outputs WebSite JSON-LD with `url`, `name`, and `potentialAction` (SearchAction).

#### 1.4 `gg_optimizer_schema_build_breadcrumb_graph`

```php
gg_optimizer_schema_build_breadcrumb_graph( WP_Post $post ) : array
```

Builds the BreadcrumbList graph for a given post. Returns an array with `@type: BreadcrumbList` and `itemListElement`.

#### 1.5 `gg_optimizer_schema_build_graph`

```php
gg_optimizer_schema_build_graph( WP_Post $post ) : array
```

Builds the content entity for singular content (BlogPosting, Article, etc.). The WebPage wrapper node is constructed by `gg_optimizer_schema_build_json_ld()`. Resolves the subtype via `gg_optimizer_schema_get_resolved_subtype()`. Includes `headline`, `description`, `image`, `datePublished`, `dateModified`, `author`, `publisher`.

#### 1.6 `gg_optimizer_schema_build_json_ld`

```php
gg_optimizer_schema_build_json_ld( WP_Post $post = null ) : array
```

Builds the complete JSON-LD array for a post, combining Organization, WebSite, WebPage wrapper, content entity, and BreadcrumbList into a `@graph` structure. For singular content, constructs a two-node pattern: a `WebPage` wrapper (carrying `primaryImageOfPage`, `breadcrumb`, `isPartOf`, `mainEntity`) and a content entity (carrying `headline`, `author`, `datePublished`, `image`, `mainEntityOfPage`). When the resolved schema subtype is `WebPage`, a single merged node is emitted. Used by the REST preview endpoint.

#### 1.7 Helper Functions

```php
gg_optimizer_schema_get_organization_content_sources( WP_Post $post ) : array
gg_optimizer_schema_extract_sameas_urls( WP_Post $post ) : array
gg_optimizer_schema_extract_logo_url( WP_Post $post ) : string
gg_optimizer_schema_get_description( WP_Post $post ) : string
gg_optimizer_schema_get_image( int $post_id ) : string
gg_optimizer_schema_get_breadcrumb_items( WP_Post $post ) : array
```

### 2. Functions — includes/schema-settings.php

#### 2.1 `gg_optimizer_schema_get_type_map`

```php
gg_optimizer_schema_get_type_map() : array
```

Returns the full type map: 9 categories with 176 subtypes.

**Structure:**
```php
[
    [ 'key' => 'Article', 'label' => 'Article', 'subtypes' => [ 'Article', 'BlogPosting', ... ] ],
    [ 'key' => 'WebPage', 'label' => 'WebPage', 'subtypes' => [ 'WebPage', 'FAQPage', ... ] ],
    // ... 7 more categories
]
```

#### 2.2 `gg_optimizer_schema_get_all_subtypes`

```php
gg_optimizer_schema_get_all_subtypes() : array
```

Returns a flat array of all 176 subtype strings.

#### 2.3 `gg_optimizer_schema_get_subtype_parent`

```php
gg_optimizer_schema_get_subtype_parent( string $subtype ) : string
```

Returns the parent category key for a given subtype, or empty string if not found.

#### 2.4 `gg_optimizer_schema_get_default_subtype`

```php
gg_optimizer_schema_get_default_subtype( string $post_type ) : string
```

Returns hardcoded defaults: `post` → `BlogPosting`, `page` → `WebPage`, others → `Article`.

#### 2.5 `gg_optimizer_schema_get_resolved_subtype`

```php
gg_optimizer_schema_get_resolved_subtype( WP_Post $post ) : string
```

Resolves the subtype for a post using the priority chain:
1. Per-post meta (`_gg_optimizer_schema_subtype`)
2. Global post-type default (from DB)
3. Hardcoded fallback

---

### 3. Hook Reference

| Hook | Type | File | Description |
|---|---|---|---|
| `gg_optimizer_schema_article_type` | filter | `schema-settings.php:215` | Override the resolved `@type` for article/page schema. Parameters: `(string $type, WP_Post $post)` |
| `gg_optimizer_schema_organization_type` | filter | `schema-settings.php:315` | Override the Organization `@type`. Parameters: `(string $type)` |

**Usage:**
```php
// Force a specific article type for a post type.
add_filter( 'gg_optimizer_schema_article_type', function ( $type, $post ) {
    if ( 'movie' === $post->post_type ) {
        return 'Movie';
    }
    return $type;
}, 10, 2 );

// Override organization type.
add_filter( 'gg_optimizer_schema_organization_type', function () {
    return 'LocalBusiness';
} );
```

---

### 4. Post Meta Reference

| Meta Key | Type | Description |
|---|---|---|
| `_gg_optimizer_schema_subtype` | `string` | Per-post schema.org subtype override. Empty string = use global default. |

---

### 5. REST API Reference

#### 5.1 `GET /gg-optimizer/v1/schema-global-settings`

**Permission:** `manage_options`

**Response:**
```json
{
  "post_type_defaults": { "post": "BlogPosting", "page": "WebPage" },
  "schema_org_settings": { "org_type": "Corporation" },
  "type_map": [ { "key": "Article", "label": "Article", "subtypes": ["Article", "BlogPosting", ...] } ]
}
```

#### 5.2 `POST /gg-optimizer/v1/schema-global-settings`

**Permission:** `manage_options`

**Request:**
```json
{
  "post_type_defaults": { "post": "NewsArticle", "page": "WebPage" },
  "schema_org_settings": { "org_type": "NGO" }
}
```

Both fields optional. Subtypes validated against type map.

#### 5.3 `GET /gg-optimizer/v1/schema-preview?post_id=N`

**Permission:** `edit_post` on the specified post

**Response:** Full JSON-LD `@graph` array as built by `gg_optimizer_schema_build_json_ld()`.

---

### 6. Class Reference

#### 6.1 GG_Optimizer_Feature_Toggle

The `GG_Optimizer_Feature_Toggle` class provides a unified interface for enabling or disabling plugin features across all feature modals.

**File:** `includes/class-gg-optimizer-feature-toggle.php`

**Methods:**

- `get_all(): array` — Returns an associative array of all feature names and their boolean states.
- `is_enabled( string $name ): bool` — Returns whether a specific feature is enabled. Defaults to `false` if no value stored.
- `set_all( array $toggles ): void` — Saves feature toggle states. Uses **merge behavior** — only keys present in the input array are updated, existing values for other keys are preserved.

**REST API:**

| Method | Route | Permission |
|---|---|---|
| GET | `/gg-optimizer/v1/feature-toggles` | `edit_posts` |
| POST | `/gg-optimizer/v1/feature-toggles` | `manage_options` |

**Usage:**
```php
if ( GG_Optimizer_Feature_Toggle::is_enabled( 'schema' ) ) {
    // Schema output is active.
}
```

---

## Integration Guide

### Disabling Schema Output

```php
// Preferred — use the feature toggle (master gate)
GG_Optimizer_Feature_Toggle::set_all( [ 'schema' => false ] );

// Programmatic check
if ( ! GG_Optimizer_Feature_Toggle::is_enabled( 'schema' ) ) {
    return; // All JSON-LD output suppressed.
}
```

When disabled via the feature toggle, `gg_optimizer_schema_output_json_ld()` returns early before any schema processing — blocking all sub-schemas (Organization, WebSite, BreadcrumbList, article/page, FAQPage, logo, sameAs) at once.

### Customizing Organization SameAs Sources

The sameAs URLs are extracted from `core/social-links` blocks with `sameAsSchema` attribute. To add custom sources:

```php
add_filter( 'gg_optimizer_schema_get_organization_content_sources', function ( $sources ) {
    $sources[] = '<p>Custom content with social links</p>';
    return $sources;
} );
```

### Overriding the Image Resolver

```php
add_filter( 'gg_optimizer_schema_image', function ( $url, $post_id ) {
    $custom = get_post_meta( $post_id, 'my_custom_image', true );
    return $custom ?: $url;
}, 10, 2 );
```

---

## Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| JSON-LD not appearing in source | Another plugin removes `wp_head` actions | Check for conflicting SEO plugins |
| Wrong schema type on a post | Per-post override or global default not configured | Check the Schema modal for that post type |
| sameAs URLs missing | Social Links block missing `sameAsSchema` attribute | Re-insert the Social Links block with sameAs enabled |
| Breadcrumbs not showing | Post is not singular | BreadcrumbList only renders on singular content |
| Preview shows error | REST endpoint unreachable | Check API accessibility; verify user permissions |

---

## Traceability

| Function / Endpoint / Hook | SRS Requirement |
|---|---|
| `gg_optimizer_schema_output_json_ld` | FR-01–FR-05 |
| Organization output functions | FR-06, FR-07, FR-08 |
| Type map functions | FR-09, FR-10, FR-11 |
| `gg_optimizer_schema_get_resolved_subtype` | FR-12 |
| `gg_optimizer_schema_article_type` filter | FR-13 |
| `gg_optimizer_schema_organization_type` filter | FR-14 |
| Global settings UI + REST | FR-15, FR-16, FR-17, FR-20, FR-21 |
| `_gg_optimizer_schema_subtype` meta | FR-18, FR-19 |
| Schema preview REST endpoint | FR-22, FR-29 |
| PluginDocumentSettingPanel + Modal | FR-23–FR-30 |
