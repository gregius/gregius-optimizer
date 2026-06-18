# Software Requirements Specification (SRS) — Social Cards

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | social-cards |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| Status | Draft |

---

## 1. Introduction

### 1.1 System Purpose

The Social Cards feature enables content editors to control how their content appears when shared on social media platforms (Open Graph, Twitter/X) and in search engine result snippets (Google). It provides per-platform title, description, and image overrides with inline previews.

### 1.2 System Scope

**Software identifier:** `gregius-optimizer/social-cards`

**Repository path:** `includes/meta-tags.php`, `includes/social-cards.php`, `includes/search.php`, `src/social-cards-sidebar.js`

The feature generates `<meta>` tags for description, canonical, Open Graph, and Twitter Cards. It does not generate Facebook-specific or LinkedIn-specific meta tags beyond standard OG.

### 1.3 System Functions Summary

- Register 11 social/search meta keys on all public post types
- Output `<meta name="description">` with canonical provenance policy
- Output `<link rel="canonical">` with fallback chain
- Output OG meta tags (og:locale, og:title, og:description, og:type, og:url, og:site_name, og:image, og:image:width, og:image:height, og:image:alt, article:published_time, article:modified_time)
- Output Twitter Card meta tags (twitter:card, twitter:site, twitter:title, twitter:description, twitter:image, twitter:image:alt)
- Output article-specific meta for published/modified time
- Per-platform title/description/image override with fallback chain
- Global Image setting that serves as fallback across all platforms
- Character counters with platform-specific limits
- REST endpoint for live meta preview
- Gutenberg sidebar panel with inline-editable cards

---

## 2. Software Requirements

### 2.1 Functional Requirements

#### 2.1.1 Meta Key Registration

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | The software MUST register `_gg_optimizer_meta_title` on all public post types. | Must |
| FR-02 | The software MUST register `_gg_optimizer_meta_description` on all public post types. | Must |
| FR-03 | The software MUST register `_gg_optimizer_meta_image` on all public post types. | Must |
| FR-04 | The software MUST register `_gg_optimizer_google_title` on all public post types. | Must |
| FR-05 | The software MUST register `_gg_optimizer_google_description` on all public post types. | Must |
| FR-06 | The software MUST register `_gg_optimizer_og_title` on all public post types. | Must |
| FR-07 | The software MUST register `_gg_optimizer_og_description` on all public post types. | Must |
| FR-08 | The software MUST register `_gg_optimizer_og_image` on all public post types. | Must |
| FR-09 | The software MUST register `_gg_optimizer_twitter_title` on all public post types. | Must |
| FR-10 | The software MUST register `_gg_optimizer_twitter_description` on all public post types. | Must |
| FR-11 | The software MUST register `_gg_optimizer_twitter_image` on all public post types. | Must |

#### 2.1.2 Meta Description Output

| ID | Requirement | Priority |
|---|---|---|
| FR-12 | The software MUST output `<meta name="description">` via `wp_head` with configurable filter `gg_optimizer_meta_output_description`. | Must |
| FR-13 | The description MUST be resolved using provenance priority: explicit override → post excerpt → post content → site tagline. | Must |
| FR-14 | Descriptions MUST be normalized (strip shortcodes, HTML, collapse whitespace) and truncated to 155 characters with ellipsis. | Must |

#### 2.1.3 Canonical URL Output

| ID | Requirement | Priority |
|---|---|---|
| FR-15 | The software MUST output `<link rel="canonical">` via `wp_head` with configurable filter `gg_optimizer_meta_output_canonical`. | Must |
| FR-16 | Canonical URL MUST resolve using priority: `wp_get_canonical_url()` → `get_permalink()` → home URL. | Must |
| FR-17 | The canonical URL MUST be filterable via `gg_optimizer_meta_canonical_url`. | Must |

#### 2.1.4 Document Title Filter

| ID | Requirement | Priority |
|---|---|---|
| FR-18 | The software MUST filter `pre_get_document_title` to use `_gg_optimizer_google_title` when set. | Must |
| FR-19 | The title filter MUST only apply on singular, non-admin pages. | Must |

#### 2.1.5 Open Graph Output

