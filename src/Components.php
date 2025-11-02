<?php
/**
 * Component Registry
 *
 * Central registry for all flyout components. Manages component registration,
 * data resolution, and instantiation in a clean, extensible way.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @since       9.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Components\Domain\EntityHeader;
use ArrayPress\WPFlyout\Components\Domain\PaymentMethod;
use ArrayPress\WPFlyout\Components\Domain\PriceBreakdown;
use ArrayPress\WPFlyout\Components\Form\CardChoice;
use ArrayPress\WPFlyout\Components\Interactive\FileManager;
use ArrayPress\WPFlyout\Components\Interactive\Notes;
use ArrayPress\WPFlyout\Components\Interactive\OrderItems;
use ArrayPress\WPFlyout\Components\Layout\Accordion;
use ArrayPress\WPFlyout\Components\Data\Timeline;

/**
 * Class Components
 *
 * Manages registration and instantiation of flyout components.
 * Provides a centralized way to define component requirements and behaviors.
 *
 * @since 9.0.0
 */
class Components {

	/**
	 * Registered component configurations
	 *
	 * @since 9.0.0
	 * @var array<string, array{
	 *     class: class-string,
	 *     fields: array<string>|string,
	 *     asset?: string,
	 *     description?: string
	 * }>
	 */
	private static array $components = [];

