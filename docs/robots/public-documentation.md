# Robots

[Editorial: Feature: Robots | Pages: 1 | Total words: ~950 | Sections: 5 (H2: 4, H3: 2) | Screenshots needed: 2 | Doc category: seo-settings | Audience: site-administrator]

This feature enables **site administrators** to control their site's `robots.txt` file and per-page search engine directives by **editing content directly from the Block Editor** — no server access required.

---

## Overview

The Robots feature gives you two layers of crawl control:

1. **Robots.txt** — Define which crawlers (search engines, AI answer engines, AI trainers) can access which parts of your site.
2. **Robots meta tag** — Control per-page indexing directives like `noindex`.

Your site ships with sensible defaults for both. The default robots.txt allows all major search engines, AI answer services (ChatGPT, Perplexity, Claude), and AI model trainers while blocking access to `wp-admin` (except `admin-ajax.php`).

<!-- IMAGE: Screenshot showing the Robots panel in the Block Editor sidebar with the "Settings" button. -->

---

## Before You Start

**Prerequisites:**
- Gregius Optimizer plugin installed and activated
- Settings → Reading → "Discourage search engines from indexing this site" should be unchecked (otherwise your custom robots.txt will not apply)

---

## How to Edit Your Robots.txt

1. Open any post or page in the Block Editor.
2. Locate the **Robots** panel in the right sidebar.
3. Click **Settings** to open the editor modal.
4. The modal shows your current robots.txt content in a text area. The site's `robots.txt` URL appears as a clickable link at the top.
5. Edit the content as needed. The text area grows automatically as you add lines.
6. Click **Update** to save.

<!-- IMAGE: Screenshot of the Robots modal showing the textarea with robots.txt content and the Update/Reset buttons. -->

**Example — Restrict AI crawlers:**
If you want to block AI model trainers while keeping search engines and AI answer engines:

```
User-agent: GPTBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: Applebot-Extended
Disallow: /

User-agent: Cohere-ai
Disallow: /
```

### How to Reset to Defaults

1. Open the **Robots** modal.
2. Click **Reset to defaults**.
3. The button is disabled when no custom override exists.

Resetting restores the built-in directives that allow all major crawlers.

---

## How Robots Meta Works

Every page on your site automatically gets a `<meta name="robots">` tag. The directives depend on the page type:

| Page type | Directive |
|---|---|
| Standard pages and posts | `index, follow` |
| Search results | `noindex, follow` |
| 404 pages | `noindex, follow` |
| Posts hidden from search (via the Sitemap panel) | `noindex, follow` |

**What `noindex, follow` means:**
- `noindex` — Search engines should not include this page in results.
- `follow` — Search engines may still follow links on this page.

### How to Noindex a Specific Post

1. Open the post in the Block Editor.
2. In the **Sitemap** panel, toggle **Hide page from search engines** to on.
3. Update or publish the post.

The post will receive a `noindex, follow` directive and will be removed from the XML sitemap.

---

## Permissions

| Action | Required capability |
|---|---|
| View robots.txt settings | `manage_options` |
| Update robots.txt content | `manage_options` |
| Hide a post from search engines | `edit_post` (on that post) |

Only site administrators can change robots.txt content. Content editors can noindex their own posts.

---

## Next Steps

- **Sitemap** — Fine-tune which content types appear in your XML sitemap
- **Schema** — Add structured data for rich search results
- **Social Cards** — Control how your pages appear on social media
- Google robots.txt documentation — External: https://developers.google.com/search/docs/crawling-indexing/robots/intro
