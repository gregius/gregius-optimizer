# Architecture Description — Schema FAQ

**Standard:** ISO/IEC/IEEE 42010:2022

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema-faq |
| Version | 0.1 — Draft |
| Date | 2026-06-07 |
| Author | Gregius Engineering |
| SRS Reference | `docs/schema-faq/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The Schema FAQ feature allows content editors to mark `core/accordion` blocks for inclusion in FAQPage structured data. Q&A pairs are extracted from the accordion's inner block structure at render time and appended to the site's existing JSON-LD `@graph`.

### 1.2 Explicitly Excluded

- FAQ storage in post meta or custom tables (data is read from block content dynamically)
- Dedicated REST endpoints (FAQ uses the schema preview endpoint)
- Third-party accordion blocks (only `core/accordion` is supported)
- FAQPage as a standalone page type (the per-post subtype selector in the Schema panel handles that independently)

### 1.3 Feature Slug

`schema-faq`

---

## 2. Architecture Views

### 2.1 Context View (AV-01)

| External System | Direction | Description |
|---|---|---|
| Block Editor (`faq-schema-editor.js`) | → provides | Toggle control via `editor.BlockEdit` HOC; registers `faqSchema` attribute on `core/accordion` |
| Post Content (`post_content`) | → reads | Block parser walks tree for accordions with `faqSchema` attribute |
| Schema Output (`schema.php`) | → extends | `build_json_ld()` calls `extract_faq_items()` and appends FAQPage node to `@graph` |
| `wp_head` | ← adds | FAQPage JSON-LD inline in existing `<script type="application/ld+json">` |
| `gg_optimizer_schema_output_faq` | → controls | Filter gate to suppress FAQPage output |

### 2.2 Component View (AV-02)

```
┌─────────────────────────────────────────────────────────────────┐
│                    faq-schema-editor.js                          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ addAttributes (blocks.registerBlockType)                     ││
│  │  → Injects faqSchema: { type: 'boolean', default: false }   ││
│  │  → Only on core/accordion                                    ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ withInspectorControls (editor.BlockEdit HOC)                 ││
│  │  → Pass-through for non-accordion blocks                     ││
│  │  → For core/accordion: wraps BlockEdit + InspectorControls  ││
│  │    → ToggleControl inside PanelBody("FAQ Schema")            ││
│  │    → checked = attributes.faqSchema                          ││
│  │    → onChange → setAttributes({ faqSchema })                 ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     includes/schema.php                          │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_extract_faq_items(WP_Post $post)         ││
│  │  → Empty post_content → return []                           ││
│  │  → parse_blocks($post->post_content)                        ││
│  │  → Delegate to find_faq_in_blocks()                         ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_find_faq_in_blocks(array $blocks)        ││
│  │  → For each block:                                          ││
│  │    → IF core/accordion && faqSchema=true                    ││
│  │      → For each inner core/accordion-item:                  ││
│  │        → Extract question from accordion-heading             ││
│  │        → Extract answer from accordion-panel paragraphs      ││
│  │        → If both non-empty → add to faq[]                   ││
│  │    → Recurse into innerBlocks                               ││
│  │  → Return faq[]                                             ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_build_json_ld()                          ││
│  │  → If singular && apply_filters('output_faq', true):        ││
│  │    → extract_faq_items($post)                               ││
│  │    → Build FAQPage node with @id, isPartOf, mainEntity[]    ││
│  │    → Append to @graph                                       ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ gg_optimizer_schema_output_json_ld()                         ││
│  │  → wp_json_encode() → echo <script type="application/ld+json">│
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

### 2.3 Runtime Interaction View (AV-03)

**Flow A: Editor — User enables FAQ Schema on an accordion**

```
  User                   Block Editor             faq-schema-editor.js
   │                          │                          │
   │— Select accordion block  │                          │
   │                          │— Render BlockEdit        │
   │                          │— WithInspectorControls   │
   │                          │   Render ToggleControl   │
   │                          │   in InspectorControls   │
   │— Toggle ON               │                          │
   │                          │— setAttributes(          │
   │                          │   { faqSchema: true })   │
   │                          │                          │
   │— Save post               │                          │
   │                          │— faqSchema=true persisted│
   │                          │   in post_content blocks │
```

