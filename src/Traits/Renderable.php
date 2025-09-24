<?php
/**
 * Renderable Trait
 *
 * Common rendering functionality for components.
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
 * Trait Renderable
 *
 * Provides common rendering methods for components.
 */
trait Renderable {

	/**
	 * Convert component to string
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->render();
	}

	/**
	 * Output the component
	 *
	 * @return void
	 */
	public function output(): void {
		echo $this->render();
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	abstract public function render(): string;

}