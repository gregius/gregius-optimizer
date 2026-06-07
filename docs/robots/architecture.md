# Architecture Description — Robots

**Standard:** ISO/IEC/IEEE 42010:2022 — Architecture Description

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | robots |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| SRS Reference | `docs/robots/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The robots feature manages two outputs: the `robots.txt` file served at the site root and the per-page `<meta name="robots">` tag. It operates entirely within WordPress PHP filters and actions — no server configuration changes are made.

### 1.2 Explicitly Excluded

- Physical `robots.txt` file creation or deletion
- `.htaccess` or web server configuration
- Crawl delay or rate limiting directives
- Sitemap management (handled by the sitemap feature)
- Third-party SEO plugin compatibility

### 1.3 Feature Slug

`robots`

---

## 2. Stakeholders and Concerns

| Stakeholder | Concern | View Reference |
|---|---|---|
| Site Administrator | Control what crawlers can access | Context View |
| Content Editor | Individual page visibility | N/A — controlled by sitemap feature's hide_from_search |
| Developer | Extend or override robot directives | Component View, ADRs |
| SEO Specialist | Ensure correct crawl directives for search + AI bots | AD-02 |

---

## 3. Architecture Views

### 3.1 Context View (AV-01)

| External System | Direction | Protocol | Description |
|---|---|---|---|
| WordPress Core (`robots_txt`) | ← intercepts | PHP filter | Replaces robots.txt content when override exists or defaults defined |
| WordPress Core (`wp_head`) | ← adds | PHP action | Outputs `<meta name="robots">` on every page |
| WordPress REST API | ← responds → receives | HTTP JSON | `GET/POST /gg-optimizer/v1/robots-txt` |
| GG_Optimizer_DB | → writes ← reads | PHP methods | Key `robots_txt_content` in shared settings table |
| Post Meta (`_gg_optimizer_hide_from_search`) | ← reads | `get_post_meta` | Checks per-post noindex flag |

### 3.2 Component View (AV-02)

```
┌──────────────────────────────────────────────────────────────────┐
│                     includes/robots.php                          │
│                                                                  │
│  ┌─────────────────────────────────────────┐                     │
│  │  gg_optimizer_is_hidden_from_search()   │                     │
│  │  - reads _gg_optimizer_hide_from_search │                     │
│  │  - normalizes truthy values             │                     │
│  └─────────────────────────────────────────┘                     │
│                                                                  │
│  ┌─────────────────────────────────────────┐                     │
│  │  gg_optimizer_output_robots_meta()      │                     │
│  │  - hooked to wp_head                    │                     │
│  │  - outputs <meta name="robots">         │                     │
│  │  - filter: gg_optimizer_robots_meta_*   │                     │
│  └─────────────────────────────────────────┘                     │
│                                                                  │
│  ┌─────────────────────────────────────────┐                     │
│  │  gg_optimizer_get_default_robots_txt()  │                     │
│  │  - heredoc with all bot directives      │                     │
│  │  - includes sitemap URL                 │                     │
│  └─────────────────────────────────────────┘                     │
│                                                                  │
│  ┌─────────────────────────────────────────┐                     │
│  │  gg_optimizer_output_robots_txt()       │                     │
│  │  - hooked to robots_txt filter          │                     │
│  │  - merge: DB override > defaults        │                     │
│  │  - filter: gg_optimizer_robots_txt_*    │                     │
│  └─────────────────────────────────────────┘                     │
│                                                                  │
│  ┌─────────────────────────────────────────┐                     │
│  │  REST /gg-optimizer/v1/robots-txt      │                     │
│  │  - GET: returns content + metadata      │                     │
│  │  - POST: saves/resets override          │                     │
│  └─────────────────────────────────────────┘                     │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                   src/robots-txt-sidebar.js                      │
│                                                                  │
│  - PluginDocumentSettingPanel "Robots"                           │
│  - Modal with TextareaControl (dynamic rows)                    │
│  - Update / Reset to defaults buttons                            │
│  - apiFetch to /gg-optimizer/v1/robots-txt                      │
└──────────────────────────────────────────────────────────────────┘
```

### 3.3 Runtime Interaction View (AV-03)

**Flow A: Administrator edits robots.txt**

```
  JS Sidebar               REST API                  DB
    │                         │                       │
    │— User opens modal       │                       │
    │— apiFetch(GET) ────────>│                       │
    │                         │— DB::get() ──────────>│
    │<──── content ──────────│<─── string ───────────│
    │— Edit textarea         │                       │
    │— Click "Update"        │                       │
    │— apiFetch(POST) ───────>│                       │
    │   {content: "..."}      │                       │
    │                         │— sanitize_textarea_  │
    │                         │  field + DB::set() ──>│
    │<─── {success: true} ───│                       │
    │— Show success notice   │                       │
