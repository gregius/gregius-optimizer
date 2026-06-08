# Developer Documentation — Schema FAQ

**Standard:** ISO/IEC/IEEE 26514:2022

---

## Overview

The Schema FAQ feature adds a `faqSchema` boolean attribute to `core/accordion` blocks. When enabled, the plugin extracts question-and-answer pairs from the accordion's inner block structure at render time and outputs them as `FAQPage` JSON-LD.

**SRS reference:** `docs/schema-faq/srs.md` (FR-01 through FR-10)

---

## API Reference

### 1. JavaScript — faq-schema-editor.js

#### 1.1 `blocks.registerBlockType` Filter

Filter name: `gregius-optimizer/faq-schema/attributes`

Injects the `faqSchema` attribute into `core/accordion` block settings:

```js
attributes: {
    ...settings.attributes,
    faqSchema: {
        type: 'boolean',
        default: false,
    },
}
```

#### 1.2 `editor.BlockEdit` HOC

Filter name: `gregius-optimizer/faq-schema/inspector`

Wraps the `core/accordion` block edit component to add an `InspectorControls` panel with a `ToggleControl`:

```jsx
<ToggleControl
    label="Include accordion Q&A in site's structured data"
    help="Include this accordion's Q&A pairs in the site's <head> as structured data (FAQPage)."
    checked={ !! attributes.faqSchema }
    onChange={ ( value ) => setAttributes( { faqSchema: !! value } ) }
/>
```

The toggle is only rendered when the accordion block is selected (`isSelected`).

#### 1.3 Filter Registration Summary

| Handle | Hook | Purpose |
|---|---|---|
| `gregius-optimizer/faq-schema/attributes` | `blocks.registerBlockType` | Add `faqSchema` attribute |
| `gregius-optimizer/faq-schema/inspector` | `editor.BlockEdit` | Add toggle control UI |

---

### 2. PHP — includes/schema.php

#### 2.1 `gg_optimizer_schema_extract_faq_items`

```php
gg_optimizer_schema_extract_faq_items( WP_Post $post ) : array
```

Entry point for FAQ extraction. Parses post content into blocks and delegates to the recursive walker.

**Parameters:**
- `$post` (`WP_Post`) — The post object to extract FAQ items from.

**Returns:**
`array` — Array of associative arrays, each with `question` (string) and `answer` (string).

**Example return:**
```php
[
    [ 'question' => 'What is Gregius?', 'answer' => 'Gregius is an orchestration layer for AI workflows in WordPress.' ],
    [ 'question' => 'Is it open source?', 'answer' => 'Yes, it is GPL-2.0-or-later.' ],
]
```

**Source:** `includes/schema.php:438`

#### 2.2 `gg_optimizer_schema_find_faq_in_blocks`

```php
gg_optimizer_schema_find_faq_in_blocks( array $blocks ) : array
```

Recursively walks a block tree, finds `core/accordion` blocks with `faqSchema` attribute set, and extracts Q&A pairs.

**Extraction logic:**
1. Detects `core/accordion` blocks where `$block['attrs']['faqSchema']` is truthy and inner blocks exist.
2. For each `core/accordion-item` inside:
   - **Question** — Reads from `core/accordion-heading` inner block. First attempts regex match for `<span class="toggle-title">` content, then falls back to the full rendered text of the heading block.
   - **Answer** — Concatenates text from all `core/paragraph` blocks inside `core/accordion-panel`.
3. Only includes pairs where both question and answer are non-empty after trimming.
4. Recurses into `innerBlocks` of all blocks (not just accordions) to handle nesting.

**Source:** `includes/schema.php:454`

---

### 3. Hook Reference

| Hook | Type | File:Line | Description |
|---|---|---|---|
| `gg_optimizer_schema_output_faq` | filter | `schema.php:934` | Suppress FAQPage output. Parameters: `(bool $output)`. Default `true`. |

**Usage:**
```php
// Disable FAQPage output entirely.
add_filter( 'gg_optimizer_schema_output_faq', '__return_false' );

// Disable FAQPage output for a specific post type.
add_filter( 'gg_optimizer_schema_output_faq', function ( $output ) {
    if ( is_singular( 'product' ) ) {
        return false;
    }
    return $output;
} );
```

---

### 4. Output Format

The FAQPage node is injected into the page's `@graph` array:

```json
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "WebPage",
            "@id": "https://example.com/faq#webpage",
            "url": "https://example.com/faq"
        },
        {
            "@type": "FAQPage",
            "@id": "https://example.com/faq#faq",
            "isPartOf": { "@id": "https://example.com/faq#webpage" },
            "mainEntity": [
                {
                    "@type": "Question",
                    "name": "What is Gregius?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Gregius is an orchestration layer for AI workflows in WordPress."
                    }
                }
            ]
        }
    ]
}
```

---

### 5. Integration Guide

#### Disabling FAQ Schema for Specific Post Types

```php
add_filter( 'gg_optimizer_schema_output_faq', function ( $output ) {
    if ( is_singular( [ 'product', 'event' ] ) ) {
        return false;
    }
    return $output;
} );
```

#### Removing the FAQ Schema Toggle from the Editor

```php
// Remove the inspector control HOC.
add_action( 'enqueue_block_editor_assets', function () {
    wp_dequeue_script( 'gg-optimizer-faq-schema-editor' );
} );
```

#### Customizing Question Extraction

The extraction is tightly coupled to the theme's accordion heading markup. If the theme does not use a `.toggle-title` span, the extraction falls back to the full rendered `<div>` content of the heading block. To customize:

```php
add_filter( 'gg_optimizer_schema_faq_question', function ( $question, $block ) {
    // $block is the accordion-heading inner block array.
    // Return the extracted question text.
    return $question;
}, 10, 2 );
```

*Note: The `gg_optimizer_schema_faq_question` filter does not exist yet — it is listed as a future extension point. Currently, extraction logic is hard-coded inside `find_faq_in_blocks`.*

---

### 6. Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| FAQPage not appearing in source | Accordion block missing `faqSchema` attribute | Verify the toggle is enabled in the block inspector |
| FAQPage not appearing in source | `gg_optimizer_schema_output_faq` filter returning false | Check for `add_filter` callbacks on this hook |
| FAQPage not appearing in source | Schema output removed via `remove_action('wp_head', ...)` | Check for conflicting plugins |
| Wrong question text extracted | Theme markup changed; `.toggle-title` span missing | Check the accordion heading HTML structure |
| Empty answer | Accordion panel uses non-paragraph blocks (lists, images) | Extract currently only collects `core/paragraph` text |
| Nested accordion items duplicated | Multiple accordion blocks with `faqSchema` inside each other | Verify only the intended accordion has the toggle enabled |
| FAQPage shows on non-singular pages | Feature should only run on singular views | Verify `is_singular()` guard in `build_json_ld()` |

---

## Traceability

| Function / Filter / Component | SRS Requirement |
|---|---|
| `addAttributes` (JS filter) | FR-01 |
| `withInspectorControls` (JS HOC) | FR-02 |
| `gg_optimizer_schema_extract_faq_items` | FR-03 |
| Question extraction from `accordion-heading` | FR-04 |
| Answer extraction from `accordion-panel` paragraphs | FR-05 |
| Non-empty pair filtering | FR-06 |
| Recursive block walking | FR-07 |
| FAQPage node in `@graph` | FR-08, FR-09 |
| `gg_optimizer_schema_output_faq` filter | FR-10 |
| `wp_strip_all_tags` + `wp_json_encode` | SEC-01, SEC-02 |
