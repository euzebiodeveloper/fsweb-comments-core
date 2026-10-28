# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.9 — 2026-10-28

### Fixed

- Escaped the attributes returned by `fsweb_cc_form_attributes`; the filter
  now receives and returns an array instead of a raw attribute string.
- Sanitized custom field HTML from `fsweb_cc_form_field_html` with an explicit
  `wp_kses()` allowlist before printing it.
- Sanitized the markup returned by the reviews filters
  (`fsweb_cc_reviews_pre_render`, `fsweb_cc_reviews_shortcode_output` and
  `fsweb_cc_reviews_html`), keeping the inline SVG icons allowed.
- Removed the unused `wp-admin/includes/media.php` include from the photo
  upload handler.
- The Settings screen kept the Appearance card while the PRO add-on was
  active, so the same page offered two places to pick a theme; the card now
  belongs to the free edition only and leaves the screen with the add-on on

## 1.0.8 — 2026-10-25

First public release of the free edition, split out of the original Comments Core
plugin (now the PRO add-on).

### Added

- `fsweb_review` custom post type and `fsweb_review_category` taxonomy
- Review editor: name, email, star rating and review text
- `[fsweb_reviews]` shortcode rendering a slider by default, plus grid and list
  layouts
- Slider with arrows, dot navigation, keyboard support and 1 to 4 cards per view
- Two themes per shortcode: `default` and `dark`
- Settings screen option to pick the default theme, used when the shortcode
  does not set one
- Optional photo upload in the submission form: JPG, PNG, GIF or WebP up to
  2 MB, stored as the review featured image
- `[fsweb_review_form]` shortcode with AJAX submission
- Moderation queue: submissions are created as pending
- Admin overview with published, pending and average rating counters
- Settings screen to open or close submissions
- Every screen opens with the same gradient hero header and quick navigation
- Form Builder and Views Builder screens ship in the free edition with the
  premium controls visible but locked behind a PRO pill that links to the
  product page; the add-on removes the pills and unlocks the controls
- Theme template overrides under `fsweb-reviews/`
- Filters and actions for extending the query, the form and the markup

### Changed

- The default layout of `[fsweb_reviews]` is the slider: deactivating an add-on
  no longer falls back to a column of cards stacked one under the other
- The review card is a two-column grid: photo and name on the left, email,
  stars and review text on the right; the date is no longer printed and on
  phones the columns stack
- The photo field is the first field of the submission form and the upload no
  longer prints the JPG/PNG/GIF/WebP hint
- The grid columns come from a data attribute instead of an inline style

- The Go PRO screen and the PRO pills link to the live product page,
  `https://flowsyncweb.com/produto/comments-core-pro/`
- The free edition no longer reads a licence option: it only checks whether the
  PRO add-on is installed, so nothing here is gated by a licence
- readme.txt documents the optional add-on and states that the plugin connects
  to no external service

### Fixed

- Hovering a card in the slider no longer clips its top edge: the viewport now
  keeps room for the lift and the shadow
- The submit button spinner kept turning before any submission: the class rule
  set `display: inline-block`, which beats the `hidden` attribute
- The review list columns used inline styles; the colours now live in the admin
  stylesheet
