# Developer Documentation — LLMs

**Standard:** ISO/IEC/IEEE 26514:2022

---

## Overview

The LLMs feature serves `/llms.txt` for large language model context, auto-generates site architecture summaries, and provides per-post inclusion toggles. It implements the [llms.txt proposal](https://llmstxt.org/).

**SRS reference:** `docs/llms/srs.md` (FR-01 through FR-32)

---

## API Reference

### 1. Functions — includes/llms.php

#### 1.1 `gg_optimizer_llms_normalize_text`

```php
gg_optimizer_llms_normalize_text( string $text ) : string
```

Normalizes text for llms.txt output: strips shortcodes, HTML tags, decodes HTML entities, and collapses whitespace.

#### 1.2 `gg_optimizer_get_llms_context`

```php
gg_optimizer_get_llms_context() : string
```

Generates the auto-generated context portion of llms.txt. Includes:
- `# {Site Title}` — H1 with site name
- `> {Site Description}` — Blockquote with tagline
- `- [Home](url): {summary}` — Homepage link with excerpt or first 40 words
- `## Core Architecture` — Default architecture section
- `## Key Specifications` — Default specifications section

Used as fallback when no stored override exists.

#### 1.3 `gg_optimizer_get_llms_key_documents`

```php
gg_optimizer_get_llms_key_documents( array $unsaved_toggles = [], array $unsaved_descriptions = [] ) : string
```

Generates the "## Key Documents" section. Queries all published posts and filters those with `_gg_optimizer_include_in_llms` enabled.

**Description priority:**
1. `$unsaved_descriptions[post_id]` — Runtime override
2. `_gg_optimizer_llms_description` — Saved custom description
3. `get_the_excerpt()` — Post excerpt
4. `wp_trim_words( content, 20 )` — First 20 words of content

**Parameters:**
- `$unsaved_toggles` — Associative array of `post_id => bool` for unsaved include overrides
- `$unsaved_descriptions` — Associative array of `post_id => string` for unsaved description overrides

#### 1.4 `gg_optimizer_output_llms_txt`

```php
gg_optimizer_output_llms_txt( string|null $override_context = null, array $unsaved_toggles = [], array $unsaved_descriptions = [] ) : void
```

Outputs the full llms.txt content. Echoes directly (used by both the live endpoint and REST preview).

**Behavior:**
- When `$override_context` is `null`, reads from DB (`llms_override`)
- When DB value is non-empty, uses it as context
- When DB value is empty, uses auto-generated `gg_optimizer_get_llms_context()`
- Appends `"\n\n"` + Key Documents + `"\nSitemap: {home}/wp-sitemap.xml"`

#### 1.5 `gg_optimizer_output_llms_head_link`

```php
gg_optimizer_output_llms_head_link() : void
```

Outputs `<link rel="help" type="text/plain" href="/llms.txt" title="LLMs Context Summary">` in the document `<head>`. Respects the `gg_optimizer_llms_enabled` filter.

---

### 2. Hook Reference

| Hook | Type | File | Description |
|---|---|---|---|
| `gg_optimizer_llms_enabled` | filter | `llms.php:38` | Disable the entire LLMs feature. Default `true`. |

**Usage:**
```php
// Disable llms.txt serving.
add_filter( 'gg_optimizer_llms_enabled', '__return_false' );
```

---

### 3. Post Meta Reference

| Meta Key | Type | Description |
|---|---|---|
| `_gg_optimizer_include_in_llms` | boolean | Include this post in llms.txt Key Documents. Default `false` (opt-in). |
| `_gg_optimizer_llms_description` | string | Custom description for the llms.txt entry. Empty = auto-generate. |

---

### 4. REST API Reference

#### 4.1 `GET /gg-optimizer/v1/llms-override`

**Permission:** `manage_options`

**Response:**
```json
{
  "llms_override": "",
  "llms_context": "# Site Title\n\n> Site tagline\n\n- [Home](https://example.com/): ...\n\n## Core Architecture\n..."
}
```

`llms_override` is the raw DB value (empty string when no override). `llms_context` is the effective context (override if set, otherwise auto-generated).

#### 4.2 `POST /gg-optimizer/v1/llms-override`

**Permission:** `manage_options`

**Request:**
```json
{
  "llms_override": "# Custom context\n\nThis is my custom llms.txt context."
}
```

- Strips everything from "## Key Documents" onward as a safety measure
- Empty value resets to auto-generated context

**Response:** `{ "success": true }`

#### 4.3 `POST /gg-optimizer/v1/llms-preview`

**Permission:** `manage_options`

**Request:**
```json
{
  "llms_override": "# Custom context",
  "unsaved_toggles": { "123": true },
  "unsaved_descriptions": { "123": "Custom description for post 123" }
}
```

All fields optional. Returns the full rendered llms.txt.

**Response:**
```json
{
  "llms_txt": "# Custom context\n\n## Key Documents\n- [Post Title](https://...): Custom description for post 123\n\nSitemap: https://example.com/wp-sitemap.xml\n"
}
```

---

### 5. LLMs.txt Format

The generated file follows this structure:

```text
# Site Name

> Site description

- [Home](https://example.com/): Homepage summary

## Core Architecture
- Architectural description line 1
- Architectural description line 2

## Key Specifications
- Specification description line 1
- Specification description line 2

## Key Documents
- [Post Title](permalink): Description

Sitemap: https://example.com/wp-sitemap.xml
```

The `## Core Architecture` and `## Key Specifications` sections are hardcoded defaults. Customize them by saving a context override via the REST API or the Gutenberg modal.

---

## Integration Guide

### Customizing the Auto-Generated Context

The auto-generated context (FR-04–FR-07) includes hardcoded architecture descriptions. To override the entire context:

```php
// Save a custom override programmatically.
GG_Optimizer_DB::set( 'llms_override', "# My Custom Context\n\nCustom description." );
```

### Disabling the Feature

```php
add_filter( 'gg_optimizer_llms_enabled', '__return_false' );
```

### Extending Key Documents with Custom Post Types

The Key Documents query uses `get_post_types( array( 'public' => true ) )`, so custom public post types are automatically included. To exclude a post type:

```php
add_filter( 'gg_optimizer_llms_get_post_types', function ( $post_types ) {
    unset( $post_types['product'] );
    return $post_types;
} );
```

---

## Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| `/llms.txt` returns 404 | Another plugin intercepts the route | Check for conflicting `template_redirect` handlers |
| Key Documents section empty | No posts have the include toggle enabled | Enable the toggle on posts in the LLMs modal |
| Custom context not saving | Permission issue or missing `manage_options` | Verify user is an administrator |
| Preview shows old content | REST cache | The 500ms debounce should auto-update; check network tab |
| Safety strip removes content | Context contains "## Key Documents" text | Avoid including auto-generated section header in custom context |

---

## Traceability

| Function / Endpoint / Hook | SRS Requirement |
|---|---|
| `template_redirect` intercept | FR-01, FR-02 |
| `gg_optimizer_output_llms_head_link` | FR-03 |
| `gg_optimizer_get_llms_context` | FR-04–FR-07 |
| `gg_optimizer_get_llms_key_documents` | FR-08–FR-12 |
| `gg_optimizer_output_llms_txt` | FR-01, FR-13–FR-15 |
| Safety strip on save | FR-16 |
| Meta field registration | FR-17, FR-18 |
| `/llms-override` GET | FR-19 |
| `/llms-override` POST | FR-20 |
| `/llms-preview` POST | FR-21 |
| PluginDocumentSettingPanel + Modal | FR-22–FR-32 |
