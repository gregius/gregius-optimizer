# Architecture Description — LLMs

**Standard:** ISO/IEC/IEEE 42010:2022

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | llms |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| SRS Reference | `docs/llms/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The LLMs feature serves an `/llms.txt` file that provides LLMs with structured information about the website. It implements the llms.txt proposal for standardizing AI agent access to website content and architecture.

### 1.2 Explicitly Excluded

- LLM inference or model interaction
- Training data preparation or content extraction beyond llms.txt
- Robots.txt or crawl directives (handled by the robots feature)
- Sitemap generation (handled by the sitemap feature)

### 1.3 Feature Slug

`llms`

---

## 2. Architecture Views

### 2.1 Context View (AV-01)

| External System | Direction | Description |
|---|---|---|
| LLM Agents / AI Crawlers | → consumes | Read `/llms.txt` for site context and key documents |
| WordPress Core (`template_redirect`) | → intercept | Serve `/llms.txt` before WordPress renders the page |
| WordPress Block Editor | → provides | LLMs modal for context editing and per-post toggles |
| WordPress REST API | → responds | 3 endpoints for override + preview |
| GG_Optimizer_DB | → writes ← reads | `llms_override` context text |
| Post Meta (2 keys) | → writes ← reads | `_gg_optimizer_include_in_llms`, `_gg_optimizer_llms_description` |

### 2.2 Component View (AV-02)

```
┌───────────────────────────────────────────────────────────────┐
│                       includes/llms.php                       │
│                                                               │
│  - Meta field registration (2 fields)                         │
│  - template_redirect: /llms.txt intercept                     │
│  - gg_optimizer_llms_normalize_text()                         │
│  - gg_optimizer_get_llms_context()        (auto-generated)    │
│  - gg_optimizer_get_llms_key_documents()  (from posts)        │
│  - gg_optimizer_output_llms_txt()         (master output)     │
│  - gg_optimizer_output_llms_head_link()   (wp_head link tag)  │
│  - REST: /llms-override (GET + POST)                          │
│  - REST: /llms-preview (POST)                                 │
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│                    src/llms-settings.js                        │
│                                                               │
│  - PluginDocumentSettingPanel "LLMs"                          │
│  - Modal: description + llmstxt.org link                      │
│  - Modal: Global Context textarea (15 rows)                   │
│  - Modal: "Include current document" toggle                   │
│  - Modal: Description textarea (conditional on toggle)        │
│  - Modal: Live Preview (formatted <pre> block)                │
│  - 500ms debounce auto-preview                                 │
│  - Update / Reset to defaults buttons                         │
│  - apiFetch to /llms-override, /llms-preview                  │
└───────────────────────────────────────────────────────────────┘
```

### 2.3 Runtime Interaction View (AV-03)

**Flow A: AI agent requests /llms.txt**

```
  LLM Agent           template_redirect          DB             WordPress
    │                     │                      │                 │
    │— GET /llms.txt      │                      │                 │
    │                     │— intercept           │                 │
    │                     │— check llms_enabled  │                 │
    │                     │— DB::get(            │                 │
    │                     │  'llms_override') ───>                 │
    │                     │<── context or '' ─────│                 │
    │                     │                      │                 │
    │                     │— IF override: use it │                 │
    │                     │— ELSE: get_llms_     │                 │
    │                     │  context()            │                 │
    │                     │  → site title, desc   │                 │
    │                     │  → homepage summary   │                 │
    │                     │  → hardcoded defaults  │                 │
    │                     │                      │                 │
    │                     │— get_llms_key_       │                 │
    │                     │  documents()          │                 │
    │                     │  → WP_Query          │                 │
    │                     │  → filter by meta    │                 │
    │                     │<── posts ────────────│                 │
    │                     │                      │                 │
    │<── plaintext ──────│                      │                 │
```

**Flow B: Editor configures LLMs settings**

```
  JS Sidebar               REST API                  DB
    │                         │                       │
    │— Open modal             │                       │
    │— GET /llms-override ────>                       │
    │                         │— DB::get(             │
    │                         │  'llms_override') ───>│
    │<── {llms_override, ─────│<──────────────────────│
    │     llms_context}       │                       │
    │                         │                       │
    │— Edit context text      │                       │
    │— Toggle current post on │                       │
    │                         │                       │
    │— POST /llms-preview ────>                       │
    │  (with context +        │                       │
    │   unsaved toggles +     │                       │
    │   unsaved descs)        │                       │
    │<── llms_txt ───────────│                       │
    │                         │                       │
    │— Click Update           │                       │
    │— POST /llms-override ───>                       │
    │  {llms_override: "..."} │                       │
    │                         │— Strip ## Key Docs   │
    │                         │— DB::set() ─────────>│
    │<── {success: true} ────│                       │
```

---

## 3. Architecture Decision Records

### AD-01: Safety Strip on Save

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-16 |
| Decision | Automatically strip everything from "## Key Documents" onward when saving the context override. |
| Rationale | The Key Documents section is auto-generated from post meta. If a user accidentally pastes a full llms.txt including Key Documents into the context field, the safety strip prevents duplicate or conflicting document listings. |
| Consequences | Users cannot customize the Key Documents section structure. The "Sitemap:" line is also auto-appended after Key Documents. |

### AD-02: Auto-Generated Context with Defaults

| ID | AD-02 |
| Linked Requirements | FR-04–FR-07 |
| Decision | Include hardcoded "## Core Architecture" and "## Key Specifications" sections in the auto-generated context. |
| Alternatives | Empty auto-generated context |
| Rationale | Provides immediate value out of the box. Site administrators can customize or replace the defaults by saving an override. |
| Consequences | The defaults are generic architectural descriptions. Administrators should customize them for their specific site. |

### AD-03: Per-Post Toggle via Boolean Meta

| ID | AD-03 |
| Linked Requirements | FR-17, FR-18 |
| Decision | Use a boolean `_gg_optimizer_include_in_llms` meta key for per-post inclusion rather than an opt-out list or taxonomy. |
| Alternatives | Post type–based auto-inclusion, taxonomy term assignment |
| Rationale | Simplest UX: each post has a clear toggle. No taxonomy maintenance. Works with any public post type. Default is `false` (opt-in). |
| Consequences | Administrators must enable each post individually. No bulk operations for llms.txt inclusion. |

### AD-04: Preview via REST with Runtime Overrides

| ID | AD-04 |
| Linked Requirements | FR-30 |
| Decision | The preview endpoint accepts runtime overrides for context, toggles, and descriptions, rendering the full llms.txt without saving. |
| Alternatives | Save-then-preview cycle, client-side reconstruction |
| Rationale | Immediate WYSIWYG experience. The 500ms debounce ensures preview updates are batched. |
| Consequences | Preview endpoint must accept three separate override parameters, increasing request payload size. |

---

## 4. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01–FR-03, FR-19–FR-21 |
| AV-02 Component View | FR-04–FR-18 |
| AV-03 Flow A | FR-01–FR-12 |
| AV-03 Flow B | FR-13–FR-16, FR-19–FR-32 |
| AD-01 Safety Strip | FR-16 |
| AD-02 Default Context | FR-04–FR-07 |
| AD-03 Boolean Toggle | FR-17, FR-18 |
| AD-04 REST Preview | FR-30 |
