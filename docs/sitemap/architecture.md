# Architecture Description — Sitemap

**Standard:** ISO/IEC/IEEE 42010:2022 — Architecture Description

> **Purpose:** Document the system architecture, key decisions, constraints, and risks for the sitemap control feature.

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | sitemap |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| SRS Reference | `docs/sitemap/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The sitemap feature extends the WordPress core sitemaps engine (5.5+) to provide per-post-type, per-taxonomy, per-author, and per-document inclusion control. It operates entirely within the existing WordPress sitemap architecture — it does not generate, parse, or cache XML files.

### 1.2 Explicitly Excluded

- XML sitemap generation (delegated to WordPress core)
- Sitemap indexing or splitting (delegated to WordPress core)
- Third-party sitemap plugin compatibility
- Sitemap cache invalidation
- Visual sitemap preview/rendering

### 1.3 Feature Slug

`sitemap` — used consistently across all documentation artifacts.

---

## 2. Stakeholders and Concerns

| Stakeholder | Concern | View Reference |
|---|---|---|
| Site Administrator | Control what appears in the sitemap without touching code | Context View, Component View |
| Content Editor | Exclude individual pages from search results | Component View |
| Developer | Understand hook chain, filter merge order, and extension points | Component View, ADRs |
| Plugin Reviewer | Verify compliance with WordPress.org plugin directory rules | ADRs |

---

## 3. Architecture Views

### 3.1 Context View (AV-01)

The sitemap feature sits inside the `gregius-optimizer` plugin. It connects to:

| External System | Direction | Protocol | Description |
|---|---|---|---|
| WordPress Core Sitemaps | ← intercepts | PHP filters | Hooks into 6 WP core sitemap filters to modify provider/post-type/taxonomy/user/query behavior |
| WordPress Block Editor | → provides | JavaScript (React) | Renders "Sitemap" sidebar panel with modal configuration |
| WordPress REST API | ← receives → responds | HTTP JSON | `GET/POST /gg-optimizer/v1/sitemap-settings` — read/write configuration |
| GG_Optimizer_DB | → writes ← reads | PHP methods | Shared key-value table (`{$prefix}gg_optimizer_settings`) for persisting settings |
| WordPress Post Meta | → writes ← reads | `register_post_meta` | Per-post `_gg_optimizer_hide_from_search` boolean flag |

### 3.2 Component View (AV-02)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        gregius-optimizer plugin                     │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                     includes/sitemap.php                      │   │
│  │                                                              │   │
│  │  ┌────────────────────────────┐  ┌─────────────────────────┐ │   │
│  │  │  Per-post meta field      │  │  Sitemap filter         │ │   │
│  │  │  registration (_hide_from │  │  functions (8)          │ │   │
│  │  │  _search)                 │  │                         │ │   │
│  │  └────────────────────────────┘  │  - gg_optimizer_       │ │   │
│  │                                  │    optimize_sitemap_   │ │   │
│  │  ┌────────────────────────────┐  │    providers           │ │   │
│  │  │  REST endpoint            │  │  - gg_optimizer_filter │ │   │
│  │  │  /sitemap-settings        │  │    _posts_sitemap_...  │ │   │
│  │  │  (GET + POST)             │  │  - gg_optimizer_filter │ │   │
│  │  └────────────────────────────┘  │    _taxonomy_sitemap_ │ │   │
│  │                                  │  - gg_optimizer_filter │ │   │
│  │                                  │    _users_sitemap_...  │ │   │
│  │                                  │  - gg_optimizer_sitemap│ │   │
│  │                                  │    _enabled_gate       │ │   │
│  │                                  │  - helper functions    │ │   │
│  │                                  │    _disabled_taxo-     │ │   │
│  │                                  │    nomies / _excluded_ │ │   │
│  │                                  │    post_types / etc.   │ │   │
│  │                                  └─────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                   src/indexing-meta-sidebar.js                │   │
│  │                                                              │   │
│  │  - PluginDocumentSettingPanel "Sitemap"                      │   │
│  │  - Modal with post type / taxonomy / author toggles          │   │
│  │  - Per-post "Hide from search engines" toggle (moved to       │   │
│  │    Robots panel as of v1.1.0)                                 │   │
│  │  - apiFetch to /gg-optimizer/v1/sitemap-settings             │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                includes/class-gg-optimizer-db.php             │   │
│  │                                                              │   │
│  │  - GG_Optimizer_DB::get('sitemap_settings', '{}')            │   │
│  │  - GG_Optimizer_DB::set('sitemap_settings', $json)           │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

### 3.3 Runtime Interaction View (AV-03)

**Flow A: Administrator configures sitemap settings**

```
Editor JS                          REST API                    DB
   │                                  │                         │
   │— User clicks "Settings"          │                         │
   │— Modal opens                     │                         │
   │— apiFetch(GET /sitemap-settings)─│                         │
   │                                  │— GG_Optimizer_DB::get()─│
   │                                  │←───── JSON ─────────────│
   │←──── settings + lists ──────────│                         │
   │— User toggles post types, etc.   │                         │
   │— User clicks "Update"            │                         │
   │— apiFetch(POST /sitemap-settings)│                         │
   │   {settings: {...}}               │                         │
   │                                  │— validate fields        │
   │                                  │— GG_Optimizer_DB::set()─│
   │←──── {success: true} ────────────│                         │
   │— Show success notice             │                         │
