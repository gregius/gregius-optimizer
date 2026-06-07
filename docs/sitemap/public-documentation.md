# Sitemap

[Editorial: Feature: Sitemap | Pages: 1 | Total words: ~1200 | Sections: 6 (H2: 5, H3: 3) | Screenshots needed: 3 | Doc category: seo-settings | Audience: site-administrator]

This feature enables **site administrators** to control which content appears in their WordPress XML sitemap by **toggling post types, taxonomies, author pages, and individual documents** directly from the Block Editor — no coding required.

---

## Overview

The Sitemap feature gives you granular control over your site's `wp-sitemap.xml` file. WordPress generates this file automatically, but you may want to exclude certain content types (like product categories with few entries) or specific pages (like thank-you pages that shouldn't appear in search results).

All controls are available from a single panel in the Block Editor sidebar. Changes take effect immediately on the next sitemap request.

**What you can control:**

- Which post types appear in the sitemap (Posts, Pages, Products, etc.)
- Which taxonomies appear (Categories, Tags, custom taxonomies)
- Whether author archive pages are included
- Which specific users are excluded from the author sitemap
- Individual documents — hide any page or post from search engines entirely

<!-- IMAGE: Screenshot showing the Sitemap panel in the Block Editor sidebar with the "Settings" button and "Hide from search engines" toggle. -->

---

## Before You Start

**Prerequisites:**

- Gregius Optimizer plugin installed and activated
- WordPress 6.9 or later

---

## How to Exclude a Post Type from the Sitemap

If your site has a custom post type that shouldn't appear in search results (e.g., internal notes, archived events), you can exclude it entirely from the sitemap.

1. Open any post or page in the Block Editor.
2. Locate the **Sitemap** panel in the right sidebar. If you don't see it, check that the post type supports custom fields.
3. Click **Settings** to open the configuration modal.
4. Under **Post types**, find the post type you want to exclude.
5. Toggle the switch to **off** (grey).
6. Click **Update** to save.

<!-- IMAGE: Screenshot of the Sitemap modal showing the Post types section with one toggle turned off. -->

The excluded post type will no longer appear in `wp-sitemap.xml`. Previously published posts of that type remain accessible on the frontend.

### How to Re-Enable a Post Type

1. Open the **Sitemap** modal again.
2. Toggle the post type back to **on**.
3. Click **Update**.

---

## How to Exclude a Taxonomy from the Sitemap

By default, `Categories` and `Tags` are excluded from the sitemap. You can change this or exclude other taxonomies.

1. Open the **Sitemap** modal from the Block Editor sidebar.
2. Scroll to the **Taxonomies** section.
3. Toggle each taxonomy on or off as needed.
4. Click **Update**.

**Note:** Taxonomies with very few entries typically add little value to search engine crawlers. Consider enabling them only when they contain substantial, unique content.

---

## How to Enable or Disable the Author Sitemap

Author archive pages are excluded from the sitemap by default. You can enable them and selectively exclude individual authors.

1. Open the **Sitemap** modal.
2. Under **Author sitemap**, toggle **Include author page in sitemap** to **on**.
3. A list of all authors appears below the toggle.
4. Toggle individual authors **off** to exclude them from the sitemap.
5. Click **Update**.

<!-- IMAGE: Screenshot of the Author sitemap section with the master toggle enabled and a few authors toggled off. -->

---

## How to Hide a Page from Search Engines

Individual pages or posts can be hidden from search engines. This adds a "noindex" instruction and removes the page from all sitemaps.

1. Open the post in the Block Editor.
2. In the **Sitemap** panel, find the **Hide page from search engines** toggle.
3. Toggle it to **on**.
4. Update or publish the post.

When enabled:
- The page gets a `<meta name="robots" content="noindex">` tag.
- The page is excluded from `wp-sitemap.xml`.
- The page remains accessible via direct link.

**Tip:** Use this for thank-you pages, admin-only content, draft-quality posts, or any page that shouldn't appear in search results.

---

## How to Reset to Defaults

If you've made changes and want to start over:

1. Open the **Sitemap** modal.
2. Click **Reset to defaults**.
3. The button is disabled when no overrides exist — if it's greyed out, you're already at default settings.

This reverts all toggles (post types, taxonomies, authors) to their original filter defaults. It does not affect the "Hide from search engines" setting on individual posts.

---

## Permissions

| Action | Required Capability |
|---|---|
| View sitemap settings | `manage_options` |
| Update sitemap settings | `manage_options` |
| Hide a post from search engines | `edit_post` (on that post) |

Only site administrators can change global sitemap settings. Content editors can hide their own posts from search engines.

---

## Next Steps

- **Robots.txt** — Learn how to customize your robots.txt directives
- **Schema** — Configure structured data for better search appearance
- **Social Cards** — Control how your content appears on social media platforms
- **LLMs** — Provide context for AI language models
- Google Sitemaps Documentation — External: https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview
