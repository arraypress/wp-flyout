<?php
/**
 * Conditional Render Trait
 *
 * Provides conditional rendering methods to components.
 *
 * @package     ArrayPress\WPFlyout\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

trait ConditionalRender {

	/**
	 * Render only if condition is true
	 *
	 * @param bool $condition Condition to check
	 *
	 * @return string Rendered HTML or empty string
	 */
	public function renderIf( bool $condition ): string {
		return $condition ? $this->render() : '';
	}

	/**
	 * Render only if value is not empty
	 *
	 * @param mixed $value Value to check
	 *
	 * @return string Rendered HTML or empty string
	 */
	public function renderIfNotEmpty( $value ): string {
		return ! empty( $value ) ? $this->render() : '';
	}

	/**
	 * Render with fallback if condition is false
	 *
	 * @param bool  $condition Condition to check
	 * @param mixed $fallback  Fallback component or HTML
	 *
	 * @return string Rendered HTML
	 */
	public function renderOrElse( bool $condition, $fallback ): string {
		if ( $condition ) {
			return $this->render();
		}

		if ( is_string( $fallback ) ) {
			return $fallback;
		}

		if ( is_object( $fallback ) && method_exists( $fallback, 'render' ) ) {
			return $fallback->render();
		}

		return '';
	}

}