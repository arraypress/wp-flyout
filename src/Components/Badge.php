<?php
/**
 * Badge Component
 *
 * Creates consistent badge elements for status indicators, labels, and tags.
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

/**
 * Class Badge
 *
 * Renders badge elements with various styles and configurations.
 *
 * @since 1.0.0
 */
class Badge {
	use Renderable;

	/**
	 * Badge text
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $text;

	/**
	 * Badge configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'type'  => 'default', // default, success, warning, error, info
		'class' => '',
	];

	/**
	 * Constructor
	 *
	 * @param string $text   Badge text.
	 * @param array  $config Optional configuration.
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( string $text, array $config = [] ) {
		$this->text   = $text;
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create a status badge
	 *
	 * @param string $status Status text.
	 * @param string $type   Status type for styling.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function status( string $status, string $type = 'default' ): self {
		return new self( $status, [ 'type' => $type ] );
	}

	/**
	 * Create a count badge
	 *
	 * @param int    $count Count to display.
	 * @param string $type  Badge type.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function count( int $count, string $type = 'info' ): self {
		return new self( (string) $count, [ 'type' => $type ] );
	}

	/**
	 * Render the badge
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	public function render(): string {
		$classes = [
			'wp-flyout-badge',
			'wp-flyout-badge-' . $this->config['type'],
		];

		if ( $this->config['class'] ) {
			$classes[] = $this->config['class'];
		}

		return sprintf(
			'<span class="%s">%s</span>',
			esc_attr( implode( ' ', $classes ) ),
			esc_html( $this->text )
		);
	}

}