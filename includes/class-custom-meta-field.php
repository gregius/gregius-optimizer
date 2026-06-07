<?php
defined( 'ABSPATH' ) || exit;
/**
 * Class GG_Optimizer_Custom_Meta_Field
 *
 * A reusable Facade class to register custom meta fields for any custom post type in WordPress,

 * @package gregius-optimizer
 */

if ( ! class_exists( 'GG_Optimizer_Custom_Meta_Field' ) ) {

class GG_Optimizer_Custom_Meta_Field {

	/**
	 * Post types to associate the meta fields with.
	 *
	 * @var array
	 */
	private $post_types;

	/**
	 * Meta fields configuration.
	 *
	 * @var array
	 */
	private $meta_fields;

	/**
	 * Class constructor.
	 *
	 * @param array|string $post_types  The post type(s) for which the meta fields will be registered.
	 * @param array        $meta_fields The meta fields configuration (key => config array).
	 */
	public function __construct( $post_types, array $meta_fields ) {
		$this->post_types  = (array) $post_types;
		$this->meta_fields = $meta_fields;

		add_action( 'init', array( $this, 'register_meta_fields' ) );
	}

	/**
	 * Register custom meta fields for the specified post types.
	 */
	public function register_meta_fields() {
		foreach ( $this->meta_fields as $meta_key => $config ) {
			$type = isset( $config['type'] ) ? $config['type'] : 'string';

			$args = array(
				'show_in_rest'      => $this->get_rest_schema( $type, $config ),
				'single'            => true,
				'type'              => $type,
				'sanitize_callback' => array( $this, 'get_sanitizer' ),
				'auth_callback'     => array( $this, 'has_permission' ),
			);

			// Add optional arguments if provided.
			if ( isset( $config['default'] ) ) {
				$args['default'] = $config['default'];
			}

			if ( isset( $config['description'] ) ) {
				$args['description'] = $config['description'];
			}

			if ( isset( $config['label'] ) ) {
				$args['label'] = $config['label'];
			}

			foreach ( $this->post_types as $post_type ) {
				if ( isset( $config['revisions_enabled'] ) && $config['revisions_enabled'] ) {
					if ( post_type_supports( $post_type, 'revisions' ) ) {
						$args['revisions_enabled'] = true;
					} else {
						unset( $args['revisions_enabled'] );
					}
				}
				register_post_meta( $post_type, $meta_key, $args );
			}
		}
	}

	/**
	 * Get the REST API schema for a field type.
	 *
	 * @param string $type   The field type.
	 * @param array  $config The field configuration.
	 *
	 * @return bool|array True for simple types, schema array for complex types.
	 */
	private function get_rest_schema( $type, $config ) {
		switch ( $type ) {
			case 'array':
				// Allow custom item schema via config, default to string items.
				$items = isset( $config['items'] ) ? $config['items'] : array( 'type' => 'string' );
				return array(
					'schema' => array(
						'type'  => 'array',
						'items' => $items,
					),
				);

			case 'object':
				// Allow custom properties schema via config, default to additionalProperties.
				$properties = isset( $config['properties'] ) ? $config['properties'] : array();
				$schema     = array(
					'type' => 'object',
				);
				if ( ! empty( $properties ) ) {
					$schema['properties'] = $properties;
				} else {
					// Allow any string properties by default.
					$schema['additionalProperties'] = array( 'type' => 'string' );
				}
				return array( 'schema' => $schema );

			default:
				return true;
		}
	}

	/**
	 * Retrieve the appropriate sanitization function for the meta field.
	 *
	 * @param mixed  $value    The value of the meta field.
	 * @param string $meta_key The meta key to identify the field configuration.
	 *
	 * @return mixed Sanitized value of the meta field.
	 */
	public function get_sanitizer( $value, $meta_key ) {
		$config = $this->meta_fields[ $meta_key ] ?? null;

		if ( null === $config ) {
			return sanitize_text_field( $value );
		}

		// Use custom sanitize callback if provided.
		if ( isset( $config['sanitize_callback'] ) && is_callable( $config['sanitize_callback'] ) ) {
			return call_user_func( $config['sanitize_callback'], $value );
		}

		// Default sanitization based on type.
		$type = isset( $config['type'] ) ? $config['type'] : 'string';

		switch ( $type ) {
			case 'integer':
				return intval( $value );

			case 'number':
				return floatval( $value );

			case 'boolean':
				return (bool) $value;

			case 'array':
				if ( ! is_array( $value ) ) {
					return array();
				}
				return $this->sanitize_recursive( $value );

			case 'object':
				if ( ! is_array( $value ) && ! is_object( $value ) ) {
					return array();
				}
				return $this->sanitize_recursive( (array) $value );

			case 'string':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Recursively sanitize a value, handling nested arrays and objects.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return mixed Sanitized value.
	 */
	private function sanitize_recursive( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_recursive' ), $value );
		}

		if ( is_bool( $value ) ) {
			return (bool) $value;
		}

		if ( is_int( $value ) ) {
			return intval( $value );
		}

		if ( is_float( $value ) ) {
			return floatval( $value );
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		// Null or other types - return as-is.
		return $value;
	}

	/**
	 * Check if the current user has permission to edit posts.
	 *
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function has_permission() {
		return current_user_can( 'edit_posts' );
	}
}
}