```

**Flow B: Sitemap XML request (frontend)**

```
WP Sitemap Engine                sitemap.php filters          DB
   │                                  │                         │
   │— wp_sitemaps_enabled             │                         │
   │─────────────────────────────────>│— gg_optimizer_sitemap_  │
   │←──── bool ───────────────────────│   enabled_gate()        │
   │                                  │                         │
   │— wp_sitemaps_add_provider        │                         │
   │─────────────────────────────────>│— if 'users' disabled    │
   │←──── provider│false ────────────│   return false           │
   │                                  │                         │
   │— wp_sitemaps_post_types          │                         │
   │─────────────────────────────────>│— gg_optimizer_sitemap_  │
   │←──── filtered array ────────────│   excluded_post_types()  │
   │                                  │   → GG_Optimizer_DB::get│
   │                                  │                         │
   │— wp_sitemaps_posts_query_args    │                         │
   │─────────────────────────────────>│— add meta_query for     │
   │←──── modified args ─────────────│   hide_from_search       │
   │                                  │                         │
   │— wp_sitemaps_taxonomies          │                         │
   │─────────────────────────────────>│— gg_optimizer_sitemap_  │
   │←──── filtered array ────────────│   disabled_taxonomies()  │
   │                                  │   → GG_Optimizer_DB::get│
   │                                  │                         │
   │— wp_sitemaps_taxonomies_query_args                          │
   │─────────────────────────────────>│— set hide_empty, order  │
   │←──── modified args ─────────────│   → exclude terms        │
   │                                  │                         │
   │— wp_sitemaps_users_query_args    │                         │
   │─────────────────────────────────>│— gg_optimizer_sitemap_  │
   │←──── args with exclude ────────│   excluded_users()        │
   │                                  │                         │
