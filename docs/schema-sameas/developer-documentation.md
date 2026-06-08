# Developer Documentation — Schema SameAs

**Standard:** ISO/IEC/IEEE 26514:2022

---

## Overview

The Schema SameAs feature adds a `sameAsSchema` boolean attribute to `core/social-links` blocks. When enabled, the plugin extracts social profile URLs from the block's inner `core/social-link` blocks at render time and includes them as the `sameAs` array in the Organization JSON-LD output.

**SRS reference:** `docs/schema-sameas/srs.md` (FR-01 through FR-12)

---

## API Reference

### 1. JavaScript — sameas-schema-editor.js

#### 1.1 `blocks.registerBlockType` Filter

Filter name: `gregius-optimizer/sameas-schema/attributes`

Injects the `sameAsSchema` attribute into `core/social-links` block settings:

```js
attributes: {
    ...settings.attributes,
    sameAsSchema: {
        type: 'boolean',
        default: false,
    },
}
```

#### 1.2 `editor.BlockEdit` HOC

Filter name: `gregius-optimizer/sameas-schema/inspector`

Wraps the `core/social-links` block edit component to add an `InspectorControls` panel with a `ToggleControl`:

```jsx
<ToggleControl
    label="Include in site's schema"
    help="Include social links in the site's <head> as structured data (Organization.sameAs)."
    checked={ !! attributes.sameAsSchema }
    onChange={ ( value ) => setAttributes( { sameAsSchema: !! value } ) }
/>
```

The toggle is only rendered when the social-links block is selected (`isSelected`).

#### 1.3 Filter Registration Summary

| Handle | Hook | Purpose |
|---|---|---|
| `gregius-optimizer/sameas-schema/attributes` | `blocks.registerBlockType` | Add `sameAsSchema` attribute |
| `gregius-optimizer/sameas-schema/inspector` | `editor.BlockEdit` | Add toggle control UI |

---

### 2. PHP — includes/schema.php

#### 2.1 `gg_optimizer_schema_extract_sameas_urls`

```php
gg_optimizer_schema_extract_sameas_urls( WP_Post $post ) : array
```

Entry point for sameAs URL extraction. Iterates through organization content sources and runs both extraction strategies on each source.

**Parameters:**
- `$post` (`WP_Post`) — The post object to extract URLs from.

**Returns:**
`array` — Flat array of de-duplicated, re-indexed social profile URLs (string).

**Example return:**
```php
[
    'https://github.com/gregius/',
    'https://www.linkedin.com/company/gregius/',
]
```

**Source:** `includes/schema.php:122`

#### 2.2 `gg_optimizer_schema_find_sameas_in_blocks`

```php
gg_optimizer_schema_find_sameas_in_blocks(
    array $blocks,
    array $seen_pattern_slugs = [],
    array $seen_block_refs = []
) : array
```

Recursive parsed block walker. Searches for `core/social-links` blocks with `sameAsSchema` attribute and collects URLs from `core/social-link` inner blocks.

**Supports:**
- `core/pattern` blocks — resolves from `WP_Block_Patterns_Registry` and recurses with slug tracking.
- `core/block` (synced patterns) — resolves the referenced `wp_block` post and recurses with ref ID tracking.
- Standard `core/social-links` — collects `url` from each `core/social-link` inner block's attributes.

**Cycle detection:** Both `$seen_pattern_slugs` and `$seen_block_refs` prevent re-visiting the same pattern or synced block in the same extraction chain.

**Source:** `includes/schema.php:148`

#### 2.3 `gg_optimizer_schema_extract_sameas_urls_from_serialized_content`

```php
gg_optimizer_schema_extract_sameas_urls_from_serialized_content( string $content ) : array
```

Regex-based scanner for serialized post content. Matches `wp:social-links` block comments, parses their JSON attributes for `sameAsSchema`, and extracts URLs from inner `wp:social-link` comments and `<a href="...">` elements.

**Extraction sources (in order):**
1. `wp:social-link` inner block comments — reads the `url` attribute from each link block's JSON.
2. `<a href="...">` elements — extracts href values from rendered HTML inside the social-links block.

