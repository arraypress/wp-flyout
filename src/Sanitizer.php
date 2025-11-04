<?php
/**
 * Field and Component Sanitizer
 *
 * Centralized sanitization logic for form fields and data-submitting components.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use DateTime;

/**
 * Class Sanitizer
 *
 * Provides sanitization methods for form fields and data-submitting components.
 */
class Sanitizer {

	/**
	 * Field type sanitizers
	 *
	 * @var array<string, callable>
	 */
	private static array $field_sanitizers = [];

	/**
	 * Component sanitizers (only for components that submit data)
	 *
	 * @var array<string, callable>
	 */
	private static array $component_sanitizers = [];

	/**
	 * Whether sanitizers have been initialized
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize default sanitizers
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::register_field_sanitizers();
		self::register_component_sanitizers();

		self::$initialized = true;
	}

	/**
	 * Register field type sanitizers
	 */
	private static function register_field_sanitizers(): void {
		self::$field_sanitizers = [
			// Text inputs
			'text'        => 'sanitize_text_field',
			'textarea'    => 'sanitize_textarea_field',
			'email'       => 'sanitize_email',
			'url'         => 'esc_url_raw',
			'tel'         => 'sanitize_text_field',
			'password'    => [ self::class, 'sanitize_password' ],

			// Numeric inputs
			'number'      => [ self::class, 'sanitize_number' ],

			// Date/Time inputs
			'date'        => [ self::class, 'sanitize_date' ],

			// Selection inputs
			'select'      => 'sanitize_text_field',
			'ajax_select' => 'sanitize_text_field',
			'radio'       => 'sanitize_text_field',
			'toggle'      => [ self::class, 'sanitize_toggle' ],

			// Special inputs
			'color'       => 'sanitize_hex_color',
			'hidden'      => 'sanitize_text_field',
		];
	}

	/**
	 * Register component sanitizers
	 *
	 * Only includes components that actually submit data with the form.
	 * Components that work via AJAX (notes, price_breakdown) or are
	 * display-only (timeline, accordion, entity_header) are NOT included.
	 */
	private static function register_component_sanitizers(): void {
		self::$component_sanitizers = [
			'order_items' => [ self::class, 'sanitize_order_items' ],
			'files'       => [ self::class, 'sanitize_files' ],
			'tags'        => [ self::class, 'sanitize_tags' ],
			'card_choice' => [ self::class, 'sanitize_card_choice' ],
		];
	}

	/**
	 * Sanitize a value based on field configuration
	 *
	 * @param mixed $value        Value to sanitize
	 * @param array $field_config Field configuration
	 *
	 * @return mixed Sanitized value
	 */
	public static function sanitize_field( $value, array $field_config ) {
		self::ensure_initialized();

		// Use custom sanitizer if provided
		if ( ! empty( $field_config['sanitize_callback'] ) && is_callable( $field_config['sanitize_callback'] ) ) {
			return call_user_func( $field_config['sanitize_callback'], $value );
		}

		$type = $field_config['type'] ?? 'text';

		// Check if it's a data-submitting component
		if ( isset( self::$component_sanitizers[ $type ] ) ) {
			return call_user_func( self::$component_sanitizers[ $type ], $value );
		}

		// Check field sanitizers
		if ( isset( self::$field_sanitizers[ $type ] ) ) {
			return call_user_func( self::$field_sanitizers[ $type ], $value );
		}

		// Default fallback
		return is_array( $value )
			? array_map( 'sanitize_text_field', $value )
			: sanitize_text_field( $value );
	}

