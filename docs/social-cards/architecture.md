# Architecture Description — Social Cards

**Standard:** ISO/IEC/IEEE 42010:2022

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | social-cards |
| Version | 0.1 — Draft |
| Date | 2026-06-06 |
| Author | Gregius Engineering |
| SRS Reference | `docs/social-cards/srs.md` |
| Status | Draft |

---

## 1. Scope and Boundary

### 1.1 System Scope

The Social Cards feature generates `<meta>` tags for Google Search snippets, Open Graph (Facebook, LinkedIn, etc.), and Twitter Cards. It provides per-platform title, description, and image overrides via Gutenberg sidebar UI with inline previews and character counters.

### 1.2 Explicitly Excluded

- Schema.org JSON-LD (handled by the schema feature)
- Sitemap generation (handled by the sitemap feature)
- Social login or sharing buttons
- Facebook-specific or LinkedIn-specific meta beyond standard OG
- Meta tags for non-public post types

### 1.3 Feature Slug

`social-cards`

---

## 2. Architecture Views

### 2.1 Context View (AV-01)

| External System | Direction | Description |
|---|---|---|
| WordPress Core (`wp_head`) | ← adds | Outputs meta description, canonical, OG, Twitter tags |
| WordPress Block Editor | → provides | Social Cards modal for per-platform overrides |
| WordPress REST API | ← responds | `/meta-preview` POST endpoint |
| Post Meta (11 keys) | → writes ← reads | Per-post title/description/image overrides |
| WordPress Media Library | → selects | Image selection via MediaUpload component |
| Social Platforms | → consumes | Crawlers read OG/Twitter meta from HTML |

### 2.2 Component View (AV-02)

```
┌───────────────────────────────────────────────────────────────┐
│                       includes/search.php                     │
│                                                               │
│  - gg_optimizer_output_meta_description()                     │
│  - gg_optimizer_get_canonical_url()                           │
│  - gg_optimizer_output_canonical_link()                       │
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│                      includes/meta-tags.php                    │
│                                                               │
│  - 11 meta key registration (GG_Optimizer_Custom_Meta_Field)  │
│  - gg_optimizer_meta_normalize_text()                         │
│  - gg_optimizer_get_meta_title()                              │
│  - gg_optimizer_get_meta_description()                        │
│  - gg_optimizer_get_platform_title() (3 platforms)            │
│  - gg_optimizer_get_platform_description() (3 platforms)      │
│  - gg_optimizer_get_platform_image() (2 platforms)            │
│  - gg_optimizer_get_metadata_context() (unified resolver)     │
│  - gg_optimizer_output_head_meta() (master wp_head emitter)   │
│  - gg_optimizer_filter_document_title()                       │
│  - REST: /meta-preview (POST)                                 │
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│                     includes/social-cards.php                  │
│                                                               │
│  - gg_optimizer_register_image_sizes()                        │
│  - gg_optimizer_get_og_locale()                               │
│  - gg_optimizer_get_social_image_data()                       │
│  - gg_optimizer_output_og_meta() (OG + Twitter)               │
└───────────────────────────────────────────────────────────────┘

┌───────────────────────────────────────────────────────────────┐
│                  src/social-cards-sidebar.js                    │
│                                                               │
│  - PluginDocumentSettingPanel "Social Cards"                  │
│  - Modal: Global Image section (150px thumbnail)              │
│  - Modal: GoogleCard (inline RichText + counters)             │
│  - Modal: OGCard (image + inline RichText + counters)         │
│  - Modal: TwitterCard (image + inline RichText + counters)    │
│  - Kebab dropdowns for image Replace/Remove per card          │
│  - Character counters per platform (60/160, 55/65, 70/200)    │
│  - Update / Reset to defaults buttons                         │
└───────────────────────────────────────────────────────────────┘
```

### 2.3 Runtime Interaction View (AV-03)

**Flow A: Page load — meta tag output**

```
  Browser              wp_head               meta-tags.php
    │                     │                       │
    │— GET /any-post      │                       │
    │                     │— action ─────────────>│
    │                     │                       │— output_meta_description()
    │                     │                       │  → meta → excerpt → content → tagline
    │                     │                       │— output_canonical_link()
    │                     │                       │  → wp_get_canonical_url() → permalink
    │                     │                       │— output_og_meta()
    │                     │                       │  → platform_title('og')
    │                     │                       │  → platform_description('og')
    │                     │                       │  → platform_image('og')
    │                     │                       │  → og:locale, og:type, og:url, article:*
    │                     │                       │— twitter:card, twitter:title, etc.
    │<── HTML + meta ────│<──────────────────────│
```