```

### 3.4 Deployment/Environment View (AV-04)

The sitemap feature has no external service dependencies and no special deployment requirements beyond the plugin itself. It operates within the standard WordPress single-site and multisite environments. On multisite, each sub-site has its own settings (stored via `$wpdb->prefix`).

---

## 4. Architecture Decision Records

### AD-01: Filter Defaults + DB Override Merge

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-20 |
| Decision | User-saved DB overrides take precedence over WordPress filter defaults. When a toggle has never been explicitly saved, the filter default applies. |
| Alternatives | DB-only storage (no filter defaults), incremental diffs only, filter always wins |
| Rationale | Allows WordPress child themes and mu-plugins to set baseline defaults via `add_filter()` while giving site administrators the final say through the UI. Non-toggle items gracefully fall through to whatever the filter chain provides. |
| Consequences | Each filter function must call `GG_Optimizer_DB::get()` and merge; performance impact is 1 DB query per page-load for the sitemap feature regardless of the number of toggles (single JSON blob). |

### AD-02: Full-Map Save (Not Incremental)

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-17, FR-19 |
| Decision | The POST endpoint always saves the complete settings object (`{ post_types: {...}, taxonomies: {...}, authors: bool, excluded_users: [...] }`). |
| Alternatives | Incremental patches (save only changed toggles), per-toggle individual endpoints |
| Rationale | Simplifies conflict resolution (last-write-wins is correct for the full object). Makes "Reset to defaults" trivial (POST `{}`). Reduces REST surface area to a single endpoint pair. |
| Consequences | The JS client must send all current toggle states on every save. Payload size is proportional to the number of post types + taxonomies (typically <50 keys). |

### AD-03: JSON Blob Storage

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-16, FR-17 |
| Decision | Store all sitemap settings as a single JSON blob under the `sitemap_settings` key in the shared settings table. |
| Alternatives | Individual rows per toggle, `wp_options` autoload, option group |
| Rationale | Shared table pattern is already established by `GG_Optimizer_DB` for the whole plugin. JSON blob avoids schema migrations when adding new toggle types. One `get()`/`set()` call suffices for the entire feature. |
| Consequences | Read-modify-write pattern for updates. Concurrent writes from two admin sessions could lose one session's changes (acceptable risk for a low-frequency admin feature). |

### AD-04: Single REST Endpoint

| Field | Value |
|---|---|
| ID | AD-04 |
| Linked Requirements | FR-16, FR-17, FR-18 |
| Decision | One REST route (`/gg-optimizer/v1/sitemap-settings`) serves both GET and POST. GET returns settings + available options. POST accepts and validates the full settings object. |
| Alternatives | Separate endpoints for post types, taxonomies, and users |
| Rationale | Fewer REST endpoints to maintain and document. Atomic save for the full configuration. Consistent with other Gregius Optimizer features (schema, robots). |
| Consequences | The GET response payload includes the full list of post types, taxonomies, and users — potentially large on sites with many CPTs/taxonomies. Acceptable because the endpoint is only called on-demand (when the modal opens). |

### AD-05: Site-Specific Taxonomy Exclusions

| Field | Value |
|---|---|
| ID | AD-05 |
| Linked Requirements | FR-09 |
| Decision | Exclude `category` and `post_tag` from the taxonomy sitemap by default via a `gg_optimizer_sitemap_disabled_taxonomies` filter added at the end of `sitemap.php`. |
| Alternatives | Include all taxonomies by default, configuration constant |
| Rationale | Most content sites have sparse taxonomy content. Including category and tag archives adds low-value URLs to the sitemap. The filter can be removed or modified by the site owner. |
| Consequences | Site owners who want category/tag URLs in their sitemap must remove this filter or re-enable them through the UI. |

---

## 5. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01, FR-03, FR-07, FR-10, FR-13, FR-16 |
| AV-02 Component View | FR-16, FR-17, FR-21 |
| AV-03 Runtime View (Flow A) | FR-16, FR-17, FR-18, FR-19 |
| AV-03 Runtime View (Flow B) | FR-01, FR-02, FR-04, FR-06, FR-08, FR-11, FR-15 |
| AD-01 Filter/DB Merge | FR-20 |
| AD-02 Full-Map Save | FR-17, FR-19 |
| AD-03 JSON Blob Storage | FR-16, FR-17 |
| AD-04 Single REST Endpoint | FR-16, FR-17, FR-18 |
| AD-05 Site-Specific Exclusions | FR-09 |

---

## 6. Constraints and Risks

### 6.1 Constraints

| Constraint | Source | Impact |
|---|---|---|
| WordPress sitemap architecture is non-extensible (no hook for per-term exclusion in providers) | WordPress core | Term exclusion must be done via query args filter; only works for providers that support query args |
| `wp_sitemaps_posts_query_args` fires per post type, not once | WordPress core | The meta query for `hide_from_search` is added on every post type sitemap query even when no documents are hidden |
| Plugin must support multisite | Internal policy | Settings are per-sub-site via `$wpdb->prefix` |
| REST endpoint limited to `manage_options` | Security | Only administrators can configure sitemap settings |

### 6.2 Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Large number of users (>1000) causes slow GET response | Low for most sites | Medium | User query limited to `publish_posts` capability; consider pagination if needed |
| Concurrent admin sessions overwrite each other's settings | Low | Low | Last-write-wins is acceptable for this infrequent operation |
| Taxonomy slug collision with post type slug in endpoint validation | Very low | Low | Validation is scoped to the correct registered list (taxonomies vs post types) |

---

## 7. Architecture Readiness Checklist

| Criteria | Status | Notes |
|---|---|---|
| Context view defined | Complete | AV-01 |
| Component view defined | Complete | AV-02 |
| Runtime interaction view defined | Complete | AV-03 (Flow A + Flow B) |
| Deployment view relevant | N/A | No external services or special deployment |
| All major decisions have ADRs | Complete | AD-01 through AD-05 |
| Coverage mapping exists | Complete | Section 5 |
| Constraints documented | Complete | Section 6.1 |
| Risks documented | Complete | Section 6.2 |
| ADRs linked to requirement IDs | Complete | AD-01→FR-20, etc. |

---

## 8. Handoff Note

The architecture is ready for implementation. Downstream skills:

| Implementation Area | Skill |
|---|---|
| REST endpoint | `wp-rest-api` |
| WordPress hooks and filters | `wp-plugin-development` |
| React sidebar panel | `wp-block-development` (JS component registration) |
| Post meta registration | `wp-plugin-development` (meta via `register_post_meta`) |
| Code quality | `wp-coding-standards` |
