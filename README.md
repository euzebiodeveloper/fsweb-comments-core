# FSWEB Comments Core

Lightweight review and feedback management for WordPress. Collect reviews with
star ratings, moderate them in the admin, and display them anywhere with a
shortcode — no page builder required.

This is the **free edition**. Premium features live in the separate
[Comments Core PRO](#pro-edition) add-on.

## Features

- `fsweb_review` custom post type with its own categories (`fsweb_review_category`)
- Review editor with name, email, star rating and review text
- Front-end submission form via `[fsweb_review_form]`
- AJAX submission, nonce protected, sanitised and escaped
- Submissions are held as **pending** until a moderator approves them
- Display reviews as a **slider** (default), **grid** or **list** via `[fsweb_reviews]`
- Two themes per shortcode: **default** and **dark**
- Photo support through the featured image, plus an optional photo upload in the submission form
- Average rating, published and pending counters in the admin overview
- Open/close submissions from **Reviews → Settings**
- Theme overrides: copy a template to `yourtheme/fsweb-reviews/slider.php`
- Translation ready (`fsweb-comments-core` text domain)

## Shortcodes

Display reviews:

```
[fsweb_reviews layout="slider" count="6" slides="3"]
```

| Attribute    | Default  | Description                                                     |
| ------------ | -------- | --------------------------------------------------------------- |
| `layout`     | `slider` | `slider`, `grid` or `list`                                       |
| `theme`      | `default`| `default` or `dark`                                             |
| `count`      | `6`      | Number of reviews (1–50)                                         |
| `slides`     | `3`      | Slider cards per view (1–4)                                      |
| `columns`    | `3`      | Grid columns (1–4)                                               |
| `category`   | —        | Category slug, comma separated                                   |
| `order`      | `newest` | `newest`, `oldest`, `random`, `menu_order`                       |
| `show_image` | `yes`    | `yes` or `no`                                                    |

Show the submission form:

```
[fsweb_review_form title="Leave a review" button="Send"]
```

## Installation

1. Copy the `fsweb-comments-core` folder into `wp-content/plugins/`.
2. Activate **FSWEB Comments Core** in *Plugins*.
3. Add reviews under **Reviews → Add New**, or publish the form shortcode and
   let visitors submit them.
4. Approve the pending submissions.

## Hooks

| Hook | Type | Purpose |
| ---- | ---- | ------- |
| `fsweb_cc_review_editor_fields` | filter | Fields on the review edit screen |
| `fsweb_cc_form_fields` | filter | Fields rendered by the submission form |
| `fsweb_cc_forms_open` | filter | Enable/disable submissions programmatically |
| `fsweb_cc_reviews_query_args` | filter | WP_Query arguments of the shortcode |
| `fsweb_cc_reviews_shortcode_output` | filter | Final shortcode markup |
| `fsweb_cc_reviews_before_loop` | action | Before the reviews loop |
| `fsweb_cc_reviews_after_loop` | action | After the reviews loop |
| `fsweb_cc_review_submitted` | action | After a front-end submission |
| `fsweb_comments_core_loaded` | action | Plugin fully booted (used by PRO) |

## PRO edition

The PRO add-on installs on top of this plugin and adds:

- Drag-and-drop **Form Builder** with extra field types and multi-step forms
- **Views Builder** — compose the review card with dynamic tags
- **Infinite slider** with autoplay, on top of the free slider
- Extra **skins** (minimal, colorful, flat) and custom CSS
- Automatic updates and priority support

## Requirements

- WordPress 6.0+
- PHP 7.4+

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).
