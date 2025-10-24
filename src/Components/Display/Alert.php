<?php
/**
 * Alert Component
 *
 * Dismissible alerts with icons, action buttons, and WordPress styling.
 * Different from WordPress admin notices - these are inline components.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Display;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\IconRenderer;

/**
 * Class Alert
 *
 * Creates dismissible alert messages with actions.
 *
 * @since 1.0.0
 */
class Alert {
	use Renderable;
	use IconRenderer;

	/**
	 * Alert message
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $message;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'type'        => 'info',      // success, info, warning, error
		'dismissible' => false,
		'icon'        => null,        // Auto-set based on type if null
		'actions'     => [],          // Array of action buttons
		'class'       => 'wp-flyout-alert',
		'inline'      => true         // true = inline, false = banner/full-width
	];

	/**
	 * Constructor
	 *
	 * @param string $message Alert message
	 * @param array  $config  Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $message, array $config = [] ) {
		$this->message = $message;
		$this->config  = array_merge( $this->config, $config );

		// Auto-set icon based on type if not specified
		if ( $this->config['icon'] === null ) {
			$this->config['icon'] = $this->get_default_icon( $this->config['type'] );
		}
	}

	/**
	 * Create a success alert
	 *
	 * @param string $message Alert message
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function success( string $message ): self {
		return new self( $message, [ 'type' => 'success' ] );
	}

	/**
	 * Create an info alert
	 *
	 * @param string $message Alert message
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function info( string $message ): self {
		return new self( $message, [ 'type' => 'info' ] );
	}

	/**
	 * Create a warning alert
	 *
	 * @param string $message Alert message
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function warning( string $message ): self {
		return new self( $message, [ 'type' => 'warning' ] );
	}

	/**
	 * Create an error alert
	 *
	 * @param string $message Alert message
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function error( string $message ): self {
		return new self( $message, [ 'type' => 'error' ] );
	}

	/**
	 * Make alert dismissible
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function dismissible(): self {
		$this->config['dismissible'] = true;

		return $this;
	}

	/**
	 * Set as banner (full-width)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function banner(): self {
		$this->config['inline'] = false;

		return $this;
	}

	/**
	 * Set as inline (default)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function inline(): self {
		$this->config['inline'] = true;

		return $this;
	}

	/**
	 * Add an action button
	 *
	 * @param string      $text    Button text
	 * @param string      $url     Button URL or '#' for JS action
	 * @param string|null $action  Optional data-action attribute
	 * @param array       $options Additional options
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function action( string $text, string $url = '#', ?string $action = null, array $options = [] ): self {
		$this->config['actions'][] = array_merge( [
			'text'   => $text,
			'url'    => $url,
			'action' => $action,
			'class'  => 'button-link',
			'icon'   => null
		], $options );

		return $this;
	}

	/**
	 * Set custom icon
	 *
	 * @param string $icon Dashicon name (without 'dashicons-' prefix)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function icon( string $icon ): self {
		$this->config['icon'] = $icon;

		return $this;
	}

	/**
	 * Get default icon for alert type
	 *
	 * @param string $type Alert type
	 *
	 * @return string Icon name
	 * @since 1.0.0
	 */
	private function get_default_icon( string $type ): string {
		$icons = [
			'success' => 'yes-alt',
			'info'    => 'info',
			'warning' => 'warning',
			'error'   => 'dismiss'
		];

		return $icons[ $type ] ?? 'info';
	}

	/**
	 * Render the alert
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		$classes = [
			$this->config['class'],
			'alert-' . $this->config['type']
		];

		if ( ! $this->config['inline'] ) {
			$classes[] = 'alert-banner';
		}

		if ( $this->config['dismissible'] ) {
			$classes[] = 'is-dismissible';
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="alert">
            <div class="alert-content-wrapper">
				<?php if ( $this->config['icon'] ) : ?>
                    <div class="alert-icon">
						<?php echo $this->render_icon( $this->config['icon'] ); ?>
                    </div>
				<?php endif; ?>

                <div class="alert-content">
                    <div class="alert-message">
						<?php echo wp_kses_post( $this->message ); ?>
                    </div>

					<?php if ( ! empty( $this->config['actions'] ) ) : ?>
                        <div class="alert-actions">
							<?php foreach ( $this->config['actions'] as $action ) : ?>
								<?php echo $this->render_action( $action ); ?>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>

				<?php if ( $this->config['dismissible'] ) : ?>
                    <button type="button" class="alert-dismiss" data-action="dismiss-alert"
                            aria-label="<?php esc_attr_e( 'Dismiss alert', 'arraypress' ); ?>">
						<?php echo $this->render_icon( 'no-alt' ); ?>
                    </button>
				<?php endif; ?>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render an action button
	 *
	 * @param array $action Action configuration
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_action( array $action ): string {
		$attrs = [
			'class' => $action['class']
		];

		if ( $action['action'] ) {
			$attrs['data-action'] = $action['action'];
		}

		$attr_string = '';
		foreach ( $attrs as $key => $value ) {
			$attr_string .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
		}

		ob_start();
		?>
        <a href="<?php echo esc_url( $action['url'] ); ?>" <?php echo $attr_string; ?>>
			<?php if ( $action['icon'] ) : ?>
				<?php echo $this->render_icon( $action['icon'] ); ?>
			<?php endif; ?>
			<?php echo esc_html( $action['text'] ); ?>
        </a>
		<?php
		return ob_get_clean();
	}

    /**
     * Quick render of alert
     *
     * @param string $message Alert message.
     * @param string $type    Alert type.
     *
     * @return string Rendered HTML.
     * @since 1.0.0
     */
    public static function quick( string $message, string $type = 'info' ): string {
        return ( new self( $message, [ 'type' => $type ] ) )->render();
    }

}