# Schema

[Editorial: Feature: Schema | Pages: 1 | Total words: ~1100 | Sections: 7 (H2: 5, H3: 3) | Screenshots needed: 2 | Doc category: seo-settings | Audience: site-administrator]

This feature enables **site administrators and content editors** to control the structured data (JSON-LD) on their WordPress site by **selecting schema.org types for each content type and individual pages** — no code required.

---

## Overview

Schema markup helps search engines understand your content. The Schema feature adds JSON-LD structured data to your pages for:

- **Organization** — Your site's brand or company info, including logo and social profiles
- **WebSite** — Site name and search action
- **BreadcrumbList** — Navigation trail for individual pages
- **Article / Page** — Content type, headline, description, image, author, publish date

All of these use schema.org types — a standard vocabulary of over 800 types. The feature includes 176 of the most useful subtypes across 9 categories (Article, WebPage, CreativeWork, Event, Organization, Person, Place, Product, Review).

<!-- IMAGE: Screenshot showing the Schema panel in the Block Editor sidebar. -->

---

## Before You Start

**Prerequisites:**
- Gregius Optimizer plugin installed and activated
- Post types must support `custom-fields` for per-post override

---

## How to Set Global Schema Defaults

Default schema subtypes ensure every post type gets the correct structured data automatically.

1. Open any post or page in the Block Editor.
2. Locate the **Schema** panel in the right sidebar.
3. Click **Settings** to open the configuration modal.

### Set the Organization Type

1. In the **Organization Type** dropdown, select your organization's schema.org type.
2. Options include `Corporation`, `LocalBusiness`, `NGO`, `EducationalOrganization`, `SportsOrganization`, and more.
3. This affects the Organization JSON-LD output across your entire site.

### Set Default Subtypes for Each Post Type

1. Scroll to the post type list below the Organization Type dropdown.
2. Each content post type (Posts, Pages, etc.) has two dropdowns:
   - **Category** — The schema.org category (Article, WebPage, CreativeWork, etc.)
   - **Subtype** — The specific subtype within that category (BlogPosting, FAQPage, Recipe, etc.)
3. Select the appropriate subtype for each post type.
4. Click **Update** to save.

<!-- IMAGE: Screenshot of the Schema modal showing the Organization Type dropdown and the post type rows with category/subtype dropdowns. -->

**Defaults:**
- Posts default to `BlogPosting` (Article category)
- Pages default to `WebPage` (WebPage category)
- All other post types default to `Article`

### How to Reset Global Defaults

1. Open the **Schema** modal.
2. Click **Reset to defaults**.
3. This reverts all global defaults. Per-post overrides are not affected.

---

## How to Override Schema for a Single Post

Individual posts can have their own schema subtype, independent of the global default.

1. Open the post in the Block Editor.
2. Open the **Schema** modal.
3. In the **Current Document** section, use the two dropdowns to select:
   - **Category** (e.g., Article, WebPage)
   - **Subtype** (e.g., NewsArticle, FAQPage)
4. The override is saved automatically with the post.

The "Current Document" section shows the currently assigned subtype. If it matches the global default, the label shows the global value.

---

## How to Preview and Copy Schema Output

1. Open the **Schema** modal.
2. Scroll to the **Preview** section at the bottom.
3. The full JSON-LD for the current post is displayed in a formatted code block.
4. Click the copy button (clipboard icon) in the top-right corner of the preview to copy the JSON to your clipboard.

Use the preview to verify your schema configuration before publishing. The preview reflects both global settings and per-post overrides.

---

## Permissions

| Action | Required capability |
|---|---|
| View global schema settings | `manage_options` |
| Update global schema defaults | `manage_options` |
| Override schema for a post | `edit_post` (on that post) |
| View schema preview | `edit_post` (on that post) |

---

## Next Steps

- **Sitemap** — Control which content appears in your XML sitemap
- **Robots.txt** — Configure crawl directives for search engines and AI bots
- **Social Cards** — Control how your content appears on social media platforms
- Schema.org documentation — External: https://schema.org/
