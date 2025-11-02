<?php
/**
 * WP Flyout Manager with Smart Data Resolution
 *
 * Manages flyout registration, AJAX handling, and automatic data mapping.
 * Provides a declarative API with intelligent property/method resolution.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     8.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Components\Domain\PriceBreakdown;
use ArrayPress\WPFlyout\Components\Form\FormField;
use ArrayPress\WPFlyout\Components\Core\ActionBar;
use ArrayPress\WPFlyout\Components\Interactive\OrderItems;
use ArrayPress\WPFlyout\Components\Interactive\Notes;
use ArrayPress\WPFlyout\Components\Interactive\FileManager;
use ArrayPress\WPFlyout\Components\Domain\EntityHeader;
use ArrayPress\WPFlyout\Components\Domain\PaymentMethod;
use ArrayPress\WPFlyout\Components\Form\CardChoice;
use Exception;

/**
 * Class Manager
 *
 * Orchestrates flyout operations with automatic data resolution and smart mapping.
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $prefix;

	/**
	 * Registered flyout configurations
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $flyouts = [];

	/**
	 * Admin pages where assets should load
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Components required across all flyouts
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $components = [];

	/**
	 * Whether assets have been enqueued
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Complex component types that may use _data suffix
	 *
	 * @since 8.0.0
	 * @var array
	 */
	private const COMPLEX_COMPONENTS = [
		'entity_header',
		'payment_method',
		'price_breakdown',
		'order_items',
		'notes',
		'files',
		'card_choice'
	];

	/**
	 * Map of field types to required asset components
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private const FIELD_COMPONENT_MAP = [
		'order_items'     => 'order-items',
		'notes'           => 'notes',
		'files'           => 'file-manager',
		'ajax_select'     => 'ajax-select',
		'tags'            => 'tags',
		'price_breakdown' => 'price-breakdown',
		'entity_header'   => 'entity-header',
		'payment_method'  => 'payment-method',
		'card_choice'     => 'card-choice',
		'accordion'       => 'accordion',
		'timeline'        => 'timeline'
	];

	/**
	 * Constructor
	 *
	 * @param string $prefix Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );

		// Single AJAX handler for all actions
		add_action( 'wp_ajax_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );

		// Auto-enqueue assets on admin pages
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Register a flyout with declarative configuration
	 *
	 * Fields are automatically mapped to data properties/methods using smart resolution:
	 * 1. Direct property access ($data->field)
	 * 2. Array key access ($data['field'])
	 * 3. Getter method ($data->get_field())
	 * 4. Direct method ($data->field())
	 * 5. Complex components also try _data suffix methods
	 *
	 * @param string  $id          Unique flyout identifier
	 * @param array   $config      {
	 *                             Flyout configuration array
	 *
	 * @type string   $title       Flyout title
	 * @type string   $width       Width size: 'small', 'medium', 'large', 'full'
	 * @type array    $panels      Panel configurations with labels or badge options
	 * @type array    $fields      Field configurations with automatic data mapping
	 * @type array    $actions     Footer action buttons
	 * @type string   $capability  Required capability (default: 'manage_options')
	 * @type array    $admin_pages Admin page hooks to load on
	 * @type callable $load        Function to load data: function($id)
	 * @type callable $save        Function to save data: function($id, $data)
	 * @type callable $delete      Function to delete data: function($id)
	 *                             }
	 * @return self Returns instance for method chaining
	 * @since 8.0.0 Added smart data resolution and panel support
	 *
	 * @since 1.0.0
	 */
	public function register_flyout( string $id, array $config ): self {
		$defaults = [
			'title'       => '',
			'width'       => 'medium',
			'panels'      => [],
			'fields'      => [],
			'actions'     => [],
			'capability'  => 'manage_options',
			'admin_pages' => [],
			'load'        => null,
			'save'        => null,
			'delete'      => null,
		];

		$config = wp_parse_args( $config, $defaults );

		// Auto-detect required components
		$this->detect_components( $config );

		// Track admin pages for asset loading
		if ( ! empty( $config['admin_pages'] ) ) {
			$this->admin_pages = array_unique(
				array_merge( $this->admin_pages, $config['admin_pages'] )
			);
		}

		// Store flyout configuration
		$this->flyouts[ $id ] = $config;

		// Register AJAX endpoints for components if needed
		$this->register_component_endpoints( $id, $config );

		return $this;
	}

	/**
	 * Central AJAX handler with security checks
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	public function handle_ajax(): void {
		try {
			// Extract and validate request
			$request = $this->validate_request();

			// Get flyout config
			$config = $this->flyouts[ $request['flyout_id'] ];

			// Route to appropriate handler
			switch ( $request['action'] ) {
				case 'load':
					$result = $this->handle_load( $config, $request );
					break;

				case 'save':
					$result = $this->handle_save( $config, $request );
					break;

				case 'delete':
					$result = $this->handle_delete( $config, $request );
					break;

				default:
					throw new Exception( 'Invalid action', 400 );
			}

			$this->send_response( $result );

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), $e->getCode() ?: 500 );
		}
	}

	/**
	 * Validate AJAX request and check permissions
	 *
	 * @return array Validated request data
	 * @throws Exception If validation fails
	 * @since 1.0.0
	 */
	private function validate_request(): array {
		// Get request parameters
		$flyout_id = sanitize_key( $_POST['flyout'] ?? '' );
		$action    = sanitize_key( $_POST['flyout_action'] ?? 'load' );

		// Validate flyout exists
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			throw new Exception( 'Invalid flyout', 400 );
		}

		$config = $this->flyouts[ $flyout_id ];

		// Check nonce
		if ( ! check_ajax_referer( 'wp_flyout_' . $this->prefix . '_' . $flyout_id, 'nonce', false ) ) {
			throw new Exception( 'Security check failed', 403 );
		}

		// Check capabilities
		if ( ! current_user_can( $config['capability'] ) ) {
			throw new Exception( 'Insufficient permissions', 403 );
		}

		return [
			'flyout_id' => $flyout_id,
			'action'    => $action,
			'id'        => $_POST['id'] ?? null,
			'form_data' => $_POST['form_data'] ?? '',
		];
	}

	/**
	 * Handle load action
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return Flyout Configured flyout instance
	 * @since 1.0.0
	 */
	private function handle_load( array $config, array $request ): Flyout {
		// Get data from callback if provided
		$data = [];
		if ( $config['load'] && is_callable( $config['load'] ) ) {
			$data = call_user_func( $config['load'], $request['id'] );

			// Allow callbacks to return false to indicate not found
			if ( $data === false ) {
				throw new Exception( 'Record not found', 404 );
			}
		}

		// Build flyout interface
		$flyout = $this->build_flyout( $config, $data, $request['id'] );

		return $flyout;
	}

	/**
	 * Handle save action
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return array Response array
	 * @since 1.0.0
	 */
	private function handle_save( array $config, array $request ): array {
		if ( ! $config['save'] || ! is_callable( $config['save'] ) ) {
			throw new Exception( 'Save not configured', 501 );
		}

		// Parse form data
		parse_str( $request['form_data'], $form_data );
		$id = $form_data['id'] ?? $request['id'] ?? null;

		// Call save handler
		$result = call_user_func( $config['save'], $id, $form_data );

		// Normalize response
		if ( $result === true ) {
			return [
				'success' => true,
				'message' => __( 'Saved successfully', 'wp-flyout' ),
				'reload'  => true
			];
		}

		if ( $result === false ) {
			throw new Exception( 'Save failed', 500 );
		}

		return is_array( $result ) ? $result : [ 'success' => true ];
	}

	/**
	 * Handle delete action
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return array Response array
	 * @since 1.0.0
	 */
	private function handle_delete( array $config, array $request ): array {
		if ( ! $config['delete'] || ! is_callable( $config['delete'] ) ) {
			throw new Exception( 'Delete not configured', 501 );
		}

		// Call delete handler
		$result = call_user_func( $config['delete'], $request['id'] );

		// Normalize response
		if ( $result === true ) {
			return [
				'success' => true,
				'message' => __( 'Deleted successfully', 'wp-flyout' ),
				'reload'  => true
			];
		}

		if ( $result === false ) {
			throw new Exception( 'Delete failed', 500 );
		}

		return is_array( $result ) ? $result : [ 'success' => true ];
	}

	/**
	 * Build flyout interface
	 *
	 * @param array  $config Flyout configuration
	 * @param mixed  $data   Data for field population
	 * @param string $id     Record ID if editing
	 *
	 * @return Flyout Configured flyout instance
	 * @since 1.0.0
	 * @since 8.0.0 Updated to support panels instead of tabs
	 *
	 */
	private function build_flyout( array $config, $data, $id = null ): Flyout {
		$flyout_instance_id = $config['id'] ?? uniqid() . '_' . ( $id ?: 'new' );
		$flyout             = new Flyout( $flyout_instance_id );

		// Set basic properties
		$flyout->set_title( $config['title'] );
		$flyout->set_width( $config['width'] );

		// Build interface with panels or single view
		if ( ! empty( $config['panels'] ) ) {
			$this->build_panel_interface( $flyout, $config['panels'], $config['fields'], $data );
		} else {
			$content = $this->render_fields( $config['fields'], $data );
			$flyout->add_content( '', $content );
		}

		// Add hidden ID field if editing
		if ( $id ) {
			$flyout->add_content( '', sprintf(
				'<input type="hidden" name="id" value="%s">',
				esc_attr( $id )
			) );
		}

		// Add footer actions
		$actions = ! empty( $config['actions'] )
			? $config['actions']
			: $this->get_default_actions( $config );

		$flyout->set_footer( $this->render_actions( $actions ) );

		return $flyout;
	}

	/**
	 * Build panel interface for flyout
	 *
	 * @param Flyout $flyout Flyout instance
	 * @param array  $panels Panel configurations
	 * @param array  $fields All field configurations
	 * @param mixed  $data   Data for field population
	 *
	 * @return void
	 * @since 8.0.0
	 */
	private function build_panel_interface( Flyout $flyout, array $panels, array $fields, $data ): void {
		// Group fields by panel
		$fields_by_panel = [];
		foreach ( $fields as $key => $field ) {
			$panel = $field['panel'] ?? $field['tab'] ?? 'default';
			if ( ! isset( $fields_by_panel[ $panel ] ) ) {
				$fields_by_panel[ $panel ] = [];
			}
			$fields_by_panel[ $panel ][ $key ] = $field;
		}

		// Add panels with their fields
		foreach ( $panels as $panel_id => $panel_config ) {
			// Handle both simple string labels and array configs
			$label = is_array( $panel_config ) ? $panel_config['label'] : $panel_config;
			$badge = is_array( $panel_config ) ? ( $panel_config['badge'] ?? null ) : null;

			$is_first = array_key_first( $panels ) === $panel_id;
			$flyout->add_tab( $panel_id, $label, $is_first );

			// Render fields for this panel
			$panel_fields = $fields_by_panel[ $panel_id ] ?? [];
			$content      = $this->render_fields( $panel_fields, $data );
			$flyout->set_tab_content( $panel_id, $content );
		}
	}

	/**
	 * Render fields from configuration with smart data resolution
	 *
	 * @param array $fields Field configurations
	 * @param mixed $data   Data object or array
	 *
	 * @return string Generated HTML
	 * @since 8.0.0 Added smart data resolution
	 *
	 * @since 1.0.0
	 */
	private function render_fields( array $fields, $data ): string {
		$output = '';

		foreach ( $fields as $field_key => $field ) {
			// Use field key as default name if not specified
			if ( ! isset( $field['name'] ) ) {
				$field['name'] = $field_key;
			}

			// Smart data resolution
			if ( ! isset( $field['value'] ) && ! isset( $field['items'] ) ) {
				$resolved_data = $this->resolve_field_data( $field_key, $field['type'] ?? 'text', $data );

				// Set value or items based on field type
				if ( in_array( $field['type'] ?? '', [ 'notes', 'files', 'order_items' ] ) ) {
					$field['items'] = $resolved_data;
				} else {
					$field['value'] = $resolved_data;
				}
			}

			// Check if field type requires special component handling
			if ( in_array( $field['type'] ?? '', self::COMPLEX_COMPONENTS, true ) ) {
				$output .= $this->render_component_field( $field_key, $field, $data );
			} else {
				// Standard form field
				$form_field = new FormField( $field );
				$output     .= $form_field->render();
			}
		}

		return $output;
	}

	/**
	 * Resolve field data using smart property/method resolution
	 *
	 * Resolution order:
	 * 1. Direct property ($data->field)
	 * 2. Array key ($data['field'])
	 * 3. Getter method ($data->get_field())
	 * 4. Direct method ($data->field())
	 * 5. For complex components, also try _data suffix
	 *
	 * @param string $field_key  Field identifier
	 * @param string $field_type Field type
	 * @param mixed  $data       Data source (object or array)
	 *
	 * @return mixed Resolved data or null
	 * @since 8.0.0
	 */
	private function resolve_field_data( string $field_key, string $field_type, $data ) {
		if ( ! $data ) {
			return null;
		}

		// Try standard resolution
		$value = $this->try_standard_resolution( $field_key, $data );

		if ( $value !== null ) {
			return $value;
		}

		// For complex components, also try with _data suffix
		if ( in_array( $field_type, self::COMPLEX_COMPONENTS, true ) ) {
			return $this->try_standard_resolution( $field_key . '_data', $data );
		}

		return null;
	}

	/**
	 * Try standard property/method resolution
	 *
	 * @param string $key  Property/method name to resolve
	 * @param mixed  $data Data source
	 *
	 * @return mixed Resolved value or null
	 * @since 8.0.0
	 */
	private function try_standard_resolution( string $key, $data ) {
		// 1. Direct property access
		if ( is_object( $data ) && property_exists( $data, $key ) ) {
			return $data->$key;
		}

		// 2. Array key access
		if ( is_array( $data ) && isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}

		// 3. Getter method (get_field)
		if ( is_object( $data ) ) {
			$getter = 'get_' . $key;
			if ( method_exists( $data, $getter ) ) {
				return $data->$getter();
			}

			// 4. Direct method (field())
			if ( method_exists( $data, $key ) ) {
				return $data->$key();
			}
		}

		return null;
	}

	/**
	 * Render component-based field
	 *
	 * @param string $field_key Field key
	 * @param array  $field     Field configuration
	 * @param mixed  $data      Original data object
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 * @since 8.0.0 Simplified to work with resolved data
	 *
	 */
	private function render_component_field( string $field_key, array $field, $data ): string {
		$type = $field['type'];

		switch ( $type ) {
			case 'order_items':
				$component = new OrderItems( $field );

				return $component->render();

			case 'notes':
				$component = new Notes( $field );

				return $component->render();

			case 'files':
				$component = new FileManager( $field );

				return $component->render();

			case 'price_breakdown':
				$component = new PriceBreakdown( $field );

				return $component->render();

			case 'entity_header':
				$component = new EntityHeader( $field );

				return $component->render();

			case 'payment_method':
				$component = new PaymentMethod( $field );

				return $component->render();

			case 'card_choice':
				$component = new CardChoice( $field );

				return $component->render();
		}

		return '';
	}

	/**
	 * Render action buttons for footer
	 *
	 * @param array $actions Action button configurations
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_actions( array $actions ): string {
		$action_bar = new ActionBar( [ 'actions' => $actions ] );

		return $action_bar->render();
	}

	/**
	 * Get default action buttons based on configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return array Default action buttons
	 * @since 1.0.0
	 */
	private function get_default_actions( array $config ): array {
		$actions = [];

		// Add save button if save handler exists
		if ( ! empty( $config['save'] ) ) {
			$actions[] = [
				'text'  => __( 'Save', 'wp-flyout' ),
				'style' => 'primary',
				'class' => 'wp-flyout-save'
			];
		}

		// Add delete button if delete handler exists
		if ( ! empty( $config['delete'] ) ) {
			$actions[] = [
				'text'  => __( 'Delete', 'wp-flyout' ),
				'style' => 'link-delete',
				'class' => 'wp-flyout-delete'
			];
		}

		// Always add cancel button
		$actions[] = [
			'text'  => __( 'Cancel', 'wp-flyout' ),
			'class' => 'wp-flyout-close'
		];

		return $actions;
	}

	/**
	 * Detect and register required components from configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function detect_components( array $config ): void {
		// Collect all fields
		$all_fields = [];
		foreach ( $config['fields'] as $field ) {
			$all_fields[] = $field;
		}

		// Detect required components from field types
		foreach ( $all_fields as $field ) {
			$type = $field['type'] ?? 'text';
			if ( isset( self::FIELD_COMPONENT_MAP[ $type ] ) ) {
				$this->components[] = self::FIELD_COMPONENT_MAP[ $type ];
			}
		}

		$this->components = array_unique( $this->components );
	}

	/**
	 * Register AJAX endpoints for components
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $config    Flyout configuration
	 *
	 * @return void
	 * @since 8.0.0 Updated to support ajax_ prefix naming
	 *
	 * @since 1.0.0
	 */
	private function register_component_endpoints( string $flyout_id, array $config ): void {
		foreach ( $config['fields'] as $field ) {
			// Check for AJAX endpoints in field config
			foreach ( $field as $key => $value ) {
				if ( str_starts_with( $key, 'ajax_' ) && is_string( $value ) ) {
					// Register the endpoint if a callback is provided
					$callback_key = str_replace( 'ajax_', '', $key ) . '_callback';
					if ( isset( $field[ $callback_key ] ) ) {
						$this->register_ajax_endpoint( $value, $field[ $callback_key ] );
					}
				}
			}
		}
	}

	/**
	 * Register a single AJAX endpoint
	 *
	 * @param string        $endpoint Endpoint name
	 * @param callable|null $callback Callback function
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function register_ajax_endpoint( string $endpoint, $callback = null ): void {
		if ( ! $callback || ! is_callable( $callback ) ) {
			return;
		}

		$action = 'wp_ajax_' . $endpoint;

		if ( ! has_action( $action ) ) {
			add_action( $action, $callback );
		}
	}

	/**
	 * Send AJAX response based on result type
	 *
	 * @param mixed $result Callback result
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function send_response( $result ): void {
		// Handle Flyout object
		if ( $result instanceof Flyout ) {
			wp_send_json_success( [
				'html' => $result->render()
			] );
		}

		// Handle array response
		if ( is_array( $result ) ) {
			if ( isset( $result['success'] ) && ! $result['success'] ) {
				wp_send_json_error(
					$result['message'] ?? 'An error occurred',
					$result['code'] ?? 400
				);
			}
			wp_send_json_success( $result );
		}

		// Handle string (raw HTML)
		if ( is_string( $result ) ) {
			wp_send_json_success( [
				'html' => $result
			] );
		}

		// Invalid response type
		wp_send_json_error( 'Invalid response from handler', 500 );
	}

	/**
	 * Render a trigger button
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Button configuration
	 *
	 * @return void Outputs HTML
	 * @since 1.0.0
	 */
	public function button( string $flyout_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $flyout_id, $data, $args );
	}

	/**
	 * Get trigger button HTML
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Button configuration
	 *
	 * @return string Button HTML or empty string if unauthorized
	 * @since 1.0.0
	 */
	public function get_button( string $flyout_id, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$text  = $args['text'] ?? __( 'Open', 'wp-flyout' );
		$class = $args['class'] ?? 'button';
		$icon  = $args['icon'] ?? '';

		$attrs = $this->build_trigger_attributes( $flyout_id, $data, 'button ' . $class );

		// Build HTML
		$html = '<button type="button"';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>';

		// Add icon if specified
		if ( $icon ) {
			$html .= sprintf(
				'<span class="dashicons dashicons-%s"></span> ',
				esc_attr( $icon )
			);
		}

		$html .= esc_html( $text );
		$html .= '</button>';

		return $html;
	}

	/**
	 * Create a trigger link
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param string $text      Link text
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Additional link arguments
	 *
	 * @return string Link HTML or empty string if unauthorized
	 * @since 1.0.0
	 */
	public function link( string $flyout_id, string $text, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$class         = $args['class'] ?? '';
		$attrs         = $this->build_trigger_attributes( $flyout_id, $data, $class );
		$attrs['href'] = '#';

		// Build HTML
		$html = '<a';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>' . esc_html( $text ) . '</a>';

		return $html;
	}

	/**
	 * Build trigger element attributes
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes
	 * @param string $class     Additional CSS classes
	 *
	 * @return array Attributes array
	 * @since 1.0.0
	 */
	private function build_trigger_attributes( string $flyout_id, array $data, string $class = '' ): array {
		$attrs = [
			'class'               => trim( 'wp-flyout-trigger ' . $class ),
			'data-flyout-manager' => $this->prefix,
			'data-flyout'         => $flyout_id,
			'data-flyout-nonce'   => wp_create_nonce( 'wp_flyout_' . $this->prefix . '_' . $flyout_id ),
		];

		// Add custom data attributes
		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( (string) $value );
		}

		return $attrs;
	}

	/**
	 * Check if current user can access flyout
	 *
	 * @param string $flyout_id Flyout identifier
	 *
	 * @return bool True if user has required capability
	 * @since 1.0.0
	 */
	private function can_access( string $flyout_id ): bool {
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			return false;
		}

		$config = $this->flyouts[ $flyout_id ];

		return current_user_can( $config['capability'] );
	}

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		// Skip if already enqueued or no flyouts registered
		if ( $this->assets_enqueued || empty( $this->flyouts ) ) {
			return;
		}

		// Check if we should load on this page
		if ( $this->should_enqueue( $hook_suffix ) ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Determine if assets should be enqueued
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return bool True if assets should load
	 * @since 1.0.0
	 */
	private function should_enqueue( string $hook_suffix ): bool {
		// Check specific configured pages first
		if ( ! empty( $this->admin_pages ) ) {
			return in_array( $hook_suffix, $this->admin_pages, true );
		}

		// Default pages where flyouts commonly appear
		$default_pages = [
			'index.php',
			'edit.php',
			'post.php',
			'post-new.php',
			'users.php',
			'user-edit.php',
			'profile.php',
			'options-general.php',
			'tools.php',
		];

		// Check for custom admin pages
		if ( str_starts_with( $hook_suffix, 'toplevel_page_' ) ||
		     str_starts_with( $hook_suffix, 'page_' ) ) {
			return true;
		}

		return in_array( $hook_suffix, $default_pages, true );
	}

	/**
	 * Enqueue required assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		// Enqueue core WP Flyout assets
		Assets::enqueue();

		// Enqueue required components
		foreach ( $this->components as $component ) {
			Assets::enqueue_component( $component );
		}

		$this->assets_enqueued = true;
	}

	/**
	 * Get all registered flyouts
	 *
	 * @return array Flyout configurations
	 * @since 1.0.0
	 */
	public function get_flyouts(): array {
		return $this->flyouts;
	}

	/**
	 * Check if flyout is registered
	 *
	 * @param string $flyout_id Flyout identifier
	 *
	 * @return bool True if flyout exists
	 * @since 1.0.0
	 */
	public function has_flyout( string $flyout_id ): bool {
		return isset( $this->flyouts[ $flyout_id ] );
	}

	/**
	 * Get manager prefix
	 *
	 * @return string Manager prefix
	 * @since 1.0.0
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

}