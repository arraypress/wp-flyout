<?php
/**
 * Class Builder Trait
 *
 * Provides utilities for building CSS class strings from arrays.
 * Handles filtering empty values and conditional classes.
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
 * Trait ClassBuilder
 *
 * Builds CSS class strings with automatic filtering and escaping.
 *
 * @since 1.0.0
 */
trait ClassBuilder {

	/**
	 * Build class string from array
	 *
	 * Filters out empty values and returns escaped class string.
	 *
	 * @param array $classes Array of class names
	 *
	 * @return string Escaped class string
	 * @since 1.0.0
	 *
	 */
	protected function build_classes( array $classes ): string {
		return esc_attr( implode( ' ', array_filter( $classes ) ) );
	}

	/**
	 * Add conditional class
	 *
	 * Returns class name if condition is true, empty string otherwise.
	 *
	 * @param string $class     Class name to add
	 * @param bool   $condition Whether to include the class
	 *
	 * @return string Class name or empty string
	 * @since 1.0.0
	 *
	 */
	protected function conditional_class( string $class, bool $condition ): string {
		return $condition ? $class : '';
	}

	/**
	 * Build classes with base and modifiers
	 *
	 * Common pattern for BEM-style classes.
	 * Example: build_component_classes('button', ['primary', 'large']) returns 'button button--primary button--large'
	 *
	 * @param string $base      Base class name
	 * @param array  $modifiers Array of modifier names
	 * @param string $separator Separator between base and modifier (default: '--')
	 *
	 * @return string Escaped class string
	 * @since 1.0.0
	 *
	 */
	protected function build_component_classes( string $base, array $modifiers = [], string $separator = '--' ): string {
		$classes = [ $base ];

		foreach ( array_filter( $modifiers ) as $modifier ) {
			$classes[] = $base . $separator . $modifier;
		}

		return $this->build_classes( $classes );
	}

}