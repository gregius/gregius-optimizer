# Software Requirements Specification (SRS) — Schema Logo

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema-logo |
| Version | 0.1 — Draft |
| Date | 2026-06-07 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The Schema Logo feature enables content editors to mark a `core/site-logo` block for inclusion in the Organization structured data. When enabled, the plugin extracts the logo image URL from the block and includes it as the `logo` property in the Organization JSON-LD node.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/schema-logo`

**Repository path:** `assets/src/scripts/logo-schema-editor.js`, `includes/schema.php`

The feature operates by reading `core/site-logo` block attributes from the post content at render time. It does not store logo data in post meta or custom tables. The URL is resolved from the block's `url` attribute first, with a fallback to the theme's `custom_logo` setting.

### 1.3 System Functions Summary

- Register `organizationLogoSchema` boolean attribute on `core/site-logo` blocks
- Provide toggle control in the block inspector
- Extract logo image URL from blocks on page render
- Include the resolved URL in the Organization JSON-LD `logo` property
- Support recursive extraction through nested blocks
- Fall back to the theme custom logo when no block URL is found

---

## 2. Software Requirements

### 2.1 Functional Requirements

#### 2.1.1 Block Attribute Registration

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST register a boolean `organizationLogoSchema` attribute (default `false`) on `core/site-logo` blocks via the `blocks.registerBlockType` filter. | Must |
| FR-02 | The software MUST add a `ToggleControl` to the `InspectorControls` of any selected `core/site-logo` block, with label text explaining the toggle enables Organization logo structured data, and a link to schema.org/logo. | Must |

#### 2.1.2 Logo URL Extraction

| ID | Requirement | Priority |
|---|---|---|
| FR-03 | On singular page render, the software MUST parse the post content into blocks and search for `core/site-logo` blocks where `attrs.organizationLogoSchema` is truthy. | Must |
| FR-04 | The software MUST prefer the block's `attrs.url` value when present, falling back to the theme's `custom_logo` setting resolved via `wp_get_attachment_image_url`. | Must |
| FR-05 | The software MUST recurse into `innerBlocks` to find site-logo blocks at any nesting depth. | Must |
| FR-06 | The software MUST return the first matching logo URL found, halting further search. | Must |

#### 2.1.3 JSON-LD Output

| ID | Requirement | Priority |
|---|---|---|
| FR-07 | The resolved logo URL MUST be included as the `logo` property in the Organization JSON-LD node output via `wp_head`. | Must |
| FR-08 | The URL MUST be passed through `esc_url_raw` before output. | Must |

#### 2.1.4 Filter Control

| ID | Requirement | Priority |
|---|---|---|
| FR-09 | The Organization JSON-LD output (including the `logo` property) MUST be controllable via the `gg_optimizer_schema_output_organization` filter. | Must |

### 2.2 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | The logo URL MUST be passed through `esc_url_raw` before inclusion in JSON-LD output. |
| SEC-02 | JSON-LD output MUST use `wp_json_encode` with `JSON_UNESCAPED_SLASHES` and `JSON_UNESCAPED_UNICODE` flags. |

### 2.3 Software Interfaces

#### 2.3.1 WordPress Hooks

| Hook | Type | Behavioral Contract |
|---|---|---|
| `blocks.registerBlockType` | filter | Injects `organizationLogoSchema` attribute into `core/site-logo` settings |
| `editor.BlockEdit` | filter | Wraps `core/site-logo` block edit with logo schema toggle control |
| `wp_head` | action | Outputs Organization JSON-LD with `logo` property via schema output pipeline |
| `gg_optimizer_schema_output_organization` | filter | Override to disable Organization schema output (default `true`) |
| `gg_optimizer_schema_image` | filter | Override the resolved image URL for schema output |

#### 2.3.2 Block Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `organizationLogoSchema` | `boolean` | `false` | When `true`, the site-logo block's image URL is included in Organization JSON-LD |

#### 2.3.3 PHP Functions

| Function | File | Purpose |
|---|---|---|
| `gg_optimizer_schema_extract_logo_url` | `schema.php` | Entry point — iterate content sources and delegate to recursive walker |
| `gg_optimizer_schema_find_logo_in_blocks` | `schema.php` | Recursive walker — find `core/site-logo` block with `organizationLogoSchema` and return URL |

### 2.4 Performance Considerations

| ID | Consideration |
|---|---|
| PERF-01 | Logo extraction only runs on singular page views where schema output is generated. |
| PERF-02 | Extraction halts on first match — no unnecessary block tree traversal. |

---

## 3. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-02 | Feature brief — block editor UI |
| FR-03–FR-06 | Feature brief — logo URL extraction logic |
| FR-07–FR-08 | Feature brief — JSON-LD output requirements |
| FR-09 | Feature brief — filter gate |
| SEC-01–SEC-02 | WordPress security best practices |
