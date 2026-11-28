---
type: Specification
title: "Software Requirements Specification (SRS) — Lifecycle"
description: "Functional, data, and operational requirements for plugin activation, deactivation, and uninstall data retention."
subsystem: lifecycle
standard: "ISO/IEC/IEEE 29148:2018"
tags: [lifecycle, specification]
timestamp: 2026-11-22T00:00:00Z
---

# Software Requirements Specification (SRS) — Lifecycle

**Standard:** ISO/IEC/IEEE 29148:2018

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | lifecycle |
| Version | 1.5.0 |
| Date | 2026-11-22 |
| Author | Gregius Engineering |
| Status | Approved |

---

## 1. Introduction

### 1.1 Purpose

This specification defines the activation, deactivation, and uninstall behavior
of Gregius Optimizer, with emphasis on data retention: when plugin data is
created, when it is preserved, and when it is removed.

### 1.2 Scope

In scope: the settings table, the `gg_optimizer_db_version` option, the
remove-data preference option, the sitemap-flush transient, and all
`_gg_optimizer_*` post meta. Out of scope: multisite network-wide data removal
(subsite data is intentionally orphaned on network delete).

---

## 2. Functional Requirements

| ID | Requirement |
|---|---|
| FR-01 | On activation, the plugin SHALL create the `{prefix}gg_optimizer_settings` table and store the schema version. |
| FR-02 | On deactivation, the plugin SHALL flush rewrite rules and MUST NOT remove settings or post meta. |
| FR-03 | On deactivation from the Plugins screen, the plugin SHALL present a modal asking whether deleting the plugin should remove its data. |
| FR-04 | The remove-data preference SHALL be stored as the `gg_optimizer_remove_data_on_uninstall` option (bool, default false). |
| FR-05 | On uninstall with the preference unset or false, the plugin SHALL preserve the settings table, the version option, and all `_gg_optimizer_*` post meta. |
| FR-06 | On uninstall with the preference true, the plugin SHALL drop the settings table, delete the version option, and delete all `_gg_optimizer_*` post meta for the current site. |
| FR-07 | The remove-data preference option and the sitemap-flush transient SHALL be removed on uninstall regardless of the preference value. |
| FR-08 | Bulk and command-line deactivation SHALL bypass the modal and preserve data (safe default). |

---

## 3. Data Requirements

The removal inventory for the current site is:

- Table `{prefix}gg_optimizer_settings` (DROP)
- Option `gg_optimizer_db_version` (delete)
- Option `gg_optimizer_remove_data_on_uninstall` (delete, always)
- Transient `gg_optimizer_sitemap_flush` (delete, always)
- Post meta `_gg_optimizer_*` (DELETE via `LIKE`, escaped — covers all 15 keys including revision rows)

---

## 4. Operational Requirements

- The uninstall handler SHALL be self-contained (`WP_UNINSTALL_PLUGIN` guard) and run after the plugin is no longer loaded.
- The deactivation rewrite flush SHALL loop all blogs on network-wide deactivation.
- The modal SHALL never block deactivation; Esc, outside click, and the primary button all proceed.
