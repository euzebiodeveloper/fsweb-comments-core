=== FSWWP Update Checker ===
Contributors: flowsyncweb
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Self-contained update checker for WordPress plugins that use a custom update
server (e.g. a Laravel app). Ships with your plugin — no external dependency.

== Description ==

Drop-in library that enables automatic plugin updates from your own update
server. Designed to be bundled inside each plugin. Multiple plugins can include
this same library without conflicts.

Each library version uses a unique class name (e.g. FSWWP_Update_Checker_1_0).
Different plugins can ship different library versions — they coexist because
the class names are different.

== How to use ==

1. Copy the entire `fswwp-update-checker/` folder into your plugin.

2. In your main plugin file, add:

    require_once plugin_dir_path( __FILE__ ) . 'fswwp-update-checker/index.php';
    new FSWWP_Update_Checker_1_0(
        'https://your-server.com/api/updates/{slug}.json',
        __FILE__
    );

   Optionally you can pass a custom slug and check period:

    new FSWWP_Update_Checker_1_0(
        'https://your-server.com/api/updates/my-plugin.json',
        __FILE__,
        'my-custom-slug',
        6   // Check every 6 hours (default: 12)
    );

== JSON Metadata Format ==

Your update server should return JSON with at least these fields:

{
    "version": "1.1.0",
    "download_url": "https://your-server.com/downloads/my-plugin-v1.1.0.zip",
    "last_updated": "2025-01-15",
    "requires": "5.0",
    "tested": "6.7",
    "requires_php": "7.4",
    "sections": {
        "description": "<p>What this plugin does</p>",
        "changelog": "<p>Version 1.1.0 - Fixed bugs</p>"
    },
    "icons": {
        "1x": "https://your-server.com/icons/icon-128x128.png",
        "2x": "https://your-server.com/icons/icon-256x256.png"
    }
}

* `version` and `download_url` are **required**.
* All other fields are optional but improve the update experience.
* For the **Previous Versions** rollback feature, include a `versions` array.
  Each entry: `{"version": "...", "download_url": "...", "date": "...", "notes": "..."}`

== Previous Versions (Version Rollback) ==

The plugin row gains a **"Previous Versions"** action link that opens a
thickbox modal. The user can select any older version and install it.

**How it works:**

1. The metadata JSON must include a `versions` array (see format above).
2. The modal shows all versions sorted newest-first, with the installed
   version highlighted.
3. Selecting a non-current version enables the **"Install Selected Version"**
   button.
4. Clicking it triggers an AJAX request that:
   - Deactivates the plugin (if active)
   - Deletes the current plugin files
   - Downloads and installs the selected version ZIP
   - Reactivates the plugin (if it was active before)
5. The plugins page reloads to reflect the version change.

**Requirements:**

* The user must have `update_plugins` capability (admin by default).
* The server's ZIP files must be valid WordPress plugin packages.
* The server must be reachable for the download.

== Conflict Prevention ==

If a user has two plugins that both include this library:

1. **Same version → safe.** The `class_exists()` guard prevents class
   redefinition. Both plugins share the class but create separate objects
   with independent slugs, hooks, and cron schedules.

2. **Different versions → safe.** Each version has its own class name
   (`FSWWP_Update_Checker_1_0`, `FSWWP_Update_Checker_2_0`, etc.). Both
   classes coexist in memory. Each plugin instantiates the version it bundled.

4. **Each instance is independent** — `new FSWWP_Update_Checker_1_0(...)`
   creates a separate object with its own slug, hooks, cache option, and
   cron schedule.

5. **Slug uniqueness is enforced** — if two plugins accidentally use the same
   slug, a clear error is shown immediately.

6. **Hook names include the slug** — filters like `fswwp_slug_in_use_{slug}`
   and cron hooks like `fswwp_check_updates_{slug}` are namespaced per slug,
   preventing collisions.

== Changelog ==

= 1.0.0 =
* Initial release.
