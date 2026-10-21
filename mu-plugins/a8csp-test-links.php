<?php
/**
 * Plugin Name: A8csp Cached 404 test links
 * Description: A Dashboard widget of links to try A8csp Cached 404 with, saying what each should get from the current settings and checking it live, and a late redirect that loose mode breaks.
 */

use A8C\SpecialProjects\A8cspCached404Page\Settings\Settings;

defined( 'ABSPATH' ) || exit;

// A late redirect, made just in time on template_redirect the way redirect plugins do it.
add_action(
	'template_redirect',
	static function (): void {
		$home    = trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		$request = trailingslashit( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ) );
		if ( $home . 'give-something-like-this/' === $request ) {
			wp_safe_redirect( home_url( '/shop/' ), 301 );
			exit;
		}
	}
);

/**
 * What a link should get with the saved settings: `early:<header>`, `native` or `redirect`.
 *
 * @param Settings    $settings The saved settings.
 * @param string      $path     The link's path.
 * @param string|null $shortcut The URL type that answers it, or null for loose mode only.
 * @param bool        $redirect Whether WordPress redirects it.
 *
 * @return array{0: string, 1: string} The expected result and why.
 */
function a8csp_test_links_expect( Settings $settings, string $path, ?string $shortcut, bool $redirect ): array {
	$wordpress = $redirect ? array( 'redirect', 'Redirected to /shop/ by the late redirect.' ) : array( 'native', 'WordPress 404.' );

	if ( ! $settings->is_enabled() ) {
		return array( $wordpress[0], $wordpress[1] . ' The plugin is off.' );
	}

	foreach ( $settings->excluded() as $excluded ) {
		if ( str_starts_with( $path, $excluded ) ) {
			return array( $wordpress[0], $wordpress[1] . ' It is under Never answer.' );
		}
	}

	if ( null !== $shortcut ) {
		$types = $settings->shortcuts();
		if ( ! isset( $types[ $shortcut ] ) ) {
			return array( $wordpress[0], $wordpress[1] . ' This site has no ' . $shortcut . ' URL type.' );
		}
		if ( $types[ $shortcut ]['enabled'] ) {
			return array( 'early:' . $shortcut, 'Cached 404: ' . $types[ $shortcut ]['label'] . ' is ticked.' );
		}
		if ( ! $settings->loose() ) {
			return array( $wordpress[0], $wordpress[1] . ' ' . $types[ $shortcut ]['label'] . ' is not ticked.' );
		}
	}

	if ( $settings->loose() ) {
		return array( 'early:loose', 'Cached 404: loose mode is on.' . ( $redirect ? ' The redirect never runs. Add the path under Never answer to fix it.' : '' ) );
	}

	return array( $wordpress[0], $wordpress[1] . ( null === $shortcut ? ' Loose mode is off, and it could be a page.' : '' ) );
}

add_action(
	'wp_dashboard_setup',
	static function (): void {
		if ( ! class_exists( Settings::class ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'a8csp_test_links',
			'Cached 404 test links',
			static function (): void {
				$settings = Settings::load();
				$links    = array(
					array( '/product/this-is-a-cached-404/', 'post_type:product', false ),
					array( '/product-category/this-is-a-cached-404/', 'taxonomy:product_cat', false ),
					array( '/tag/this-is-a-cached-404/', 'taxonomy:post_tag', false ),
					array( '/this-might-be-a-page/', null, false ),
					array( '/give-something-like-this/', null, true ),
				);

				echo '<p>Now: the plugin is <strong>' . ( $settings->is_enabled() ? 'on' : 'off' ) . '</strong>, loose mode is <strong>' . ( $settings->loose() ? 'on' : 'off' ) . '</strong>. Change them under <a href="' . esc_url( admin_url( 'options-general.php?page=a8csp-cached-404' ) ) . '">Settings → Cached 404</a>, then reload this page.</p>';
				echo '<ul class="a8csp-test-links">';
				foreach ( $links as list( $path, $shortcut, $redirect ) ) {
					list( $expect, $why ) = a8csp_test_links_expect( $settings, $path, $shortcut, $redirect );
					echo '<li data-expect="' . esc_attr( $expect ) . '"><a href="' . esc_url( home_url( $path ) ) . '" target="_blank" rel="noopener"><code>' . esc_html( $path ) . '</code></a><br />';
					echo '<span class="description">Should get: ' . esc_html( $why ) . '</span><br />';
					echo '<strong data-result>Checking…</strong></li>';
				}
				echo '</ul>';
				?>
				<script>
				document.querySelectorAll( '.a8csp-test-links [data-expect]' ).forEach( async ( item ) => {
					const result = item.querySelector( '[data-result]' );
					try {
						const response = await fetch( item.querySelector( 'a' ).href, { method: 'HEAD', cache: 'no-store' } );
						const header = response.headers.get( 'x-a8csp-404' ) || '';
						const got = response.redirected ? 'redirect' : ( header ? 'early:' + header : 'native' );
						const text = response.redirected
							? 'Got: redirected to ' + new URL( response.url ).pathname
							: ( header ? 'Got: cached 404 (X-A8CSP-404: ' + header + ')' : 'Got: WordPress, status ' + response.status );
						const matched = got === item.dataset.expect;
						result.textContent = text + ( matched ? ', as expected.' : ', NOT as expected.' );
						result.style.color = matched ? '#007017' : '#d63638';
					} catch ( error ) {
						result.textContent = 'Could not check.';
					}
				} );
				</script>
				<?php
			}
		);
	}
);
