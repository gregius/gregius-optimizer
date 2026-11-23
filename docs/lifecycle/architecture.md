---
type: Architecture
title: "Architecture Description — Lifecycle"
description: "Architecture views, ADRs, constraints, and risks for plugin activation, deactivation, and uninstall data retention."
subsystem: lifecycle
standard: "ISO/IEC/IEEE 42010:2022"
tags: [lifecycle, architecture]
timestamp: 2026-11-22T00:00:00Z
---

# Architecture Description — Lifecycle

**Standard:** ISO/IEC/IEEE 42010:2022 — Architecture Description

---

## Document Information

| Field | Value |
|---|---|
| Project | Gregius Optimizer |
| Software Component | lifecycle |
| Version | 1.4.0 |
| Date | 2026-11-22 |
| Author | Gregius Engineering |
| Status | Approved |

---

## 1. Context

Gregius Optimizer stores settings in a custom table and per-post metadata in
standard post meta. Its lifecycle is governed by three WordPress hooks plus a
self-contained uninstall handler.

## 2. Component View

| Component | Responsibility |
|---|---|
| `GG_Optimizer_DB::maybe_create_tables()` | Activation — create/upgrade the settings table. |
| `register_deactivation_hook` closure | Flush rewrites; never delete data. |
| `GG_Optimizer_Data_Retention` | REST endpoint persisting the remove-data preference. |
| Deactivation modal (`assets/src/scripts/deactivation-modal.js`) | Collect the preference at deactivation time. |
| `uninstall.php` → `GG_Optimizer_DB::uninstall()` | Remove (or preserve) data on plugin deletion. |

## 3. Architecture Decision Records

### AD-01 — Deletion at uninstall, not deactivation

WordPress requires deactivation to be non-destructive. Data removal is
deferred to uninstall, surfaced only through a deactivation modal.

### AD-02 — Preference stored as a plain option

The remove-data preference lives in `gg_optimizer_remove_data_on_uninstall`
rather than the custom table, so it remains readable even if the table is
missing during uninstall.

### AD-03 — Current-site-only deletion

Removal targets the current site only. Multisite subsite data is intentionally
orphaned on network delete — documented, not fixed.

## 4. Risks & Constraints

- Core shows "delete its data" whenever `uninstall.php` exists, regardless of
  the preference. Mitigated via modal copy and the readme FAQ.
- Post-meta `DELETE` does not invalidate object cache; irrelevant because the
  plugin is already removed.