| ID | Requirement | Priority |
|---|---|---|
| FR-20 | The software MUST output OG meta tags via `wp_head` with configurable filter `gg_optimizer_meta_output_og`. | Must |
| FR-21 | OG title MUST resolve with priority: `_gg_optimizer_og_title` → `_gg_optimizer_meta_title` → `_gg_optimizer_google_title` → `wp_get_document_title()`. | Must |
| FR-22 | OG description MUST resolve with priority: `_gg_optimizer_og_description` → `_gg_optimizer_meta_description` → `_gg_optimizer_google_description` → excerpt → content → site tagline. | Must |
| FR-23 | OG image MUST resolve with priority: `_gg_optimizer_og_image` → featured image → `_gg_optimizer_meta_image`. | Must |
| FR-24 | OG type MUST be `article` for singular non-page posts, `website` otherwise. | Must |
| FR-25 | OG locale MUST derive from site locale with filter `gg_optimizer_meta_og_locale`. | Must |
| FR-26 | article:published_time and article:modified_time MUST output for `article` type posts. | Must |

#### 2.1.6 Twitter Card Output

| ID | Requirement | Priority |
|---|---|---|
| FR-27 | The software MUST output Twitter Card meta tags via `wp_head` with configurable filter `gg_optimizer_meta_output_twitter`. | Must |
| FR-28 | Twitter card type MUST be `summary_large_image` when image exists, `summary` otherwise. | Must |
| FR-29 | Twitter title MUST resolve with priority: `_gg_optimizer_twitter_title` → `_gg_optimizer_meta_title` → `_gg_optimizer_google_title` → `wp_get_document_title()`. | Must |
| FR-30 | Twitter description MUST resolve with priority: `_gg_optimizer_twitter_description` → `_gg_optimizer_meta_description` → `_gg_optimizer_google_description` → excerpt → content → site tagline. | Must |
| FR-31 | Twitter image MUST resolve with priority: `_gg_optimizer_twitter_image` → `_gg_optimizer_og_image` → featured image → `_gg_optimizer_meta_image`. | Must |

#### 2.1.7 Image Size Registration

| ID | Requirement | Priority |
|---|---|---|
| FR-32 | The software MUST register `gg_optimizer_og` image size (1200×630, cropped) via `after_setup_theme`. | Must |
| FR-33 | Image dimensions MUST be filterable: `gg_optimizer_og_image_width`, `gg_optimizer_og_image_height`, `gg_optimizer_og_image_crop`. | Must |

#### 2.1.8 REST API

| ID | Requirement | Priority |
|---|---|---|
| FR-34 | The software MUST expose `POST /gg-optimizer/v1/meta-preview` returning resolved meta tags for a post. | Must |
| FR-35 | The endpoint MUST accept postId, metaTitle, metaDescription, metaImageId, featuredMediaId, excerpt, content parameters. | Must |
| FR-36 | Permission MUST check `edit_post` on the given post ID. | Must |

#### 2.1.9 Gutenberg Sidebar Panel

| ID | Requirement | Priority |
|---|---|---|
| FR-37 | The software MUST register a "Social Cards" panel in the Block Editor sidebar via `PluginDocumentSettingPanel`. | Must |
| FR-38 | The panel MUST contain a description with links to Google Search Snippets, Open Graph, and Twitter Cards documentation. | Must |
| FR-39 | The panel MUST have a "Settings" button that opens a modal. | Must |

#### 2.1.10 Modal Configuration

| ID | Requirement | Priority |
|---|---|---|
| FR-40 | The modal MUST display a "Global Image" section with MediaUpload and kebab dropdown (Replace/Remove). | Must |
| FR-41 | The Global Image section MUST show a 150px square thumbnail with a description of fallback behavior. | Must |
| FR-42 | The modal MUST display a "Search Snippet" card with inline-editable Google title and description. | Must |
| FR-43 | The Google card MUST show character counters (max 60 for title, 160 for description). | Must |
| FR-44 | The modal MUST display an "Open Graph" card with inline-editable title, description, and image with kebab dropdown. | Must |
| FR-45 | The OG card MUST show character counters (max 55 for title, 65 for description). | Must |
| FR-46 | The modal MUST display a "Twitter / X" card with inline-editable title, description, and image with kebab dropdown. | Must |
| FR-47 | The Twitter card MUST show character counters (max 70 for title, 200 for description). | Must |
| FR-48 | Each card MUST display the resolved fallback values as placeholders when no override is set. | Must |
| FR-49 | The modal MUST have "Update" and "Reset to defaults" buttons. | Must |
| FR-50 | The "Reset to defaults" button MUST be disabled when no overrides exist. | Must |

