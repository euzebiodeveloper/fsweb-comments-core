# 1.0.6 - 26/09/2026
  * Fix: the license screen now trusts `domain_authorized` from the /validate response instead of the license `status` — a key released on this domain keeps answering `active`, so trusting the license status brought the screen back "Active" right after a deactivation. The local status now reads Active only when this domain is authorized, and Pending otherwise (expired/invalid keys keep their own status).
  * Fix: `deactivate()` also clears the cached license/update state (update-checker metadata, README transient and the WordPress update_plugins transient) so no stale "active" metadata survives the release.

# 1.0.5 - 26/09/2026
  * Fix: the license expiry is always rendered as a date in UTC — the license screen and the activation AJAX response no longer print the raw ISO string, and the value no longer shifts one day back on UTC-3 sites (2027-09-11T00:00:00+00:00 reads 11/09/2027 on the first render and after the refresh alike).
  * Fix: the license key field placeholder now shows an example in the real key format — four groups of eight characters, no FSWEB- prefix.

# 1.0.4 - 26/09/2026
  * Fix: license activation and deactivation no longer depend on another FSWEB plugin's constants — each plugin now resolves the license API URL from its own constant, so an outdated sibling plugin can no longer point this one at the retired endpoint.

# 1.0.3 - 26/09/2026
  * Fix: License activation and deactivation now call the FSWEB license API (api.flowsyncweb.com/api/license) instead of the retired License Manager for WooCommerce bridge, which answered "License Manager for WooCommerce is not active."
  * Fix: The API response body is now the source of truth for license outcomes; transport failures (unreachable server or 5xx) keep the current license state and show a neutral notice instead of an alarming error.

# 1.0.2 - 26/09/2026
  * New: Hero with quick-action shortcuts on every admin screen — dashboard, form builder, views builder and license.
  * New: License screen rebuilt in the family's visual language, full width, with shortcut cards.
  * New: Status pills, stat cards and buttons aligned with the FSWEB family design vocabulary.
  * New: New admin interface strings translated to pt_BR; the .pot, .po and .mo catalogs were regenerated.
  * New: License management simplified — the key is only activated or deactivated; the manual verification button and endpoint are gone.
  * Fix: Hero shortcuts that returned "Cannot load ..." on the review screens now use the correct menu URL.

# 1.0.1 - 22/09/2026
  * Fix: Readme parser now accepts `readme.txt` (lowercase) as well as `README.txt`, so the "View details" popup shows every section again.
  * New: The update-checker cache is cleared when a license is activated, so stale license or server errors stop blocking automatic updates.

# 1.0.0 - 13/07/2026
  * New: Initial release.
  * Drag-and-drop Form Builder with 8 field types (text, textarea, email, URL, date, select, rating, image).
  * Multi-step forms and star ratings.
  * Image uploads with featured-image support.
  * Views Builder with grid, slider, and list layouts, skins, and custom CSS.
  * Review moderation queue (submissions are held as pending).
  * License activation and automatic updates.
  * Shortcodes: [fsweb_reviews] and [fsweb_review_form].
