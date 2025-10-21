<?php
/**
 * WP Flyout Component Helper Functions
 *
 * Helper functions for quickly creating flyout UI components.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

use ArrayPress\WPFlyout\Assets;
use ArrayPress\WPFlyout\Components\Accordion;
use ArrayPress\WPFlyout\Components\ActionBar;
use ArrayPress\WPFlyout\Components\Badge;
use ArrayPress\WPFlyout\Components\CardChoice;
use ArrayPress\WPFlyout\Components\Collapsible;
use ArrayPress\WPFlyout\Components\Confirmation;
use ArrayPress\WPFlyout\Components\EmptyState;
use ArrayPress\WPFlyout\Components\FeatureList;
use ArrayPress\WPFlyout\Components\FileManager;
use ArrayPress\WPFlyout\Components\FormField;
use ArrayPress\WPFlyout\Components\InfoGrid;
use ArrayPress\WPFlyout\Components\SectionHeader;
use ArrayPress\WPFlyout\Components\DataTable;
use ArrayPress\WPFlyout\Components\Separator;
use ArrayPress\WPFlyout\Components\SimpleList;
use ArrayPress\WPFlyout\Components\Spinner;
use ArrayPress\WPFlyout\Components\TagInput;
use ArrayPress\WPFlyout\Components\Toggle;

if ( ! function_exists( 'wp_flyout_action_bar' ) ) {
	/**
	 * Create an action bar component
	 *
	 * @param array $config Optional configuration.
	 *
	 * @return ActionBar
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_action_bar( array $config = [] ): ActionBar {
		return new ActionBar( $config );
	}
}

if ( ! function_exists( 'wp_flyout_badge' ) ) {
	/**
	 * Create a badge component
	 *
	 * @param string $text   Badge text.
	 * @param array  $config Optional configuration.
	 *
	 * @return Badge
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_badge( string $text, array $config = [] ): Badge {
		return new Badge( $text, $config );
	}
}

if ( ! function_exists( 'wp_flyout_empty_state' ) ) {
	/**
	 * Create an empty state component
	 *
	 * @param string $title  Title text.
	 * @param array  $config Optional configuration.
	 *
	 * @return EmptyState
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_empty_state( string $title, array $config = [] ): EmptyState {
		return new EmptyState( $title, $config );
	}
}

if ( ! function_exists( 'wp_flyout_feature_list' ) ) {
	/**
	 * Create a feature list component
	 *
	 * @param array $items  List items.
	 * @param array $config Optional configuration.
	 *
	 * @return FeatureList
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_feature_list( array $items, array $config = [] ): FeatureList {
		return new FeatureList( $items, $config );
	}
}

if ( ! function_exists( 'wp_flyout_form_field' ) ) {
	/**
	 * Create a form field component
	 *
	 * @param string $type  Field type.
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param array  $args  Additional arguments.
	 *
	 * @return FormField
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_form_field( string $type, string $name, string $label, array $args = [] ): FormField {
		return new FormField( array_merge( [
			'type'  => $type,
			'name'  => $name,
			'label' => $label,
		], $args ) );
	}
}

if ( ! function_exists( 'wp_flyout_info_grid' ) ) {
	/**
	 * Create an info grid component
	 *
	 * @param array $items  Grid items.
	 * @param array $config Optional configuration.
	 *
	 * @return InfoGrid
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_info_grid( array $items = [], array $config = [] ): InfoGrid {
		return new InfoGrid( $items, $config );
	}
}

if ( ! function_exists( 'wp_flyout_section_header' ) ) {
	/**
	 * Create a section header component
	 *
	 * @param string      $title       Section title.
	 * @param string      $description Optional description.
	 * @param string|null $icon        Optional dashicon.
	 *
	 * @return SectionHeader
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_section_header( string $title, string $description = '', ?string $icon = null ): SectionHeader {
		return SectionHeader::create( $title, $description, $icon );
	}
}

if ( ! function_exists( 'wp_flyout_toggle' ) ) {
	/**
	 * Create a toggle component
	 *
	 * @param string $name    Field name.
	 * @param string $label   Toggle label.
	 * @param bool   $checked Whether checked.
	 * @param array  $config  Optional configuration.
	 *
	 * @return Toggle
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_toggle( string $name, string $label, bool $checked = false, array $config = [] ): Toggle {
		return Toggle::create( $name, $label, $checked, $config );
	}
}

/* =========================================
   FORM HELPER SHORTCUTS
   Quick access to FormField static methods
   ========================================= */

