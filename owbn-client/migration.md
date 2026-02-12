# OWBN Client v3.0.0 — Manager v2 Compliance Migration

## Context

The owbn-chronicle-plugin (manager) was refactored to v2.0.0 with a generic entity registry, unified save/validate/API handlers, and consolidated settings in `cc-settings.php`. The owbn-client plugin currently has its OWN duplicate set of settings (`owbn_owc_enable_chronicles`, `owbn_owc_chronicles_mode`, etc.) that are completely independent from the manager's settings (`owbn_enable_chronicles`, `owbn_chronicles_mode`, etc.).

On a site where both plugins are installed (like studiodev), this creates confusion — the admin has to configure the same enable/mode/API settings in TWO places. The client settings are unconfigured on studiodev, which is why the /chronicles/ page shows nothing.

**Goal**: Make the client delegate to the manager's settings when both plugins are on the same site. Client keeps its own settings only for standalone deployments (remote-only sites without the manager installed).

## Working Directory

Development workspace: `/Users/greghacke/Development/owbn-dev/owbn-client/owbn-client-v2/`
— Copy current client into `owbn-client-v2/` as working space (same pattern as chronicle plugin refactor)
— Deploy to studiodev when ready for testing (replace `/Users/greghacke/Studio/studiodev/wp-content/plugins/owbn-client/`)
— Source (v2.1.1 baseline): `/Users/greghacke/Development/owbn-dev/owbn-client/owbn-client/`

## Phase 1: Settings Delegation (client-register.php)

**File**: `includes/core/client-register.php`

Add manager detection and settings delegation:

```
owc_manager_active(): bool
  — returns true if owbn-chronicle-manager plugin is active
  — check: function_exists('owbn_get_entity_types') or class/function from the manager

owc_get_effective_option(string $key, $default = false)
  — if manager active AND key is for chronicles/coordinators, read from manager options
  — mapping: 'enable_chronicles' → 'owbn_enable_chronicles'
  — mapping: 'chronicles_mode' → 'owbn_chronicles_mode'
  — mapping: 'chronicles_url' → 'owbn_chronicles_remote_url'
  — mapping: 'chronicles_api_key' → 'owbn_chronicles_api_key' (for local serving)
                                   or 'owbn_chronicles_remote_key' (for remote consuming)
  — mapping: same pattern for coordinators
  — territories: always use client's own options (different manager plugin)
  — if manager NOT active, fall through to get_option(owc_option_name($key), $default)
```

Update existing functions to use delegation:

```
owc_chronicles_enabled() → use owc_get_effective_option('enable_chronicles')
owc_coordinators_enabled() → use owc_get_effective_option('enable_coordinators')
owc_get_mode($type) → use owc_get_effective_option($type . '_mode', 'local')
```

## Phase 2: API Functions (client-api.php)

**File**: `includes/core/client-api.php`

Update cached fetch functions to read URLs and API keys through the delegation layer:

- `owc_get_chronicles()` remote branch: read URL and key via `owc_get_effective_option()`
- `owc_get_coordinators()` remote branch: same
- `owc_get_chronicle_detail()` remote branch: same
- `owc_get_coordinator_detail()` remote branch: same
- Territory functions: no change (different manager plugin)

No changes to local fetch functions — they query the WP database directly using post types and meta fields that haven't changed between manager v1 and v2.

## Phase 3: Settings Page (settings.php)

**File**: `includes/admin/settings.php`

When manager is active:
- Show an info banner at the top of Chronicles section: "Chronicle settings are managed by the C&C Plugin. Go to Settings > C&C Plugin to configure."
- Same banner for Coordinators section
- Hide the enable/mode/URL/key fields for chronicles and coordinators (they'd be ignored anyway)
- Keep Territory settings fully editable (different manager)
- Keep page assignment dropdowns visible for all types (those are client-specific)
- Keep cache settings visible

When manager is NOT active:
- Show all settings as currently designed (no change)

## Phase 4: Version Bump

**File**: `owbn-client.php`
- Update version header to 3.0.0
- Update version constant to 3.0.0

## Files Changed Summary

| File | Change |
|------|--------|
| `owbn-client.php` | Version bump to 3.0.0 |
| `includes/core/client-register.php` | Add `owc_manager_active()`, `owc_get_effective_option()`, update enabled/mode helpers |
| `includes/core/client-api.php` | Update remote fetch to use `owc_get_effective_option()` for URL/key |
| `includes/admin/settings.php` | Add manager-active detection banner, hide managed fields |

## Files NOT Changed

- All render files (render-chronicles-list.php, etc.) — data structure unchanged
- shortcodes.php — no changes needed
- rewrites.php — no changes needed
- activation.php — no changes needed
- data-fetch.php — no changes needed
- Territory-related code — different manager plugin, untouched

## Manager Option → Client Option Mapping

| Client Key | Manager Option (when active) |
|------------|------------------------------|
| `enable_chronicles` | `owbn_enable_chronicles` |
| `chronicles_mode` | `owbn_chronicles_mode` |
| `chronicles_api_key` | `owbn_chronicles_remote_key` (remote mode) |
| `chronicles_url` | `owbn_chronicles_remote_url` |
| `enable_coordinators` | `owbn_enable_coordinators` |
| `coordinators_mode` | `owbn_coordinators_mode` |
| `coordinators_api_key` | `owbn_coordinators_remote_key` (remote mode) |
| `coordinators_url` | `owbn_coordinators_remote_url` |

Note: When the manager is active and mode is "local", the client reads directly from the local database — no URL/key needed.

## Verification

1. **Studiodev (both plugins installed, local mode)**:
   - Visit /chronicles/ — should show all 167 published chronicles
   - Visit /chronicles/{slug}/ — should show full chronicle detail
   - Visit /coordinators/ — should show all 45 coordinators grouped by type
   - Client settings page should show banner for chronicles/coordinators
   - No client-side configuration needed for chronicles/coordinators

2. **API test**:
   - `POST /wp-json/owbn-cc/v1/entities/chronicle/list` with API key — should return data
   - Clear cache and verify fresh data loads

3. **Standalone client test** (future — when manager not installed):
   - Client settings page shows full settings form
   - Client uses its own options for enable/mode/URL/key

## Risk Assessment

- **Low risk**: Local fetch functions (`owc_get_local_chronicles()`, etc.) don't change — they query post types and meta fields directly, which are identical between manager v1 and v2
- **Low risk**: Render files untouched — data arrays have the same shape
- **Medium risk**: Settings delegation logic must correctly detect manager presence and map options — thorough testing on studiodev required
- **No risk to territories**: Completely separate code path and manager plugin