**Flow B: Frontend — Page render produces FAQPage JSON-LD**

```
  Browser               wp_head                 schema.php
    │                     │                        │
    │— GET /faq-page      │                        │
    │                     │— action ──────────────>│
    │                     │                        │— output_json_ld()
    │                     │                        │  → build_json_ld()
    │                     │                        │    → extract_faq_items()
    │                     │                        │      → parse_blocks()
    │                     │                        │      → find_faq_in_blocks()
    │                     │                        │        → core/accordion found
    │                     │                        │        → faqSchema = true
    │                     │                        │        → extract heading title
    │                     │                        │        → extract panel text
    │                     │                        │        → return 3 pairs
    │                     │                        │    → Build FAQPage node
    │                     │                        │    → Append to @graph
    │                     │                        │  → wp_json_encode()
    │<── HTML + <script> ─│<───────────────────────│
    │        ld+json>     │                        │
    │  (includes FAQPage) │                        │
```

---

## 3. Architecture Decision Records

### AD-01: Block Attribute Over Post Meta

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-01, FR-02 |
| Decision | Store the FAQ Schema toggle as a block attribute (`faqSchema`) rather than post meta. |
| Alternatives | Post meta key `_gg_optimizer_faq_schema`, custom block styles/classnames |
| Rationale | The FAQ toggle is semantically tied to the accordion block, not to the post. Using a block attribute means the setting moves with the block when copied, duplicated, or reused in patterns. Post meta would break on block duplication and require synchronization logic. |
| Consequences | Extraction must parse post content at render time (no direct meta query). Block attributes are only accessible from the block editor and the frontend parser — not from admin list tables or REST queries. |

### AD-02: Heading Span + Paragraph Extraction

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-04, FR-05 |
| Decision | Extract the question from `core/accordion-heading` inner blocks by regex-matching `<span class="toggle-title">` content, with a fallback to full rendered text. Extract the answer from all `core/paragraph` blocks inside `core/accordion-panel`. |
| Alternatives | Full HTML rendering of the heading block, extracting from `<summary>` element, extracting from editor-only rich text attributes |
| Rationale | The `.toggle-title` span is the theme-specific aria-label element that best represents the visible question text. The paragraph-only approach for answers avoids including navigation markup, buttons, or other non-content elements that might appear inside the panel. |
| Consequences | Tightly coupled to the theme's accordion markup. If the theme changes the heading structure, the regex must be updated. Answers are limited to paragraph text — lists, images, and embeds are excluded from FAQ text. |

### AD-03: Filter Gate for Output Control

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-10 |
| Decision | Control FAQPage output via `apply_filters('gg_optimizer_schema_output_faq', true)` in `build_json_ld()`, gating the entire FAQ extraction + output path. |
| Alternatives | JavaScript-side toggle on a post meta key, removing the `wp_head` action, or CSS/JS runtime removal |
| Rationale | A PHP filter allows site-level or conditional disable without modifying editor content. Administrators can disable FAQ schema for specific post types, user roles, or environments without touching the accordion blocks themselves. |
| Consequences | Requires a filter callback or the parent Schema feature toggle for disablement. No dedicated UI in the admin for this specific filter — the FAQPage output is also gated by the parent Schema modal's on/off toggle. |

---

## 4. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01, FR-02, FR-08, FR-09, FR-10 |
| AV-02 Component View | FR-03, FR-04, FR-05, FR-06, FR-07 |
| AV-03 Flow A | FR-01, FR-02 |
| AV-03 Flow B | FR-03–FR-09 |
| AD-01 Block Attribute | FR-01 |
| AD-02 Extraction Strategy | FR-04, FR-05 |
| AD-03 Filter Gate | FR-10 |
