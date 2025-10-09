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

/**
 * Class Assets
 */
class Assets {

	/**
	 * Available components and their assets
	 *
	 * @var array
	 */
	private static array $components = [
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
			'deps'   => []
		],
		'product-display' => [
			'script' => '', // No JS needed
			'style'  => 'css/components/product-display.css',
			'deps'   => []
		],
		'field-group'     => [
			'script' => '', // No JS needed
			'style'  => 'css/components/field-group.css',
			'deps'   => []
		],
		'flex-row'        => [
			'script' => '', // No JS needed
			'style'  => 'css/components/flex-row.css',
			'deps'   => []
		]
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

		// Register core CSS
		\wp_register_composer_style_from_file(
			'wp-flyout',
			$base_file,
			'css/wp-flyout.css',
			[ 'dashicons' ],
			$version
		);

		// Register core JavaScript
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

			// Register component script if exists and not empty
			if ( ! empty( $config['script'] ) ) {
				$deps = array_merge( [ 'jquery', 'wp-flyout' ], $config['deps'] ?? [] );
				\wp_register_composer_script_from_file(
					$handle,
					$base_file,
					$config['script'],
					$deps,
					$version
				);
			}

			// Register component style if exists and not empty
			if ( ! empty( $config['style'] ) ) {
				\wp_register_composer_style_from_file(
					$handle,
					$base_file,
					$config['style'],
					[ 'wp-flyout' ],
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

		// Enqueue component assets
		wp_enqueue_style( $handle );
		wp_enqueue_script( $handle );

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

}