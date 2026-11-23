---
type: DeveloperReference
title: "Developer Documentation — Lifecycle"
description: "API reference for lifecycle hooks, the data-retention REST endpoint, and the uninstall handler."
subsystem: lifecycle
standard: "ISO/IEC/IEEE 26514:2022"
tags: [lifecycle, developerreference]
timestamp: 2026-11-22T00:00:00Z
---

# Developer Documentation — Lifecycle

**Standard:** ISO/IEC/IEEE 26514:2022 — Developer Reference Documentation

---

## Overview

The lifecycle subsystem manages activation, deactivation, and uninstall. Its
single user-facing behavior is the remove-data-on-uninstall preference, which
defaults to keep (preserve) data.

## Options

| Option | Type | Default | Description |
|---|---|---|---|
| `gg_optimizer_remove_data_on_uninstall` | bool | `false` | When `true`, uninstall removes the settings table, version option, and `_gg_optimizer_*` post meta. |

## REST API

### `POST /gg-optimizer/v1/data-retention`

Persists the remove-data preference. Requires `manage_options`.

**Request body:**

```json
{ "remove_data": true }
```

**Response:**

```json
{ "remove_data": true }
```

## Hooks

| Hook | Type | Description |
|---|---|---|
| `register_activation_hook` | callback | Creates the settings table and flushes rewrites. |
| `register_deactivation_hook` | callback | Flushes rewrites (loops blogs on network-wide deactivation). |

## Uninstall

`uninstall.php` is self-contained: it guards on `WP_UNINSTALL_PLUGIN`, requires
the DB class, and calls `GG_Optimizer_DB::uninstall()`.
