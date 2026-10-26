=== FSWEB Comments Core ===
Contributors: flowsyncweb
Tags: reviews, testimonials, feedback, ratings, shortcode
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Collect reviews with star ratings, moderate them and display them anywhere with a shortcode — no page builder required.

== Description ==

FSWEB Comments Core is a lightweight review and feedback manager for WordPress. Visitors submit reviews through a simple form, you approve them, and the shortcodes print them anywhere on the site.

**Free features**

* `fsweb_review` custom post type with its own categories
* Review editor with name, e-mail, star rating and review text
* Front-end submission form via `[fsweb_review_form]`
* AJAX submission, nonce protected, sanitized and escaped
* Submissions are held as **pending** until a moderator approves them
* Slider (default), grid and list layouts via `[fsweb_reviews]`
* Default and dark themes, chosen per shortcode
* Photo support through the featured image, including an optional upload in the submission form
* Average rating and post counters in the admin overview
* Open or close submissions from Reviews → Settings
* Theme overrides: copy a template to `yourtheme/fsweb-reviews/slider.php`
* Translation ready

**Optional add-on**

The free edition is complete, has no time limit and no locked features. An optional PRO add-on, distributed from our own site and **not included in this package**, adds the form builder, the views builder, the infinite slider with autoplay, the extra skins and automatic updates. None of that code ships here.

**Shortcodes**

`[fsweb_reviews]` attributes: `layout` (slider|grid|list), `theme` (default|dark), `count` (1–50), `slides` (1–4), `columns` (1–4), `category`, `order` (newest|oldest|random|menu_order), `show_image` (yes|no).

`[fsweb_review_form]` attributes: `title`, `button`. Visitors can attach an optional photo (JPG, PNG, GIF or WebP, up to 2 MB); it becomes the review featured image after moderation.

**Hooks for developers**

* `fsweb_cc_review_editor_fields` (filter)
* `fsweb_cc_form_fields` (filter)
* `fsweb_cc_forms_open` (filter)
* `fsweb_cc_reviews_query_args` (filter)
* `fsweb_cc_reviews_shortcode_output` (filter)
* `fsweb_cc_reviews_before_loop` / `fsweb_cc_reviews_after_loop` (actions)
* `fsweb_cc_review_submitted` (action)
* `fsweb_comments_core_loaded` (action)

== Installation ==

1. Upload the `fsweb-comments-core` folder to `/wp-content/plugins/`.
2. Activate **FSWEB Comments Core** in the *Plugins* screen.
3. Add reviews under **Reviews → Add New**, or publish the form shortcode and let visitors submit them.
4. Approve the pending submissions.

== Privacy ==

The free plugin does not connect to any external service and does not send any data out of the site. The reviews, names and e-mails submitted through the form are stored in the site's own database; the e-mail is visible only in the WordPress admin.

== Frequently Asked Questions ==

= Does the free version have a time limit? =

No. The free edition is fully functional and has no expiry.

= How do I display the reviews? =

Paste `[fsweb_reviews]` in any post, page or widget. It renders the slider by default; use `layout="grid"` or `layout="list"` for the other formats and `theme="dark"` for the dark cards.

= Where do the submissions go? =

They are stored as `fsweb_review` posts with the **pending** status until an administrator approves them.

== Screenshots ==

1. Overview screen with counters and quick actions.
2. Review editor with star rating.
3. Reviews displayed with the slider shortcode.

== Changelog ==

= 1.0.8 =
* Initial release: review post type, submission form, slider, grid and list shortcodes, admin screens.
* The slider is the default layout, with arrows, dot navigation and keyboard support.
* Default and dark themes, selectable per shortcode.
* Optional PRO add-on documented; no feature is held behind a licence in this edition.

== Upgrade Notice ==

= 1.0.8 =
Initial release.
