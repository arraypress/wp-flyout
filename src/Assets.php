<?php
/**
 * Assets Manager for WP Flyout
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

class Assets {

	/**
	 * Core JavaScript files
	 *
	 * @var array
	 */
	private static array $core_scripts = [
		'js/wp-flyout.js',          // Core flyout functionality
		'js/core/forms.js',         // Form utilities
		'js/core/manager.js',       // Manager integration
		'js/core/alert.js'          // Alert component
	];

	/**
	 * Available components and their assets
	 *
	 * @var array
	 */
	private static array $components = [
		// Interactive Components (always separate)
		'file-manager'    => [
			'script' => 'js/components/file-manager.js',
			'style'  => 'css/components/file-manager.css',
			'deps'   => [ 'jquery-ui-sortable' ]
		],
		'notes'           => [
			'script' => 'js/components/notes.js',
			'style'  => 'css/components/notes.css',
			'deps'   => []
		],
		'order-items'     => [
			'script' => 'js/components/order-items.js',
			'style'  => 'css/components/order-items.css',
			'deps'   => [ 'wp-flyout-ajax-select' ]
		],

		// Optional Components (loaded on demand)
		'ajax-select'     => [
			'script' => 'js/components/ajax-select.js',
			'style'  => 'css/components/ajax-select.css',
			'deps'   => []
		],
		'tags'            => [
			'script' => 'js/components/tags.js',
			'style'  => 'css/components/tags.css',
			'deps'   => []
		],
		'accordion'       => [
			'script' => 'js/components/accordion.js',
			'style'  => 'css/components/accordion.css',
			'deps'   => []
		],
		'card-choice'     => [
			'script' => '', // No JS for this component
			'style'  => 'css/components/card-choice.css',
			'deps'   => []
		],
		'timeline'        => [
			'script' => '', // No JS for this component
			'style'  => 'css/components/timeline.css',
			'deps'   => []
		],
		'price-breakdown' => [
			'script' => 'js/components/price-breakdown.js',
			'style'  => 'css/components/price-breakdown.css',
			'deps'   => []
		],
	];

	/**
	 * Initialize assets
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
	}

	/**
	 * Register assets for use by plugins
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		$base_file = __FILE__;
		$version   = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '3.0.0';

		// Register core CSS (single consolidated file)
		wp_register_composer_style(
			'wp-flyout',
			$base_file,
			'css/flyout/core.css', // The consolidated CSS file
			[ 'dashicons' ],
			$version
		);

		// Register core JavaScript files
		$js_deps        = [ 'jquery' ];
		$last_js_handle = '';

		foreach ( self::$core_scripts as $js_file ) {
			$handle = self::get_handle_from_path( $js_file );

			wp_register_composer_script(
				$handle,
				$base_file,
				$js_file,
				$js_deps,
				$version
			);

			// Make each subsequent file depend on the previous one
			$js_deps        = [ $handle ];
			$last_js_handle = $handle;
		}

		// Register virtual 'wp-flyout' script handle (depends on all core scripts)
		wp_register_script( 'wp-flyout', false, [ $last_js_handle ], $version );

		// Add inline script for global access
		wp_add_inline_script(
			'wp-flyout-wp-flyout', // First core script handle
			'window.WPFlyout = window.WPFlyout || {};',
			'before'
		);

		// Register component assets
		self::register_components( $base_file, $version );
	}

	/**
	 * Register component assets
	 *
	 * @param string $base_file Base file path
	 * @param string $version   Version string
	 *
	 * @return void
	 */
	private static function register_components( string $base_file, string $version ): void {
		foreach ( self::$components as $name => $config ) {
			$handle = 'wp-flyout-' . $name;

			// Register component script if exists
			if ( ! empty( $config['script'] ) ) {
				$deps = array_merge( [ 'jquery', 'wp-flyout' ], $config['deps'] ?? [] );
				wp_register_composer_script(
					$handle,
					$base_file,
					$config['script'],
					$deps,
					$version
				);
			}

			// Register component style if exists
			if ( ! empty( $config['style'] ) ) {
				wp_register_composer_style(
					$handle,
					$base_file,
					$config['style'],
					[ 'wp-flyout' ], // Depends on core styles
					$version
				);
			}
		}
	}

	/**
	 * Enqueue flyout assets
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		// Ensure assets are registered
		if ( ! wp_style_is( 'wp-flyout', 'registered' ) ) {
			self::register_assets();
		}

		// This will enqueue all core CSS and JS
		wp_enqueue_style( 'wp-flyout' );
		wp_enqueue_script( 'wp-flyout' );
	}

	/**
	 * Enqueue specific component assets
	 *
	 * @param string $component Component name
	 *
	 * @return bool Whether component was enqueued
	 */
	public static function enqueue_component( string $component ): bool {
		if ( ! isset( self::$components[ $component ] ) ) {
			return false;
		}

		// Ensure core is loaded first
		self::enqueue();

		$handle = 'wp-flyout-' . $component;

		// Handle dependencies
		$config = self::$components[ $component ];
		if ( ! empty( $config['deps'] ) ) {
			foreach ( $config['deps'] as $dep ) {
				// Check if it's another component dependency
				if ( str_starts_with( $dep, 'wp-flyout-' ) ) {
					$dep_component = str_replace( 'wp-flyout-', '', $dep );
					if ( isset( self::$components[ $dep_component ] ) ) {
						self::enqueue_component( $dep_component );
					}
				} else {
					// Enqueue non-component dependencies (like jquery-ui-sortable)
					wp_enqueue_script( $dep );
				}
			}
		}

		// Enqueue component assets
		if ( wp_style_is( $handle, 'registered' ) ) {
			wp_enqueue_style( $handle );
		}

		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_enqueue_script( $handle );
		}

		// Handle special requirements
		if ( $component === 'file-manager' ) {
			wp_enqueue_media();
		}

		return true;
	}

	/**
	 * Check if component exists
	 *
	 * @param string $component Component name
	 *
	 * @return bool
	 */
	public static function has_component( string $component ): bool {
		return isset( self::$components[ $component ] );
	}

	/**
	 * Get list of available components
	 *
	 * @return array Component names
	 */
	public static function get_components(): array {
		return array_keys( self::$components );
	}

	/**
	 * Generate handle from file path
	 *
	 * @param string $file_path File path
	 * @param string $prefix    Handle prefix (default: 'wp-flyout')
	 *
	 * @return string Generated handle
	 */
	private static function get_handle_from_path( string $file_path, string $prefix = 'wp-flyout' ): string {
		$name = basename( $file_path, '.css' );
		$name = basename( $name, '.js' );

		return $prefix . '-' . $name;
	}

}