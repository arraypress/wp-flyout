<?php
/**
 * HTML Attributes Trait
 *
 * Provides functionality for building HTML attribute strings from arrays.
 *
 * @package     ArrayPress\WPFlyout\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

trait HtmlAttributes {

	/**
	 * Build HTML attributes string from array
	 *
	 * @param array $attrs Attributes array (key => value)
	 *
	 * @return string HTML attributes string
	 */
	protected function build_attributes( array $attrs ): string {
		$result = [];
		foreach ( $attrs as $key => $value ) {
			if ( $value === null || $value === false ) {
				continue;
			}
			if ( $value === true ) {
				$result[] = esc_attr( $key );
			} else {
				$result[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
			}
		}

		return implode( ' ', $result );
	}

	/**
	 * Build data attributes from array
	 *
	 * @param array $data Data attributes (without 'data-' prefix)
	 *
	 * @return string HTML data attributes string
	 */
	protected function build_data_attributes( array $data ): string {
		$attrs = [];
		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = $value;
		}

		return $this->build_attributes( $attrs );
	}

	/**
	 * Merge and build attributes
	 *
	 * @param array $defaults Default attributes
	 * @param array $custom   Custom attributes to merge
	 *
	 * @return string HTML attributes string
	 */
	protected function merge_attributes( array $defaults, array $custom ): string {
		return $this->build_attributes( array_merge( $defaults, $custom ) );
	}

}