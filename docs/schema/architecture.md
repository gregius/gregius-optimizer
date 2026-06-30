---
type: Architecture
title: "Architecture Description — Schema"
description: "Architecture views, ADRs, constraints, and risks for Organization, WebSite, BreadcrumbList, and content JSON-LD schema generation and output."
subsystem: schema
standard: "ISO/IEC/IEEE 42010:2022"
tags: [schema, architecture]
timestamp: 2026-06-30T00:00:00Z
---

# Architecture Description — Schema

**Standard:** ISO/IEC/IEEE 42010:2022

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| SRS Reference | `docs/schema/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The schema feature generates structured data (JSON-LD) for Organization, WebSite, BreadcrumbList, and article/page content. It provides a subtype management UI in the Block Editor.

### 1.2 Explicitly Excluded

- Schema for non-WordPress content
- VideoObject, Recipe, Event, or other specialized schema that requires plugin-specific data
- JSON-LD validation or testing
- Google Rich Results testing

### 1.3 Feature Slug

`schema`

---

## 2. Architecture Views

### 2.1 Context View (AV-01)

| External System | Direction | Description |
|---|---|---|
| WordPress Core (`wp_head`) | ← adds | Outputs 4 JSON-LD script blocks |
| WordPress Block Editor | → provides | Schema modal for subtype configuration |
| WordPress REST API | ← responds → receives | 3 endpoints for settings + preview |
| GG_Optimizer_DB | → writes ← reads | `schema_post_type_defaults`, `schema_org_settings` |
| Post Meta (`_gg_optimizer_schema_subtype`) | → writes ← reads | Per-post subtype override |

### 2.2 Component View (AV-02)

```
┌───────────────────────────────────────────────────────────────┐
│                      includes/schema.php                      │
│                                                               │
│  ┌─────────────────────────────┐  ┌─────────────────────────┐│
│  │ JSON-LD Output              │  │ Helper Functions        ││
│  │                             │  │                         ││
│  │ - gg_optimizer_schema_      │  │ - get_organization_     ││
│  │   output_organization_      │  │   content_sources()     ││
│  │   json_ld()                 │  │ - extract_sameas_urls() ││
│  │ - gg_optimizer_schema_      │  │ - extract_logo_url()    ││
│  │   output_website_json_ld()  │  │ - get_description()     ││
│  │ - gg_optimizer_schema_      │  │ - get_image()           ││
│  │   output_breadcrumb_json_ld │  │ - build_graph()         ││ <-- Content entity only
│  │ - gg_optimizer_schema_      │  │ - build_json_ld()       ││ <-- WebPage wrapper + content entity pair
│  │   output_json_ld() (master) │  │ - get_breadcrumb_items()││
│  └─────────────────────────────┘  │   graph()               ││
│                                    └─────────────────────────┘│
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│                  includes/schema-settings.php                 │
│                                                               │
│  - gg_optimizer_schema_get_type_map() (9 categories, 174)    │
│  - gg_optimizer_schema_get_all_subtypes()                    │
│  - gg_optimizer_schema_get_subtype_parent()                  │
│  - gg_optimizer_schema_get_default_subtype()                 │
│  - gg_optimizer_schema_get_resolved_subtype()                │
│  - REST: /schema-global-settings (GET + POST)                │
│  - REST: /schema-preview (GET)                               │
│  - Filters: gg_optimizer_schema_article_type                 │
│  - Filters: gg_optimizer_schema_organization_type            │
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│               src/schema-settings-sidebar.js                  │
│                                                               │
│  - PluginDocumentSettingPanel "Schema"                       │
│  - Modal: Organization Type dropdown                         │
│  - Modal: Per-post-type subtype rows (category + subtype)    │
│  - Modal: Current Document override (category + subtype)     │
│  - Modal: JSON-LD preview with clipboard copy                │
│  - apiFetch to /schema-global-settings, /schema-preview      │
└───────────────────────────────────────────────────────────────┘
```

### 2.3 Runtime Interaction View (AV-03)

**Flow A: Page load — JSON-LD output**

```
  Browser              wp_head                  schema.php
    │                     │                         │
    │— GET /any-page      │                         │
    │                     │— action ───────────────>│
    │                     │                         │— output_organization_json_ld()
    │                     │                         │  → read org_type from DB
    │                     │                         │  → extract sameAs from content
    │                     │                         │  → extract logo URL
    │                     │                         │— output_website_json_ld()
    │                     │                         │— IF singular:
    │                     │                         │   → build_graph()
    │                     │                         │   → build_breadcrumb_graph()
    │<─── HTML + 4 LD ───│<────────────────────────│
