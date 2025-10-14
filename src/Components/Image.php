<?php
/**
 * Image Component - Simplified
 *
 * Basic image display with placeholder support.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class Image
 *
 * Renders images with fallback placeholder.
 *
 * @since 1.0.0
 */
class Image {
	use Renderable;

	/**
	 * Image configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [];

	/**
	 * Constructor
	 *
	 * @param string $src  Image source URL.
	 * @param string $alt  Alternative text.
	 * @param array  $args Optional arguments.
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( string $src, string $alt = '', array $args = [] ) {
		$this->config = array_merge( [
			'src'         => $src,
			'alt'         => $alt,
			'size'        => 40, // Square size in pixels
			'class'       => '',
			'rounded'     => false,
			'placeholder' => 'dashicons-format-image',
		], $args );
	}

	/**
	 * Create a thumbnail image
	 *
	 * @param string $src Image source.
	 * @param string $alt Alternative text.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function thumbnail( string $src, string $alt = '' ): self {
		return new self( $src, $alt, [
			'size'    => 40,
			'rounded' => true,
		] );
	}

	/**
	 * Create an avatar image
	 *
	 * @param string $src Image source or email for gravatar.
	 * @param string $alt Alternative text.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function avatar( string $src, string $alt = '' ): self {
		// Handle gravatar if email provided
		if ( is_email( $src ) ) {
			$src = get_avatar_url( $src, [ 'size' => 80 ] );
		}

		return new self( $src, $alt, [
			'size'        => 40,
			'rounded'     => true,
			'placeholder' => 'dashicons-admin-users',
		] );
	}

	/**
	 * Render the image
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	public function render(): string {
		if ( empty( $this->config['src'] ) ) {
			return $this->render_placeholder();
		}

		$classes = [ 'wp-flyout-image' ];
		if ( $this->config['class'] ) {
			$classes[] = $this->config['class'];
		}
		if ( $this->config['rounded'] ) {
			$classes[] = 'rounded';
		}

		return sprintf(
			'<img src="%s" alt="%s" class="%s" width="%d" height="%d" loading="lazy">',
			esc_url( $this->config['src'] ),
			esc_attr( $this->config['alt'] ),
			esc_attr( implode( ' ', $classes ) ),
			absint( $this->config['size'] ),
			absint( $this->config['size'] )
		);
	}

	/**
	 * Render placeholder
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	private function render_placeholder(): string {
		$classes = [ 'wp-flyout-image-placeholder' ];
		if ( $this->config['rounded'] ) {
			$classes[] = 'rounded';
		}

		return sprintf(
			'<div class="%s" style="width: %dpx; height: %dpx;">
				<span class="dashicons %s"></span>
			</div>',
			esc_attr( implode( ' ', $classes ) ),
			absint( $this->config['size'] ),
			absint( $this->config['size'] ),
			esc_attr( $this->config['placeholder'] )
		);
	}

}