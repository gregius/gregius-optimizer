# Architecture Description — Schema SameAs

**Standard:** ISO/IEC/IEEE 42010:2022

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema-sameas |
| Version | 0.1 — Draft |
| Date | 2026-06-07 |
| Author | Gregius Engineering |
| SRS Reference | `docs/schema-sameas/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The Schema SameAs feature allows content editors to mark a `core/social-links` block for inclusion in the Organization's structured data `sameAs` array. Social profile URLs are extracted from the block's inner `core/social-link` blocks at render time via two complementary strategies.

### 1.2 Explicitly Excluded

- Social URL storage in post meta or custom tables (data is read from block content dynamically)
- Dedicated REST endpoints (sameAs uses the existing schema preview endpoint)
- Third-party social link blocks (only `core/social-links` is supported)
- URL validation beyond `esc_url_raw` (no link checking, no social platform verification)

### 1.3 Feature Slug

`schema-sameas`

---

## 2. Architecture Views

### 2.1 Context View (AV-01)

| External System | Direction | Description |
|---|---|---|
| Block Editor (`sameas-schema-editor.js`) | → provides | Toggle control via `editor.BlockEdit` HOC; registers `sameAsSchema` attribute on `core/social-links` |
| Post Content (`post_content`) | → reads | Two extraction paths: parsed block tree and regex serialized scanner |
| WP Block Patterns Registry | → reads | Resolves `core/pattern` blocks to find nested social-links in pattern content |
| Synced Pattern Posts (`wp_block` CPT) | → reads | Resolves `core/block` synced pattern refs to find nested social-links |
| Schema Output (`schema.php`) | → extends | `output_organization_json_ld()` calls `extract_sameas_urls()` and includes result in Organization `sameAs` array |
| `wp_head` | ← adds | Organization JSON-LD with `sameAs` array |

### 2.2 Component View (AV-02)

```
┌─────────────────────────────────────────────────────────────────┐
│                   sameas-schema-editor.js                       │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ addAttributes (blocks.registerBlockType)                     ││
│  │  → Injects sameAsSchema: boolean, default false              ││
│  │  → Only on core/social-links                                 ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ withInspectorControls (editor.BlockEdit HOC)                 ││
│  │  → Pass-through for non-social-links blocks                  ││
│  │  → For core/social-links: BlockEdit + InspectorControls     ││
│  │    → ToggleControl inside PanelBody("Organization SameAs")  ││
│  │    → checked = attributes.sameAsSchema                       ││
│  │    → onChange → setAttributes()                              ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     includes/schema.php                          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_extract_sameas_urls(WP_Post $post)       ││
│  │  → Get content sources                                       ││
│  │  → For each source:                                          ││
│  │    → find_sameas_in_blocks(parse_blocks())  (parsed path)    ││
│  │    → extract_sameas_urls_from_serialized()  (regex path)    ││
│  │  → Merge, array_unique, array_values, return                 ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_find_sameas_in_blocks(array $blocks)     ││
│  │  → For each block:                                          ││
│  │    → IF core/pattern: resolve from registry, recurse        ││
│  │      with seen_pattern_slugs cycle detection                 ││
│  │    → IF core/block: resolve synced post, recurse            ││
│  │      with seen_block_refs cycle detection                    ││
│  │    → IF core/social-links && sameAsSchema=true:             ││
│  │      → For inner core/social-link: collect attrs.url        ││
│  │    → Recurse into innerBlocks                                ││
│  │  → Return URL array                                          ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_extract_sameas_urls_from_serialized()    ││
│  │  → Regex: match wp:social-links comments with attrs         ││
│  │  → If sameAsSchema=true in parsed attrs:                    ││
│  │    → Regex: extract wp:social-link attrs for url            ││
│  │    → Regex: extract <a href> from rendered HTML             ││
│  │  → Return URL array                                          ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_output_organization_json_ld()            ││
│  │  → Build Organization node with name, url, logo, sameAs     ││
│  │  → sameAs = extract_sameas_urls($post)                       ││
│  │  → Output via wp_json_encode                                 ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

