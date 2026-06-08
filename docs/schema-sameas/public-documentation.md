[Editorial: Feature: Schema SameAs | Pages: 1 | Total words: ~600 | Sections: 5 (H2: 5, H3: 3) | Screenshots needed: 2 | Doc category: seo-settings | Audience: content-editor]

Connect your social profiles to your Organization structured data so search engines can associate your brand across platforms — no code required.

When enabled, the social links from your Social Links block are automatically included in the hidden JSON-LD that tells search engines about your organization's online presence.

---

## Overview

The `sameAs` property in schema.org tells search engines which social media profiles belong to your organization. This helps search engines associate your brand across platforms and can appear in Google's knowledge panel.

The plugin works with the standard WordPress **Social Links** block (`core/social-links`). When you enable the toggle on the block, the plugin automatically collects the URLs from each social link and includes them in your Organization JSON-LD output.

---

## Before You Start

### Prerequisites

- Gregius Optimizer plugin installed and activated
- A **Social Links** block (`core/social-links`) with social links added (e.g., GitHub, LinkedIn, X/Twitter)

---

## Enable SameAs Schema on a Social Links Block

### Enable the toggle

1. Open any post, page, or template in the Block Editor.
2. Add or select a **Social Links** block.
3. In the right sidebar (Block tab), locate the **Organization SameAs** panel.
4. Toggle **"Include in site's schema"** to ON.
5. Save or update.

The URLs from your social links are now included in the Organization JSON-LD for every page on the site.

### How URLs are collected

The plugin reads your Social Links block at page-render time:

1. It scans the block for each individual **Social Link** item and collects its URL.
2. It also scans the rendered HTML for any `<a href="...">` links, as a fallback for links added outside the standard block structure.
3. Duplicate URLs are removed automatically.
4. If you use **Reusable blocks** or **Patterns** that contain social links, the plugin follows those references to collect URLs from them as well.

Only links inside a Social Links block with the toggle enabled are collected. Regular inline links in your content are not affected.

---

## Permissions

| Action | Required capability |
|---|---|
| Toggle SameAs Schema on a social-links block | `edit_post` (on that post) |
| View structured data on frontend | None (public) |

---

## Next Steps

- **Schema** — Set global defaults per post type and override subtypes per post
- **Logo Schema** — Include your site logo in Organization structured data
- [Google Rich Results Test](https://search.google.com/test/rich-results) — External resource
