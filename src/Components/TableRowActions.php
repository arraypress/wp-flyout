<?php
/**
 * Table Row Actions Component
 *
 * Generates consistent row action links for list tables
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class TableRowActions
 *
 * Creates row action links for WordPress list tables with flyout integration
 */
class TableRowActions {
	use Renderable;

	/**
	 * Actions array
	 *
	 * @var array
	 */
	private array $actions = [];

	/**
	 * Configuration
	 *
	 * @var array
	 */
	private array $config = [
		'separator' => ' | ',
		'class'     => 'row-actions',
		'wrap'      => true
	];

	/**
	 * Constructor
	 *
	 * @param array $config Optional configuration
	 */
	public function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Add an edit action that opens a flyout
	 *
	 * @param string $flyout_id Flyout ID to trigger
	 * @param mixed  $item_id   Item ID
	 * @param string $text      Link text
	 *
	 * @return self
	 */
	public function add_edit( string $flyout_id, $item_id, string $text = 'Edit' ): self {
		$this->actions['edit'] = sprintf(
			'<a href="#" data-flyout-trigger="%s" data-flyout-action="load" data-id="%s">%s</a>',
			esc_attr( $flyout_id ),
			esc_attr( (string) $item_id ),
			esc_html( $text )
		);

		return $this;
	}

	/**
	 * Add a view action
	 *
	 * @param string $url  URL to view page
	 * @param string $text Link text
	 *
	 * @return self
	 */
	public function add_view( string $url, string $text = 'View' ): self {
		$this->actions['view'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html( $text )
		);

		return $this;
	}

	/**
	 * Add a delete action with AJAX support
	 *
	 * @param mixed  $item_id Item ID
	 * @param string $nonce   Security nonce
	 * @param string $text    Link text
	 * @param string $class   Additional CSS class
	 *
	 * @return self
	 */
	public function add_delete( $item_id, string $nonce, string $text = 'Delete', string $class = 'wp-flyout-delete' ): self {
		$this->actions['delete'] = sprintf(
			'<a href="#" class="%s" data-id="%s" data-nonce="%s">%s</a>',
			esc_attr( $class ),
			esc_attr( (string) $item_id ),
			esc_attr( $nonce ),
			esc_html( $text )
		);

		return $this;
	}

	/**
	 * Add a custom action
	 *
	 * @param string $key  Action key
	 * @param string $html Action HTML
	 *
	 * @return self
	 */
	public function add_custom( string $key, string $html ): self {
		$this->actions[ $key ] = $html;

		return $this;
	}

	/**
	 * Render the row actions
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		if ( empty( $this->actions ) ) {
			return '';
		}

		$html = implode( $this->config['separator'], $this->actions );

		if ( $this->config['wrap'] ) {
			return sprintf(
				'<div class="%s">%s</div>',
				esc_attr( $this->config['class'] ),
				$html
			);
		}

		return $html;
	}

	/**
	 * Create standard CRUD actions
	 *
	 * @param string $flyout_id Flyout ID
	 * @param mixed  $item_id   Item ID
	 * @param array  $urls      URLs for view/delete
	 *
	 * @return self
	 */
	public static function crud( string $flyout_id, $item_id, array $urls = [] ): self {
		$actions = new self();

		$actions->add_edit( $flyout_id, $item_id );

		if ( ! empty( $urls['view'] ) ) {
			$actions->add_view( $urls['view'] );
		}

		if ( ! empty( $urls['delete_nonce'] ) ) {
			$actions->add_delete( $item_id, $urls['delete_nonce'] );
		}

		return $actions;
	}
}