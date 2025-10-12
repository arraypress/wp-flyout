<?php
/**
 * AJAX Select Component
 *
 * Provides AJAX-powered select functionality with auto-loading assets.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Assets;

/**
 * Class AjaxSelect
 *
 * AJAX-powered select elements with automatic asset loading.
 *
 * @since 1.0.0
 */
class AjaxSelect {
	use Renderable;

	/**
	 * Load AJAX select assets
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function load_assets(): void {
		Assets::enqueue_component( 'ajax-select' );
	}

	/**
	 * Generate AJAX select field
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field configuration
	 * @return string Generated HTML
	 */
	public static function field( array $args ): string {
		// Load assets
		self::load_assets();

		// Default configuration
		$defaults = [
			'name'            => '',
			'id'              => '',
			'ajax'            => '',
			'placeholder'     => 'Type to search...',
			'empty_option'    => null,
			'min_length'      => 3,
			'delay'           => 300,
			'initial_results' => 20,
			'value'           => '',
			'text'            => '',
			'nonce'           => '',
			'class'           => '',
			'required'        => false,
			'disabled'        => false,
			'ajax_url'        => ''
		];

		$args = wp_parse_args( $args, $defaults );

		// Auto-generate ID if not provided
		if ( empty( $args['id'] ) && ! empty( $args['name'] ) ) {
			$args['id'] = sanitize_key( $args['name'] );
		}

		// Build attributes
		return sprintf( '<select %s></select>', self::build_attributes( $args ) );
	}

	/**
	 * Build HTML attributes string
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Configuration array
	 * @return string HTML attributes
	 */
	private static function build_attributes( array $args ): string {
		$attrs = [];

		// Standard attributes
		foreach ( [ 'name', 'id', 'class' ] as $attr ) {
			if ( ! empty( $args[ $attr ] ) ) {
				$attrs[] = sprintf( '%s="%s"', $attr, esc_attr( $args[ $attr ] ) );
			}
		}

		// Boolean attributes
		foreach ( [ 'required', 'disabled' ] as $attr ) {
			if ( $args[ $attr ] ) {
				$attrs[] = $attr;
			}
		}

		// Data attributes
		$data_attrs = [
			'ajax'            => $args['ajax'],
			'placeholder'     => $args['placeholder'],
			'empty-option'    => $args['empty_option'],
			'min-length'      => $args['min_length'],
			'delay'           => $args['delay'],
			'initial-results' => $args['initial_results'],
			'value'           => $args['value'],
			'text'            => $args['text'],
			'nonce'           => $args['nonce'],
			'ajax-url'        => $args['ajax_url']
		];

		// Add data attributes
		foreach ( $data_attrs as $key => $value ) {
			if ( $value !== '' && $value !== null ) {
				$attrs[] = sprintf( 'data-%s="%s"', $key, esc_attr( (string) $value ) );
			}
		}

		return implode( ' ', $attrs );
	}

	/**
	 * Render AJAX select field directly
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field configuration
	 * @return void
	 */
	public static function render( array $args ): void {
		echo self::field( $args );
	}
}