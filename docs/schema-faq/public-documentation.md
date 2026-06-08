[Editorial: Feature: FAQ Schema | Pages: 1 | Total words: ~650 | Sections: 5 (H2: 5, H3: 3) | Screenshots needed: 2 | Doc category: seo-settings | Audience: content-editor]

Turn any accordion block into structured FAQ data for search engines — no code required.

When enabled, the Q&A pairs inside your accordion appear as rich results (expandable FAQ entries) directly in search.

---

## Overview

FAQ Schema markup tells search engines that a section of your page contains a list of questions and answers. Google can display this as a rich result with expandable FAQ items — increasing visibility and click-through rate.

The plugin works with the standard WordPress **Accordion** block (`core/accordion`). When you enable the FAQ toggle on an accordion, the plugin automatically:

- Reads the question from each accordion item's **heading**
- Reads the answer from each accordion item's **panel content**
- Generates the correct `FAQPage` JSON-LD markup in the page's `<head>`

No extra configuration is needed. No code, no shortcodes, no third-party services.

---

## Before You Start

### Prerequisites

- Gregius Optimizer plugin installed and activated
- A post or page containing an **Accordion** block (`core/accordion`) with your Q&A content

---

## Enable FAQ Schema on an Accordion

### Enable the toggle

1. Open any post or page in the Block Editor.
2. Add an **Accordion** block and fill in your questions and answers inside the accordion items.
3. Select the accordion block wrapper (click the accordion container, not an individual item).
4. In the right sidebar (Block tab), locate the **FAQ Schema** panel.
5. Toggle **"Include accordion Q&A in site's structured data"** to ON.
6. Save or update the post.

That is all. The FAQ structured data is generated automatically when the page loads.

### How Q&A pairs are determined

The plugin reads your accordion structure at page-render time.

**Question** — Taken from each accordion item's heading. The plugin looks for the text inside the heading's title area — the clickable label users see to expand the item. Write questions as complete, natural-language questions (e.g., "What is Gregius?") for best search engine matching.

**Answer** — Taken from all **paragraph blocks** inside the accordion item's panel. Text from every paragraph in the panel is combined into a single answer. Only standard paragraph blocks are included — lists, images, buttons, and other block types are not extracted.

**Required both** — A pair is only included in structured data when both question and answer are non-empty. Accordion items with headings but no content (or vice versa) are silently excluded.

---

## Permissions

| Action | Required capability |
|---|---|
| Toggle FAQ Schema on an accordion | `edit_post` (on that post) |
| View structured data on frontend | None (public) |

---

## Next Steps

- **Schema** — Set global defaults per post type and override subtypes per post
- **Social Cards** — Control how your content appears on social media platforms
- [Google Rich Results Test](https://search.google.com/test/rich-results) — External resource