```

**Flow B: Administrator configures schema settings**

```
  JS Sidebar              REST API                      DB
    │                         │                          │
    │— Open modal             │                          │
    │— apiFetch(GET           │                          │
    │  /schema-global-settings)─>                        │
    │                         │— DB::get() twice ───────>│
    │<──── defaults + map ───│<────────────────────────│
    │                         │                          │
    │— Also fetch            │                          │
    │  /schema-preview       │                          │
    │<──── JSON-LD preview ──│                          │
    │                         │                          │
    │— Edit default subtypes │                          │
    │— Click Update           │                          │
    │— POST /schema-global-  │                          │
    │  settings ──────────────>                         │
    │                         │— Validate subtypes      │
    │                         │— DB::set() twice ──────>│
    │<─── {success: true} ───│                          │
```

---

## 3. Architecture Decision Records

### AD-01: Full Type Map (Not Curated)

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-09, FR-10 |
| Decision | Include all 174 subtypes across 9 categories rather than a curated subset. |
| Rationale | Prevents the need for updates as schema.org evolves. Users can select any valid subtype. |
| Consequences | Larger UI (scrollable subtype lists). Validation must accept any known subtype. |

### AD-02: @graph Wrapper for Multiple Nodes

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-05 |
| Decision | When multiple graph nodes (Organization + WebSite + WebPage wrapper + content entity + BreadcrumbList) are present, wrap them in a `@graph` array. Nodes are linked via `mainEntity` / `mainEntityOfPage` references. |
| Alternatives | Separate `<script>` tags per node |
| Rationale | Single JSON-LD block is cleaner and aligns with Google's preferred structure. |
| Consequences | All output is consolidated into one `script[type="application/ld+json"]` block. |

### AD-03: SameAs from Social Links Blocks

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-07 |
| Decision | Extract `sameAs` URLs from `core/social-links` blocks that have `sameAsSchema` attribute enabled. |
| Alternatives | Manual URL input, social media URL setting |
| Rationale | Reuses existing block content. No additional UI needed. Content editors control which links appear. |
| Consequences | Only works with block themes or content using the Social Links block. |

### AD-04: Three-Layer Subtype Resolution

| Field | Value |
|---|---|
| ID | AD-04 |
| Linked Requirements | FR-12, FR-13, FR-14 |
| Decision | Priority: per-post meta → global post-type default → hardcoded fallback (post→BlogPosting, page→WebPage, others→Article). |
| Alternatives | Two-layer (no global defaults), single global default for all |
| Rationale | Maximizes flexibility. Content editors override individual posts. Administrators set global defaults. Hardcoded fallback ensures every post has a valid subtype. |
| Consequences | Three DB/meta lookups per post in the worst case. |

---

## 4. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01–FR-04, FR-20–FR-22 |
| AV-02 Component View | FR-09–FR-11, FR-20–FR-22 |
| AV-03 Flow A | FR-01–FR-08 |
| AV-03 Flow B | FR-15–FR-17, FR-20, FR-21, FR-29 |
| AD-01 Full Type Map | FR-09, FR-10 |
| AD-02 @graph Wrapper | FR-05 |
| AD-03 SameAs from Blocks | FR-07 |
| AD-04 Three-Layer Resolution | FR-12, FR-13, FR-14 |

# Related

- Upstream requirements: [srs.md](srs.md) — software requirements specification
- Downstream developer reference: [developer-documentation.md](developer-documentation.md) — API reference and integration guide
