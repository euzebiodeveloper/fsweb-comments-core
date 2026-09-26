=== Comments Core ===
Contributors: flowsyncweb
Donate link: https://flowsyncweb.com/
Tags: comments, feedback, reviews, spam
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, high-performance comment and feedback management system designed for seamless integration and developer-friendly customization.

== Description ==

Comments Core is a modern comment and feedback management plugin built for WordPress. It replaces the default comment system with a flexible, extensible solution that supports custom fields, review forms, star ratings, and dynamic display views — all managed through an intuitive admin interface.

Key features:

* **Custom Form Builder** — Drag-and-drop builder to create review and feedback forms with text, email, URL, date, select, rating, and image fields.
* **Review Editor** — Manage and moderate reviews from a dedicated admin panel with inline editing.
* **Display Views** — Design how reviews appear on the front end with a visual layout builder. Choose grid, list, or masonry layouts.
* **Multi-step Forms** — Break long forms into manageable steps for better user experience.
* **Star Ratings** — Built-in star rating field with customizable appearance.
* **Image Uploads** — Allow users to attach images to their reviews.
* **Spam Protection** — Integrated anti-spam measures to keep your reviews clean.
* **Developer Friendly** — Hooks, filters, and a clean codebase for easy customization.

== Installation ==

1. Upload the `fsweb-comments-core` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Comments Core → Forms** to create your first review form.
4. Go to **Comments Core → Views** to design how reviews display.
5. Use the `[fsweb_review_form id="your-form-id"]` shortcode to embed forms on any page.

== Frequently Asked Questions ==

= How do I display reviews on my site? =

Create a View under **Comments Core → Views**, then embed it with the `[fsweb_review_view id="your-view-id"]` shortcode.

= Can I customize the appearance of reviews? =

Yes, the View builder includes style controls for colors, fonts, spacing, and layout. You can also add custom CSS.

= Does this work with any theme? =

Comments Core is designed to work with any well-coded WordPress theme. Some themes may require minor CSS adjustments.

== Screenshots ==

1. Form builder interface — drag and drop fields to create custom review forms.
2. Review editor — manage and moderate submitted reviews.
3. View builder — design how reviews appear on the front end.
4. Front-end review display with star ratings.

== Changelog ==

= 1.0.2 =
* New: Hero with quick-action shortcuts on every admin screen: dashboard, form builder, views builder and license.
* New: License screen rebuilt in the family's visual language — full width, with shortcut cards.
* New: Status pills, stat cards and buttons aligned with the FSWEB family design vocabulary.
* New: New admin interface strings translated to pt_BR; the .pot, .po and .mo catalogs were regenerated.
* New: License management simplified — the key is only activated or deactivated; the manual verification button and endpoint are gone.
* Fix: Hero shortcuts that returned "Cannot load ..." on the review screens now use the correct menu URL.

= 1.0.1 =
* Fix: Readme parser accepts readme.txt (lowercase) as well as README.txt, so the View details popup shows every section again.
* New: Update-checker cache cleared when a license is activated, so stale license or server errors stop blocking automatic updates.

= 1.0.0 =
* Initial release.
* Custom form builder with 8 field types.
* Review management and moderation.
* Display view builder with grid, list, and masonry layouts.
* Star ratings and image uploads.

== Upgrade Notice ==

= 1.0.2 =
UI refresh on every admin screen (hero shortcuts, cards and pills), complete pt_BR translation and a fix for the hero shortcuts on the review screens.

= 1.0.1 =
Maintenance release: the plugin details popup shows all sections again and the licence activation now clears stale update errors.

= 1.0.0 =
Initial release.
