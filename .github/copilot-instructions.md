# Gregius Optimizer Coding Standards

This document defines implementation boundaries and day-to-day engineering rules for `gregius-optimizer`.

## 1) Plugin Ownership and Boundaries

`gregius-optimizer` is the standalone SEO/AEO/SMO/LLMO extension plugin.

- `gregius-optimizer` owns all optimization features: sitemap, robots, schema, social cards, meta tags, and LLMs context.
- Do not absorb consumer-domain logic into `gregius-optimizer` that belongs in other plugins.
- All public interfaces, hooks, and contracts exposed by `gregius-optimizer` are stable APIs. Breaking changes require an explicit versioning discussion.

## 2) Public Documentation — Gutenberg HTML Format

All Gregius Optimizer public documentation pages (generated via `wp-public-documentation` skill + Gutenberg block markup) must follow this exact Gutenberg block markup layout.

### Layout

Two-column layout: sticky sidebar table of contents (33.33%) on the left, content (66.66%) on the right.

```html
<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"right":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide" style="padding-right:0;padding-left:0">
  <!-- wp:column {"width":"33.33%"} -->
  <div class="wp-block-column" style="flex-basis:33.33%">
    ...sidebar TOC...
  </div>
  <!-- /wp:column -->
  <!-- wp:column {"width":"66.66%"} -->
  <div class="wp-block-column" style="flex-basis:66.66%">
    ...content...
  </div>
  <!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

### Sidebar Table of Contents

The TOC sits inside a sticky-positioned group with a heading-3 title and small-font list. Every `<li>` uses `<!-- wp:list-item -->` wrappers. Nested sub-lists use `<!-- wp:list -->` inside the parent `<li>`.

```html
<!-- wp:group {"className":"is-sticky-scroll","style":{"position":{"type":"sticky","top":"0px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group is-sticky-scroll">
  <!-- wp:heading {"fontSize":"heading-4","anchor":"in-this-page"} -->
  <h2 id="in-this-page" class="wp-block-heading has-heading-4-font-size">In this page</h2>
  <!-- /wp:heading -->

  <!-- wp:list {"fontSize":"medium"} -->
  <ul class="wp-block-list has-medium-font-size"><!-- wp:list-item -->
  <li><a href="#section-slug">Section Title</a><!-- wp:list -->
  <ul class="wp-block-list"><!-- wp:list-item -->
  <li><a href="#subsection-slug">Subsection Title</a></li>
  <!-- /wp:list-item --></ul>
  <!-- /wp:list --></li>
  <!-- /wp:list-item -->
  ...
  </ul>
  <!-- /wp:list -->

  <!-- wp:separator -->
  <hr class="wp-block-separator has-alpha-channel-opacity"/>
  <!-- /wp:separator -->
</div>
<!-- /wp:group -->
```

### Content Block Conventions

| Block | Attribute pattern | Example |
|-------|-------------------|---------|
| H2 | `{"anchor":"..."}` | `<!-- wp:heading {"anchor":"overview"} -->` |
| H3 | `{"level":3,"anchor":"..."}` | `<!-- wp:heading {"level":3,"anchor":"prerequisites"} -->` |
| Paragraph | `{"className":"wp-block-paragraph"}` | `<!-- wp:paragraph {"className":"wp-block-paragraph"} -->` |
| List | `{"className":"wp-block-list"}` + `<!-- wp:list-item -->` on every `<li>` | See below |
| Table | `{"hasFixedLayout":false}` | `<!-- wp:table {"hasFixedLayout":false} -->` |
| Separator | (no attributes) | `<!-- wp:separator -->` |

**List example:**
```html
<!-- wp:list {"className":"wp-block-list"} -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Item one</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Item two</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
```

**Table example:**
```html
<!-- wp:table {"hasFixedLayout":false} -->
<figure class="wp-block-table"><table>
  <thead><tr><th>Column</th><th>Value</th></tr></thead>
  <tbody><tr><td>Item</td><td>Description</td></tr></tbody>
</table></figure>
<!-- /wp:table -->
```

### Section Order

1. **Overview** (H2) with **Prerequisites** (H3)
2. Separator
3. One or more **How-to** sections (H2) with H3 subsections, each separated by separators
4. Separator
5. **Permissions** (H2, table format)
6. Separator
7. **Next Steps** (H2, local links + global links)

### Anchor Derivation

Derive from heading text: lowercase, spaces to hyphens, strip punctuation. Duplicate H3 headings use disambiguated anchors (e.g., `#tips-connections`, `#tips-models`).

### Validation

After generation, validate with:
```bash
BLOCKS_DB_PATH=/home/hector/.wp-blockmarkup-mcp/blocks.db wp-blocks validate <file.html>
```

Confirm the TOC `core/list` shows `N inner blocks` (one per `wp:list-item`), not `0 inner blocks`.

## 3) Do Not Do (Concrete Anti-Patterns)

- Do not absorb consumer-domain feature logic into `gregius-optimizer` namespaces or directories.
- Do not expose unstable or internal classes as public contracts.
- Do not introduce breaking changes to public hook signatures, API payloads, or schema contracts when additive changes would solve the requirement.
- Do not add undocumented public hooks or filters.
