<?php
/**
 * Confirmation Component
 *
 * Inline confirmation UI for actions requiring user verification.
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
 * Class Confirmation
 *
 * Creates inline confirmation dialogs.
 *
 * @since 1.0.0
 */
class Confirmation {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'message'       => '',
		'confirm_text'  => '',
		'cancel_text'   => '',
		'confirm_class' => 'button-primary',
		'cancel_class'  => 'button-secondary',
		'type'          => 'default', // default, danger
		'class'         => 'wp-flyout-confirmation',
		'action'        => '', // data-action for confirm button
		'visible'       => false
	];

	/**
	 * Constructor
	 *
	 * @param string $message Confirmation message
	 * @param array  $config  Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $message, array $config = [] ) {
		$defaults = [
			'confirm_text' => __( 'Confirm', 'arraypress' ),
			'cancel_text'  => __( 'Cancel', 'arraypress' )
		];

		$this->config            = array_merge( $this->config, $defaults, $config );
		$this->config['message'] = $message;
	}

	/**
	 * Create a delete confirmation
	 *
	 * @param string $message Optional custom message
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function delete( string $message = '' ): self {
		return new self(
			$message ?: __( 'Are you sure you want to delete this?', 'arraypress' ),
			[
				'type'          => 'danger',
				'confirm_text'  => __( 'Delete', 'arraypress' ),
				'confirm_class' => 'button-primary button-danger'
			]
		);
	}

	/**
	 * Create a standard confirmation
	 *
	 * @param string $message Confirmation message
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function standard( string $message ): self {
		return new self( $message );
	}

	/**
	 * Render the confirmation
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		$classes = [
			$this->config['class'],
			'type-' . $this->config['type']
		];

		if ( ! $this->config['visible'] ) {
			$classes[] = 'hidden';
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <div class="confirmation-message">
				<?php echo esc_html( $this->config['message'] ); ?>
            </div>
            <div class="confirmation-actions">
                <button type="button"
                        class="<?php echo esc_attr( $this->config['confirm_class'] ); ?>"
                        data-confirmation="confirm"
					<?php echo ! empty( $this->config['action'] ) ? 'data-action="' . esc_attr( $this->config['action'] ) . '"' : ''; ?>>
					<?php echo esc_html( $this->config['confirm_text'] ); ?>
                </button>
                <button type="button"
                        class="<?php echo esc_attr( $this->config['cancel_class'] ); ?>"
                        data-confirmation="cancel">
					<?php echo esc_html( $this->config['cancel_text'] ); ?>
                </button>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}
}