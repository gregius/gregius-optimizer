# Software Requirements Specification (SRS) — Robots

**Standard:** ISO/IEC/IEEE 29148:2018 — Software Requirements Specification

> **Purpose:** Define precisely what the robots control feature must do.

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | robots |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The robots feature gives site administrators full control over their site's `robots.txt` file and per-page robots meta directives. Administrators can override the default robots.txt content, reset to built-in defaults, and control the robots meta tag policy — all from the Block Editor sidebar without accessing server files.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/robots`

**Repository path:** `public/wp-content/plugins/gregius-optimizer/includes/robots.php`, `src/robots-txt-sidebar.js`

The robots feature extends the WordPress `robots_txt` filter and provides its own `wp_head` output for robots meta. It does not replace or bypass WordPress core search engine visibility settings. The robots.txt override is stored in the shared `GG_Optimizer_DB` key-value table.

### 1.3 System Overview

#### 1.3.1 System Functions Summary

- Override the site's `robots.txt` content with custom directives
- Reset robots.txt to built-in defaults (or filter-provided defaults)
- Output per-page `<meta name="robots">` tags with configurable content
- Apply noindex to search results pages and 404 pages automatically
- Apply noindex to individual posts flagged with `hide_from_search`
- Default robots.txt includes rules for search bots, AI answer engines, and AI model trainers
- REST endpoint for reading and writing the robots.txt override

#### 1.3.2 User Characteristics

| User Class | Technical Level | Primary Interaction Point |
|---|---|---|
| Content Editor | Low-technical | Block Editor sidebar — "Robots" panel (meta only) |
| Site Administrator | Technical | Block Editor sidebar — "Robots" panel → modal |

---

## 2. References

- ISO/IEC/IEEE 29148:2018
- Google robots.txt documentation: https://developers.google.com/search/docs/crawling-indexing/robots/intro
- robots.txt specification: https://www.robotstxt.org/
- WordPress REST API Handbook

---

## 3. Software Requirements

### 3.1 Functional Requirements

#### 3.1.1 Robots.txt Override

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST intercept the WordPress `robots_txt` filter to provide custom robots.txt directives. | Must |
| FR-02 | The software MUST check for a stored override in the database before returning robots.txt output. If an override exists, it MUST be returned instead of the default. | Must |
| FR-03 | When no override is stored, the software MUST return the default robots.txt directives defined by `gg_optimizer_get_default_robots_txt()`. | Must |
| FR-04 | The default robots.txt MUST include rules for traditional search bots (`Googlebot`, `Bingbot`), AI answer engines (`OAI-SearchBot`, `ChatGPT-User`, `PerplexityBot`, `ClaudeBot`, `Claude-Web`), and AI model trainers (`Google-Extended`, `GPTBot`, `Applebot-Extended`, `Cohere-ai`). | Must |
| FR-05 | The software MUST respect WordPress's `blog_public` setting: if the site is not public, the original WordPress robots.txt output MUST be returned without modification. | Must |
| FR-06 | The software MUST provide a filter (`gg_optimizer_robots_txt_enabled`) to disable the robots.txt override entirely. | Must |

#### 3.1.2 Robots Meta Tag

| ID | Requirement | Priority |
|---|---|---|
| FR-07 | The software MUST output a `<meta name="robots">` tag in `wp_head` on all frontend pages. | Must |
| FR-08 | The default meta content MUST be `index, follow` on all pages. | Must |
| FR-09 | Search results pages and 404 pages MUST receive a `noindex, follow` directive. | Must |
| FR-10 | Singular posts with `_gg_optimizer_hide_from_search` meta enabled MUST receive a `noindex, follow` directive. | Must |
| FR-11 | The software MUST provide a filter (`gg_optimizer_robots_meta_enabled`) to disable robots meta output entirely. | Must |
| FR-12 | The software MUST provide a filter (`gg_optimizer_robots_meta_content`) to override the robots meta content string. | Must |

#### 3.1.3 REST API

| ID | Requirement | Priority |
|---|---|---|
| FR-13 | The software MUST expose a REST endpoint `GET /gg-optimizer/v1/robots-txt` that returns the current robots.txt content, whether a custom override exists, and the site's robots.txt URL. | Must |
| FR-14 | The software MUST expose a REST endpoint `POST /gg-optimizer/v1/robots-txt` that accepts a `content` string and persists it as the robots.txt override. | Must |
| FR-15 | POSTing an empty `content` string MUST reset the override; the next GET will return the default content with `has_custom: false`. | Must |

#### 3.1.4 Gutenberg Sidebar Panel

| ID | Requirement | Priority |
|---|---|---|
| FR-16 | The software MUST register a "Robots" panel in the Block Editor sidebar via `PluginDocumentSettingPanel`. | Must |
| FR-17 | The panel MUST contain a brief description with a link to Google's robots.txt documentation. | Must |
| FR-18 | The panel MUST have a "Settings" button that opens a modal with a textarea for editing robots.txt content. | Must |

#### 3.1.5 Modal Editor

| ID | Requirement | Priority |
|---|---|---|
| FR-19 | The modal MUST display the site's robots.txt URL as a clickable link. | Must |
| FR-20 | The modal MUST contain a textarea for editing robots.txt content with dynamic row count (minimum 20 rows, grows with content). | Must |
| FR-21 | The modal MUST have an "Update" button that saves the content and a "Reset to defaults" button that clears the override. | Must |
| FR-22 | The "Reset to defaults" button MUST be disabled when no custom override exists. | Must |

### 3.2 Performance Requirements

| ID | Requirement |
|---|---|
| PERF-01 | The robots.txt filter MUST perform at most 1 database query per request. |

### 3.3 Software Interfaces

#### 3.3.1 WordPress Core Hooks

| Hook | Type | Behavioral Contract | Condition |
|---|---|---|---|
| `robots_txt` | filter | Override robots.txt content with custom or default directives | On `/robots.txt` request |
| `wp_head` | action | Output `<meta name="robots">` tag | On every frontend page load |
| `wp_robots` | filter | N/A — feature uses legacy `wp_head` approach | — |

#### 3.3.2 REST API Contracts

| Endpoint | Method | Permission | Request Shape | Response Shape |
|---|---|---|---|---|
| `/gg-optimizer/v1/robots-txt` | GET | `manage_options` | — | `{ content: string, has_custom: bool, robots_txt_url: string }` |
| `/gg-optimizer/v1/robots-txt` | POST | `manage_options` | `{ content: string }` | `{ success: bool }` |

#### 3.3.3 Database Interface

| Data Item | Storage | Sanitization on Write |
|---|---|---|
| `robots_txt_content` | `{$prefix}gg_optimizer_settings` key-value | `sanitize_textarea_field` |

### 3.4 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | All REST endpoints MUST check `current_user_can('manage_options')`. |
| SEC-02 | All output MUST be escaped using `esc_attr`/`esc_url` at the point of output. |
| SEC-03 | Direct file access MUST be blocked via `defined( 'ABSPATH' ) || exit;`. |

---

## 4. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-06 | Product brief — robots.txt override |
| FR-07–FR-12 | Product brief — robots meta |
| FR-13–FR-15 | Product brief — REST API |
| FR-16–FR-22 | Product brief — sidebar panel + modal |
