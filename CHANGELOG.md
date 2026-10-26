# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- The grid columns come from a data attribute instead of an inline style

- The Go PRO screen and the PRO pills link to the live product page,
  `https://flowsyncweb.com/produto/comments-core-pro/`
- The free edition no longer reads a licence option: it only checks whether the
  PRO add-on is installed, so nothing here is gated by a licence
- readme.txt documents the optional add-on and states that the plugin connects
  to no external service

### Fixed

- The review list columns used inline styles; the colours now live in the admin
  stylesheet
