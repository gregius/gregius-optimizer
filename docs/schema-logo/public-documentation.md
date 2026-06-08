[Editorial: Feature: Schema Logo | Pages: 1 | Total words: ~550 | Sections: 5 (H2: 5, H3: 3) | Screenshots needed: 2 | Doc category: seo-settings | Audience: content-editor]

Include your site logo in the Organization structured data so search engines understand your brand — no code required.

When enabled, the logo from your Site Logo block is automatically included in the hidden JSON-LD that describes your organization to search engines.

---

## Overview

Schema markup helps search engines understand your organization. The `logo` property tells search engines which image represents your brand, and it can appear in Google's knowledge panel and branded search results.

The plugin works with the standard WordPress **Site Logo** block (`core/site-logo`). When you enable the Organization Logo toggle on the block, the plugin automatically includes the logo URL in your site's Organization JSON-LD output.

---

## Before You Start

### Prerequisites

- Gregius Optimizer plugin installed and activated
- A **Site Logo** block (`core/site-logo`) placed in your content (typically in a header template part or page content)

---

## Enable Logo Schema on a Site Logo Block

### Enable the toggle

1. Open any post, page, or template in the Block Editor.
2. Add or select a **Site Logo** block.
3. In the right sidebar (Block tab), locate the **Organization Logo** panel.
4. Toggle **"Include logo in site's structured data"** to ON.
5. Save or update.

The logo URL is now included in the Organization JSON-LD for every page on the site.

### How the logo URL is resolved

The plugin reads your Site Logo block's attributes at page-render time:

1. If the block has a **URL attribute** set (the image URL), it uses that directly.
2. If no URL is stored in the block attributes, it falls back to the **theme custom logo** set in the WordPress Customizer (Appearance → Customize → Site Identity).
3. Only one logo is output — the first matching `core/site-logo` block with the toggle enabled wins.

If you have multiple Site Logo blocks across your site (e.g., in header and footer), enable the toggle on only the one you want search engines to treat as the canonical brand logo.

---

## Permissions

| Action | Required capability |
|---|---|
| Toggle Logo Schema on a site-logo block | `edit_post` (on that post) |
| View structured data on frontend | None (public) |

---

## Next Steps

- **Schema** — Set global defaults per post type and override subtypes per post
- **SameAs Schema** — Connect social links to your organization's structured data
- [Google Rich Results Test](https://search.google.com/test/rich-results) — External resource