### 2.2 Software Interfaces

#### 2.2.1 WordPress Core Hooks

| Hook | Type | Behavioral Contract |
|---|---|---|
| `wp_head` | action | Output meta description, canonical, OG meta, Twitter meta |
| `pre_get_document_title` | filter | Override document title with Google-specific override |
| `after_setup_theme` | action | Register `gg_optimizer_og` image size |

#### 2.2.2 Filters

| Filter | Description |
|---|---|
| `gg_optimizer_meta_output_description` | Disable meta description output. Default `true`. |
| `gg_optimizer_meta_output_og` | Disable Open Graph output. Default `true`. |
| `gg_optimizer_meta_output_twitter` | Disable Twitter Card output. Default `true`. |
| `gg_optimizer_meta_output_canonical` | Disable canonical output. Default `true`. |
| `gg_optimizer_meta_canonical_url` | Override canonical URL string. |
| `gg_optimizer_meta_og_locale` | Override OG locale. |
| `gg_optimizer_meta_twitter_site` | Set `twitter:site` value. |
| `gg_optimizer_meta_article_publisher` | Set `article:publisher` URL. |
| `gg_optimizer_og_image_width` | Override OG image width. Default 1200. |
| `gg_optimizer_og_image_height` | Override OG image height. Default 630. |
| `gg_optimizer_og_image_crop` | Override OG image crop. Default `true`. |

#### 2.2.3 REST API Contract

| Endpoint | Method | Permission | Response |
|---|---|---|---|
| `/gg-optimizer/v1/meta-preview` | POST | `edit_post` | `{ title, description, url, image, imageAlt, ogType, twitterCard, siteName, tags: {} }` |

#### 2.2.4 Post Meta Interface

| Meta Key | Type | Description |
|---|---|---|
| `_gg_optimizer_meta_title` | string | Common meta title override |
| `_gg_optimizer_meta_description` | string | Common meta description override |
| `_gg_optimizer_meta_image` | string | Common meta image (attachment ID) |
| `_gg_optimizer_google_title` | string | Google-specific title override |
| `_gg_optimizer_google_description` | string | Google-specific description override |
| `_gg_optimizer_og_title` | string | Open Graph title override |
| `_gg_optimizer_og_description` | string | Open Graph description override |
| `_gg_optimizer_og_image` | string | Open Graph image override (attachment ID) |
| `_gg_optimizer_twitter_title` | string | Twitter title override |
| `_gg_optimizer_twitter_description` | string | Twitter description override |
| `_gg_optimizer_twitter_image` | string | Twitter image override (attachment ID) |

### 2.3 Security Requirements

| ID | Requirement |
|---|---|
| SEC-01 | REST endpoint MUST check `current_user_can('edit_post', $post_id)`. |
| SEC-02 | All meta tag output MUST use `esc_attr()` or `esc_url()`. |
| SEC-03 | Post meta values MUST be normalized via `gg_optimizer_meta_normalize_text()`. |

---

## 3. Traceability

| SRS ID | Source |
|---|---|
| FR-01–FR-11 | Product brief — meta key registration |
| FR-12–FR-14 | Product brief — meta description |
| FR-15–FR-17 | Product brief — canonical URL |
| FR-18–FR-19 | Product brief — document title filter |
| FR-20–FR-26 | Product brief — Open Graph |
| FR-27–FR-31 | Product brief — Twitter Cards |
| FR-32–FR-33 | Product brief — image size |
| FR-34–FR-36 | Product brief — REST API |
| FR-37–FR-39 | Product brief — sidebar panel |
| FR-40–FR-50 | Product brief — modal configuration |