```

**Flow B: Robots.txt request (frontend)**

```
  Browser                 WordPress               robots.php
    │                         │                       │
    │— GET /robots.txt        │                       │
    │                         │— robots_txt filter ──>│
    │                         │                       │— blog_public check
    │                         │                       │— DB::get('robots_txt_content')
    │                         │                       │— override exists? return it
    │                         │                       │— else: get_default_robots_txt()
    │<─── plain text ─────────│<──────────────────────│
```

**Flow C: Page load — meta robots output**

```
  Browser                 WordPress               robots.php
    │                         │                       │
    │— GET /any-page          │                       │
    │                         │— wp_head action ─────>│
    │                         │                       │— robots_meta_enabled?
    │                         │                       │— is_search() → noindex
    │                         │                       │— is_404() → noindex
    │                         │                       │— is_singular() + hidden → noindex
    │                         │                       │— else: index, follow
    │<─── HTML with meta ────│<──────────────────────│
    │     robots tag          │                       │
```

---

## 4. Architecture Decision Records

### AD-01: DB Override with Filter Default Fallback

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-02, FR-03 |
| Decision | The `robots_txt` filter callback checks the DB first. If an override exists, it is returned verbatim. If not, the built-in default (or filter-provided default) is returned. |
| Rationale | Simple precedence: user override > plugin defaults > WordPress original. No merge complexity. |
| Consequences | Clearing the override (POST empty string) causes the next request to return defaults. One DB read per robots.txt request. |

### AD-02: Explicit Bot Classification in Defaults

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-04 |
| Decision | The default robots.txt explicitly classifies bots into three groups: traditional search, conversational/generative answer engines, and AI model trainers — each with distinct `User-agent` entries allowing full access. |
| Alternatives | Single `User-agent: *` with `Allow: /` for all bots |
| Rationale | Provides transparency about which AI bots the site permits. Administrators can modify the defaults via the UI or `gg_optimizer_robots_txt_enabled` filter if they wish to restrict specific bot classes. |
| Consequences | Longer robots.txt file. Bot classification must be maintained as new AI bots emerge. |

### AD-03: Legacy `wp_head` Approach for Meta Robots

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-07, FR-08 |
| Decision | Output robots meta via `wp_head` action using the legacy `<meta name="robots">` tag, not the newer `wp_robots` filter. |
| Alternatives | Use `wp_robots` filter (WordPress 5.7+) |
| Rationale | The legacy approach predates the core feature and provides a consistent noindex path for all page types. The `gg_optimizer_robots_meta_content` filter allows full customization. Both approaches can coexist since WordPress merges them. |
| Consequences | Duplicate robots meta tags if core `wp_robots` is also active (benign — search engines respect the most restrictive). |

---

## 5. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01, FR-07, FR-13 |
| AV-02 Component View | FR-13, FR-16 |
| AV-03 Flow A | FR-13, FR-14, FR-15 |
| AV-03 Flow B | FR-01, FR-02, FR-03, FR-04, FR-05, FR-06 |
| AV-03 Flow C | FR-07, FR-08, FR-09, FR-10, FR-11, FR-12 |
| AD-01 DB Override + Fallback | FR-02, FR-03 |
| AD-02 Bot Classification | FR-04 |
| AD-03 Legacy Meta Approach | FR-07 |

---

## 6. Constraints and Risks

| Constraint | Source | Impact |
|---|---|---|
| `robots_txt` filter only fires on `/robots.txt` requests | WordPress core | No impact |
| Meta robots output must not break `wp_robots` filter if active | WordPress core | Both can coexist safely |
| Plugin must support multisite | Internal policy | Settings are per-sub-site via `$wpdb->prefix` |

| Risk | Likelihood | Mitigation |
|---|---|---|
| New AI bots not covered in defaults | Medium | Documented filter extension point |
| Very large custom robots.txt in textarea | Low | Textarea has dynamic rows; no hard limit |

---

## 7. Architecture Readiness Checklist

| Criteria | Status |
|---|---|
| Context view defined | Complete |
| Component view defined | Complete |
| Runtime views defined | Complete |
| All major decisions have ADRs | Complete |
| Coverage mapping exists | Complete |
| Constraints documented | Complete |
| Risks documented | Complete |

---

## 8. Handoff Note

Downstream skills: `wp-plugin-development` (hooks + REST), `wp-block-development` (JS component), `wp-coding-standards`.
