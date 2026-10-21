<?php
/**
 * Plugin Name: A8csp Cached 404 test links
 * Description: A Dashboard widget of links to try A8csp Cached 404 with, and a late redirect that loose mode breaks.
 */

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

add_action(
	'wp_dashboard_setup',
	static function (): void {
		wp_add_dashboard_widget(
			'a8csp_test_links',
			'Cached 404 test links',
			static function (): void {
				$links = array(
					'/product/this-is-a-cached-404/'          => 'Cached 404 when Products is ticked.',
					'/product-category/this-is-a-cached-404/' => 'Cached 404 when Product categories is ticked.',
					'/tag/this-is-a-cached-404/'              => 'Cached 404 when Tags is ticked.',
					'/this-might-be-a-page/'                  => 'WordPress 404: it could be a page. With loose mode on, it gets the cached 404.',
					'/give-something-like-this/'              => 'Late redirect to /shop/, made on template_redirect. With loose mode on, it gets the cached 404 and the redirect never runs. Add /give-something-like-this/ under Never answer to fix it.',
				);

				echo '<ul>';
				foreach ( $links as $path => $note ) {
					echo '<li><a href="' . esc_url( home_url( $path ) ) . '" target="_blank" rel="noopener"><code>' . esc_html( $path ) . '</code></a><br /><span class="description">' . esc_html( $note ) . '</span></li>';
				}
				echo '</ul>';
			}
		);
	}
);
