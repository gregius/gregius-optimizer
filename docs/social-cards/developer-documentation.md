# Developer Documentation — Social Cards

**Standard:** ISO/IEC/IEEE 26514:2022

---

## Overview

The Social Cards feature provides per-platform title, description, and image overrides for Google Search snippets, Open Graph, and Twitter Cards. It registers 11 meta keys on all public post types, outputs HTML `<meta>` tags via `wp_head`, and provides a Gutenberg sidebar UI with inline previews and character counters.

**SRS reference:** `docs/social-cards/srs.md` (FR-01 through FR-50)

---

## API Reference

### 1. Functions — includes/meta-tags.php

#### 1.1 `gg_optimizer_meta_normalize_text`

```php
gg_optimizer_meta_normalize_text( string $text ) : string
```

Normalizes text for meta tags: strips shortcodes, HTML tags, collapses whitespace, and trims.

#### 1.2 `gg_optimizer_get_meta_title`

```php
gg_optimizer_get_meta_title( WP_Post|null $post = null, array $overrides = [] ) : string
```

Resolves the canonical meta title. Priority:
1. Explicit override in `$overrides['meta_title']`
2. Saved `_gg_optimizer_meta_title` post meta
3. `wp_get_document_title()`

#### 1.3 `gg_optimizer_get_meta_description`

```php
gg_optimizer_get_meta_description( WP_Post|null $post = null, array $overrides = [] ) : string
```

Resolves meta description. Priority:
1. Explicit override in `$overrides['meta_description']`
2. Saved `_gg_optimizer_meta_description` post meta
3. Excerpt → content → site tagline
Truncated to 155 characters with ellipsis.

#### 1.4 `gg_optimizer_get_platform_title`

```php
gg_optimizer_get_platform_title( WP_Post|null $post, string $platform, array $overrides = [] ) : string
```

Resolves title for a specific platform. `$platform` is `'google'`, `'og'`, or `'twitter'`. Priority:
1. Platform-specific post meta (`_gg_optimizer_{platform}_title`)
2. Common `_gg_optimizer_meta_title`
3. `wp_get_document_title()`

#### 1.5 `gg_optimizer_get_platform_description`

```php
gg_optimizer_get_platform_description( WP_Post|null $post, string $platform, array $overrides = [] ) : string
```

Resolves description for a specific platform. Priority:
1. Platform-specific post meta (`_gg_optimizer_{platform}_description`)
2. Common `_gg_optimizer_meta_description`
3. Excerpt → content → site tagline

#### 1.6 `gg_optimizer_get_platform_image`

```php
gg_optimizer_get_platform_image( int $post_id, string $platform, array $overrides = [] ) : array
```

Resolves social image for OG or Twitter. `$platform` is `'og'` or `'twitter'`. Priority:
1. Platform-specific post meta (`_gg_optimizer_{platform}_image`)
2. Downward platform fallback (twitter → og)
3. `_gg_optimizer_meta_image`
4. Featured image
Returns `{ id, url, alt, width, height, type }`.

#### 1.7 `gg_optimizer_get_metadata_context`

```php
gg_optimizer_get_metadata_context( WP_Post|null $post = null, array $overrides = [] ) : array
```

Unified metadata context used by both `wp_head` output and REST preview. Returns `{ title, description, canonical, og_url, og_type, site_name, locale, image }`.

#### 1.8 `gg_optimizer_output_head_meta`

```php
gg_optimizer_output_head_meta() : void
```

Master output function hooked to `wp_head` at priority 1. Calls output functions for meta description, canonical, robots, OG, Twitter, LLMs link, and schema.

#### 1.9 `gg_optimizer_filter_document_title`

```php
gg_optimizer_filter_document_title( string $title ) : string
```

Filter for `pre_get_document_title`. Substitutes `_gg_optimizer_google_title` when set. Only applies on singular, non-admin pages.

### 2. Functions — includes/social-cards.php

#### 2.1 `gg_optimizer_register_image_sizes`

```php
gg_optimizer_register_image_sizes() : void
```

Registers `gg_optimizer_og` image size (1200×630, cropped). Hooked to `after_setup_theme`.

#### 2.2 `gg_optimizer_get_og_locale`

```php
gg_optimizer_get_og_locale() : string
```

Resolves OG locale from site locale. Filterable via `gg_optimizer_meta_og_locale`.

#### 2.3 `gg_optimizer_get_social_image_data`

