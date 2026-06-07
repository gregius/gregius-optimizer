# Social Cards

[Editorial: Feature: Social Cards | Pages: 1 | Total words: ~1300 | Sections: 9 (H2: 7, H3: 2) | Screenshots needed: 3 | Doc category: smo-settings | Audience: content-editor]

Control how your content appears when shared on social media and in search results. This feature lets you set **separate titles, descriptions, and images** for Google Search, Open Graph (Facebook, LinkedIn), and Twitter/X — all from your post editor.

---

## Overview

When someone shares your content on social media or finds it through search, platforms use meta tags to build a preview card. The Social Cards feature gives you control over these previews with:

- **Search Snippet (Google)** — Title and description for Google search results
- **Open Graph (Facebook, LinkedIn, etc.)** — Title, description, and image for social sharing
- **Twitter / X** — Title, description, and image for Twitter Cards

Each platform has its own set of overrides, so you can optimize separately for search and social.

<!-- IMAGE: Screenshot showing the Social Cards panel in the Block Editor sidebar with the Settings button. -->

---

## Before You Start

**Prerequisites:**
- Gregius Optimizer plugin installed and activated
- Post types must support `custom-fields` for meta overrides
- Image dimensions: OG images resize to 1200×630 px; Twitter prefers square or 2:1 ratio

---

## How to Set a Global Fallback Image

The Global Image serves as a fallback for all platform cards when no platform-specific image is set.

1. Open any post or page in the Block Editor.
2. Locate the **Social Cards** panel in the right sidebar.
3. Click **Settings** to open the configuration modal.
4. In the **Global Image** section, click the image area to open the media library.
5. Select an image. A 150×150 px thumbnail preview appears.
6. Use the kebab menu (⋮) in the top-right corner of the image to **Replace** or **Remove** it.

**How images are resolved per card:**
- Each card (OG, Twitter) checks its own image first
- If not set, it falls back to the post's featured image
- If no featured image, it uses the Global Image

There is no cascade from one card's image to another's. Each platform resolves independently.

---

## How to Customize the Search Snippet (Google)

1. Open the **Social Cards** modal.
2. In the **Search Snippet** card, you'll see a live preview styled like a Google search result.
3. The card shows:
   - A favicon and URL bar (top)
   - An editable **title** field (blue link text)
   - An editable **description** field (gray text)
4. Type directly into the fields to override the snippet.
5. Character counters below each field show your current count against the recommended maximum:
   - Title: 60 characters max
   - Description: 160 characters max

**Note:** When you set a Google title, it also updates the browser's `<title>` tag for that page, so search engines and browser tabs stay consistent.

<!-- IMAGE: Screenshot of the Google Search Snippet card showing inline editing with character counters. -->

---

## How to Customize Open Graph Cards (Facebook, LinkedIn)

1. Open the **Social Cards** modal.
2. In the **Open Graph** section, you'll see a live preview card with:
   - An image area at the top (click to select from media library)
   - A hostname label
   - An editable **title** field
   - An editable **description** field
3. Click the image area to choose or replace the OG-specific image.
4. Use the kebab menu (⋮) on the image to **Replace** or **Remove**.
5. Type directly into the title and description fields.
6. Character counters:
   - Title: 55 characters max
   - Description: 65 characters max

**Pro tip:** Open Graph images display at 1200×630 px. Use high-resolution images with this aspect ratio for best results.

---

## How to Customize Twitter / X Cards

1. Open the **Social Cards** modal.
2. In the **Twitter / X** section, you'll see a live preview card similar to OG but styled for Twitter.
3. The card includes:
   - An image area at the top (independent of the OG image)
   - A domain label
   - An editable **title** field
   - An editable **description** field
4. The Twitter card type is automatically set:
   - **Summary card** (small image) — when no image is set
   - **Summary large image** (large image) — when an image is set
5. Character counters:
   - Title: 70 characters max
   - Description: 200 characters max

Twitter image overrides are independent of OG — you can use different images for each platform.

---

## How to Reset Overrides

1. Open the **Social Cards** modal.
2. If any overrides are set, the **Reset to defaults** button becomes active.
3. Click **Reset to defaults** to clear all 9 override fields (Google, OG, Twitter titles/descriptions/images, plus the Global Image).
4. The button is disabled when no overrides exist.

Each card's empty fields use the fallback values automatically (post title, excerpt, featured image).

---

## Permissions

| Action | Required capability |
|---|---|
| Edit social meta for a post | `edit_post` (on that post) |
| Upload images via Media Library | `upload_files` |
| View meta preview | `edit_post` (on that post) |

---

## Next Steps

- **Schema** — Add structured data markup to help search engines understand your content
- **Sitemap** — Control which content appears in your XML sitemap
- **Robots.txt** — Configure crawl directives for search engines and AI bots
- Google Search Snippet documentation — External: https://developers.google.com/search/docs/appearance/structured-data/search-gallery
- Open Graph protocol — External: https://ogp.me/
- Twitter Cards documentation — External: https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards
