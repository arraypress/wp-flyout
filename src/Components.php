<?php
/**
 * Component Registry
 *
 * Central registry for all flyout components.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Components\Domain\EntityHeader;
use ArrayPress\WPFlyout\Components\Domain\PaymentMethod;
use ArrayPress\WPFlyout\Components\Domain\PriceBreakdown;
use ArrayPress\WPFlyout\Components\Form\CardChoice;
use ArrayPress\WPFlyout\Components\Form\FormField;
use ArrayPress\WPFlyout\Components\Interactive\FileManager;
use ArrayPress\WPFlyout\Components\Interactive\Notes;
use ArrayPress\WPFlyout\Components\Interactive\OrderItems;
use ArrayPress\WPFlyout\Components\Layout\Accordion;
use ArrayPress\WPFlyout\Components\Data\Timeline;
use InvalidArgumentException;

/**
 * Class Components
 *
 * Manages registration and instantiation of flyout components.
 */
class Components {

	/**
	 * Registered component configurations
	 *
	 * @var array<string, array>
	 */
	private static array $components = [];

	/**
	 * Whether default components have been initialized
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize default components
	 *
	 * @return void
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
			'asset'       => null,
			'description' => 'Unified header for any entity'
		] );

		// Interactive Components
		self::register( 'order_items', [
			'class'       => OrderItems::class,
			'fields'      => 'items',
			'asset'       => 'order-items',
			'description' => 'Order line items with quantities and pricing'
		] );

		self::register( 'notes', [
			'class'       => Notes::class,
			'fields'      => 'items', // Changed from 'notes' to 'items' for consistency
			'asset'       => 'notes',
			'description' => 'Notes/comments with add/delete functionality'
		] );

		self::register( 'files', [
			'class'       => FileManager::class,
			'fields'      => 'items', // Changed from 'files' to 'items' for consistency
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
			'fields'      => 'items',
			'asset'       => 'accordion',
			'description' => 'Collapsible content sections'
		] );

		self::register( 'timeline', [
			'class'       => Timeline::class,
			'fields'      => 'events',
			'asset'       => 'timeline',
			'description' => 'Chronological event timeline'
		] );

		// Special form field types that need assets
		self::register( 'tags', [
			'class'       => FormField::class,
			'fields'      => 'value',
			'asset'       => 'tags',
			'description' => 'Tag input field'
		] );

		self::register( 'ajax_select', [
			'class'       => FormField::class,
			'fields'      => 'value',
			'asset'       => 'ajax-select',
			'description' => 'AJAX-powered select field'
		] );

		self::$initialized = true;

		do_action( 'wp_flyout_components_init', self::$components );
	}

	/**
	 * Register a custom component
	 *
	 * @param string $type   Component type identifier
	 * @param array  $config Component configuration
	 *
	 * @return void
	 * @throws InvalidArgumentException If component class doesn't exist
	 */
	public static function register( string $type, array $config ): void {
		if ( ! isset( $config['class'] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Component "%s" must have a class defined', $type )
			);
		}

		if ( ! class_exists( $config['class'] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Component class "%s" does not exist', $config['class'] )
			);
		}

		self::$components[ $type ] = $config;
	}

	/**
	 * Unregister a component
	 *
	 * @param string $type Component type to unregister
	 *
	 * @return bool True if component was unregistered
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
	 * @param string $type Component type
	 *
	 * @return array|null Component configuration or null if not found
	 */
	public static function get( string $type ): ?array {
		self::ensure_initialized();

		return self::$components[ $type ] ?? null;
	}

	/**
	 * Get all registered components
	 *
	 * @return array<string, array> All registered components
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
	 */
	public static function is_component( string $type ): bool {
		self::ensure_initialized();

		return isset( self::$components[ $type ] );
	}

