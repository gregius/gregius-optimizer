# Architecture Description — Schema Logo

**Standard:** ISO/IEC/IEEE 42010:2022

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema-logo |
| Version | 0.1 — Draft |
| Date | 2026-06-07 |
| Author | Gregius Engineering |
| SRS Reference | `docs/schema-logo/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The Schema Logo feature allows content editors to mark a `core/site-logo` block for inclusion in the Organization's structured data `logo` property. The logo URL is extracted from the block's attributes at render time with a fallback to the theme's custom logo.

### 1.2 Explicitly Excluded

- Logo storage in post meta or custom tables (data is read from block content dynamically)
- Dedicated REST endpoints (logo uses the existing schema preview endpoint)
- Third-party logo blocks (only `core/site-logo` is supported)
- Image validation, dimension checking, or format enforcement

### 1.3 Feature Slug

`schema-logo`

---

## 2. Architecture Views

### 2.1 Context View (AV-01)

| External System | Direction | Description |
|---|---|---|
| Block Editor (`logo-schema-editor.js`) | → provides | Toggle control via `editor.BlockEdit` HOC; registers `organizationLogoSchema` attribute on `core/site-logo` |
| Post Content (`post_content`) | → reads | Block parser walks tree for site-logo blocks with `organizationLogoSchema` attribute |
| Theme Customizer (`custom_logo`) | → reads | Fallback source when block `url` attribute is empty |
| Schema Output (`schema.php`) | → extends | `output_organization_json_ld()` calls `extract_logo_url()` and includes result in Organization `logo` property |
| `wp_head` | ← adds | Organization JSON-LD with `logo` property |

### 2.2 Component View (AV-02)

```
┌─────────────────────────────────────────────────────────────────┐
│                    logo-schema-editor.js                         │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ addAttributes (blocks.registerBlockType)                     ││
│  │  → Injects organizationLogoSchema: boolean, default false    ││
│  │  → Only on core/site-logo                                    ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ withInspectorControls (editor.BlockEdit HOC)                 ││
│  │  → Pass-through for non-site-logo blocks                     ││
│  │  → For core/site-logo: wraps BlockEdit + InspectorControls  ││
│  │    → ToggleControl inside PanelBody("Organization Logo")     ││
│  │    → checked = attributes.organizationLogoSchema             ││
│  │    → onChange → setAttributes()                              ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     includes/schema.php                          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_extract_logo_url(WP_Post $post)          ││
│  │  → Get content sources via organization_content_sources()   ││
│  │  → For each source: parse_blocks() → find_logo_in_blocks()  ││
│  │  → Return first URL found, or ''                             ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_find_logo_in_blocks(array $blocks)       ││
│  │  → For each block:                                          ││
│  │    → IF core/site-logo && organizationLogoSchema=true       ││
│  │      → Use block attrs.url if available                      ││
│  │      → Else fallback to theme mod custom_logo               ││
│  │      → Return esc_url_raw() of first match                   ││
│  │    → Recurse into innerBlocks                                ││
│  │  → Return '' (no match)                                      ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_output_organization_json_ld()            ││
│  │  → Build Organization node with name, url, logo, sameAs     ││
│  │  → logo = extract_logo_url($post)                            ││
│  │  → Output via wp_json_encode                                 ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

### 2.3 Runtime Interaction View (AV-03)

**Flow A: Editor — User enables Logo Schema on a site-logo block**

```
  User                   Block Editor            logo-schema-editor.js
   │                          │                          │
   │— Select site-logo block  │                          │
   │                          │— Render BlockEdit        │
   │                          │— WithInspectorControls   │
   │                          │   Render ToggleControl   │
   │                          │   in InspectorControls   │
   │— Toggle ON               │                          │
   │                          │— setAttributes(          │
   │                          │   { organizationLogoSchema: true })
   │                          │                          │
   │— Save post               │                          │
   │                          │— organizationLogoSchema  │
   │                          │   persisted in block     │
```

**Flow B: Frontend — Page render includes logo in Organization JSON-LD**

```
  Browser               wp_head                 schema.php
    │                     │                        │
    │— GET /about         │                        │
    │                     │— action ──────────────>│
    │                     │                        │— output_organization_json_ld()
    │                     │                        │  → extract_logo_url()
    │                     │                        │    → get_organization_content_sources()
    │                     │                        │    → parse_blocks()
    │                     │                        │    → find_logo_in_blocks()
    │                     │                        │      → core/site-logo found
    │                     │                        │      → organizationLogoSchema = true
    │                     │                        │      → attrs.url available
    │                     │                        │      → return esc_url_raw(url)
    │                     │                        │  → Build Organization node
    │                     │                        │  → Include logo property
    │<── HTML + <script> ─│<───────────────────────│
    │       ld+json>      │                        │
    │"logo":"https://..." │                        │
```

---

## 3. Architecture Decision Records

### AD-01: Block Attribute Over Post Meta

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-01, FR-02 |
| Decision | Store the Logo Schema toggle as a block attribute (`organizationLogoSchema`) rather than post meta. |
| Alternatives | Post meta key `_gg_optimizer_org_logo`, custom block style |
| Rationale | The logo toggle is semantically tied to the site-logo block, not the post. Using a block attribute means the setting moves with the block on duplication or reuse. |
| Consequences | Extraction must parse post content at render time. Attribute is not queryable from admin list tables. |

### AD-02: First-Match Extraction (No Aggregation)

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-04, FR-06 |
| Decision | Extract the first matching logo URL and halt. Do not aggregate multiple site-logo blocks or merge URLs. |
| Alternatives | Collect all matching URLs and use the last; merge into an array |
| Rationale | A site should have exactly one Organization logo. The first match is deterministic and predictable. |
| Consequences | If multiple site-logo blocks have the toggle enabled, only the first (by DOM order) is used. Editors should only enable the toggle on one block. |

### AD-03: Fallback Chain with Theme Custom Logo

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-04 |
| Decision | When the site-logo block has no `url` attribute (e.g., using the default site icon), fall back to the theme's `custom_logo` setting resolved via `wp_get_attachment_image_url`. |
| Alternatives | Require a URL in the block attributes; use the site icon as final fallback |
| Rationale | The site-logo block in WordPress does not always populate `attrs.url` — it may be rendered dynamically from the theme mod. The fallback ensures a logo URL is still produced when the toggle is enabled. |
| Consequences | If the theme mod is also empty, no logo URL is output (empty string). Editors should ensure a logo is set in the Customizer or the block URL is populated. |

---

## 4. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01, FR-02, FR-07, FR-08, FR-09 |
| AV-02 Component View | FR-03, FR-04, FR-05, FR-06 |
| AV-03 Flow A | FR-01, FR-02 |
| AV-03 Flow B | FR-03–FR-08 |
| AD-01 Block Attribute | FR-01 |
| AD-02 First-Match | FR-06 |
| AD-03 Fallback Chain | FR-04 |