### 2.3 Runtime Interaction View (AV-03)

**Flow A: Editor — User enables SameAs Schema on a social-links block**

```
  User                   Block Editor         sameas-schema-editor.js
   │                          │                       │
   │— Select social-links     │                       │
   │  block                   │                       │
   │                          │— Render BlockEdit     │
   │                          │— WithInspectorControls│
   │                          │   Render ToggleControl│
   │— Toggle ON               │                       │
   │                          │— setAttributes(       │
   │                          │   { sameAsSchema: true })
   │— Save post               │                       │
   │                          │— sameAsSchema=true    │
   │                          │   persisted in block  │
```

**Flow B: Frontend — Page render extracts sameAs URLs**

```
  Browser               wp_head                 schema.php
    │                     │                        │
    │— GET /about         │                        │
    │                     │— action ──────────────>│
    │                     │                        │— output_organization_json_ld()
    │                     │                        │  → extract_sameas_urls()
    │                     │                        │    → get_org_content_sources()
    │                     │                        │    → find_sameas_in_blocks()
    │                     │                        │      → core/social-links found
    │                     │                        │      → sameAsSchema = true
    │                     │                        │      → collect link URLs
    │                     │                        │    → extract_sameas_from_serialized()
    │                     │                        │      → regex match social-links
    │                     │                        │      → extract URLs
    │                     │                        │    → array_unique, array_values
    │                     │                        │  → Build Organization node
    │                     │                        │  → Include sameAs array
    │<── HTML + <script> ─│<───────────────────────│
    │       ld+json>      │                        │
    │"sameAs":["https://..│                        │
```

---

## 3. Architecture Decision Records

### AD-01: Dual Extraction Strategy (Parsed + Regex)

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-03 |
| Decision | Use both a parsed block tree walker and a regex-based serialized content scanner for URL extraction. |
| Alternatives | Parsed blocks only; regex only; server-side DOM parsing |
| Rationale | The parsed walker handles pattern blocks, synced patterns, and arbitrary nesting but may miss blocks rendered by shortcodes or legacy content. The regex scanner catches serialized comments that the block parser might skip. Running both ensures maximum coverage. |
| Consequences | URLs may be collected twice — de-duplication with `array_unique` is mandatory. Two code paths to maintain. |

### AD-02: Cycle Detection for Pattern/Synced Blocks

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-04, FR-05 |
| Decision | Track visited pattern slugs and synced block refs with arrays. Skip re-visiting any slug or ref already seen in the current extraction chain. |
| Alternatives | Depth limit (max 5 levels); no cycle detection |
| Rationale | Pattern blocks can reference other patterns, creating circular references. Synced blocks (reusable blocks) can contain themselves. Without cycle detection, extraction would loop infinitely or exhaust memory. |
| Consequences | Slightly more complex code (two tracking arrays). Patterns that are intentionally duplicated are only scanned once, which is acceptable since URLs are de-duplicated later. |

### AD-03: Block Attribute Over Post Meta

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-01, FR-02 |
| Decision | Store the SameAs Schema toggle as a block attribute (`sameAsSchema`) rather than post meta. |
| Alternatives | Post meta key `_gg_optimizer_sameas`, custom block style |
| Rationale | The sameAs toggle is semantically tied to the social-links block, not the post. Attribute follows the block on duplication or reuse. |
| Consequences | Extraction must parse post content at render time. Attribute is not queryable from admin list tables. |

---

## 4. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01, FR-02, FR-09, FR-10, FR-11 |
| AV-02 Component View | FR-03–FR-08 |
| AV-03 Flow A | FR-01, FR-02 |
| AV-03 Flow B | FR-03–FR-10 |
| AD-01 Dual Extraction | FR-03 |
| AD-02 Cycle Detection | FR-04, FR-05 |
| AD-03 Block Attribute | FR-01 |
