<?php
/**
 * Component Registry
 *
 * Central registry for all flyout components.
 * Updated with Timeline using 'items' field for consistency.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.1.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Components\EntityHeader;
use ArrayPress\WPFlyout\Components\PaymentMethod;
use ArrayPress\WPFlyout\Components\PriceSummary;
use ArrayPress\WPFlyout\Components\CardChoice;
use ArrayPress\WPFlyout\Components\FormField;
use ArrayPress\WPFlyout\Components\FileManager;
use ArrayPress\WPFlyout\Components\Notes;
use ArrayPress\WPFlyout\Components\LineItems;
use ArrayPress\WPFlyout\Components\Accordion;
use ArrayPress\WPFlyout\Components\Timeline;
use ArrayPress\WPFlyout\Components\SectionHeader;
use ArrayPress\WPFlyout\Components\Separator;
use ArrayPress\WPFlyout\Components\EmptyState;
use ArrayPress\WPFlyout\Components\DataTable;
use ArrayPress\WPFlyout\Components\InfoGrid;
use ArrayPress\WPFlyout\Components\Alert;
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
			'data_fields' => [
				'payment_method',
				'payment_brand',
				'payment_last4',
				'stripe_risk_score',
				'stripe_risk_level'
			],
			'asset'       => 'payment-method',
			'description' => 'Displays payment method with card brand icons and risk indicators'
		] );

		self::register( 'price_summary', [
			'class'       => PriceSummary::class,
			'data_fields' => [ 'items', 'subtotal', 'tax', 'discount', 'total', 'currency' ],
			'asset'       => 'price-summary',
			'description' => 'Price summary with line items and totals'
		] );

		self::register( 'entity_header', [
			'class'       => EntityHeader::class,
			'data_fields' => [ 'title', 'subtitle', 'image', 'icon', 'badges', 'meta', 'description' ],
			'asset'       => null,
			'description' => 'Unified header for any entity'
		] );

		// Interactive Components
		self::register( 'line_items', [
			'class'       => LineItems::class,
			'data_fields' => 'items',
			'asset'       => 'line-items',
			'description' => 'Order line items with quantities and pricing'
		] );

		self::register( 'notes', [
			'class'       => Notes::class,
			'data_fields' => 'items',
			'asset'       => 'notes',
			'description' => 'Notes/comments with add/delete functionality'
		] );

		self::register( 'files', [
			'class'       => FileManager::class,
			'data_fields' => 'items',
			'asset'       => 'file-manager',
			'description' => 'File attachments with drag-drop sorting'
		] );

		// Form Components
		self::register( 'card_choice', [
			'class'       => CardChoice::class,
			'data_fields' => [ 'options', 'value' ],
			'asset'       => 'card-choice',
			'description' => 'Card-style radio/checkbox selections'
		] );

		// Layout Components
		self::register( 'accordion', [
			'class'       => Accordion::class,
			'data_fields' => 'items',
			'asset'       => 'accordion',
			'description' => 'Collapsible content sections'
		] );

		self::register( 'timeline', [
			'class'       => Timeline::class,
			'data_fields' => 'items',
			'asset'       => 'timeline',
			'description' => 'Chronological event timeline'
		] );

		// Special form field types that need assets
		self::register( 'tags', [
			'class'       => FormField::class,
			'data_fields' => 'value',
			'asset'       => 'tags',
			'description' => 'Tag input field'
		] );

		self::register( 'ajax_select', [
			'class'       => FormField::class,
			'data_fields' => 'value',
			'asset'       => 'ajax-select',
			'description' => 'AJAX-powered select field'
		] );

		// Display Components
		self::register( 'section_header', [
			'class'       => SectionHeader::class,
			'data_fields' => [ 'title', 'description', 'icon' ],
			'asset'       => null,
			'description' => 'Section headers with optional descriptions'
		] );

		self::register( 'separator', [
			'class'       => Separator::class,
			'data_fields' => [ 'text', 'icon' ],
			'asset'       => null,
			'description' => 'Visual dividers with optional text'
		] );

		self::register( 'empty_state', [
			'class'       => EmptyState::class,
			'data_fields' => [ 'icon', 'title', 'description', 'action_text' ],
			'asset'       => null,
			'description' => 'Empty state messages'
		] );

		self::register( 'data_table', [
			'class'       => DataTable::class,
			'data_fields' => [ 'columns', 'data' ],
			'asset'       => null,
			'description' => 'Structured data table display'
		] );

		self::register( 'info_grid', [
			'class'       => InfoGrid::class,
			'data_fields' => 'items',
			'asset'       => null,
			'description' => 'Information grid layout'
		] );

		self::register( 'alert', [
			'class'       => Alert::class,
			'data_fields' => [ 'type', 'message', 'title' ],
			'asset'       => null,
			'description' => 'Alert messages with various styles'
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
		if ( ! $component || ! isset( $component['data_fields'] ) ) {
			return [ 'value' => self::resolve_value( $field_key, $data ) ];
		}

		// This handles cases like pricing => ['items' => ..., 'subtotal' => ...]
		$resolved = self::resolve_value( $field_key, $data );

		// If we found an array at field_key, check if it has the fields we need
		if ( is_array( $resolved ) ) {
			// For string fields (like 'items'), check if it exists in resolved data
			if ( is_string( $component['data_fields'] ) ) {
				$field_name = $component['data_fields'];
				// If the resolved array has the field we need, return the whole array
				if ( isset( $resolved[ $field_name ] ) ) {
					return $resolved;
				}

				// Otherwise return the field name => resolved value
				return [ $field_name => $resolved ];
			}

			// For array fields, check if resolved has all/most required fields
			if ( is_array( $component['data_fields'] ) ) {
				$has_any_field = false;
				foreach ( $component['data_fields'] as $field ) {
					if ( isset( $resolved[ $field ] ) ) {
						$has_any_field = true;
						break;
					}
				}
				// If the resolved array contains any of our expected fields, use it
				if ( $has_any_field ) {
					return $resolved;
				}
			}
		}

		// Handle single field components (like 'items' for notes, timeline, etc.)
		if ( is_string( $component['data_fields'] ) ) {
			$field_name = $component['data_fields'];
			// Try to resolve the value
			$value = self::resolve_value( $field_key, $data );

			// ALWAYS return with the proper field name as key
			return [ $field_name => $value ];
		}

		// Otherwise resolve each field individually (fallback for scattered fields)
		$result = [];
		foreach ( $component['data_fields'] as $field ) {
			// Special case: 'value' should use the field_key
			if ( $field === 'value' ) {
				$result[ $field ] = self::resolve_value( $field_key, $data );
			} else {
				$result[ $field ] = self::resolve_value( $field, $data );
			}
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