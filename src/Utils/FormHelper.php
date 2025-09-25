<?php
/**
 * Form Helper Utility
 *
 * Provides helper methods for common form patterns like hidden fields,
 * nonces, and form metadata.
 *
 * @package     ArrayPress\WPFlyout\Utils
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Utils;

/**
 * Class FormHelper
 *
 * Static utility methods for form generation.
 */
class FormHelper {

	/**
	 * Generate a hidden field
	 *
	 * @param string $name  Field name
	 * @param mixed  $value Field value
	 *
	 * @return string HTML for hidden field
	 */
	public static function hidden_field( string $name, $value ): string {
		return sprintf(
			'<input type="hidden" name="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Generate a nonce field
	 *
	 * @param string $action Nonce action
	 * @param string $name   Field name (defaults to '_wpnonce')
	 *
	 * @return string HTML for nonce field
	 */
	public static function nonce_field( string $action, string $name = '_wpnonce' ): string {
		return self::hidden_field( $name, wp_create_nonce( $action ) );
	}

	/**
	 * Generate multiple hidden fields
	 *
	 * @param array $fields Array of name => value pairs
	 *
	 * @return string HTML for all hidden fields
	 */
	public static function hidden_fields( array $fields ): string {
		$output = '';
		foreach ( $fields as $name => $value ) {
			$output .= self::hidden_field( $name, $value ) . "\n";
		}

		return $output;
	}

	/**
	 * Generate form metadata fields (ID and nonce)
	 *
	 * @param string     $id_field_name Name of the ID field
	 * @param int|string $id_value      Value of the ID
	 * @param string     $nonce_action  Nonce action
	 * @param string     $nonce_name    Nonce field name
	 *
	 * @return string HTML for metadata fields
	 */
	public static function form_metadata(
		string $id_field_name,
		$id_value,
		string $nonce_action,
		string $nonce_name = '_wpnonce'
	): string {
		return self::hidden_field( $id_field_name, $id_value ) . "\n" .
		       self::nonce_field( $nonce_action, $nonce_name );
	}

	/**
	 * Generate referer field
	 *
	 * @param string $name Field name (defaults to '_wp_http_referer')
	 *
	 * @return string HTML for referer field
	 */
	public static function referer_field( string $name = '_wp_http_referer' ): string {
		$referer = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );

		return self::hidden_field( $name, esc_url( $referer ) );
	}

	/**
	 * Generate action field for admin forms
	 *
	 * @param string $action Action value
	 *
	 * @return string HTML for action field
	 */
	public static function action_field( string $action ): string {
		return self::hidden_field( 'action', $action );
	}

	/**
	 * Generate a complete set of form security fields
	 *
	 * @param string $nonce_action    Nonce action
	 * @param bool   $include_referer Whether to include referer field
	 *
	 * @return string HTML for security fields
	 */
	public static function security_fields( string $nonce_action, bool $include_referer = false ): string {
		$output = self::nonce_field( $nonce_action );

		if ( $include_referer ) {
			$output .= "\n" . self::referer_field();
		}

		return $output;
	}
}