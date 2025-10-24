<?php
/**
 * Heading Component
 *
 * Simple heading elements (h1-h6) with consistent styling.
 *
 * @package     ArrayPress\WPFlyout\Components\Core
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Core;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class Heading
 *
 * Renders heading elements with consistent styling.
 *
 * @since 1.0.0
 */
class Heading {
	use Renderable;

	/**
	 * Heading text
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $text;

	/**
	 * HTML tag
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $tag;

	/**
	 * Heading configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'class' => 'wp-flyout-heading',
		'id'    => '',
	];

	/**
	 * Constructor
	 *
	 * @param string $text   Heading text.
	 * @param string $tag    HTML tag (h1-h6).
	 * @param array  $config Optional configuration.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $text, string $tag = 'h3', array $config = [] ) {
		$this->text   = $text;
		$this->tag    = in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] ) ? $tag : 'h3';
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create an h1 heading
	 *
	 * @param string $text Heading text.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function h1( string $text ): self {
		return new self( $text, 'h1' );
	}

	/**
	 * Create an h2 heading
	 *
	 * @param string $text Heading text.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function h2( string $text ): self {
		return new self( $text, 'h2' );
	}

	/**
	 * Create an h3 heading
	 *
	 * @param string $text Heading text.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function h3( string $text ): self {
		return new self( $text, 'h3' );
	}

	/**
	 * Create an h4 heading
	 *
	 * @param string $text Heading text.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function h4( string $text ): self {
		return new self( $text, 'h4' );
	}

	/**
	 * Create an h5 heading
	 *
	 * @param string $text Heading text.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function h5( string $text ): self {
		return new self( $text, 'h5' );
	}

	/**
	 * Create an h6 heading
	 *
	 * @param string $text Heading text.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function h6( string $text ): self {
		return new self( $text, 'h6' );
	}

	/**
	 * Quick render of heading
	 *
	 * @param string $text Heading text.
	 * @param string $tag  HTML tag (h1-h6).
	 *
	 * @return string Rendered HTML.
	 * @since 1.0.0
	 */
	public static function quick( string $text, string $tag = 'h3' ): string {
		return ( new self( $text, $tag ) )->render();
	}

	/**
	 * Render the heading
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 */
	public function render(): string {
		$attrs = sprintf( 'class="%s"', esc_attr( $this->config['class'] ) );

		if ( ! empty( $this->config['id'] ) ) {
			$attrs .= sprintf( ' id="%s"', esc_attr( $this->config['id'] ) );
		}

		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$this->tag,
			$attrs,
			esc_html( $this->text )
		);
	}

}