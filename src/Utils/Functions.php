<?php
/**
 * WP Flyout Core Helper Functions
 *
 * Core functionality helpers for flyout system initialization and management.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

use ArrayPress\WPFlyout\Manager;

// Global managers registry
global $wp_flyout_managers;
$wp_flyout_managers = [];

if ( ! function_exists( 'register_flyout' ) ) {
	/**
	 * Register a flyout with automatic manager handling
	 *
	 * This is the simplified global registration method that automatically
	 * creates and manages Manager instances. The ID should follow the pattern:
	 * 'prefix_flyout_name' (e.g., 'myapp_edit_customer', 'shop_add_product')
	 *
	 * The prefix (first part before underscore) becomes the manager namespace,
	 * and the rest becomes the flyout ID within that manager.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id     Full flyout identifier (prefix_name format)
	 * @param array  $config {
	 *     Flyout configuration array
	 *
	 *     @type string   $title       Flyout title
	 *     @type string   $width       Width: 'small', 'medium', 'large', 'full'
	 *     @type array    $panels      Panel configurations (optional)
	 *     @type array    $fields      Field configurations
	 *     @type array    $actions     Footer action buttons
	 *     @type string   $capability  Required capability (default: 'manage_options')
	 *     @type array    $admin_pages Admin page hooks to load on
	 *     @type callable $load        Function to load data: function($id)
	 *     @type callable $save        Function to save data: function($id, $data)
	 *     @type callable $delete      Function to delete data: function($id)
	 * }
	 * @return Manager|null The manager instance or null if registration failed
	 */
	function register_flyout( string $id, array $config = [] ) {
		global $wp_flyout_managers;

		// Parse the ID to extract prefix and flyout name
		$parts = explode( '_', $id, 2 );

		// If no underscore, use the whole ID as both prefix and flyout name
		if ( count( $parts ) === 1 ) {
			$prefix = $id;
			$flyout_id = 'default';
		} else {
			$prefix = $parts[0];
			$flyout_id = $parts[1];
		}

		// Get or create manager instance
		if ( ! isset( $wp_flyout_managers[ $prefix ] ) ) {
			$wp_flyout_managers[ $prefix ] = new Manager( $prefix );
		}

		// Register the flyout
		try {
			$wp_flyout_managers[ $prefix ]->register_flyout( $flyout_id, $config );
			return $wp_flyout_managers[ $prefix ];
		} catch ( Exception $e ) {
			error_log( 'Failed to register flyout: ' . $e->getMessage() );
			return null;
		}
	}
}

if ( ! function_exists( 'get_flyout_button' ) ) {
	/**
	 * Get a flyout trigger button HTML
	 *
	 * This is a convenience function that works with the global registration.
	 * It automatically determines the correct manager from the flyout ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id   Full flyout identifier (same as used in register_flyout)
	 * @param array  $data Data attributes to pass to the flyout
	 * @param array  $args {
	 *     Button configuration
	 *
	 *     @type string $text  Button text (default: 'Open')
	 *     @type string $class Additional CSS classes
	 *     @type string $icon  Dashicon name (without 'dashicons-' prefix)
	 * }
	 * @return string Button HTML or empty string if flyout not found
	 */
	function get_flyout_button( string $id, array $data = [], array $args = [] ): string {
		global $wp_flyout_managers;

		// Parse the ID to find the manager
		$parts = explode( '_', $id, 2 );

		if ( count( $parts ) === 1 ) {
			$prefix = $id;
			$flyout_id = 'default';
		} else {
			$prefix = $parts[0];
			$flyout_id = $parts[1];
		}

		// Try to get the manager button
		if ( isset( $wp_flyout_managers[ $prefix ] ) ) {
			return $wp_flyout_managers[ $prefix ]->get_button( $flyout_id, $data, $args );
		}

		// Return empty if manager not found
		return '';
	}
}

if ( ! function_exists( 'render_flyout_button' ) ) {
	/**
	 * Render a flyout trigger button
	 *
	 * Outputs the button HTML directly.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id   Full flyout identifier
	 * @param array  $data Data attributes to pass
	 * @param array  $args Button configuration
	 * @return void
	 */
	function render_flyout_button( string $id, array $data = [], array $args = [] ): void {
		echo get_flyout_button( $id, $data, $args );
	}
}

if ( ! function_exists( 'get_flyout_link' ) ) {
	/**
	 * Get a flyout trigger link HTML
	 *
	 * This is a convenience function that works with the global registration.
	 * It automatically determines the correct manager from the flyout ID.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id   Full flyout identifier (same as used in register_flyout)
	 * @param string $text Link text to display
	 * @param array  $data Data attributes to pass to the flyout
	 * @param array  $args {
	 *     Link configuration
	 *
	 *     @type string $class  Additional CSS classes
	 *     @type string $target Link target attribute (e.g., '_blank')
	 * }
	 * @return string Link HTML or empty string if flyout not found
	 */
	function get_flyout_link( string $id, string $text, array $data = [], array $args = [] ): string {
		global $wp_flyout_managers;

		// Parse the ID to find the manager
		$parts = explode( '_', $id, 2 );

		if ( count( $parts ) === 1 ) {
			$prefix = $id;
			$flyout_id = 'default';
		} else {
			$prefix = $parts[0];
			$flyout_id = $parts[1];
		}

		// Try to get the manager link
		if ( isset( $wp_flyout_managers[ $prefix ] ) ) {
			return $wp_flyout_managers[ $prefix ]->link( $flyout_id, $text, $data, $args );
		}

		// Return empty if manager not found
		return '';
	}
}

if ( ! function_exists( 'render_flyout_link' ) ) {
	/**
	 * Render a flyout trigger link
	 *
	 * Outputs the link HTML directly.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id   Full flyout identifier
	 * @param string $text Link text to display
	 * @param array  $data Data attributes to pass
	 * @param array  $args Link configuration
	 * @return void
	 */
	function render_flyout_link( string $id, string $text, array $data = [], array $args = [] ): void {
		echo get_flyout_link( $id, $text, $data, $args );
	}
}