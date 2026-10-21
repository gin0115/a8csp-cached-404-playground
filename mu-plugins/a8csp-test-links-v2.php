<?php
/**
 * Plugin Name: A8csp Cached 404 test links
 * Description: A Dashboard widget with a link for every URL pattern A8csp Cached 404 knows on this site, each saying what the current settings should give it, and a late redirect that loose mode breaks.
 */

use A8C\SpecialProjects\A8cspCached404Page\Detection\Detector;
use A8C\SpecialProjects\A8cspCached404Page\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * The saved settings with this site's URL types detected now, as the settings page shows them.
 * Detected types are only stored when a box is saved, so a new site has none stored.
 *
 * @return Settings
 */
function a8csp_test_links_settings(): Settings {
	return ( new Detector() )->detect( Settings::load() );
}

// A late redirect, made just in time on template_redirect the way redirect plugins do it.
add_action(
	'template_redirect',
	static function (): void {
		$home    = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		$request = trailingslashit( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ) );
		if ( $home . '/give-something-like-this/' === $request ) {
			wp_safe_redirect( home_url( '/shop/' ), 301 );
			exit;
		}
	}
);

/**
 * A URL type's address with a made-up slug, such as /tag/this-is-a-cached-404/.
 *
 * @param array<string, mixed> $type The URL type, from Settings::shortcuts().
 * @param string               $slug The slug.
 *
 * @return string
 */
function a8csp_test_links_path( array $type, string $slug ): string {
	if ( 'post' === $type['type'] ) {
		return '/' . str_replace(
			array( '%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', '%postname%' ),
			array( '2024', '01', '31', '09', '30', '00', $slug ),
			(string) $type['base']
		) . '/';
	}

	return '/' . $type['base'] . '/' . $slug . '/';
}

/**
 * What the saved settings should give a link.
 *
 * @param Settings $settings The saved settings.
 * @param string   $path     The link's path.
 * @param bool     $type_on  Whether the URL type that answers it is ticked.
 * @param bool     $loose    Whether loose mode answers it: a post-like address, not an archive.
 * @param string   $native   What it gets when the plugin leaves it to WordPress.
 *
 * @return string
 */
function a8csp_test_links_expected( Settings $settings, string $path, bool $type_on, bool $loose, string $native ): string {
	// Off: everything goes to WordPress.
	if ( ! $settings->is_enabled() ) {
		return $native;
	}

	// Under Never answer: goes to WordPress.
	foreach ( $settings->excluded() as $excluded ) {
		if ( str_starts_with( $path, $excluded ) ) {
			return $native;
		}
	}

	// Its URL type is ticked: cached.
	if ( $type_on ) {
		return 'Cached 404';
	}

	// Loose mode is on and covers this address: cached.
	if ( $loose && $settings->loose() ) {
		return 'Cached 404';
	}

	return $native;
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
				$settings = a8csp_test_links_settings();
				$rows     = array();

				// One made-up address for every URL type the plugin knows on this site.
				foreach ( $settings->shortcuts() as $type ) {
					$single = in_array( $type['type'], array( 'post_type', 'post' ), true );
					$path   = a8csp_test_links_path( $type, 'this-is-a-cached-404' );
					$rows[] = array( $type['label'], $path, $type['enabled'], $single, 'Native 404' );

					// Made-up paths after a real single item; nested types take extra parts as their own items.
					if ( $single && empty( $type['hierarchical'] ) ) {
						$item = get_posts(
							array(
								'post_type'   => 'post' === $type['type'] ? 'post' : $type['name'],
								'numberposts' => 1,
								'post_status' => 'publish',
							)
						);
						if ( array() !== $item ) {
							$extra  = a8csp_test_links_path( $type, $item[0]->post_name ) . 'made-up-thing/';
							$rows[] = array( $type['label'] . ', made-up path after a real one', $extra, $type['enabled'] && $settings->extra_segments(), true, 'Native 404' );
						}
					}
				}

				$rows[] = array( 'A missing page', '/this-might-be-a-page/', false, true, 'Native 404' );
				$rows[] = array( 'A missing page, late redirect to /shop/', '/give-something-like-this/', false, true, 'Not 404' );

				echo '<ul>';
				foreach ( $rows as list( $label, $path, $type_on, $loose, $native ) ) {
					echo '<li><strong>' . esc_html( $label ) . '</strong><br />';
					echo '<a href="' . esc_url( home_url( $path ) ) . '" target="_blank" rel="noopener"><code>' . esc_html( $path ) . '</code></a><br />';
					echo 'EXPECTED: ' . esc_html( a8csp_test_links_expected( $settings, $path, (bool) $type_on, $loose, $native ) ) . '</li>';
				}
				echo '</ul>';
			}
		);
	}
);
