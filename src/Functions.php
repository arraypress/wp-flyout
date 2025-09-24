<?php
/**
 * WP Flyout Helper Functions
 *
 * Global helper functions for easy integration of flyout functionality.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 */

declare( strict_types=1 );

use ArrayPress\WPFlyout\Flyout;
use ArrayPress\WPFlyout\Components\InfoGrid;
use ArrayPress\WPFlyout\Components\Badge;
use ArrayPress\WPFlyout\Components\FileManager;
use ArrayPress\WPFlyout\Components\FormField;
use ArrayPress\WPFlyout\Components\ActionBar;

if ( ! function_exists( 'wp_flyout' ) ) {
	/**
	 * Create a new flyout instance
	 *
	 * @param string $id     Flyout ID
	 * @param array  $config Optional configuration
	 *
	 * @return Flyout
	 */
	function wp_flyout( string $id, array $config = [] ): Flyout {
		return new Flyout( $id, $config );
	}
}

if ( ! function_exists( 'wp_flyout_info_grid' ) ) {
	/**
	 * Create an info grid component
	 *
	 * @param array $items  Grid items
	 * @param array $config Optional configuration
	 *
	 * @return InfoGrid
	 */
	function wp_flyout_info_grid( array $items = [], array $config = [] ): InfoGrid {
		return new InfoGrid( $items, $config );
	}
}

if ( ! function_exists( 'wp_flyout_badge' ) ) {
	/**
	 * Create a badge component
	 *
	 * @param string $text   Badge text
	 * @param array  $config Optional configuration
	 *
	 * @return Badge
	 */
	function wp_flyout_badge( string $text, array $config = [] ): Badge {
		return new Badge( $text, $config );
	}
}

if ( ! function_exists( 'wp_flyout_file_manager' ) ) {
	/**
	 * Create a file manager component
	 *
	 * @param array  $files       Initial files
	 * @param string $name_prefix Input name prefix
	 * @param array  $config      Optional configuration
	 *
	 * @return FileManager
	 */
	function wp_flyout_file_manager( array $files = [], string $name_prefix = 'files', array $config = [] ): FileManager {
		return new FileManager( $files, $name_prefix, $config );
	}
}

if ( ! function_exists( 'wp_flyout_form_field' ) ) {
	/**
	 * Create a form field component
	 *
	 * @param array $field Field configuration
	 *
	 * @return FormField
	 */
	function wp_flyout_form_field( array $field ): FormField {
		return new FormField( $field );
	}
}

if ( ! function_exists( 'wp_flyout_action_bar' ) ) {
	/**
	 * Create an action bar component
	 *
	 * @param array $config Optional configuration
	 *
	 * @return ActionBar
	 */
	function wp_flyout_action_bar( array $config = [] ): ActionBar {
		return new ActionBar( $config );
	}
}