	/**
	 * Whether default components have been initialized
	 *
	 * @since 9.0.0
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize default components
	 *
	 * Registers all built-in components with their configurations.
	 * This is called automatically when needed but can be called
	 * manually to ensure components are registered early.
	 *
	 * @return void
	 * @since 9.0.0
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Domain Components
		self::register( 'payment_method', [
			'class'       => PaymentMethod::class,
			'fields'      => [ 'payment_method', 'payment_brand', 'payment_last4' ],
			'asset'       => 'payment-method',
			'description' => 'Displays payment method with card brand icons'
		] );

		self::register( 'price_breakdown', [
			'class'       => PriceBreakdown::class,
			'fields'      => [ 'items', 'subtotal', 'tax', 'discount', 'shipping', 'total', 'currency' ],
			'asset'       => 'price-breakdown',
			'description' => 'Detailed price breakdown with line items and totals'
		] );

		self::register( 'entity_header', [
			'class'       => EntityHeader::class,
			'fields'      => [ 'title', 'subtitle', 'image', 'icon', 'badges', 'meta', 'description' ],
			'asset'       => null, // No special asset needed
			'description' => 'Unified header for any entity (customer, product, order)'
		] );

		// Interactive Components
		self::register( 'order_items', [
			'class'       => OrderItems::class,
			'fields'      => 'items', // Single field
			'asset'       => 'order-items',
			'description' => 'Order line items with quantities and pricing'
		] );

		self::register( 'notes', [
			'class'       => Notes::class,
			'fields'      => 'notes', // Single field
			'asset'       => 'notes',
			'description' => 'Notes/comments with add/delete functionality'
		] );

		self::register( 'files', [
			'class'       => FileManager::class,
			'fields'      => 'files', // Single field
			'asset'       => 'file-manager',
			'description' => 'File attachments with drag-drop sorting'
		] );

		// Form Components
		self::register( 'card_choice', [
			'class'       => CardChoice::class,
			'fields'      => [ 'options', 'value' ],
			'asset'       => 'card-choice',
			'description' => 'Card-style radio/checkbox selections'
		] );

		// Layout Components
		self::register( 'accordion', [
			'class'       => Accordion::class,
			'fields'      => 'items', // Single field
			'asset'       => 'accordion',
			'description' => 'Collapsible content sections'
		] );

		self::register( 'timeline', [
			'class'       => Timeline::class,
			'fields'      => 'events', // Single field
			'asset'       => 'timeline',
			'description' => 'Chronological event timeline'
		] );

		self::$initialized = true;

		/**
		 * Fires after default components are registered
		 *
		 * @param array $components Registered components array
		 *
		 * @since 9.0.0
		 */
		do_action( 'wp_flyout_components_init', self::$components );
	}

	/**
	 * Register a custom component
	 *
	 * Allows plugins to register their own component types.
	 *
	 * @param string      $type        Component type identifier
	 * @param array       $config      {
	 *                                 Component configuration
	 *
	 * @type class-string $class       Component class name
	 * @type array|string $fields      Fields to resolve from data (array or single string)
	 * @type string|null  $asset       Optional asset handle to enqueue
	 * @type string       $description Optional component description
	 *                                 }
	 * @return void
	 * @throws \InvalidArgumentException If component already registered or class doesn't exist
	 * @since 9.0.0
	 *
	 */
	public static function register( string $type, array $config ): void {
		// Validate configuration
		if ( ! isset( $config['class'] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Component "%s" must have a class defined', $type )
			);
		}

		if ( ! class_exists( $config['class'] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Component class "%s" does not exist', $config['class'] )
			);
		}

		// Warn if overriding existing component
		if ( isset( self::$components[ $type ] ) && self::$initialized ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf( 'Component type "%s" is already registered', $type ),
				'9.0.0'
			);
		}

		self::$components[ $type ] = $config;
	}

	/**
	 * Unregister a component
	 *
	 * Removes a component from the registry. Useful for replacing
	 * default components with custom implementations.
	 *
	 * @param string $type Component type to unregister
	 *
	 * @return bool True if component was unregistered, false if not found
	 * @since 9.0.0
	 *
	 */
	public static function unregister( string $type ): bool {
		if ( isset( self::$components[ $type ] ) ) {
			unset( self::$components[ $type ] );

			return true;
		}

		return false;
	}

	/**
	 * Get component configuration
	 *
	 * Retrieves the configuration for a registered component type.
	 *
	 * @param string $type Component type
	 *
	 * @return array|null Component configuration or null if not found
	 * @since 9.0.0
	 *
	 */
	public static function get( string $type ): ?array {
		self::ensure_initialized();

		return self::$components[ $type ] ?? null;
	}

	/**
	 * Get all registered components
	 *
	 * Returns all registered component configurations.
	 *
	 * @return array<string, array> All registered components
	 * @since 9.0.0
	 *
	 */
	public static function get_all(): array {
		self::ensure_initialized();

		return self::$components;
	}

	/**
	 * Check if type is a registered component
	 *
	 * @param string $type Component type to check
	 *
	 * @return bool True if component is registered
	 * @since 9.0.0
	 *
	 */
	public static function is_component( string $type ): bool {
		self::ensure_initialized();

		return isset( self::$components[ $type ] );
	}

	/**
	 * Create component instance
	 *
	 * Instantiates a component with the provided configuration.
	 *
	 * @param string $type   Component type
	 * @param array  $config Component configuration/data
	 *
	 * @return object|null Component instance or null if type not found
	 * @since 9.0.0
	 *
	 */
	public static function create( string $type, array $config ) {
		self::ensure_initialized();

		$component_config = self::get( $type );

		if ( ! $component_config || ! isset( $component_config['class'] ) ) {
			return null;
		}

		$class = $component_config['class'];

		/**
		 * Filter component configuration before instantiation
		 *
		 * @param array  $config Component configuration
		 * @param string $type   Component type
		 * @param string $class  Component class name
		 *
		 * @since 9.0.0
		 */
		$config = apply_filters( 'wp_flyout_component_config', $config, $type, $class );
		$config = apply_filters( "wp_flyout_component_{$type}_config", $config );

		return new $class( $config );
	}

	/**
	 * Resolve component data from a data source
	 *
	 * Intelligently resolves the required data fields for a component
	 * from an object or array data source.
	 *
	 * @param string $type      Component type
	 * @param string $field_key Field identifier
	 * @param mixed  $data      Data source (object or array)
	 *
	 * @return array Resolved data array ready to merge with field config
	 * @since 9.0.0
	 *
	 */
	public static function resolve_data( string $type, string $field_key, $data ): array {
		self::ensure_initialized();

		$component = self::get( $type );

		// For non-components, just resolve as simple value
		if ( ! $component || ! isset( $component['fields'] ) ) {
			return [ 'value' => self::resolve_value( $field_key, $data ) ];
		}

		// Try to resolve the field key as a complete dataset first
		$resolved = self::resolve_value( $field_key, $data );
		if ( is_array( $resolved ) ) {
			return $resolved;
		}

		// Handle single field components
		if ( is_string( $component['fields'] ) ) {
			$field_name = $component['fields'];
			$value      = self::resolve_value( $field_key, $data )
				?: self::resolve_value( $field_name, $data );

			return [ $field_name => $value ];
		}

		// Resolve multiple fields
		$result = [];
		foreach ( $component['fields'] as $field ) {
			$result[ $field ] = self::resolve_value( $field, $data );
		}

		return $result;
	}

	/**
	 * Get required asset for component
	 *
	 * Returns the asset handle that should be enqueued for this component.
	 *
	 * @param string $type Component type
	 *
	 * @return string|null Asset handle or null if no asset required
	 * @since 9.0.0
	 *
	 */
	public static function get_asset( string $type ): ?string {
		self::ensure_initialized();

		return self::$components[ $type ]['asset'] ?? null;
	}

	/**
	 * Get all required assets for registered components
	 *
	 * Returns unique list of all assets needed by registered components.
	 *
	 * @return array<string> Unique asset handles
	 * @since 9.0.0
	 *
	 */
	public static function get_all_assets(): array {
		self::ensure_initialized();

		$assets = [];
		foreach ( self::$components as $component ) {
			if ( ! empty( $component['asset'] ) ) {
				$assets[] = $component['asset'];
			}
		}

		return array_unique( $assets );
	}

	/**
	 * Resolve a single value from data source
	 *
	 * Attempts multiple strategies to extract a value from the data source:
	 * 1. Direct property access
	 * 2. Array key access
	 * 3. Getter method (get_field)
	 * 4. Direct method call (field())
	 * 5. Data suffix method (field_data())
	 *
	 * @param string $key  Property/method name to resolve
	 * @param mixed  $data Data source (object or array)
	 *
	 * @return mixed Resolved value or null if not found
	 * @since  9.0.0
	 * @access private
	 *
	 */
	private static function resolve_value( string $key, $data ) {
		if ( ! $data ) {
			return null;
		}

		// 1. Try direct property access
		if ( is_object( $data ) && property_exists( $data, $key ) ) {
			return $data->$key;
		}

		// 2. Try array key access
		if ( is_array( $data ) && isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}

		// Only try methods if we have an object
		if ( ! is_object( $data ) ) {
			return null;
		}

		// 3. Try getter method (get_field)
		$getter = 'get_' . $key;
		if ( method_exists( $data, $getter ) ) {
			return $data->$getter();
		}

		// 4. Try direct method call (field())
		if ( method_exists( $data, $key ) ) {
			return $data->$key();
		}

		// 5. Try with _data suffix (field_data())
		$data_method = $key . '_data';
		if ( method_exists( $data, $data_method ) ) {
			return $data->$data_method();
		}

		return null;
	}

	/**
	 * Ensure components are initialized
	 *
	 * Lazy initialization of default components.
	 *
	 * @return void
	 * @since  9.0.0
	 * @access private
	 *
	 */
	private static function ensure_initialized(): void {
		if ( ! self::$initialized ) {
			self::init();
		}
	}

	/**
	 * Reset registry
	 *
	 * Clears all registered components and resets initialization state.
	 * Primarily for testing purposes.
	 *
	 * @return void
	 * @internal
	 *
	 * @since 9.0.0
	 */
	public static function reset(): void {
		self::$components  = [];
		self::$initialized = false;
	}
}