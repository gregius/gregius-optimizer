# Software Requirements Specification (SRS) — Schema

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The schema feature enables site administrators and content editors to control structured data markup (JSON-LD) on their WordPress site. It provides a full schema.org type hierarchy (9 categories, 174 subtypes), per-post-type default assignment, per-post override, organization type selection, and a live JSON-LD preview.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/schema`

**Repository path:** `includes/schema.php`, `includes/schema-settings.php`, `src/schema-settings-sidebar.js`

The feature generates JSON-LD structured data for Organization, Website, BreadcrumbList, and article/page subtypes. It does not generate schema for third-party plugins, e-commerce products, or events unless the content type is assigned a matching subtype.

### 1.3 System Functions Summary

- Output Organization JSON-LD with configurable subtype (Organization → subtypes)
- Output WebSite JSON-LD with search URL
- Output BreadcrumbList JSON-LD for singular content
- Output article/page JSON-LD for singular content with resolved subtype
- Full schema.org type map (9 categories, 174 subtypes)
- Global post-type default subtype assignment
- Per-post schema subtype override via post meta
- Organization type selection dropdown
- Live JSON-LD preview in the modal with clipboard copy
- REST endpoints for reading/writing global settings and fetching preview

---

## 2. Software Requirements

### 2.1 Functional Requirements

#### 2.1.1 JSON-LD Output

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST output Organization JSON-LD (`Organization` or a selected subtype) on all pages via `wp_head`. | Must |
| FR-02 | The software MUST output WebSite JSON-LD with `url`, `name`, and `search` action on all pages via `wp_head`. | Must |
| FR-03 | The software MUST output BreadcrumbList JSON-LD on singular posts/pages. | Must |
| FR-04 | The software MUST output article/page JSON-LD on singular posts/pages using the resolved schema.org subtype. | Must |
| FR-05 | The JSON-LD MUST use the `@graph` wrapper when multiple graph nodes are present on a page. | Must |
| FR-06 | The Organization JSON-LD MUST support a configurable subtype (e.g., `Corporation`, `LocalBusiness`, `NGO`). | Must |
| FR-07 | The Organization JSON-LD MUST include `sameAs` URLs extracted from `core/social-links` blocks with `sameAsSchema` enabled. | Must |
| FR-08 | The Organization JSON-LD MUST include a `logo` property using the first image found in `core/site-logo` or `core/image` blocks in the organization content source. | Must |

#### 2.1.2 Schema.org Type Map

| ID | Requirement | Priority |
|---|---|---|
| FR-09 | The software MUST provide a type map of 9 categories: Article, WebPage, CreativeWork, Event, Organization, Person, Place, Product, Review. | Must |
| FR-10 | The type map MUST include all 174 subtypes across the 9 categories. | Must |
| FR-11 | The software MUST provide functions to get all subtypes flat, get the parent category for a subtype, and get the hardcoded default for a post type. | Must |

#### 2.1.3 Subtype Resolution Chain

| ID | Requirement | Priority |
|---|---|---|
| FR-12 | The subtype for a post MUST be resolved using a priority chain: per-post meta → global post-type default → hardcoded fallback. | Must |
| FR-13 | The resolved subtype MUST override the schema.org `@type` in the JSON-LD output via the `gg_optimizer_schema_article_type` filter. | Must |
| FR-14 | The resolved organization type MUST override the Organization `@type` via the `gg_optimizer_schema_organization_type` filter. | Must |

#### 2.1.4 Global Settings

| ID | Requirement | Priority |
|---|---|---|
| FR-15 | The software MUST allow setting a default schema.org subtype for each public content post type. | Must |
| FR-16 | Non-content post types (attachment, customize_changeset, nav_menu_item, wp_block, wp_font_face, wp_font_family, wp_global_styles, wp_navigation, wp_template, wp_template_part) MUST be excluded from the global defaults UI. | Must |
| FR-17 | The software MUST allow selecting an Organization subtype for the site-wide Organization node. | Must |

#### 2.1.5 Per-Post Override

| ID | Requirement | Priority |
|---|---|---|
| FR-18 | The software MUST register the `_gg_optimizer_schema_subtype` meta field on all public post types. | Must |
| FR-19 | Content editors MUST be able to override the schema subtype for the current post via a dropdown in the Schema modal. | Must |

#### 2.1.6 REST API

| ID | Requirement | Priority |
|---|---|---|
| FR-20 | The software MUST expose `GET /gg-optimizer/v1/schema-global-settings` returning post-type defaults, organization settings, and the full type map. | Must |
| FR-21 | The software MUST expose `POST /gg-optimizer/v1/schema-global-settings` to save post-type defaults and organization settings with subtype validation. | Must |
| FR-22 | The software MUST expose `GET /gg-optimizer/v1/schema-preview?post_id=N` returning the full JSON-LD graph for a given post. | Must |

#### 2.1.7 Gutenberg Sidebar Panel

| ID | Requirement | Priority |
|---|---|---|
| FR-23 | The software MUST register a "Schema" panel in the Block Editor sidebar via `PluginDocumentSettingPanel`. | Must |
| FR-24 | The panel MUST contain a description with a link to schema.org. | Must |
| FR-25 | The panel MUST have a "Settings" button that opens a modal. | Must |

#### 2.1.8 Modal Configuration

| ID | Requirement | Priority |
|---|---|---|
| FR-26 | The modal MUST display an "Organization Type" dropdown with all Organization subtypes. | Must |
| FR-27 | The modal MUST display per-post-type rows with category and subtype dropdowns for setting global defaults. | Must |
| FR-28 | The modal MUST display a "Current Document" section with category and subtype dropdowns for the current post. | Must |
| FR-29 | The modal MUST display a live JSON-LD preview with a copy-to-clipboard button. | Must |
| FR-30 | The modal MUST have "Update" and "Reset to defaults" buttons. | Must |

### 2.2 Software Interfaces

#### 2.2.1 WordPress Core Hooks

| Hook | Type | Behavioral Contract |
|---|---|---|
| `wp_head` | action | Output Organization, WebSite, BreadcrumbList, and article/page JSON-LD |
| `gg_optimizer_schema_article_type` | filter | Override the resolved `@type` for article/page schema |
| `gg_optimizer_schema_organization_type` | filter | Override the Organization `@type` |

#### 2.2.2 REST API Contracts

| Endpoint | Method | Permission | Response Shape |
|---|---|---|---|
| `/gg-optimizer/v1/schema-global-settings` | GET | `manage_options` | `{ post_type_defaults: {}, schema_org_settings: {}, type_map: [] }` |
| `/gg-optimizer/v1/schema-global-settings` | POST | `manage_options` | `{ success: bool }` |
| `/gg-optimizer/v1/schema-preview` | GET | `edit_post` | Full JSON-LD graph object |

#### 2.2.3 Database Interface

| Data Item | Storage Key | Description |
|---|---|---|
| `schema_post_type_defaults` | `{$prefix}gg_optimizer_settings` | JSON map of `post_type => subtype` |
| `schema_org_settings` | `{$prefix}gg_optimizer_settings` | JSON with `org_type` field |

### 2.3 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | All REST endpoints MUST check `current_user_can('manage_options')` or `current_user_can('edit_post', $post_id)`. |
| SEC-02 | Subtype values MUST be validated against the known type map before saving. |
| SEC-03 | JSON-LD MUST be escaped for safe HTML output. |

---

## 3. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-08 | Product brief — JSON-LD output |
| FR-09–FR-11 | Product brief — type map |
| FR-12–FR-14 | Product brief — subtype resolution |
| FR-15–FR-17 | Product brief — global settings |
| FR-18–FR-19 | Product brief — per-post override |
| FR-20–FR-22 | Product brief — REST API |
| FR-23–FR-30 | Product brief — UI |
