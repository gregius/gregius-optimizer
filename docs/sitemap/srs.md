---
type: Specification
title: "Software Requirements Specification (SRS) — Sitemap"
description: "Define precisely what the sitemap control feature must do — not how to build it. Every requirement is necessary, unambiguous, singular, and verifiable."
subsystem: sitemap
standard: "ISO/IEC/IEEE 29148:2018"
tags: [sitemap, specification]
timestamp: 2026-06-30T00:00:00Z
---

# Software Requirements Specification (SRS) — Sitemap

**Standard:** ISO/IEC/IEEE 29148:2018 — Software Requirements Specification

> **Purpose:** Define precisely what the sitemap control feature must do — not how to build it. Every requirement is necessary, unambiguous, singular, and verifiable.

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | sitemap |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| StRS Reference | N/A — direct product brief |
| SyRS Reference | N/A — SRS produced directly from product brief |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The sitemap feature gives site administrators granular control over which content types, taxonomies, author pages, and individual documents appear in the WordPress core sitemap (`wp-sitemap.xml`). Administrators toggle inclusion per post type, per taxonomy, per author, and per post without writing code or editing theme files.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/sitemap`

**Repository path:** `public/wp-content/plugins/gregius-optimizer/includes/sitemap.php`, `src/indexing-meta-sidebar.js`

The sitemap feature extends the WordPress core sitemaps system introduced in WordPress 5.5. It does not replace or duplicate the core sitemap engine. All toggles are persisted via the shared `GG_Optimizer_DB` key-value table and merged with WordPress filter defaults at runtime.

### 1.3 System Overview

#### 1.3.1 System Context

The sitemap feature lives inside the Gregius Optimizer plugin. It intercepts six WordPress sitemap filters (`wp_sitemaps_add_provider`, `wp_sitemaps_post_types`, `wp_sitemaps_posts_query_args`, `wp_sitemaps_taxonomies`, `wp_sitemaps_taxonomies_query_args`, `wp_sitemaps_users_query_args`, `wp_sitemaps_enabled`) and one filter of its own (`gg_optimizer_sitemap_disabled_taxonomies`). A REST endpoint (`/gg-optimizer/v1/sitemap-settings`) provides read/write access to the stored configuration. The Gutenberg sidebar panel ("Sitemap") exposes the modal UI.

#### 1.3.2 System Functions Summary

- Enable/disable the entire sitemap feature via a master gate
- Include/exclude post types from the post sitemap
- Include/exclude taxonomies from the taxonomy sitemap
- Enable/disable the author (users) sitemap provider
- Exclude individual users from the author sitemap
- Exclude individual terms from taxonomy sitemaps (via filter)
- Hide individual documents from search engines (noindex + sitemap exclusion) via post meta
- Persist all toggles to the shared settings database and merge with WordPress filter defaults
- Site-specific default: exclude `category` and `post_tag` taxonomies from sitemap

#### 1.3.3 User Characteristics

| User Class | Technical Level | Primary Interaction Point |
|---|---|---|
| Content Editor | Low-technical | Block Editor sidebar — "Sitemap" panel → "Current Document" |
| Site Administrator | Technical | Block Editor sidebar — "Sitemap" panel → "Settings" modal |

### 1.4 Definitions

| Term | Definition |
|---|---|
| Sitemap | An XML file (`wp-sitemap.xml`) listing URLs on a site that search engines should crawl |
| Provider | A WordPress sitemap subsystem responsible for generating sitemap entries for a specific content type (posts, taxonomies, users) |
| Master gate | A global enable/disable switch that overrides all sitemap output |
| Noindex | An HTML meta tag and/or X-Robots-Tag HTTP header instructing search engines not to index a page |

### 1.5 Abbreviations and Acronyms

| Abbreviation | Expansion |
|---|---|
| CPT | Custom Post Type |
| REST | Representational State Transfer |
| WPCS | WordPress Coding Standards |

---

## 2. References

- ISO/IEC/IEEE 29148:2018 — Systems and software engineering: Life cycle processes — Requirements engineering
- WordPress Sitemaps: https://developer.wordpress.org/advanced-administration/wordpress/sitemaps/
- WordPress REST API Handbook: https://developer.wordpress.org/rest-api/
- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/

---

## 3. Software Requirements

> **Requirement notation:**
> - `MUST` — mandatory. Implementation is incomplete without this.
> - `SHOULD` — strongly desired. Deviation requires documented justification.
> - `MAY` — optional enhancement.
>
> Each requirement has a unique ID: `FR-[NN]`.

---

### 3.1 Functional Requirements

#### 3.1.1 Sitemap Master Gate

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST provide a master gate that can enable or disable the entire sitemap output via a WordPress filter (`gg_optimizer_sitemap_enabled`). | Must |
| FR-02 | When the master gate is disabled, ALL WordPress sitemap output MUST be suppressed regardless of individual toggle states. | Must |

#### 3.1.2 Post Type Sitemap Control

| ID | Requirement | Priority |
|---|---|---|
| FR-03 | The software MUST allow site administrators to include or exclude each public post type from the post sitemap independently. | Must |
| FR-04 | Excluded post types MUST NOT appear in the `wp_sitemaps_post_types` filter output. | Must |
| FR-05 | When a post type is excluded, the posts sitemap query for that post type MUST return zero results (via `post__in = array(0)`). | Must |
| FR-06 | Every individual post with `_gg_optimizer_hide_from_search` meta set to `true` MUST be excluded from the posts sitemap query regardless of its post type toggle. | Must |

#### 3.1.3 Taxonomy Sitemap Control

| ID | Requirement | Priority |
|---|---|---|
| FR-07 | The software MUST allow site administrators to include or exclude each public taxonomy from the taxonomy sitemap independently. | Must |
| FR-08 | Excluded taxonomies MUST NOT appear in the `wp_sitemaps_taxonomies` filter output. | Must |
| FR-09 | By default, `category` and `post_tag` MUST be excluded from the taxonomy sitemap (site-specific configuration). | Must |

#### 3.1.4 Author (Users) Sitemap Control

| ID | Requirement | Priority |
|---|---|---|
| FR-10 | The software MUST allow site administrators to enable or disable the author (users) sitemap provider. | Must |
| FR-11 | When disabled, the users provider MUST be removed from the sitemap providers list. | Must |
| FR-12 | When enabled, individual users MUST be includable/excludable from the author sitemap via a toggle list. | Must |

#### 3.1.5 Per-Document Exclusion

| ID | Requirement | Priority |
|---|---|---|
| FR-13 | The software MUST register the `_gg_optimizer_hide_from_search` boolean meta field on all public post types. | Must |
| FR-14 | When `_gg_optimizer_hide_from_search` is enabled on a post, the software MUST output a `noindex` robots meta tag on that post's frontend page. | Must |
| FR-15 | Posts with `_gg_optimizer_hide_from_search` enabled MUST be excluded from all XML sitemap providers (posts, taxonomies, users). | Must |

#### 3.1.6 REST API

| ID | Requirement | Priority |
|---|---|---|
| FR-16 | The software MUST expose a REST endpoint `GET /gg-optimizer/v1/sitemap-settings` that returns the current saved settings, all available public post types, all available public taxonomies, all users with `publish_posts` capability, and the sitemap URL. | Must |
| FR-17 | The software MUST expose a REST endpoint `POST /gg-optimizer/v1/sitemap-settings` that accepts a settings object and persists it. | Must |
| FR-18 | The POST endpoint MUST validate post type slugs against the list of registered public post types, taxonomy slugs against registered taxonomies, and user IDs against existing users before saving. | Must |
| FR-19 | Posting an empty settings object MUST reset all saved overrides and revert to filter defaults. | Must |

#### 3.1.7 Filter/DB Merge

| ID | Requirement | Priority |
|---|---|---|
| FR-20 | The software MUST merge WordPress filter defaults (via `gg_optimizer_sitemap_disabled_taxonomies`, `gg_optimizer_sitemap_excluded_post_types`, etc.) with user-saved overrides from the database. User overrides MUST take precedence over filter defaults. | Must |

#### 3.1.8 Gutenberg Sidebar Panel

| ID | Requirement | Priority |
|---|---|---|
| FR-21 | The software MUST register a "Sitemap" panel in the Block Editor sidebar via `PluginDocumentSettingPanel`. | Must |
| FR-22 | The panel MUST contain a brief description with a link to Google's sitemap documentation. | Must |
| FR-23 | The panel MUST display a "Hide page from search engines" toggle for the current post's `_gg_optimizer_hide_from_search` meta. | Must |
| FR-24 | The panel MUST have a "Settings" button that opens a modal with the full sitemap configuration. | Must |

#### 3.1.9 Modal Configuration

| ID | Requirement | Priority |
|---|---|---|
| FR-25 | The modal MUST display the site's sitemap URL (`/wp-sitemap.xml`) as a clickable link. | Must |
| FR-26 | The modal MUST display a "Post types" section where each public post type can be toggled on/off for sitemap inclusion. | Must |
| FR-27 | The modal MUST display a "Taxonomies" section where each public taxonomy can be toggled on/off. | Must |
| FR-28 | The modal MUST display an "Author sitemap" section with a master toggle and per-user toggles that appear when the master toggle is enabled. | Must |
| FR-29 | The modal MUST have an "Update" button that saves changes and a "Reset to defaults" button that reverts all overrides. | Must |

---

### 3.2 Usability Requirements

| ID | Requirement | Priority |
|---|---|---|
| UX-01 | The "Sitemap" panel MUST be discoverable immediately when editing any public post type. | Must |
| UX-02 | The modal MUST display a loading spinner while fetching settings. | Must |
| UX-03 | Success and error notices MUST be dismissible. | Must |
| UX-04 | The "Reset to defaults" button MUST be disabled when there are no saved overrides to reset. | Must |

---

### 3.3 Performance Requirements

| ID | Requirement | Measurement Method |
|---|---|---|
| PERF-01 | The sitemap filter callbacks MUST NOT perform more than 2 database queries per page load. | Query Monitor |
| PERF-02 | The REST endpoint MUST limit user queries to only authors with `publish_posts` capability. | Manual inspection |
| PERF-03 | The bundled JS for the sitemap panel MUST be part of the shared `editor.js` bundle (no separate asset). | `@wordpress/scripts` bundle analysis |

---

### 3.4 Software Interfaces

#### 3.4.1 WordPress Core Hooks

| Hook | Type | Behavioral Contract | Condition |
|---|---|---|---|
| `wp_sitemaps_enabled` | filter | Master gate — if disabled, suppresses all sitemap output | On every request where sitemap is requested |
| `wp_sitemaps_add_provider` | filter | Removes the "users" provider when disabled | On sitemap initialization |
| `wp_sitemaps_post_types` | filter | Excludes disabled post types from the sitemap post type list | On sitemap initialization for the posts provider |
| `wp_sitemaps_posts_query_args` | filter | Excludes posts with `hide_from_search` meta and excluded post types from the posts query | On each post type sitemap page query |
| `wp_sitemaps_taxonomies` | filter | Excludes disabled taxonomies from the sitemap taxonomy list | On sitemap initialization for the taxonomy provider |
| `wp_sitemaps_taxonomies_query_args` | filter | Forces `hide_empty=true`, orders by name, and excludes specified term IDs | On each taxonomy sitemap page query |
| `wp_sitemaps_users_query_args` | filter | Excludes specified user IDs from the users provider | On users sitemap page query |

#### 3.4.2 REST API Contracts

| Endpoint | Method | Permission | Request Shape | Response Shape |
|---|---|---|---|---|
| `/gg-optimizer/v1/sitemap-settings` | GET | `manage_options` | — | `{ settings: {}, post_types: [], taxonomies: [], users: [], sitemap_url: string }` |
| `/gg-optimizer/v1/sitemap-settings` | POST | `manage_options` | `{ settings: { post_types: {}, taxonomies: {}, authors: bool, excluded_users: [] } }` | `{ success: bool }` |

#### 3.4.3 Database Interface

| Data Item | Storage Location | Sanitization on Write | Escaping on Output | Retention Policy |
|---|---|---|---|---|
| Sitemap settings (JSON) | `{$prefix}gg_optimizer_settings` table, key `sitemap_settings` | `wp_json_encode` after validation | `json_decode` | Until explicitly reset or plugin uninstall |
| Post hide-from-search flag | `post_meta` (`_gg_optimizer_hide_from_search`) | `register_post_meta` with proper type/schema | `bool` cast | Until post deleted or meta deleted |

---

### 3.5 Software Operations

1. Administrator opens the Block Editor on any public post type.
2. The "Sitemap" panel renders in the sidebar showing the per-document hide-from-search toggle.
3. Administrator clicks "Settings" to open the modal.
4. The modal fetches current settings via `GET /gg-optimizer/v1/sitemap-settings`.
5. Administrator toggles post types, taxonomies, and author settings.
6. Administrator clicks "Update"; the modal saves via `POST /gg-optimizer/v1/sitemap-settings`.
7. On the next sitemap XML request, the WordPress sitemap engine runs through the filter chain.
8. Each filter reads the saved settings from `GG_Optimizer_DB::get()` and merges with defaults, then returns the modified provider/query/taxonomy list.

---

### 3.6 Software Modes and States

| State | Entry Trigger | Exit Trigger | Behavior in This State |
|---|---|---|---|
| Default (no overrides) | Fresh plugin install | User saves any override | Filter defaults control sitemap output. Category/post_tag excluded. |
| Overrides active | User saves settings via modal | User clicks "Reset to defaults" or saves empty `{}` | User toggles override filter defaults for each toggled item. |
| Master gate disabled | `gg_optimizer_sitemap_enabled` returns false | Filter returns true | All sitemap XML output suppressed. |

---

### 3.7 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | All REST endpoints MUST check `current_user_can('manage_options')` before returning or saving data. |
| SEC-02 | All post type, taxonomy, and user ID inputs MUST be validated against the actual registered lists before saving. |
| SEC-03 | All output to HTML MUST be escaped using `esc_html`, `esc_attr`, or `esc_url` at the point of output. |
| SEC-04 | All database writes MUST use `$wpdb->prepare()` or parameterized methods (`$wpdb->replace`, `$wpdb->delete`). |
| SEC-05 | Direct file access MUST be blocked in all PHP files via `defined( 'ABSPATH' ) || exit;`. |

---

### 3.8 Data Management

| Data Item | Storage Location | Sanitization on Write | Escaping on Output | Retention Policy |
|---|---|---|---|---|
| `sitemap_settings` (JSON blob) | `{$prefix}gg_optimizer_settings` | Validated field-by-field in REST callback, then `wp_json_encode` | `json_decode` — used internally by PHP, not rendered directly | Until reset or uninstall |

---

### 3.9 Compliance and Policies

| Policy | Requirement |
|---|---|
| WordPress Coding Standards | All PHP MUST pass PHPCS with the `WordPress` ruleset. All JS MUST pass ESLint with `@wordpress/eslint-plugin`. |
| WCAG 2.1 AA | All interactive elements in the sidebar panel and modal MUST be keyboard-navigable and use standard ARIA roles. |
| WordPress.org plugin directory | Submission planned: YES — pass `wp plugin check --format=json` with zero blocking issues. |

---

## 4. Verification and Acceptance

| Requirement Group | Verification Method |
|---|---|
| Functional requirements | Manual integration tests + visual inspection of sitemap XML output |
| REST API | wp-cli REST check + manual POST/GET round-trip |
| Performance | Query Monitor inspection |
| Security | PHPCS static analysis |
| WCAG compliance | Keyboard navigation check |
| Plugin directory compliance | `wp plugin check --format=json` |

---

## 5. Traceability

| SRS Requirement ID | Source | Verification References |
|---|---|---|
| FR-01–FR-02 | Product brief — master gate | Integration test: toggle gate, verify sitemap XML 200/404 |
| FR-03–FR-06 | Product brief — post type control | Integration test: disable a post type, verify XML omits it |
| FR-07–FR-09 | Product brief — taxonomy control | Integration test: disable a taxonomy, verify XML omits it |
| FR-10–FR-12 | Product brief — author sitemap | Integration test: toggle author provider on/off, verify XML |
| FR-13–FR-15 | Product brief — per-document exclusion | Integration test: set meta, verify `<meta name="robots">` appears and XML omits post |
| FR-16–FR-19 | Product brief — REST API | wp-cli: `wp rest get /gg-optimizer/v1/sitemap-settings` |
| FR-20 | Architecture decision — filter/DB merge | PHPUnit: verify filter default + DB override merge order |
| FR-21–FR-29 | Product brief — UI | Visual inspection of panel and modal |

---

## 6. Appendices

### 6.1 Assumptions and Dependencies

- WordPress 5.5+ (sitemaps introduced in 5.5; project requires 6.9+)
- `GG_Optimizer_DB` class exists and the settings table is created on plugin activation
- The custom meta field helper class (`GG_Optimizer_Custom_Meta_Field`) is available
- The `_gg_optimizer_hide_from_search` meta field is registered on all public post types via `register_post_meta()` with `show_in_rest`. No `custom-fields` support declaration is required.

### 6.2 Open Issues (TBD List)

| ID | Section | Issue | Owner | Target Date |
|---|---|---|---|---|
| TBD-01 | 3.1.5 | Whether `_gg_optimizer_hide_from_search` should also affect `wp_robots` filter | Engineering | Pre-release |

### 6.3 Decisions Log

| Date | Decision | Rationale | Decided By |
|---|---|---|---|
| 2026-06-06 | User toggles always save the full settings map (not incremental diffs) | Simplifies the data model; avoids merge complexity for concurrent edits | Engineering |
| 2026-06-06 | Filter defaults take precedence over missing DB keys (user override only when explicitly toggled) | Clear semantics: no toggle = filter default; explicit toggle = saved override wins | Engineering |
| 2026-06-06 | Category and post_tag excluded by default via site-specific filter | Reduces low-value sitemap noise for most content sites | Product |

# Related

- Upstream specification for this subsystem: [architecture.md](architecture.md) — architecture views and design decisions
