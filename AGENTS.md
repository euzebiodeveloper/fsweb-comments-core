# AGENTS.md — fsweb-comments-core

> WordPress plugin: Comments Core — lightweight comment & feedback management.  
> Stack: PHP 7.0+, WordPress 5.0+, no build step, no Composer.

## Project
WordPress plugin following the WP Plugin Boilerplate structure.  
Entry point: `fsweb-comments-core.php`. No autoloader — all deps loaded with `require_once`.

## Commands
No build/test/lint tooling configured. PHP lint: `php -l <file>`.

## Architecture

| Dir | Role |
|---|---|
| `fsweb-comments-core.php` | Bootstrap: defines version constant, hooks activation/deactivation, loads core class, instantiates Update Checker, calls `run()` |
| `includes/` | Core business logic: `Loader` (hook registry), `i18n`, `Activator`, `Deactivator`, main `Fsweb_Comments_Core` orchestrator |
| `admin/` | Admin-facing hooks, CSS, JS, partials. Class: `Fsweb_Comments_Core_Admin` |
| `public/` | Public-facing hooks, CSS, JS, partials. Class: `Fsweb_Comments_Core_Public` |
| `fsweb-update-checker/` | Self-contained update-checker library (SOLID). `FSWEB_Update_Checker_1_0` facade, internal classes in `includes/` |
| `languages/` | `.pot` translation template |
| `.github/workflows/` | CI: `build-zip.yml` — on PR merge, generates `fsweb-comments-core.zip` artifact |

## Conventions

### SOLID
- **S**ingle responsibility: each class has one reason to change (Admin ≠ Public ≠ Loader ≠ i18n).
- **O**pen/closed: extend via hooks (`add_action`/`add_filter`), don't modify core classes.
- **L**iskov: classes are concrete and final by default — only subclass if a hook system is designed for it.
- **I**nterface segregation: no fat interfaces — use the Loader for hook binding, keep classes focused.
- **D**ependency inversion: inject `$plugin_name` and `$version` via constructor; never hardcode dependencies.

### WordPress Security (non-negotiable)

| Rule | Do | Never |
|---|---|---|
| **Sanitize inputs** | `sanitize_text_field()`, `sanitize_email()`, `sanitize_key()`, `wp_kses()` | Use raw `$_GET`/`$_POST`/`$_REQUEST` |
| **Escape outputs** | `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` | Echo raw HTML/data |
| **Unslash** | `wp_unslash()` before sanitizing superglobals | `stripslashes()` or raw access |
| **Nonces** | `wp_nonce_field()`, `check_admin_referer()`, `wp_verify_nonce()` | Process form submits without nonce |
| **Capabilities** | `current_user_can()` checks on admin pages | Assume user is authorized |
| **DB queries** | Use `$wpdb->prepare()` for dynamic SQL | String interpolation in SQL |
| **Direct access** | `if ( ! defined( 'WPINC' ) ) { die; }` at top of standalone `.php` files | Leave PHP files callable directly |

### JS / CSS (non-negotiable)
- **ALWAYS** use `wp_enqueue_script()` / `wp_enqueue_style()` on the `wp_enqueue_scripts` or `admin_enqueue_scripts` hooks.
- **NEVER** put `<script>`, `<style>`, inline `onclick`/`onload` inside PHP strings or template partials.
- **NEVER** use `wp_head` / `wp_footer` hooks to echo raw `<script>` tags.

### PHP Style
- **Classes**: `Pascal_Snake_Case` — `Fsweb_Comments_Core_Admin`
- **Functions**: `snake_case` — `run_fsweb_comments_core()`
- **Constants**: `UPPER_SNAKE_CASE` — `FSWEB_COMMENTS_CORE_VERSION`
- **Visibility**: always explicit (`public`, `protected`, `private`)
- **Docblocks**: every class, method, and property gets a PHPDoc with `@since`, `@param`, `@return`, `@var`
- **File header**: every `.php` file gets the boilerplate block with `@package`, `@subpackage`, `@since`

### Internationalization
- Text domain: `fsweb-comments-core`
- `__()`, `_e()`, `esc_html__()`, `esc_attr__()` with the text domain as 2nd argument.
- No hardcoded English strings in UI output.

### File Structure
- `index.php` in every subdirectory (empty, prevents directory listing).
- Partials (HTML fragments) go in `*/partials/`.
- CSS/JS in `*/css/` and `*/js/` respectively.

## Notes
- Update Checker library (`fsweb-update-checker/`) is versioned by class name (`FSWEB_Update_Checker_1_0`) to avoid conflicts when multiple plugins bundle it.