**Source:** `includes/schema.php:67`

---

### 3. Hook Reference

| Hook | Type | File:Line | Description |
|---|---|---|---|
| `gg_optimizer_schema_output_organization` | filter | `schema.php:320` | Suppress entire Organization JSON-LD output (including sameAs and logo). Parameters: `(bool $output)`. Default `true`. |
| `gg_optimizer_schema_get_organization_content_sources` | filter | `schema.php:19` | Customize content sources searched for sameAs URLs. Parameters: `(array $sources, WP_Post $post)`. |

**Usage:**
```php
// Add a custom content source for social links.
add_filter( 'gg_optimizer_schema_get_organization_content_sources', function ( $sources, $post ) {
    $sources[] = get_post_meta( $post->ID, 'custom_social_html', true );
    return $sources;
}, 10, 2 );

// Disable Organization schema output entirely.
add_filter( 'gg_optimizer_schema_output_organization', '__return_false' );
```

---

### 4. Output Format

The Organization JSON-LD node includes the `sameAs` array when URLs are found:

```json
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Organization",
            "@id": "https://example.com#organization",
            "name": "My Site",
            "url": "https://example.com/",
            "sameAs": [
                "https://github.com/gregius/",
                "https://www.linkedin.com/company/gregius/"
            ]
        }
    ]
}
```

If no URLs are found, the `sameAs` property is omitted from the Organization node entirely.

---

### 5. Integration Guide

#### Disabling Organization Output

```php
add_filter( 'gg_optimizer_schema_output_organization', '__return_false' );
```

This removes the entire Organization JSON-LD node, including `sameAs`, `logo`, `name`, and `url`.

#### Customizing Content Sources for Social Links

```php
add_filter( 'gg_optimizer_schema_get_organization_content_sources', function ( $sources, $post ) {
    // Add rendered content from a custom meta field.
    $custom_social = get_post_meta( $post->ID, 'footer_social_html', true );
    if ( $custom_social ) {
        $sources[] = $custom_social;
    }
    return $sources;
}, 10, 2 );
```

#### Removing the SameAs Schema Toggle from the Editor

```php
add_action( 'enqueue_block_editor_assets', function () {
    wp_dequeue_script( 'gg-optimizer-sameas-schema-editor' );
} );
```

### 6. Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| sameAs URLs not appearing | Social-links block missing `sameAsSchema` attribute | Verify the toggle is enabled in the block inspector |
| sameAs URLs not appearing | `gg_optimizer_schema_output_organization` filter returning false | Check for `add_filter` callbacks on this hook |
| Duplicate URLs in output | Both extraction paths found same URL | Verify `array_unique` is called (it is — in `extract_sameas_urls`) |
| Infinite loop on page render | Pattern or synced block circular reference | Check for patterns that include themselves; cycle detection should prevent this |
| Missing URLs from patterns | Pattern registered but not in `WP_Block_Patterns_Registry` | Verify the pattern is registered before the schema output runs |
| Social links in synced block not found | Synced block post not published or trashed | Check that the `wp_block` post exists and is published |

---

## Traceability

| Function / Filter / Component | SRS Requirement |
|---|---|
| `addAttributes` (JS filter) | FR-01 |
| `withInspectorControls` (JS HOC) | FR-02 |
| `gg_optimizer_schema_extract_sameas_urls` + dual strategy | FR-03 |
| Pattern/synced block recursion | FR-04 |
| Cycle detection (`$seen_pattern_slugs`, `$seen_block_refs`) | FR-05 |
| Regex serialized scanner | FR-06 |
| `array_unique` + `array_values` | FR-07 |
| `esc_url_raw` on each URL | FR-08 |
| `sameAs` array in `output_organization_json_ld` | FR-09, FR-10 |
| `gg_optimizer_schema_output_organization` filter | FR-11 |
| `gg_optimizer_schema_get_organization_content_sources` filter | FR-12 |
| `esc_url_raw` + `wp_json_encode` | SEC-01, SEC-02 |
