# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 — 2026-10-25

First release of the free edition, split out of the original Comments Core
plugin (now the PRO add-on).

### Added

- `fsweb_review` custom post type and `fsweb_review_category` taxonomy
- Review editor: name, email, star rating and review text
- `[fsweb_reviews]` shortcode with grid and list layouts
- `[fsweb_review_form]` shortcode with AJAX submission
- Moderation queue: submissions are created as pending
- Admin overview with published, pending and average rating counters
- Settings screen to open or close submissions
- Theme template overrides under `fsweb-reviews/`
- Filters and actions for extending the query, the form and the markup
