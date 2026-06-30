---
type: Specification
title: "Software Requirements Specification (SRS) — LLMs"
description: "Functional, data, and operational requirements for Auto-generated llms.txt content with per-post toggles for LLM consumption."
subsystem: llms
standard: "ISO/IEC/IEEE 29148:2018"
tags: [llms, specification]
timestamp: 2026-06-30T00:00:00Z
---

# Software Requirements Specification (SRS) — LLMs

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | llms |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The LLMs feature enables site administrators to generate and customize an `/llms.txt` file that provides structured information about the website to large language models (LLMs) at inference time. It implements the llms.txt proposal for standardizing how websites expose their content and architecture to AI agents.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/llms`

**Repository path:** `includes/llms.php`, `src/llms-settings.js`

The feature serves `/llms.txt` at the site root, outputs a `<link rel="help">` tag in `<head>`, and provides a Gutenberg sidebar UI for configuring the context text and per-post inclusion.

### 1.3 System Functions Summary

- Serve `/llms.txt` at the site root with `Content-Type: text/plain`
- Output `<link rel="help" type="text/plain" href="/llms.txt">` in document head
- Auto-generate llms.txt from site title, description, homepage, and architecture defaults
- Generate a "Key Documents" section from posts with include toggle enabled
- Custom context override saved to database
- Per-post toggle to include/exclude from Key Documents
- Per-post custom description for llms.txt entry
- REST endpoints for reading/writing context override and previewing full output
- Gutenberg sidebar panel with modal editor and live preview

---

## 2. Software Requirements

### 2.1 Functional Requirements

#### 2.1.1 LLMs.txt Serving

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST serve `/llms.txt` at the site root via `template_redirect` with `Content-Type: text/plain`, using `wp_basename()` for multisite-safe URL matching at any path depth. | Must |
| FR-02 | The software MUST disable `/llms.txt` serving when the `gg_optimizer_llms_enabled` filter returns `false`. | Must |
| FR-03 | The software MUST output `<link rel="help" type="text/plain" href="/llms.txt">` in the document `<head>` via `wp_head`. | Must |

#### 2.1.2 Auto-Generated Context

| ID | Requirement | Priority |
|---|---|---|
| FR-04 | The software MUST auto-generate a context header using site title (H1), site description (blockquote), and homepage summary (list item). | Must |
| FR-05 | The homepage summary MUST use the page excerpt when available, otherwise the first 40 words of content. | Must |
| FR-06 | The auto-generated context MUST include a "## Core Architecture" section with default architectural descriptions. | Must |
| FR-07 | The auto-generated context MUST include a "## Key Specifications" section with default specification descriptions. | Must |

#### 2.1.3 Key Documents Generation

| ID | Requirement | Priority |
|---|---|---|
| FR-08 | The software MUST generate a "## Key Documents" section listing all published posts with `_gg_optimizer_include_in_llms` enabled. | Must |
| FR-09 | Each entry MUST be formatted as `- [Title](permalink): description`. | Must |
| FR-10 | The description MUST resolve with priority: runtime override → `_gg_optimizer_llms_description` meta → excerpt → first 20 words of content. | Must |
| FR-11 | The Key Documents section MUST support runtime toggle and description overrides for preview without saving. | Must |
| FR-12 | The software MUST append a "Sitemap:" line pointing to `/wp-sitemap.xml` after the Key Documents section. | Must |

#### 2.1.4 Context Override

| ID | Requirement | Priority |
|---|---|---|
| FR-13 | The software MUST allow saving a custom llms.txt override to the database via the `llms_override` key in `gg_optimizer_settings`. | Must |
| FR-14 | When an override exists, the software MUST output the override instead of the auto-generated context. | Must |
| FR-15 | When no override exists (empty string), the software MUST fall back to auto-generated context. | Must |
| FR-16 | Saving an override MUST strip everything from "## Key Documents" onward as a safety measure, since that section is auto-generated. | Must |

#### 2.1.5 Meta Fields

| ID | Requirement | Priority |
|---|---|---|
| FR-17 | The software MUST register `_gg_optimizer_include_in_llms` (boolean) on all public post types. | Must |
| FR-18 | The software MUST register `_gg_optimizer_llms_description` (text) on all public post types. | Must |

#### 2.1.6 REST API

| ID | Requirement | Priority |
|---|---|---|
| FR-19 | The software MUST expose `GET /gg-optimizer/v1/llms-override` returning the stored override and the effective context. | Must |
| FR-20 | The software MUST expose `POST /gg-optimizer/v1/llms-override` to save the context override. | Must |
| FR-21 | The software MUST expose `POST /gg-optimizer/v1/llms-preview` to render full llms.txt with runtime overrides. | Must |

#### 2.1.7 Gutenberg Sidebar Panel

| ID | Requirement | Priority |
|---|---|---|
| FR-22 | The software MUST register an "LLMs" panel in the Block Editor sidebar via `PluginDocumentSettingPanel`. | Must |
| FR-23 | The panel MUST contain a description with a link to llmstxt.org. | Must |
| FR-24 | The panel MUST have a "Settings" button that opens a modal. | Must |

#### 2.1.8 Modal Configuration

| ID | Requirement | Priority |
|---|---|---|
| FR-25 | The modal MUST display a description linking to llmstxt.org. | Must |
| FR-26 | The modal MUST display a "Global Context" textarea for editing the llms.txt context portion. | Must |
| FR-27 | The modal MUST display a "Include the current document" toggle for the current post. | Must |
| FR-28 | When toggled on, the modal MUST display a "Description" textarea for a custom llms.txt entry. | Must |
| FR-29 | The modal MUST display a live "Preview" section showing the full llms.txt output. | Must |
| FR-30 | The preview MUST update automatically via debounce on context/toggle/description changes. | Must |
| FR-31 | The modal MUST have "Update" and "Reset to defaults" buttons. | Must |
| FR-32 | The "Reset to defaults" button MUST be disabled when no custom override exists. | Must |

### 2.2 Software Interfaces

#### 2.2.1 WordPress Core Hooks

| Hook | Type | Behavioral Contract |
|---|---|---|
| `template_redirect` | action | Intercept `/llms.txt` requests (using `wp_basename()` for multisite-safe URL matching) and serve plain text |
| `wp_head` | action | Output `<link rel="help">` pointing to `/llms.txt` |

#### 2.2.2 Filters

| Filter | Description |
|---|---|
| `gg_optimizer_llms_enabled` | Disable the entire LLMs feature. Default `true`. |

#### 2.2.3 REST API Contracts

| Endpoint | Method | Permission | Response |
|---|---|---|---|
| `/gg-optimizer/v1/llms-override` | GET | `manage_options` | `{ llms_override: string, llms_context: string }` |
| `/gg-optimizer/v1/llms-override` | POST | `manage_options` | `{ success: bool }` |
| `/gg-optimizer/v1/llms-preview` | POST | `manage_options` | `{ llms_txt: string }` |

#### 2.2.4 Database Interface

| Data Item | Storage Key | Description |
|---|---|---|
| `llms_override` | `{$prefix}gg_optimizer_settings` | Custom llms.txt context override (plain text) |

#### 2.2.5 Post Meta Interface

| Meta Key | Type | Description |
|---|---|---|
| `_gg_optimizer_include_in_llms` | boolean | Include this post in the llms.txt Key Documents section |
| `_gg_optimizer_llms_description` | string | Custom description for the llms.txt entry |

### 2.3 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | All REST endpoints MUST check `current_user_can('manage_options')`. |
| SEC-02 | llms.txt output MUST NOT escape auto-generated content (intentional raw text). |

---

## 3. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-03 | Product brief — llms.txt serving |
| FR-04–FR-07 | Product brief — auto-generated context |
| FR-08–FR-12 | Product brief — key documents |
| FR-13–FR-16 | Product brief — context override |
| FR-17–FR-18 | Product brief — meta fields |
| FR-19–FR-21 | Product brief — REST API |
| FR-22–FR-24 | Product brief — sidebar panel |
| FR-25–FR-32 | Product brief — modal configuration |

# Related

- Upstream specification for this subsystem: [architecture.md](architecture.md) — architecture views and design decisions
