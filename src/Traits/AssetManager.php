<?php
/**
 * Asset Management Trait for WP Flyout
 *
 * Handles asset loading and centralized localization for the WP Flyout library
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

use ArrayPress\WPFlyout\Flyout;

trait AssetManager {

	/**
	 * Flag to track if core assets are enqueued
	 *
	 * @var bool
	 */
	private static bool $core_enqueued = false;

	/**
	 * Global configuration for all components
	 *
	 * @var array
	 */
	private static array $global_config = [
		'currency_symbol' => '$',
		'date_format'     => 'M j, Y',
		'time_format'     => 'g:i a',
		'locale'          => 'en_US'
	];

	/**
	 * Set global configuration
	 *
	 * @param array $config Configuration options
	 *
	 * @return void
	 */
	public static function set_global_config( array $config ): void {
		self::$global_config = array_merge( self::$global_config, $config );
	}

	/**
	 * Get the base file path for asset resolution
	 *
	 * @return string
	 */
	private static function get_base_file(): string {
		return dirname( __DIR__ ) . '/Flyout.php';
	}

	/**
	 * Enqueue core flyout assets and all components
	 *
	 * @return void
	 */
	public static function enqueue_core_assets(): void {
		if ( self::$core_enqueued ) {
			return;
		}

		$base_file = self::get_base_file();

		// Core CSS
		wp_enqueue_style_from_composer_file(
			'wp-flyout',
			$base_file,
			'css/wp-flyout.css'
		);

		// Core JavaScript with dependencies
		wp_enqueue_script_from_composer_file(
			'wp-flyout',
			$base_file,
			'js/wp-flyout.js',
			[ 'jquery', 'jquery-ui-sortable' ]
		);

		// Load all component assets (needed for AJAX content)
		self::enqueue_all_component_assets();

		// Centralized localization for all components
		self::localize_scripts();

		self::$core_enqueued = true;
	}

	/**
	 * Enqueue all component assets
	 * Since flyouts load via AJAX, all component assets must be available
	 *
	 * @return void
	 */
	private static function enqueue_all_component_assets(): void {
		$base_file = self::get_base_file();

		// Enqueue WordPress Media Library - must be called first
		wp_enqueue_media();

		// File Manager Component
		wp_enqueue_style_from_composer_file(
			'wp-flyout-file-manager',
			$base_file,
			'css/components/file-manager.css',
			[ 'wp-flyout' ]
		);

		wp_enqueue_script_from_composer_file(
			'wp-flyout-file-manager',
			$base_file,
			'js/components/file-manager.js',
			[ 'wp-flyout', 'jquery-ui-sortable' ]
		);

		// Notes Component
		wp_enqueue_style_from_composer_file(
			'wp-flyout-notes',
			$base_file,
			'css/components/notes.css',
			[ 'wp-flyout' ]
		);

		wp_enqueue_script_from_composer_file(
			'wp-flyout-notes',
			$base_file,
			'js/components/notes.js',
			[ 'wp-flyout' ]
		);

		// Order Items Component
		wp_enqueue_style_from_composer_file(
			'wp-flyout-order-items',
			$base_file,
			'css/components/order-items.css',
			[ 'wp-flyout' ]
		);

		wp_enqueue_script_from_composer_file(
			'wp-flyout-order-items',
			$base_file,
			'js/components/order-items.js',
			[ 'wp-flyout' ]
		);

		// Sections Component
		wp_enqueue_style_from_composer_file(
			'wp-flyout-sections',
			$base_file,
			'css/components/sections.css',
			[ 'wp-flyout' ]
		);
	}

	/**
	 * Centralized script localization
	 *
	 * @return void
	 */
	private static function localize_scripts(): void {
		// Get all registered flyouts and their configurations
		$flyouts = self::get_registered_flyouts_config();

		// Build the master configuration object
		$localization = [
			// Core settings
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'wpApiUrl'   => rest_url(),
			'nonce'      => wp_create_nonce( 'wp_flyout_nonce' ),

			// Global configuration
			'currency'   => self::$global_config['currency_symbol'],
			'dateFormat' => self::$global_config['date_format'],
			'timeFormat' => self::$global_config['time_format'],
			'locale'     => self::$global_config['locale'],

			// Flyout-specific configurations
			'flyouts'    => $flyouts,

			// Component configurations
			'components' => [
				'orderItems'  => [
					'action'     => 'get_product_details',
					'nonce'      => wp_create_nonce( 'order_items_nonce' ),
					'currency'   => self::$global_config['currency_symbol'],
					'defaultTax' => apply_filters( 'wp_flyout_default_tax_rate', 0 ),
					'maxItems'   => apply_filters( 'wp_flyout_max_order_items', 50 )
				],
				'notes'       => [
					'defaultPrefix' => 'wp_flyout_notes',
					'confirmDelete' => __( 'Delete this note?', 'wp-flyout' ),
					'maxLength'     => apply_filters( 'wp_flyout_note_max_length', 1000 )
				],
				'fileManager' => [
					'maxFiles'     => apply_filters( 'wp_flyout_max_files', 10 ),
					'allowedTypes' => apply_filters( 'wp_flyout_allowed_file_types', [ 'pdf', 'zip', 'jpg', 'png' ] ),
					'maxSize'      => apply_filters( 'wp_flyout_max_file_size', 10485760 ) // 10MB
				]
			],

			// UI strings for internationalization
			'i18n'       => [
				'saving'        => __( 'Saving...', 'wp-flyout' ),
				'loading'       => __( 'Loading...', 'wp-flyout' ),
				'error'         => __( 'An error occurred', 'wp-flyout' ),
				'success'       => __( 'Saved successfully', 'wp-flyout' ),
				'confirmDelete' => __( 'Are you sure you want to delete this?', 'wp-flyout' ),
				'required'      => __( 'Please fill in all required fields', 'wp-flyout' ),
				'noItems'       => __( 'No items found', 'wp-flyout' )
			]
		];

		// Localize to the main wp-flyout script
		wp_localize_script( 'wp-flyout', 'wpFlyoutConfig', $localization );
	}

	/**
	 * Get configuration for all registered flyouts
	 *
	 * @return array
	 */
	private static function get_registered_flyouts_config(): array {
		$configs = [];

		// Access the Flyout class's registered instances
		$registered = Flyout::get_registered();

		foreach ( $registered as $id => $flyout ) {
			$ajax = $flyout->get_ajax_config();
			if ( $ajax['prefix'] ) {
				$configs[ $id ] = [
					'ajax' => [
						'load_action'   => $ajax['prefix'] . '_load',
						'save_action'   => $ajax['prefix'] . '_save',
						'delete_action' => $ajax['prefix'] . '_delete',
						'nonce'         => wp_create_nonce( $ajax['prefix'] . '_nonce' )
					]
				];
			}
		}

		return $configs;
	}

	/**
	 * Reset enqueued tracking (useful for testing)
	 *
	 * @return void
	 */
	public static function reset_enqueued(): void {
		self::$core_enqueued = false;
	}

}