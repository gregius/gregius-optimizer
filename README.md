# Gregius Optimizer

**SEO, AEO, SMO, and LLMO editor extensions — all from Gutenberg sidebar panels.**

Gregius Optimizer gives content editors and site administrators a unified control panel for search, social, and AI metadata without leaving the block editor.

## At a Glance

- Toggle sitemap post types, taxonomies, and author archives
- Edit robots.txt from a modal textarea with live preview
- Assign schema.org subtypes from a 174-type map across 9 categories
- Manage per-platform social card metadata (Google, Open Graph, Twitter/X)
- Auto-generate `/llms.txt` for AI agent discoverability
- All settings persist in a dedicated database table with REST API endpoints

## Capabilities

### Sitemap
Toggle post types, taxonomies, and authors in your XML sitemap. Exclude individual posts from search engines with a single click. DB-backed overrides with filter-provided baseline defaults.

### Robots.txt
Full robots.txt editor with dynamic row sizing in a modal. Reset to WordPress defaults at any time. Built-in directives for search crawlers (Googlebot, Bingbot), AI crawlers (ChatGPT-User, ClaudeBot, PerplexityBot), and LLM trainers (GPTBot, Google-Extended).

### Schema
174 schema.org subtypes across 9 categories — Article, WebPage, CreativeWork, Event, Organization, Person, Place, Product, and Review. Assign global defaults per post type, override per post. Organization JSON-LD includes sameAs and logo. JSON preview with clipboard copy.

### Social Cards
Per-platform title, description, and image overrides for Google Search Snippets, Open Graph (Facebook, LinkedIn), and Twitter/X Cards. Inline RichText previews with character counters (Google 60/160, OG 55/65, Twitter 70/200). Global fallback image section with kebab dropdown.

### LLMs
Auto-generate `/llms.txt` from site content for AI agent discoverability. Custom global context editing with live preview. Per-post include toggle with custom descriptions.

### Architecture
- **Custom DB table** (`gg_optimizer_settings`) shared across all features
- **Filter-based architecture** — every output group can be disabled via hooks
- **REST API endpoints** for all settings: sitemap, robots, schema, social cards, LLMs
- **WordPress 6.9+ block editor** — all panels use `PluginDocumentSettingPanel` in the Gutenberg sidebar
- **Panel order**: Sitemap → Robots → Schema → Social Cards → LLMs

## Requirements

- **WordPress**: 6.9 or higher
- **PHP**: 8.2 or higher

## Quick Start

1. Upload the `gregius-optimizer` folder to `/wp-content/plugins/`
2. Activate through the Plugins screen in WordPress
3. Open any post or page in the block editor — the optimizer panels appear in the right sidebar

## How It Works

1. **Sitemap & Robots** modify WordPress core sitemap and robots filters with DB-backed overrides.
2. **Schema** outputs JSON-LD via `wp_head` using a 3-layer resolution chain: post meta → global default → hardcoded fallback.
3. **Social Cards** register 11 post meta keys with per-platform resolvers and output OG/Twitter meta tags through `wp_head`.
4. **LLMs** serves `/llms.txt` via `template_redirect`, auto-generating descriptions from post excerpt or content.
5. All panels communicate with WordPress via REST API endpoints that read/write the `gg_optimizer_settings` table.

## Documentation

- **Sitemap**: [SRS](./docs/sitemap/srs.md) · [Architecture](./docs/sitemap/architecture.md) · [Developer Docs](./docs/sitemap/developer-documentation.md)
- **Robots**: [SRS](./docs/robots/srs.md) · [Architecture](./docs/robots/architecture.md) · [Developer Docs](./docs/robots/developer-documentation.md)
- **Schema**: [SRS](./docs/schema/srs.md) · [Architecture](./docs/schema/architecture.md) · [Developer Docs](./docs/schema/developer-documentation.md)
- **Social Cards**: [SRS](./docs/social-cards/srs.md) · [Architecture](./docs/social-cards/architecture.md) · [Developer Docs](./docs/social-cards/developer-documentation.md)
- **LLMs**: [SRS](./docs/llms/srs.md) · [Architecture](./docs/llms/architecture.md) · [Developer Docs](./docs/llms/developer-documentation.md)

## Development

1. Clone this repository into `wp-content/plugins/`
2. Run `npm install` in the plugin root to install build dependencies
3. Run `npm install` in `assets/` to install editor script dependencies
4. Run `npm run build` in `assets/` to compile editor scripts
5. Run `npm run plugin-zip` from the plugin root to create a distributable zip

## Support

- **Issues**: [GitHub Issues](https://github.com/gregius/gregius-optimizer/issues)
- **Website**: [gregius.com](https://gregius.com)

## License

GPL v2 or later. See [LICENSE](./LICENSE) for details.

---

**Website**: [gregius.com](https://gregius.com) · **GitHub**: [gregius/gregius-optimizer](https://github.com/gregius/gregius-optimizer)