	/**
	 * Create component instance
	 *
	 * @param string $type   Component type
	 * @param array  $config Component configuration
	 *
	 * @return object|null Component instance or null if type not found
	 */
	public static function create( string $type, array $config ) {
		self::ensure_initialized();

		$component_config = self::get( $type );
		if ( ! $component_config || ! isset( $component_config['class'] ) ) {
			return null;
		}

		$class = $component_config['class'];

		$config = apply_filters( 'wp_flyout_component_config', $config, $type, $class );
		$config = apply_filters( "wp_flyout_component_{$type}_config", $config );

		return new $class( $config );
	}

	/**
	 * Resolve component data from a data source
	 *
	 * @param string $type      Component type
	 * @param string $field_key Field identifier
	 * @param mixed  $data      Data source (object or array)
	 *
	 * @return array Resolved data array
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

			// For 'items' fields, try multiple resolutions
			if ( $field_name === 'items' ) {
				// Try the field key itself first
				$value = self::resolve_value( $field_key, $data );

				// If not found, try common alternatives based on the field key
				if ( $value === null ) {
					// For 'notes' field, try 'notes' property
					if ( $field_key === 'notes' ) {
						$value = self::resolve_value( 'notes', $data );
					} // For 'files' field, try 'files' or 'attachments'
					elseif ( $field_key === 'files' || $field_key === 'attachments' ) {
						$value = self::resolve_value( 'files', $data )
							?: self::resolve_value( 'attachments', $data );
					} // For 'order_items' field
					elseif ( $field_key === 'order_items' ) {
						$value = self::resolve_value( 'order_items', $data )
							?: self::resolve_value( 'items', $data );
					}
				}
			} else {
				$value = self::resolve_value( $field_key, $data )
					?: self::resolve_value( $field_name, $data );
			}

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
	 * Resolve a single value from data source (PUBLIC)
	 *
	 * @param string $key  Property/method name to resolve
	 * @param mixed  $data Data source (object or array)
	 *
	 * @return mixed Resolved value or null if not found
	 */
	public static function resolve_value( string $key, $data ) {
		if ( ! $data ) {
			return null;
		}

		// 1. Direct property access
		if ( is_object( $data ) && property_exists( $data, $key ) ) {
			return $data->$key;
		}

		// 2. Array key access
		if ( is_array( $data ) && isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}

		// Only try methods if we have an object
		if ( ! is_object( $data ) ) {
			return null;
		}

		// 3. Getter method (get_field)
		$getter = 'get_' . $key;
		if ( method_exists( $data, $getter ) ) {
			return $data->$getter();
		}

		// 4. Direct method call (field())
		if ( method_exists( $data, $key ) ) {
			return $data->$key();
		}

		// 5. Try with _data suffix (field_data())
		$data_method = $key . '_data';
		if ( method_exists( $data, $data_method ) ) {
			return $data->$data_method();
		}

		// 6. For underscore properties, try camelCase
		if ( str_contains( $key, '_' ) ) {
			$camelCase = lcfirst( str_replace( ' ', '', ucwords( str_replace( '_', ' ', $key ) ) ) );
			if ( method_exists( $data, $camelCase ) ) {
				return $data->$camelCase();
			}
		}

		return null;
	}

	/**
	 * Get required asset for component
	 *
	 * @param string $type Component type
	 *
	 * @return string|null Asset handle or null if no asset required
	 */
	public static function get_asset( string $type ): ?string {
		self::ensure_initialized();

		return self::$components[ $type ]['asset'] ?? null;
	}

	/**
	 * Get all required assets for registered components
	 *
	 * @return array<string> Unique asset handles
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
	 * Ensure components are initialized
	 *
	 * @return void
	 */
	private static function ensure_initialized(): void {
		if ( ! self::$initialized ) {
			self::init();
		}
	}

	/**
	 * Reset registry (for testing)
	 *
	 * @return void
	 * @internal
	 */
	public static function reset(): void {
		self::$components  = [];
		self::$initialized = false;
	}

}