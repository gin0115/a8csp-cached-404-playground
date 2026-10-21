# A8csp Cached 404 Page: Playground test site

[Open the test site in WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/gin0115/a8csp-cached-404-playground/main/blueprint.json)

`blueprint.json` builds:

- WordPress (latest) on PHP 8.4, logged in as `admin`, with `/%postname%/` permalinks;
- WooCommerce and Query Monitor, active, with WooCommerce marked as set up: no setup wizard, task lists hidden, and the store live (coming soon mode off);
- Polylang and TranslatePress, installed but not active (turn on one at a time);
- WooCommerce's sample products, with their categories and tags;
- `a8csp-cached-404-page.zip` (1.0.0-RC3) unzipped into `wp-content/mu-plugins/`, with `a8csp-cached-404-page-loader.php` beside it to load it.

It opens on **Settings → Cached 404**. The plugin does nothing until it is turned on there and a URL type is ticked.

To use a newer release, replace `a8csp-cached-404-page.zip` with that release's zip.
