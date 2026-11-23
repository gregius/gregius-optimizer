<?php
/**
 * Data-retention preference REST controller.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for the remove-data-on-uninstall preference.
 */
class GG_Optimizer_Data_Retention {

	/**
	 * Register the data-retention REST route.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			'gg-optimizer/v1',
			'/data-retention',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_update' ),
					'permission_callback' => static function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(
						'remove_data' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Persist the remove-data-on-uninstall preference.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public static function rest_update( WP_REST_Request $request ) {
		$remove = (bool) $request->get_param( 'remove_data' );
		update_option( GG_Optimizer_DB::UNINSTALL_OPTION, $remove );

		return rest_ensure_response( array( 'remove_data' => $remove ) );
	}
}
