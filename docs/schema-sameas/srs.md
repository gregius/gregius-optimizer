# Software Requirements Specification (SRS) — Schema SameAs

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema-sameas |
| Version | 0.1 — Draft |
| Date | 2026-06-07 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The Schema SameAs feature enables content editors to mark a `core/social-links` block for inclusion in the Organization structured data. When enabled, the plugin extracts social profile URLs from the block and includes them as the `sameAs` array in the Organization JSON-LD node.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/schema-sameas`

**Repository path:** `assets/src/scripts/sameas-schema-editor.js`, `includes/schema.php`

The feature operates by reading `core/social-links` block attributes and `core/social-link` inner block URLs from the post content at render time. It uses two complementary extraction strategies: a parsed block tree walker and a regex-based serialized content scanner. The walker handles pattern blocks and synced patterns with cycle detection.

### 1.3 System Functions Summary

- Register `sameAsSchema` boolean attribute on `core/social-links` blocks
- Provide toggle control in the block inspector
- Extract social profile URLs from social-link inner blocks on page render
- Support extraction from parsed blocks and serialized content
- Detect and prevent infinite loops from pattern/synced block cycles
- De-duplicate collected URLs
- Include the resolved URLs in the Organization JSON-LD `sameAs` array

---

## 2. Software Requirements

### 2.1 Functional Requirements

#### 2.1.1 Block Attribute Registration

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST register a boolean `sameAsSchema` attribute (default `false`) on `core/social-links` blocks via the `blocks.registerBlockType` filter. | Must |
| FR-02 | The software MUST add a `ToggleControl` to the `InspectorControls` of any selected `core/social-links` block, with label text explaining the toggle enables Organization sameAs structured data, and a link to schema.org/sameAs. | Must |

#### 2.1.2 URL Extraction

| ID | Requirement | Priority |
|---|---|---|
| FR-03 | On singular page render, the software MUST extract social profile URLs from `core/social-links` blocks where `attrs.sameAsSchema` is truthy, using both a parsed block tree walker and a regex-based serialized content scanner. | Must |
| FR-04 | The parsed block walker MUST recurse into `core/pattern` and `core/block` (synced pattern) inner blocks to find social-links blocks at any nesting depth. | Must |
| FR-05 | The parsed block walker MUST track visited pattern slugs and synced block refs to prevent infinite recursion from circular pattern references. | Must |
| FR-06 | The regex-based scanner MUST match `wp:social-links` block comments with `sameAsSchema` attribute in serialized content and extract URLs from `wp:social-link` inner block comments and `<a href="...">` elements. | Must |
| FR-07 | The collected URLs MUST be de-duplicated with `array_unique` and re-indexed with `array_values`. | Must |
| FR-08 | Each extracted URL MUST be passed through `esc_url_raw`. | Must |

#### 2.1.3 JSON-LD Output

| ID | Requirement | Priority |
|---|---|---|
| FR-09 | The resolved URLs MUST be included as the `sameAs` array in the Organization JSON-LD node output via `wp_head`. | Must |
| FR-10 | If no URLs are found, the `sameAs` property MUST be omitted from the Organization node entirely. | Must |

#### 2.1.4 Filter Control

| ID | Requirement | Priority |
|---|---|---|
| FR-11 | The Organization JSON-LD output (including the `sameAs` array) MUST be controllable via the `gg_optimizer_schema_output_organization` filter. | Must |
| FR-12 | The content sources searched for sameAs URLs MUST be customizable via the `gg_optimizer_schema_get_organization_content_sources` filter. | Must |

### 2.2 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | All extracted URLs MUST be passed through `esc_url_raw` before inclusion in JSON-LD output. |
| SEC-02 | JSON-LD output MUST use `wp_json_encode` with `JSON_UNESCAPED_SLASHES` and `JSON_UNESCAPED_UNICODE` flags. |

### 2.3 Software Interfaces

#### 2.3.1 WordPress Hooks

| Hook | Type | Behavioral Contract |
|---|---|---|
| `blocks.registerBlockType` | filter | Injects `sameAsSchema` attribute into `core/social-links` settings |
| `editor.BlockEdit` | filter | Wraps `core/social-links` block edit with sameAs toggle control |
| `wp_head` | action | Outputs Organization JSON-LD with `sameAs` array via schema output pipeline |
| `gg_optimizer_schema_output_organization` | filter | Override to disable Organization schema output (default `true`) |
| `gg_optimizer_schema_get_organization_content_sources` | filter | Customize content sources searched for sameAs URLs |

#### 2.3.2 Block Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `sameAsSchema` | `boolean` | `false` | When `true`, social link URLs are included in Organization `sameAs` array |

#### 2.3.3 PHP Functions

| Function | File | Purpose |
|---|---|---|
| `gg_optimizer_schema_extract_sameas_urls` | `schema.php` | Entry point — merge results from parsed block walker and serialized scanner |
| `gg_optimizer_schema_find_sameas_in_blocks` | `schema.php` | Recursive parsed block walker with pattern/synced block cycle detection |
| `gg_optimizer_schema_extract_sameas_urls_from_serialized_content` | `schema.php` | Regex-based scanner for serialized social-links block comments |

### 2.4 Performance Considerations

| ID | Consideration |
|---|---|
| PERF-01 | Extraction only runs on singular page views where schema output is generated. |
| PERF-02 | Pattern/synced block cycle detection prevents infinite loops on recursive content structures. |
| PERF-03 | The serialized content scanner uses a single-pass regex, minimizing overhead. |

---

## 3. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-02 | Feature brief — block editor UI |
| FR-03–FR-08 | Feature brief — URL extraction logic |
| FR-09–FR-10 | Feature brief — JSON-LD output requirements |
| FR-11–FR-12 | Feature brief — filter gates |
| SEC-01–SEC-02 | WordPress security best practices |