```php
gg_optimizer_get_social_image_data( int $post_id, array $overrides = [] ) : array
```

Resolves social image for common meta. Priority:
1. `$overrides['meta_image_id']`
2. `_gg_optimizer_meta_image` post meta
3. `$overrides['featured_media_id']`
4. Post featured image

#### 2.4 `gg_optimizer_output_og_meta`

```php
gg_optimizer_output_og_meta() : void
```

Outputs all Open Graph and Twitter Card `<meta>` tags. Respects `gg_optimizer_meta_output_og` and `gg_optimizer_meta_output_twitter` filters. Includes `og:locale`, `og:title`, `og:description`, `og:type`, `og:url`, `og:site_name`, `og:image`, `og:image:width`, `og:image:height`, `og:image:alt`, `article:published_time`, `article:modified_time`, `twitter:card`, `twitter:site`, `twitter:title`, `twitter:description`, `twitter:image`, `twitter:image:alt`.

### 3. Functions — includes/search.php

#### 3.1 `gg_optimizer_output_meta_description`

```php
gg_optimizer_output_meta_description() : void
```

Outputs `<meta name="description">` tag. Filterable via `gg_optimizer_meta_output_description`.

#### 3.2 `gg_optimizer_get_canonical_url`

```php
gg_optimizer_get_canonical_url( WP_Post|null $post = null ) : string
```

Resolves canonical URL. Priority: `wp_get_canonical_url()` → `get_permalink()` → home URL. Handles front page, archives, taxonomy, author, and search pages.

#### 3.3 `gg_optimizer_output_canonical_link`

```php
gg_optimizer_output_canonical_link() : void
```

Outputs `<link rel="canonical">` tag. Filterable via `gg_optimizer_meta_output_canonical` and `gg_optimizer_meta_canonical_url`.

---

### 4. Hook Reference

| Hook | Type | File | Description |
|---|---|---|---|
| `gg_optimizer_meta_output_description` | filter | `search.php:22` | Disable meta description. Default `true`. |
| `gg_optimizer_meta_output_canonical` | filter | `search.php:111` | Disable canonical. Default `true`. |
| `gg_optimizer_meta_canonical_url` | filter | `search.php:115` | Override canonical URL string. |
| `gg_optimizer_meta_output_og` | filter | `social-cards.php:120` | Disable Open Graph. Default `true`. |
| `gg_optimizer_meta_output_twitter` | filter | `social-cards.php:124` | Disable Twitter Cards. Default `true`. |
| `gg_optimizer_meta_og_locale` | filter | `social-cards.php:33` | Override OG locale string. |
| `gg_optimizer_meta_twitter_site` | filter | `social-cards.php:149` | Set `twitter:site` value. |
| `gg_optimizer_meta_article_publisher` | filter | `social-cards.php:150` | Set `article:publisher` URL. |
| `gg_optimizer_og_image_width` | filter | `social-cards.php:18` | OG image width. Default 1200. |
| `gg_optimizer_og_image_height` | filter | `social-cards.php:19` | OG image height. Default 630. |
| `gg_optimizer_og_image_crop` | filter | `social-cards.php:20` | OG image crop. Default `true`. |

**Usage:**
```php
// Disable Open Graph output entirely.
add_filter( 'gg_optimizer_meta_output_og', '__return_false' );

// Set a Twitter site handle.
add_filter( 'gg_optimizer_meta_twitter_site', function () {
    return '@yourhandle';
} );

// Override canonical URL for a specific post type.
add_filter( 'gg_optimizer_meta_canonical_url', function ( $url ) {
    if ( is_singular( 'product' ) ) {
        return trailingslashit( $url ) . 'ref=canonical';
    }
    return $url;
} );
```

---

### 5. Post Meta Reference

| Meta Key | Type | Revisions | Description |
|---|---|---|---|
| `_gg_optimizer_meta_title` | string | Yes | Common meta title override |
| `_gg_optimizer_meta_description` | string | Yes | Common meta description override |
| `_gg_optimizer_meta_image` | string | Yes | Common meta image (attachment ID) |
| `_gg_optimizer_google_title` | string | Yes | Google-specific title override |
| `_gg_optimizer_google_description` | string | Yes | Google-specific description override |
| `_gg_optimizer_og_title` | string | Yes | Open Graph title override |
| `_gg_optimizer_og_description` | string | Yes | Open Graph description override |
| `_gg_optimizer_og_image` | string | Yes | Open Graph image override (attachment ID) |
| `_gg_optimizer_twitter_title` | string | Yes | Twitter title override |
| `_gg_optimizer_twitter_description` | string | Yes | Twitter description override |
| `_gg_optimizer_twitter_image` | string | Yes | Twitter image override (attachment ID) |

