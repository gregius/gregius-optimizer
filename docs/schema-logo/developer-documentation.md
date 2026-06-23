# Developer Documentation — Schema Logo

**Standard:** ISO/IEC/IEEE 26514:2022

---

## Overview

The Schema Logo feature adds an `organizationLogoSchema` boolean attribute to `core/site-logo` blocks. When enabled, the plugin extracts the logo image URL from the block at render time and includes it as the `logo` property in the Organization JSON-LD output.

**SRS reference:** `docs/schema-logo/srs.md` (FR-01 through FR-09)

---

## API Reference

### 1. JavaScript — logo-schema-editor.js

#### 1.1 `blocks.registerBlockType` Filter

Filter name: `gregius-optimizer/logo-schema/attributes`

Injects the `organizationLogoSchema` attribute into `core/site-logo` block settings:

```js
attributes: {
    ...settings.attributes,
    organizationLogoSchema: {
        type: 'boolean',
        default: false,
    },
}
```

#### 1.2 `editor.BlockEdit` HOC

Filter name: `gregius-optimizer/logo-schema/inspector`

Wraps the `core/site-logo` block edit component to add an `InspectorControls` panel with a `ToggleControl`:

```jsx
<ToggleControl
    label="Include logo in site's structured data"
    help="Include the site logo in the site's <head> as structured data (Organization.logo)."
    checked={ !! attributes.organizationLogoSchema }
    onChange={ ( value ) => setAttributes( { organizationLogoSchema: !! value } ) }
/>
```

The toggle is only rendered when the site-logo block is selected (`isSelected`).

#### 1.3 Filter Registration Summary

| Handle | Hook | Purpose |
|---|---|---|
| `gregius-optimizer/logo-schema/attributes` | `blocks.registerBlockType` | Add `organizationLogoSchema` attribute |
| `gregius-optimizer/logo-schema/inspector` | `editor.BlockEdit` | Add toggle control UI |

---

### 2. PHP — includes/schema.php

#### 2.1 `gg_optimizer_schema_extract_logo_url`

```php
gg_optimizer_schema_extract_logo_url( WP_Post $post ) : string
```

Entry point for logo URL extraction. Iterates through organization content sources and delegates to the recursive block walker.

**Parameters:**
- `$post` (`WP_Post`) — The post object to extract the logo URL from.

**Returns:**
`string` — The logo image URL, or empty string if no logo is found.

**Example return:**
```
https://example.com/wp-content/uploads/2026/05/logo.png
```

**Source:** `includes/schema.php:253`

#### 2.2 `gg_optimizer_schema_find_logo_in_blocks`

```php
gg_optimizer_schema_find_logo_in_blocks( array $blocks ) : string
```

Recursively walks a block tree, finds the first `core/site-logo` block with `organizationLogoSchema` attribute set, and returns its image URL.

**Resolution logic:**
1. Detects `core/site-logo` blocks where `$block['attrs']['organizationLogoSchema']` is truthy.
2. If `$block['attrs']['url']` is present, uses it directly with `esc_url_raw`.
3. If no URL in attributes, falls back to `get_theme_mod( 'custom_logo' )` resolved via `wp_get_attachment_image_url`.
4. Recurse into `innerBlocks` of all blocks to handle nesting.
5. Returns the first match found — halts further search.

**Source:** `includes/schema.php:277`

---

### 3. Hook Reference

| Hook | Type | File:Line | Description |
|---|---|---|---|
| `gg_optimizer_schema_output_organization` | filter | `schema.php:320` | Suppress entire Organization JSON-LD output (including logo and sameAs). Parameters: `(bool $output)`. Default `true`. |

---

### 4. Output Format

The Organization JSON-LD node includes the `logo` property when a URL is resolved:

```json
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Organization",
            "@id": "https://example.com#organization",
            "name": "My Site",
            "url": "https://example.com/",
            "logo": "https://example.com/wp-content/uploads/2026/05/logo.png",
            "sameAs": [
                "https://github.com/myorg"
            ]
        }
    ]
}
```

If no logo URL is found, the `logo` property is omitted from the Organization node entirely.

---

### 5. Integration Guide

#### Disabling Organization Output

```php
add_filter( 'gg_optimizer_schema_output_organization', '__return_false' );
```

This removes the entire Organization JSON-LD node, including `logo`, `sameAs`, `name`, and `url`.

#### Removing the Logo Schema Toggle from the Editor

```php
add_action( 'enqueue_block_editor_assets', function () {
    wp_dequeue_script( 'gg-optimizer-logo-schema-editor' );
} );
```

### 6. Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| Logo not appearing in Organization JSON-LD | Site-logo block missing `organizationLogoSchema` attribute | Verify the toggle is enabled in the block inspector |
| Logo not appearing | `gg_optimizer_schema_output_organization` filter returning false | Check for `add_filter` callbacks on this hook |
| Logo not appearing | Schema feature toggle disabled in the Schema modal | Check `GG_Optimizer_Feature_Toggle::is_enabled('schema')` — the parent toggle blocks all sub-schemas |
| Wrong logo URL | Nested site-logo block takes precedence | Only enable the toggle on one site-logo block |
| No logo even with toggle on | Block `attrs.url` empty and no theme custom logo set | Set a site logo in the Customizer or populate the block's URL attribute |
| Logo appears on non-singular pages | Feature should only run on singular views | Verify `is_singular()` guard in `output_organization_json_ld()` |

---

## Traceability

| Function / Filter / Component | SRS Requirement |
|---|---|
| `addAttributes` (JS filter) | FR-01 |
| `withInspectorControls` (JS HOC) | FR-02 |
| `gg_optimizer_schema_extract_logo_url` | FR-03 |
| URL resolution: attrs.url → theme custom_logo | FR-04 |
| Recursive block walking | FR-05 |
| First-match halt | FR-06 |
| Organization `logo` property in JSON-LD | FR-07, FR-08 |
| `gg_optimizer_schema_output_organization` filter | FR-09 |
| `esc_url_raw` + `wp_json_encode` | SEC-01, SEC-02 |
