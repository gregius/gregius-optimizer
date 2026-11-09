<?php
/**
 * Virtual root files (llms.txt, robots.txt) served outside the main query.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'gg_optimizer_is_virtual_file' ) ) {
	/**
	 * Whether the current request is the canonical URL for a virtual root file.
	 *
	 * Exact match, so "/anything/llms.txt" and "/llms.txt/" do not resolve.
	 *
	 * @param string $file File name, e.g. 'llms.txt'.
	 * @return bool
	 */
	function gg_optimizer_is_virtual_file( $file ) {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request   = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$canonical = (string) wp_parse_url( home_url( '/' . $file ), PHP_URL_PATH );

		return '' !== $canonical && $request === $canonical;
	}
}

if ( ! function_exists( 'gg_optimizer_register_virtual_file' ) ) {
	/**
	 * Register a virtual root file with a correct HTTP status.
	 *
	 * `pre_handle_404` runs before WordPress commits a 404, so returning true
	 * leaves the response at 200 and skips core's nocache_headers(). Because
	 * that also drops the no-store policy core otherwise applies, the handler
	 * sets an explicit `Cache-Control: no-store`.
	 *
	 * @param string   $file       File name, e.g. 'llms.txt'.
	 * @param callable $is_enabled Returns whether the file should be served.
	 * @param callable $render     Echoes the file body.
	 * @return void
	 */
	function gg_optimizer_register_virtual_file( $file, callable $is_enabled, callable $render ) {
		add_filter(
			'pre_handle_404',
			static function ( $preempt ) use ( $file, $is_enabled ) {
				if ( ! gg_optimizer_is_virtual_file( $file ) || ! $is_enabled() ) {
					return $preempt;
				}
				return true;
			}
		);

		add_action(
			'template_redirect',
			static function () use ( $file, $is_enabled, $render ) {
				if ( ! gg_optimizer_is_virtual_file( $file ) || ! $is_enabled() ) {
					return;
				}

				header( 'Content-Type: text/plain; charset=utf-8' );
				header( 'Cache-Control: no-store' );

				$render();
				exit;
			},
			0
		);
	}
}
