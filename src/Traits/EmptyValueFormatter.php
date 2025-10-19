<?php
/**
 * Empty Value Formatter Trait
 *
 * Provides consistent formatting for empty, null, and various data types.
 * Ensures uniform display of missing or empty values across components.
 *
 * @package     ArrayPress\WPFlyout\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

/**
 * Trait EmptyValueFormatter
 *
 * Formats values for display with consistent empty state handling.
 *
 * @since 1.0.0
 */
trait EmptyValueFormatter {

	/**
	 * Format value for display
	 *
	 * Handles various data types and empty states consistently.
	 * - Empty values return configured empty text
	 * - Booleans return Yes/No
	 * - Arrays are joined with commas
	 * - Everything else is cast to string
	 *
	 * @param mixed  $value      Value to format
	 * @param string $empty_text Text to display for empty values (default: '—')
	 *
	 * @return string Formatted value string
	 * @since 1.0.0
	 *
	 */
	protected function format_value( $value, string $empty_text = '—' ): string {
		// Handle empty values (but not 0 or '0')
		if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
			return $empty_text;
		}

		// Handle booleans
		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'wp-flyout' ) : __( 'No', 'wp-flyout' );
		}

		// Handle arrays
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		// Handle objects with __toString
		if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
			return (string) $value;
		}

		// Default: cast to string
		return (string) $value;
	}

	/**
	 * Check if value is considered empty
	 *
	 * More nuanced than PHP's empty() - treats 0 and '0' as non-empty.
	 *
	 * @param mixed $value Value to check
	 *
	 * @return bool True if empty
	 * @since 1.0.0
	 *
	 */
	protected function is_empty_value( $value ): bool {
		return empty( $value ) && $value !== 0 && $value !== '0';
	}

	/**
	 * Format boolean as yes/no
	 *
	 * @param bool   $value    Boolean value
	 * @param string $yes_text Text for true (default: 'Yes')
	 * @param string $no_text  Text for false (default: 'No')
	 *
	 * @return string Yes or No text
	 * @since 1.0.0
	 *
	 */
	protected function format_boolean( bool $value, string $yes_text = '', string $no_text = '' ): string {
		$yes_text = $yes_text ?: __( 'Yes', 'wp-flyout' );
		$no_text  = $no_text ?: __( 'No', 'wp-flyout' );

		return $value ? $yes_text : $no_text;
	}

}