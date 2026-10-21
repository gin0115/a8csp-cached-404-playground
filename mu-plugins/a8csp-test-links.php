<?php
/**
 * Plugin Name: A8csp Cached 404 test links
 * Description: A Dashboard widget of links to try A8csp Cached 404 with.
 */

defined( 'ABSPATH' ) || exit;

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
