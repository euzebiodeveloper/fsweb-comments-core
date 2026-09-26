# 1.1.0 - 26/09/2026
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
