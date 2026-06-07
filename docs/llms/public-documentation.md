# LLMs

[Editorial: Feature: LLMs | Pages: 1 | Total words: ~1000 | Sections: 7 (H2: 6, H3: 1) | Screenshots needed: 2 | Doc category: llmo-settings | Audience: site-administrator]

Optimize your site for AI agents and large language models with an `/llms.txt` file — a structured text file that helps LLMs understand your website's content and architecture.

---

## Overview

llms.txt is a proposal for standardizing how websites provide information to AI agents at inference time. It's a simple text file at `/llms.txt` that contains:

- **Site context** — Your site's name, description, and architecture overview
- **Key Documents** — Links to important pages with brief descriptions

The LLMs feature automatically generates this file from your site's content. You can customize the context section and choose which pages appear in the Key Documents list.

### What LLMs See

When an AI agent visits your site, it can read `/llms.txt` to understand:

- What your site is about
- How your site is structured
- Which pages are most important
- What each page contains

This helps LLMs provide more accurate and relevant responses when referencing your content.

<!-- IMAGE: Screenshot of the LLMs panel in the Block Editor sidebar. -->

---

## Before You Start

**Prerequisites:**
- Gregius Optimizer plugin installed and activated
- Post types must support `custom-fields` for per-post toggles

---

## How to Customize the Global Context

The context section appears at the top of `/llms.txt` and describes your site to AI agents.

1. Open any post or page in the Block Editor.
2. Locate the **LLMs** panel in the right sidebar.
3. Click **Settings** to open the configuration modal.
4. In the **Global Context** textarea, edit the text that appears at the top of `/llms.txt`.
5. Click **Update** to save.

**Auto-generated context:** If you don't set a custom context, the plugin automatically generates one using:
- Your site title (from Settings → General)
- Your site tagline
- A summary of your homepage
- Default architecture and specification descriptions

<!-- IMAGE: Screenshot of the LLMs modal showing the Global Context textarea and Preview section. -->

---

## How to Include a Post in Key Documents

The Key Documents section lists important pages with links and descriptions for AI agents.

1. Open a post or page in the Block Editor.
2. Open the **LLMs** modal.
3. Toggle **Include the current document in site's llms.txt** to **on**.
4. (Optional) Enter a custom **Description** for the entry. If you leave it empty, the plugin uses:
   - Your custom LLMs description → Post excerpt → First 20 words of content
5. The change is saved automatically with the post.

Once enabled, the post appears in the `## Key Documents` section of `/llms.txt` as a formatted link with description.

---

## How to Preview the Full llms.txt

1. Open the **LLMs** modal.
2. Scroll to the **Preview** section at the bottom.
3. The preview shows the complete `/llms.txt` as it would appear to an AI agent.
4. The preview updates automatically as you edit the context text or toggle posts on/off.

Use the preview to verify your configuration before it goes live.

---

## How to Reset to Defaults

1. Open the **LLMs** modal.
2. Click **Reset to defaults**.
3. This clears your custom context override and reverts to the auto-generated content.
4. The button is only active when a custom override is saved.

Resetting does not affect per-post include toggles or descriptions — those are stored per post and remain unchanged.

---

## Permissions

| Action | Required capability |
|---|---|
| View /llms.txt | Public (no authentication) |
| Edit global LLMs context | `manage_options` |
| Toggle post in Key Documents | `edit_post` (on that post) |

---

## Next Steps

- **Sitemap** — Control which content appears in your XML sitemap
- **Robots.txt** — Configure crawl directives for search engines and AI bots
- **Schema** — Add structured data markup to help search engines understand your content
- llmstxt.org — External: https://llmstxt.org/
