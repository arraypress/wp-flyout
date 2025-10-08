<?php
/**
 * Assets Manager for WP Flyout
 *
 * Handles registration and enqueuing of CSS and JavaScript files
 * using the WordPress Composer Assets library.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

/**
 * Class Assets
 *
 * Manages script and style registration for the flyout library.
 *
 * @since 3.0.0
 */
class Assets {

	/**
	 * Initialize assets
	 *
	 * @return void
	 * @since 3.0.0
	 *
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
	}

	/**
	 * Register assets for use by plugins
	 *
	 * @return void
	 * @since 3.0.0
	 *
	 */
	public static function register_assets(): void {
		$base_file = __FILE__;
		$version   = defined( 'WP_DEBUG' ) && WP_DEBUG ? false : '3.0.0';

		// Register core CSS - use global namespace with backslash
		\wp_register_composer_style_from_file(
			'wp-flyout',
			$base_file,
			'css/wp-flyout.css',
			[ 'dashicons' ],
			$version
		);

		// Register core JavaScript - use global namespace with backslash
		\wp_register_composer_script_from_file(
			'wp-flyout',
			$base_file,
			'js/wp-flyout.js',
			[ 'jquery' ],
			$version
		);

		// Add inline script for global access
		\wp_add_inline_script(
			'wp-flyout',
			'window.WPFlyout = window.WPFlyout || {};',
			'before'
		);
	}

	/**
	 * Enqueue flyout assets
	 *
	 * Call this in your plugin when you need flyout functionality.
	 *
	 * @return void
	 * @since 3.0.0
	 *
	 */
	public static function enqueue(): void {
		wp_enqueue_style( 'wp-flyout' );
		wp_enqueue_script( 'wp-flyout' );
	}
}