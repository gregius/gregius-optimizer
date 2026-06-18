<?php
/**
 * Feature toggle CRUD for Gregius Optimizer.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

class GG_Optimizer_Feature_Toggle {

	const KEY      = 'feature_toggles';
	const FEATURES = array( 'sitemap', 'robots', 'schema', 'social_cards', 'llms' );

	public static function get_all(): array {
		$raw    = GG_Optimizer_DB::get( self::KEY );
		$stored = $raw ? json_decode( $raw, true ) : array();
		$out    = array();
		foreach ( self::FEATURES as $f ) {
			$out[ $f ] = isset( $stored[ $f ] ) ? (bool) $stored[ $f ] : false;
		}
		return $out;
	}

	public static function is_enabled( string $name ): bool {
		$all = self::get_all();
		return $all[ $name ] ?? false;
	}

	public static function set_all( array $toggles ): void {
		$existing = self::get_all();
		foreach ( self::FEATURES as $f ) {
			if ( array_key_exists( $f, $toggles ) ) {
				$existing[ $f ] = ! empty( $toggles[ $f ] );
			}
		}
		GG_Optimizer_DB::set( self::KEY, wp_json_encode( $existing ) );
	}

	/**
	 * REST API: get all feature toggles.
	 */
	public static function rest_get(): \WP_REST_Response {
		return rest_ensure_response( self::get_all() );
	}

	/**
	 * REST API: update feature toggles.
	 */
	public static function rest_update( \WP_REST_Request $request ): \WP_REST_Response {
		$toggles = $request->get_param( 'toggles' );
		if ( ! is_array( $toggles ) ) {
			return new \WP_REST_Response(
				array( 'code' => 'invalid_toggles', 'message' => 'toggles must be an object' ),
				400
			);
		}
		self::set_all( $toggles );
		return rest_ensure_response( self::get_all() );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			'gg-optimizer/v1',
			'/feature-toggles',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get' ),
					'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_update' ),
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
					'args'                => array(
						'toggles' => array(
							'required'          => true,
							'type'              => 'object',
							'sanitize_callback' => array( __CLASS__, 'rest_sanitize_toggles' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Sanitize toggles for REST input.
	 */
	public static function rest_sanitize_toggles( $toggles ): array {
		$clean = array();
		foreach ( self::FEATURES as $f ) {
			$clean[ $f ] = ! empty( $toggles[ $f ] );
		}
		return $clean;
	}
}
