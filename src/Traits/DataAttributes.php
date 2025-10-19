<?php
/**
 * Data Attributes Trait
 *
 * Provides functionality for rendering HTML data attributes from arrays.
 * Used by components that need to output data-* attributes for JavaScript interaction.
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
 * Trait DataAttributes
 *
 * Renders data attributes for HTML elements from configuration arrays.
 *
 * @since 1.0.0
 */
trait DataAttributes {

	/**
	 * Render data attributes from array
	 *
	 * Converts an associative array into HTML data attributes.
	 * Example: ['action' => 'save', 'id' => '123'] becomes 'data-action="save" data-id="123"'
	 *
	 * @param array|null $data Data array (key => value pairs). If null, uses $this->config['data']
	 *
	 * @return string HTML data attributes string
	 * @since 1.0.0
	 *
	 */
	protected function render_data_attributes( ?array $data = null ): string {
		$data = $data ?? ( $this->config['data'] ?? [] );

		if ( empty( $data ) ) {
			return '';
		}

		$attrs = [];
		foreach ( $data as $key => $value ) {
			if ( $value !== null && $value !== '' ) {
				$attrs[] = sprintf(
					'data-%s="%s"',
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}
		}

		return implode( ' ', $attrs );
	}

	/**
	 * Render a single data attribute
	 *
	 * @param string $key   Attribute name (without 'data-' prefix)
	 * @param mixed  $value Attribute value
	 *
	 * @return string HTML data attribute
	 * @since 1.0.0
	 *
	 */
	protected function render_data_attribute( string $key, $value ): string {
		if ( $value === null || $value === '' ) {
			return '';
		}

		return sprintf(
			'data-%s="%s"',
			esc_attr( $key ),
			esc_attr( (string) $value )
		);
	}

}