# 💬 Comments Core

[![WordPress](https://img.shields.io/badge/wordpress-6.0%2B-blue?style=flat-square)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.0%2B-purple?style=flat-square)](https://www.php.net/)
[![Version](https://img.shields.io/badge/version-1.0.6-orange?style=flat-square)](https://flowsyncweb.com/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square)](https://opensource.org/licenses/GPL-2.0)

**Contributors:** [flowsyncweb](https://github.com/flowsyncweb)  
**Website:** [flowsyncweb.com](https://flowsyncweb.com/)  
**Tags:** comments, feedback, reviews, spam, testimonials  
**Requires at least:** 6.0  
**Tested up to:** 7.0  
**Stable version:** 1.0.6  
**License:** GPLv2 or later

A lightweight, high-performance comment and feedback management system designed for seamless integration and developer-friendly customization.

---

## ✨ Features

- 🧱 **Drag-and-drop Form Builder** — Build review and feedback forms with 8 field types: text, textarea, email, URL, date, select, star rating, and image upload
- 🎨 **Views Builder** — Design how reviews appear with a visual builder: rows, columns, and reorderable components with per-element style controls
- 🖼️ **Multiple Layouts** — Grid, slider, and list layouts
- 🎭 **Skins & Custom CSS** — Dark, minimal, colorful, and flat skins, or write your own custom CSS
- ⭐ **Star Ratings** — Built-in star rating field with customizable appearance
- 📷 **Image Uploads** — Let visitors attach photos to their reviews
- 🪜 **Multi-step Forms** — Break long forms into manageable steps for a better user experience
- 🛡️ **Moderation & Spam Control** — Submissions enter a pending queue for your approval
- 🔐 **License & Automatic Updates** — Activate a license key to receive one-click updates from your own server
- 🧩 **Developer Friendly** — Hooks, filters, and theme template overrides for easy customization

---

## 📝 Description

**Comments Core** is a modern comment and feedback management plugin built for WordPress. It replaces the default comment system with a flexible, extensible solution that supports custom fields, review forms, star ratings, and dynamic display views — all managed through an intuitive admin interface.

Perfect for product reviews, testimonials, business feedback, or any site that wants to collect and showcase visitor reviews. With a drag-and-drop form builder and a visual views builder, you can create and style review experiences without touching a single line of code.

---

## ⚙️ How to Use

### 📋 Display Reviews
```
[fsweb_reviews]
```

**Available Parameters:**
- `id` - Saved view ID from the Views Builder (uses the view's settings)
- `layout` - `grid` (default), `slider`, or `list`
- `count` - Number of reviews to display (default: 6, max: 50)
- `category` - Filter by category slug
- `order` - `newest` (default), `oldest`, `random`, or `menu_order`
- `show_image` - Show/hide review images (`yes`/`no`)

**Example:**
```
[fsweb_reviews id="default" layout="grid" count="9" category="testimonials"]
```

### 📝 Review Submission Form
```
[fsweb_review_form]
```

**Available Parameters:**
- `id` - Form ID from the Form Builder (defaults to the first available form)

**Example:**
```
[fsweb_review_form id="default"]
```

### 🚀 Quick Setup
1. Activate your license under **Reviews → License**
2. Create a form in **Reviews → Form Builder**
3. Design the display in **Reviews → Views Builder**
4. Copy the generated shortcode and paste it on any page
5. Publish and start collecting reviews!

---

## 📦 Installation

1. Upload the `fsweb-comments-core` folder to the `/wp-content/plugins/` directory (or install the ZIP via **Plugins → Add New → Upload Plugin**).
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Reviews → License** and activate your license key.
4. Build your form and view, then embed the shortcodes — done!

---

## 🎯 Use Cases

- 🛍️ **eCommerce:** Product reviews and ratings
- ⭐ **Testimonials:** Social proof for your business
- 🍽️ **Restaurants & Local:** Venue reviews with photos
- 🏢 **Corporate Sites:** Customer feedback and satisfaction surveys
- 📰 **Portals:** Reader comments and community feedback
- 🎓 **Educational Sites:** Course and instructor reviews

---

## 🔧 Minimum Requirements

- WordPress 6.0 or higher
- PHP 7.0 or higher
- A valid license key (for automatic updates and front-end rendering)

---

## 🧩 Dependencies

Comments Core ships with **no third-party front-end dependencies**:

- **jQuery UI** — the drag-and-drop builders use the jQuery UI library bundled with WordPress core.
- **FSWEB Update Checker** — a self-contained, versioned library bundled inside the plugin that powers license-based automatic updates from your own server.

---

## 📖 Documentation & Support

- [Plugin Website](https://flowsyncweb.com/)
- [Support](https://flowsyncweb.com/)
- [Changelog](CHANGELOG.md)

---

## 📢 Turn your comments section into a full review system with Comments Core!
