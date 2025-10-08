<?php
/**
 * WP Flyout Core Helper Functions
 *
 * Core functionality helpers for flyout system initialization and management.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 */

declare( strict_types=1 );

use ArrayPress\WPFlyout\Flyout;
use ArrayPress\WPFlyout\Assets;

if ( ! function_exists( 'wp_flyout' ) ) {
	/**
	 * Create a new flyout instance
	 *
	 * @since 3.0.0
	 *
	 * @param string $id     Flyout ID.
	 * @param array  $config Optional configuration.
	 * @return Flyout
	 */
	function wp_flyout( string $id, array $config = [] ): Flyout {
		return new Flyout( $id, $config );
	}
}

if ( ! function_exists( 'wp_flyout_init' ) ) {
	/**
	 * Initialize the flyout system
	 *
	 * Call this in your plugin's init hook to register assets.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	function wp_flyout_init(): void {
		Assets::init();
	}
}

if ( ! function_exists( 'wp_flyout_enqueue' ) ) {
	/**
	 * Enqueue flyout assets
	 *
	 * Call this when you need flyout functionality on a page.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	function wp_flyout_enqueue(): void {
		Assets::enqueue();
	}
}