	/**
	 * Sanitize form data based on fields configuration
	 *
	 * @param array $raw_data Raw form data
	 * @param array $fields   Field configurations
	 *
	 * @return array Sanitized data
	 */
	public static function sanitize_form_data( array $raw_data, array $fields ): array {
		self::ensure_initialized();

		$sanitized = [];

		// Sanitize configured fields
		foreach ( $fields as $field_key => $field_config ) {
			$field_name = $field_config['name'] ?? $field_key;

			if ( ! isset( $raw_data[ $field_name ] ) ) {
				continue;
			}

			$sanitized[ $field_name ] = self::sanitize_field(
				$raw_data[ $field_name ],
				$field_config
			);
		}

		// Sanitize any additional fields not in config (like 'id')
		foreach ( $raw_data as $key => $value ) {
			if ( ! isset( $sanitized[ $key ] ) ) {
				$sanitized[ $key ] = is_array( $value )
					? array_map( 'sanitize_text_field', $value )
					: sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	// ========================================
	// Field Sanitization Methods
	// ========================================

	/**
	 * Sanitize number field
	 *
	 * @param mixed $value Raw value
	 *
	 * @return int|float Sanitized number
	 */
	public static function sanitize_number( $value ) {
		// Check if it's a float/decimal
		if ( str_contains( (string) $value, '.' ) ) {
			return floatval( $value );
		}

		return intval( $value );
	}

	/**
	 * Sanitize password field
	 *
	 * @param mixed $value Raw value
	 *
	 * @return string Sanitized password
	 */
	public static function sanitize_password( $value ): string {
		// Don't use sanitize_text_field as it strips some valid password chars
		return trim( (string) $value );
	}

	/**
	 * Sanitize date field
	 *
	 * @param mixed $value Raw value
	 *
	 * @return string Sanitized date or empty string
	 */
	public static function sanitize_date( $value ): string {
		$date = DateTime::createFromFormat( 'Y-m-d', $value );

		return $date ? $date->format( 'Y-m-d' ) : '';
	}

	/**
	 * Sanitize toggle/checkbox field
	 *
	 * @param mixed $value Raw value
	 *
	 * @return string '1' or '0'
	 */
	public static function sanitize_toggle( $value ): string {
		return $value ? '1' : '0';
	}

	// ========================================
	// Component Sanitization Methods
	// ========================================

	/**
	 * Sanitize tags array
	 *
	 * @param mixed $value Raw value
	 *
	 * @return array Sanitized tags
	 */
	public static function sanitize_tags( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Sanitize card choice selection
	 *
	 * @param mixed $value Raw value
	 *
	 * @return string|array Sanitized selection
	 */
	public static function sanitize_card_choice( $value ) {
		// Can be single value or array for multi-select
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize order items array
	 *
	 * @param mixed $items Raw items
	 *
	 * @return array Sanitized items
	 */
	public static function sanitize_order_items( $items ): array {
		if ( ! is_array( $items ) ) {
			return [];
		}

		return array_map( function ( $item ) {
			return [
				'product_id' => absint( $item['product_id'] ?? 0 ),
				'name'       => sanitize_text_field( $item['name'] ?? '' ),
				'quantity'   => max( 1, absint( $item['quantity'] ?? 1 ) ),
				'price'      => absint( $item['price'] ?? 0 ), // Stored as cents
			];
		}, $items );
	}

	/**
	 * Sanitize files array
	 *
	 * @param mixed $files Raw files
	 *
	 * @return array Sanitized files
	 */
	public static function sanitize_files( $files ): array {
		if ( ! is_array( $files ) ) {
			return [];
		}

		return array_map( function ( $file ) {
			return [
				'name'          => sanitize_text_field( $file['name'] ?? '' ),
				'url'           => esc_url_raw( $file['url'] ?? '' ),
				'attachment_id' => absint( $file['attachment_id'] ?? 0 ),
				'lookup_key'    => sanitize_key( $file['lookup_key'] ?? '' ),
			];
		}, $files );
	}

	// ========================================
	// Registration Methods
	// ========================================

	/**
	 * Register custom field sanitizer
	 *
	 * @param string   $type      Field type
	 * @param callable $sanitizer Sanitizer callback
	 */
	public static function register_field_sanitizer( string $type, callable $sanitizer ): void {
		self::ensure_initialized();
		self::$field_sanitizers[ $type ] = $sanitizer;
	}

	/**
	 * Register custom component sanitizer
	 *
	 * @param string   $type      Component type
	 * @param callable $sanitizer Sanitizer callback
	 */
	public static function register_component_sanitizer( string $type, callable $sanitizer ): void {
		self::ensure_initialized();
		self::$component_sanitizers[ $type ] = $sanitizer;
	}

	/**
	 * Ensure sanitizers are initialized
	 */
	private static function ensure_initialized(): void {
		if ( ! self::$initialized ) {
			self::init();
		}
	}

}