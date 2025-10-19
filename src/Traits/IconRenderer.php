<?php
/**
 * Icon Renderer Trait
 *
 * Provides utilities for rendering WordPress Dashicons consistently.
 * Simplifies icon output across components.
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
 * Trait IconRenderer
 *
 * Renders Dashicons with consistent markup.
 *
 * @since 1.0.0
 */
trait IconRenderer {

	/**
	 * Render a dashicon
	 *
	 * @param string $icon    Icon name (without 'dashicons-' prefix)
	 * @param array  $classes Additional CSS classes
	 *
	 * @return string Icon HTML
	 * @since 1.0.0
	 *
	 */
	protected function render_icon( string $icon, array $classes = [] ): string {
		if ( empty( $icon ) ) {
			return '';
		}

		$all_classes = array_merge(
			[ 'dashicons', 'dashicons-' . $icon ],
			$classes
		);

		// Use ClassBuilder trait if available, otherwise inline
		if ( method_exists( $this, 'build_classes' ) ) {
			$class_string = $this->build_classes( $all_classes );
		} else {
			$class_string = esc_attr( implode( ' ', array_filter( $all_classes ) ) );
		}

		return sprintf( '<span class="%s"></span>', $class_string );
	}

	/**
	 * Render icon with text
	 *
	 * Common pattern for icon + label combinations.
	 *
	 * @param string $icon Icon name
	 * @param string $text Text to display
	 * @param string $gap  Gap size (CSS value)
	 *
	 * @return string Icon and text HTML
	 * @since 1.0.0
	 *
	 */
	protected function render_icon_text( string $icon, string $text, string $gap = '5px' ): string {
		$icon_html = $this->render_icon( $icon );
		$text_html = esc_html( $text );

		if ( $gap ) {
			return sprintf(
				'<span style="display: inline-flex; align-items: center; gap: %s;">%s<span>%s</span></span>',
				esc_attr( $gap ),
				$icon_html,
				$text_html
			);
		}

		return $icon_html . ' ' . $text_html;
	}

	/**
	 * Get icon HTML for conditional rendering
	 *
	 * Returns icon HTML if icon name is provided, empty string otherwise.
	 *
	 * @param string|null $icon    Icon name or null
	 * @param array       $classes Additional CSS classes
	 *
	 * @return string Icon HTML or empty string
	 * @since 1.0.0
	 *
	 */
	protected function maybe_render_icon( ?string $icon, array $classes = [] ): string {
		return $icon ? $this->render_icon( $icon, $classes ) : '';
	}

}