---

### 6. REST API Reference

#### 6.1 `POST /gg-optimizer/v1/meta-preview`

**Permission:** `edit_post` on the given post.

**Request:**
```json
{
  "postId": 123,
  "metaTitle": "Custom meta title",
  "metaDescription": "Custom meta description",
  "metaImageId": 45,
  "featuredMediaId": 67,
  "excerpt": "Post excerpt text",
  "content": "Post content text"
}
```

All fields except `postId` are optional.

**Response:**
```json
{
  "title": "Custom meta title | Site Name",
  "description": "Custom meta description",
  "url": "https://example.com/post-slug",
  "image": "https://example.com/uploads/image.jpg",
  "imageAlt": "Alt text",
  "ogType": "article",
  "twitterCard": "summary_large_image",
  "siteName": "Site Name",
  "tags": {
    "description": "Custom meta description",
    "canonical": "https://example.com/post-slug",
    "og:locale": "en_US",
    "og:title": "Custom meta title | Site Name",
    "og:description": "Custom meta description",
    "og:type": "article",
    "og:url": "https://example.com/post-slug",
    "og:site_name": "Site Name",
    "og:image": "https://example.com/uploads/image.jpg",
    "og:image:width": 1200,
    "og:image:height": 630,
    "twitter:card": "summary_large_image",
    "twitter:site": "@yourhandle",
    "twitter:title": "Custom meta title | Site Name",
    "twitter:description": "Custom meta description",
    "twitter:image": "https://example.com/uploads/image.jpg"
  }
}
```

---

### 7. Character Limits

| Field | Max Characters |
|---|---|
| Google title | 60 |
| Google description | 160 |
| OG title | 55 |
| OG description | 65 |
| Twitter title | 70 |
| Twitter description | 200 |

---

## Integration Guide

### Disabling Meta Tag Output

```php
// Remove all custom meta output from wp_head.
remove_action( 'wp_head', 'gg_optimizer_output_head_meta', 1 );
```

### Disabling Specific Tag Groups

```php
add_filter( 'gg_optimizer_meta_output_description', '__return_false' );
add_filter( 'gg_optimizer_meta_output_og', '__return_false' );
add_filter( 'gg_optimizer_meta_output_twitter', '__return_false' );
add_filter( 'gg_optimizer_meta_output_canonical', '__return_false' );
```

### Customizing the Description Provenance

The description resolver follows: override → meta → excerpt → content → tagline. To add a custom source:

```php
add_filter( 'gg_optimizer_get_meta_description', function ( $description, $post, $overrides ) {
    $custom = get_post_meta( $post->ID, 'my_custom_desc', true );
    return $custom ?: $description;
}, 10, 3 );
```

---

## Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| Meta tags not appearing in source | Another plugin removes `wp_head` actions | Check for conflicting SEO plugins; verify `remove_action` not called |
| Wrong image shown on Twitter | Twitter-specific override set or caching | Clear Twitter Card cache via validator; check `_gg_optimizer_twitter_image` meta |
| Character counter shows wrong value | RichText returns HTML content | Counter strips HTML tags before counting |
| OG image not updating | Image size not regenerated | Run `wp media regenerate` or use a force-regenerate plugin |
| Preview endpoint returns error | Missing postId or permissions | Verify post exists and user has `edit_post` capability |

---

## Traceability

| Function / Endpoint / Hook | SRS Requirement |
|---|---|
| `GG_Optimizer_Custom_Meta_Field` registration | FR-01–FR-11 |
| `gg_optimizer_output_meta_description` | FR-12–FR-14 |
| `gg_optimizer_output_canonical_link` | FR-15–FR-17 |
| `gg_optimizer_filter_document_title` | FR-18–FR-19 |
| `gg_optimizer_output_og_meta` (OG section) | FR-20–FR-26 |
| `gg_optimizer_output_og_meta` (Twitter section) | FR-27–FR-31 |
| `gg_optimizer_register_image_sizes` | FR-32–FR-33 |
| `/meta-preview` POST endpoint | FR-34–FR-36 |
| PluginDocumentSettingPanel + Modal | FR-37–FR-50 |
