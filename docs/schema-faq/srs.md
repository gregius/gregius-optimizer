# Software Requirements Specification (SRS) — Schema FAQ

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | schema-faq |
| Version | 0.1 — Draft |
| Date | 2026-06-07 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The Schema FAQ feature enables content editors to mark `core/accordion` blocks as FAQ structured data. When enabled, the plugin extracts question-and-answer pairs from the accordion's heading and panel content and outputs them as `FAQPage` JSON-LD in the page's `@graph`.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/schema-faq`

**Repository path:** `assets/src/scripts/faq-schema-editor.js`, `includes/schema.php`

The feature operates by reading `core/accordion` block attributes from the post content at render time. It does not store FAQ data in post meta, custom tables, or REST endpoints. The extraction is tightly coupled to the theme's accordion block markup — questions are read from the `core/accordion-heading` inner block (targeting a `.toggle-title` span), and answers are read from `core/paragraph` blocks inside `core/accordion-panel`.

### 1.3 System Functions Summary

- Register `faqSchema` boolean attribute on `core/accordion` blocks
- Provide toggle control in the block inspector
- Extract Q&A pairs from accordion blocks on page render
- Output `FAQPage` JSON-LD node in the existing schema `@graph`
- Support recursive extraction through nested blocks
- Provide a filter to disable FAQPage output

---

## 2. Software Requirements

### 2.1 Functional Requirements

#### 2.1.1 Block Attribute Registration

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST register a boolean `faqSchema` attribute (default `false`) on `core/accordion` blocks via the `blocks.registerBlockType` filter. | Must |
| FR-02 | The software MUST add a `ToggleControl` to the `InspectorControls` of any selected `core/accordion` block, with label text explaining the toggle enables FAQ structured data output, and a link to schema.org/FAQPage. | Must |

#### 2.1.2 Q&A Extraction

| ID | Requirement | Priority |
|---|---|---|
| FR-03 | On singular page render, the software MUST parse the post content into blocks and search for `core/accordion` blocks where `attrs.faqSchema` is truthy. | Must |
| FR-04 | For each accordion item (`core/accordion-item`) inside a detected accordion, the software MUST extract the question text from the `core/accordion-heading` inner block, preferring text inside a `<span class="toggle-title">` element, falling back to the full rendered text of the heading block. | Must |
| FR-05 | For each accordion item, the software MUST extract the answer text from all `core/paragraph` blocks inside the `core/accordion-panel` inner block, concatenated with a space separator. | Must |
| FR-06 | The software MUST only include a Q&A pair in the output when both question and answer are non-empty after trimming whitespace. | Must |
| FR-07 | The software MUST recurse into `innerBlocks` to find nested accordions and other block structures. | Must |

#### 2.1.3 JSON-LD Output

| ID | Requirement | Priority |
|---|---|---|
| FR-08 | The extracted Q&A pairs MUST be output as a `FAQPage` JSON-LD node inside the page's `@graph` structure with `@type: FAQPage` and `mainEntity` containing one `Question` + `acceptedAnswer` entry per pair. | Must |
| FR-09 | The `FAQPage` node MUST include `isPartOf` pointing to the parent WebPage `@id`. | Must |

#### 2.1.4 Filter Control

| ID | Requirement | Priority |
|---|---|---|
| FR-10 | The software MUST provide an `gg_optimizer_schema_output_faq` filter that, when returning `false`, suppresses the FAQPage output entirely without affecting block attributes or post content. | Must |

### 2.2 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | All extracted text MUST be passed through `wp_strip_all_tags` before inclusion in JSON-LD output. |
| SEC-02 | JSON-LD output MUST use `wp_json_encode` with `JSON_UNESCAPED_SLASHES` and `JSON_UNESCAPED_UNICODE` flags. |

### 2.3 Software Interfaces

#### 2.3.1 WordPress Hooks

| Hook | Type | Behavioral Contract |
|---|---|---|
| `blocks.registerBlockType` | filter | Injects `faqSchema` attribute into `core/accordion` settings |
| `editor.BlockEdit` | filter | Wraps `core/accordion` block edit with FAQ toggle control |
| `wp_head` | action | Outputs FAQPage JSON-LD via schema output pipeline |
| `gg_optimizer_schema_output_faq` | filter | Override to disable FAQPage output (default `true`) |

#### 2.3.2 Block Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `faqSchema` | `boolean` | `false` | When `true`, the accordion's Q&A pairs are included in FAQPage structured data |

#### 2.3.3 PHP Functions

| Function | File | Purpose |
|---|---|---|
| `gg_optimizer_schema_extract_faq_items` | `schema.php` | Entry point — parse post content and delegate to recursive walker |
| `gg_optimizer_schema_find_faq_in_blocks` | `schema.php` | Recursive walker — detect accordion blocks with `faqSchema` and extract pairs |

### 2.4 Performance Considerations

| ID | Consideration |
|---|---|
| PERF-01 | Q&A extraction only runs on singular page views where schema output is generated. |
| PERF-02 | Extraction is a single pass through the block tree with no additional database queries. |

---

## 3. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-02 | Feature brief — block editor UI |
| FR-03–FR-07 | Feature brief — Q&A extraction logic |
| FR-08–FR-09 | Feature brief — JSON-LD output requirements |
| FR-10 | Feature brief — filter gate |
| SEC-01–SEC-02 | WordPress security best practices |
