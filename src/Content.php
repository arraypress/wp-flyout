<?php
/**
 * Content Accumulator
 *
 * Simplifies building content by eliminating string concatenation.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

class Content {

	/**
	 * Content parts to render
	 *
	 * @var array
	 */
	private array $parts = [];

	/**
	 * Add a component or HTML string
	 *
	 * @param mixed $component Component object with render() method or HTML string
	 *
	 * @return self
	 */
	public function add( $component ): self {
		if ( $component !== null && $component !== '' ) {
			$this->parts[] = $component;
		}

		return $this;
	}

	/**
	 * Add multiple components at once
	 *
	 * @param array $components Array of components
	 *
	 * @return self
	 */
	public function addMany( array $components ): self {
		foreach ( $components as $component ) {
			$this->add( $component );
		}

		return $this;
	}

	/**
	 * Add component only if condition is true
	 *
	 * @param bool  $condition Condition to check
	 * @param mixed $component Component to add
	 *
	 * @return self
	 */
	public function addIf( bool $condition, $component ): self {
		if ( $condition ) {
			$this->add( $component );
		}

		return $this;
	}

	/**
	 * Add component only if value is not empty
	 *
	 * @param mixed $value     Value to check
	 * @param mixed $component Component to add
	 *
	 * @return self
	 */
	public function addIfNotEmpty( $value, $component ): self {
		if ( ! empty( $value ) ) {
			$this->add( $component );
		}

		return $this;
	}

	/**
	 * Add a section with header
	 *
	 * @param string $title         Section title
	 * @param mixed  ...$components Components to include in section
	 *
	 * @return self
	 */
	public function addSection( string $title, ...$components ): self {
		$this->add( Components\SectionHeader::quick( $title ) );
		foreach ( $components as $component ) {
			$this->add( $component );
		}

		return $this;
	}

	/**
	 * Add a separator
	 *
	 * @param string $text Optional separator text
	 *
	 * @return self
	 */
	public function addSeparator( string $text = '' ): self {
		$this->add( new Components\Separator( $text ) );

		return $this;
	}

	/**
	 * Check if content is empty
	 *
	 * @return bool
	 */
	public function isEmpty(): bool {
		return empty( $this->parts );
	}

	/**
	 * Get count of parts
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->parts );
	}

	/**
	 * Clear all content
	 *
	 * @return self
	 */
	public function clear(): self {
		$this->parts = [];

		return $this;
	}

	/**
	 * Render all content parts
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		$output = '';

		foreach ( $this->parts as $part ) {
			if ( is_string( $part ) ) {
				$output .= $part;
			} elseif ( is_object( $part ) && method_exists( $part, 'render' ) ) {
				$output .= $part->render();
			} elseif ( is_object( $part ) && method_exists( $part, '__toString' ) ) {
				$output .= (string) $part;
			}
		}

		return $output;
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->render();
	}

	/**
	 * Static factory method
	 *
	 * @param mixed ...$components Initial components to add
	 *
	 * @return self
	 */
	public static function create( ...$components ): self {
		$content = new self();
		foreach ( $components as $component ) {
			$content->add( $component );
		}

		return $content;
	}

}