**Flow B: Editor configures social cards**

```
  JS Sidebar           Post Meta               Media Library
    │                     │                        │
    │— Open modal         │                        │
    │— Edit Google title  │                        │
    │                     │— _gg_optimizer_        │
    │                     │  google_title saved    │
    │                     │                        │
    │— Select OG image    │                        │
    │— Click MediaUpload  │                        │
    │                     │                        │— Open media picker
    │<─── image selected ─│<────────────────────────│
    │                     │                        │
    │— _gg_optimizer_     │                        │
    │  og_image saved     │                        │
    │                     │                        │
    │— Click Update       │                        │
    │  (notification)     │                        │
```

---

## 3. Architecture Decision Records

### AD-01: No CardSection Component

| Field | Value |
|---|---|
| ID | AD-01 |
| Linked Requirements | FR-42, FR-44, FR-46 |
| Decision | Each card (Google, OG, Twitter) wraps its own outer div with border/radius/overflow rather than sharing a common `CardSection` component. |
| Rationale | Each card has different internal layout (Google uses grid for favicon/URL, OG/Twitter use image headers). A common component would require complex prop branching that negates reuse benefits. |
| Consequences | Some CSS repetition across the three card components. |

### AD-02: Kebab Over Replace/Remove Buttons

| Field | Value |
|---|---|
| ID | AD-02 |
| Linked Requirements | FR-40, FR-44, FR-46 |
| Decision | Use a kebab menu (three-dot dropdown) for image Replace/Remove actions instead of inline buttons. |
| Alternatives | Side-by-side Replace/Remove buttons |
| Rationale | Cleaner visual — the kebab overlay on the image corner avoids layout shifts and keeps the focus on the preview. |
| Consequences | Slightly more complex implementation (Dropdown + MenuGroup + MenuItem). |

### AD-03: Platform-Specific Image Resolvers

| Field | Value |
|---|---|
| ID | AD-03 |
| Linked Requirements | FR-23, FR-31 |
| Decision | Use `gg_optimizer_get_platform_image()` for OG/Twitter with downward cascade (twitter → og → common → featured), and `gg_optimizer_get_social_image_data()` for the common/meta image (meta override → featured). |
| Alternatives | Single image resolver for all platforms |
| Rationale | Twitter and OG can have different images. The Twitter-specific cascade falls through to OG before common, allowing cross-platform fallback. The common image has a simpler chain (meta override → featured). |
| Consequences | Two resolver functions with overlapping but distinct fallback logic. |

### AD-04: Unified Metadata Context

| Field | Value |
|---|---|
| ID | AD-04 |
| Linked Requirements | FR-34 |
| Decision | Provide `gg_optimizer_get_metadata_context()` as a single resolver returning all shared metadata fields (title, description, canonical, OG URL, OG type, site name, locale, image). |
| Rationale | Prevents parity drift between wp_head output and REST preview response. Both the live tags and the `/meta-preview` endpoint draw from the same context. |
| Consequences | REST endpoint must pass runtime overrides (metaTitle, excerpt, content) into the context resolver. |

### AD-05: Google Title Overrides `<title>` Tag

| Field | Value |
|---|---|
| ID | AD-05 |
| Linked Requirements | FR-18, FR-19 |
| Decision | Filter `pre_get_document_title` to substitute the Google-specific override when set, rather than only using the common meta title. |
| Alternatives | Always use `_gg_optimizer_meta_title` for the `<title>` tag |
| Rationale | The Google override is specifically for the search result title. Changing the document `<title>` to match ensures consistency between the browser/HTML title and the Google snippet. |
| Consequences | Only triggers on singular, non-admin pages when `in_the_loop()`. |

---

## 4. Architecture Coverage Mapping

| Architecture Item | Requirement ID |
|---|---|
| AV-01 Context View | FR-01–FR-11, FR-34–FR-36 |
| AV-02 Component View | FR-12–FR-33 |
| AV-03 Flow A | FR-12–FR-33 |
| AV-03 Flow B | FR-40–FR-50 |
| AD-01 No CardSection | FR-42, FR-44, FR-46 |
| AD-02 Kebab Menu | FR-40, FR-44, FR-46 |
| AD-03 Platform Image Resolvers | FR-23, FR-31 |
| AD-04 Unified Context | FR-34 |
| AD-05 Google Title Override | FR-18, FR-19 |