if ( ! function_exists( 'wp_flyout_hidden_field' ) ) {
	/**
	 * Generate a hidden field
	 *
	 * @param string $name  Field name.
	 * @param mixed  $value Field value.
	 *
	 * @return string HTML for hidden field.
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_hidden_field( string $name, $value ): string {
		return FormField::hidden( $name, $value );
	}
}

if ( ! function_exists( 'wp_flyout_nonce_field' ) ) {
	/**
	 * Generate a nonce field
	 *
	 * @param string $action Nonce action.
	 * @param string $name   Field name.
	 *
	 * @return string HTML for nonce field.
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_nonce_field( string $action, string $name = '_wpnonce' ): string {
		return FormField::nonce( $action, $name );
	}
}

if ( ! function_exists( 'wp_flyout_form_security' ) ) {
	/**
	 * Generate form security fields (nonce + optional referer)
	 *
	 * @param string $nonce_action    Nonce action.
	 * @param bool   $include_referer Whether to include referer field.
	 *
	 * @return string HTML for security fields.
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_form_security( string $nonce_action, bool $include_referer = false ): string {
		return FormField::security( $nonce_action, $include_referer );
	}
}

if ( ! function_exists( 'wp_flyout_data_table' ) ) {
	/**
	 * Create a data table component
	 *
	 * @param array $data   Data array (key => value pairs).
	 * @param array $config Optional configuration.
	 *
	 * @return DataTable
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_data_table( array $data = [], array $config = [] ): DataTable {
		return new DataTable( $data, $config );
	}
}

if ( ! function_exists( 'wp_flyout_enqueue_component' ) ) {
	/**
	 * Enqueue specific flyout component assets
	 *
	 * @param string $component Component name (e.g., 'file-manager')
	 *
	 * @return bool Whether component was enqueued successfully
	 */
	function wp_flyout_enqueue_component( string $component ): bool {
		return Assets::enqueue_component( $component );
	}
}

if ( ! function_exists( 'wp_flyout_has_component' ) ) {
	/**
	 * Check if a component is available
	 *
	 * @param string $component Component name
	 *
	 * @return bool
	 */
	function wp_flyout_has_component( string $component ): bool {
		return Assets::has_component( $component );
	}
}

if ( ! function_exists( 'wp_flyout_file_manager' ) ) {
	/**
	 * Create a file manager component
	 *
	 * @param array  $files       Initial files array
	 * @param string $name_prefix Input name prefix
	 * @param array  $config      Optional configuration
	 *
	 * @return FileManager
	 */
	function wp_flyout_file_manager( array $files = [], string $name_prefix = 'files', array $config = [] ): FileManager {
		return new FileManager( $files, $name_prefix, $config );
	}
}

if ( ! function_exists( 'wp_flyout_tag_input' ) ) {
	/**
	 * Create a tag input component
	 *
	 * @param string $name   Field name.
	 * @param string $label  Field label.
	 * @param array  $config Optional configuration.
	 *
	 * @return TagInput
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_tag_input( string $name, string $label, array $config = [] ): TagInput {
		return new TagInput( $name, $label, $config );
	}
}

if ( ! function_exists( 'wp_flyout_accordion' ) ) {
	/**
	 * Create an accordion component
	 *
	 * @param array $config Optional configuration.
	 *
	 * @return Accordion
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_accordion( array $config = [] ): Accordion {
		return new Accordion( $config );
	}
}

if ( ! function_exists( 'wp_flyout_collapsible' ) ) {
	/**
	 * Create a collapsible component
	 *
	 * @param string $title   Section title.
	 * @param string $content Section content.
	 * @param array  $config  Optional configuration.
	 *
	 * @return Collapsible
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_collapsible( string $title, string $content, array $config = [] ): Collapsible {
		return Collapsible::create( $title, $content, $config );
	}
}

if ( ! function_exists( 'wp_flyout_separator' ) ) {
	/**
	 * Create a separator component
	 *
	 * @param string $text Optional text label.
	 *
	 * @return Separator
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_separator( string $text = '' ): Separator {
		return new Separator( $text );
	}
}

if ( ! function_exists( 'wp_flyout_spinner' ) ) {
	/**
	 * Create a spinner component
	 *
	 * @param array $config Optional configuration.
	 *
	 * @return Spinner
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_spinner( array $config = [] ): Spinner {
		return new Spinner( $config );
	}
}

if ( ! function_exists( 'wp_flyout_simple_list' ) ) {
	/**
	 * Create a simple list component
	 *
	 * @param array $items  List items.
	 * @param array $config Optional configuration.
	 *
	 * @return SimpleList
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_simple_list( array $items = [], array $config = [] ): SimpleList {
		return new SimpleList( $items, $config );
	}
}

if ( ! function_exists( 'wp_flyout_confirmation' ) ) {
	/**
	 * Create a confirmation component
	 *
	 * @param string $message Confirmation message.
	 * @param array  $config  Optional configuration.
	 *
	 * @return Confirmation
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_confirmation( string $message, array $config = [] ): Confirmation {
		return new Confirmation( $message, $config );
	}
}

if ( ! function_exists( 'wp_flyout_card_choice' ) ) {
	/**
	 * Create a card choice component
	 *
	 * @param string $name   Field name.
	 * @param array  $config Configuration options.
	 *
	 * @return CardChoice
	 * @since 1.0.0
	 *
	 */
	function wp_flyout_card_choice( string $name, array $config = [] ): CardChoice {
		return new CardChoice( $name, $config );
	}
}