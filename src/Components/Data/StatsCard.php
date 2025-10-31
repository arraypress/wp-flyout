<?php
/**
 * StatsCard Component
 *
 * Displays statistical information in a card format.
 *
 * @package     ArrayPress\WPFlyout\Components\Data
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Data;

use ArrayPress\WPFlyout\Traits\Renderable;

class StatsCard {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Default configuration
	 *
	 * @var array
	 */
	private const DEFAULTS = [
		'id'          => '',
		'title'       => '',
		'value'       => '',
		'subtitle'    => '',
		'icon'        => '',
		'trend'       => null,
		'trend_label' => '',
		'color'       => '',
		'link'        => '',
		'link_text'   => 'View Details',
		'class'       => '',
		'footer'      => ''
	];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, self::DEFAULTS );

		// Auto-generate ID if not provided
		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'stats-card-' . wp_generate_uuid4();
		}
	}

	/**
	 * Render the component
	 *
	 * @return string
	 */
	public function render(): string {
		if ( empty( $this->config['title'] ) && empty( $this->config['value'] ) ) {
			return '';
		}

		$classes = $this->get_classes();
		$style = $this->get_style();

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->config['id'] ); ?>"
		     class="<?php echo esc_attr( $classes ); ?>"
			<?php echo $style; ?>>

			<?php if ( $this->config['icon'] ) : ?>
				<div class="stats-card-icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
				</div>
			<?php endif; ?>

			<div class="stats-card-content">
				<?php if ( $this->config['title'] ) : ?>
					<h3 class="stats-card-title"><?php echo esc_html( $this->config['title'] ); ?></h3>
				<?php endif; ?>

				<div class="stats-card-value">
					<?php echo wp_kses_post( $this->config['value'] ); ?>

					<?php if ( $this->config['trend'] !== null ) : ?>
						<?php $this->render_trend(); ?>
					<?php endif; ?>
				</div>

				<?php if ( $this->config['subtitle'] ) : ?>
					<div class="stats-card-subtitle">
						<?php echo esc_html( $this->config['subtitle'] ); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $this->config['link'] || $this->config['footer'] ) : ?>
				<div class="stats-card-footer">
					<?php if ( $this->config['link'] ) : ?>
						<a href="<?php echo esc_url( $this->config['link'] ); ?>" class="stats-card-link">
							<?php echo esc_html( $this->config['link_text'] ); ?>
							<span class="dashicons dashicons-arrow-right-alt"></span>
						</a>
					<?php endif; ?>

					<?php if ( $this->config['footer'] ) : ?>
						<div class="stats-card-footer-text">
							<?php echo wp_kses_post( $this->config['footer'] ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get card classes
	 *
	 * @return string
	 */
	private function get_classes(): string {
		$classes = [ 'stats-card' ];

		if ( $this->config['color'] ) {
			$classes[] = 'stats-card-' . $this->config['color'];
		}

		if ( $this->config['link'] ) {
			$classes[] = 'has-link';
		}

		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		return implode( ' ', $classes );
	}

	/**
	 * Get inline styles
	 *
	 * @return string
	 */
	private function get_style(): string {
		if ( $this->config['color'] && str_starts_with( $this->config['color'], '#' ) ) {
			return 'style="--stats-card-color: ' . esc_attr( $this->config['color'] ) . '"';
		}
		return '';
	}

	/**
	 * Render trend indicator
	 */
	private function render_trend(): void {
		$trend = (float) $this->config['trend'];
		$class = $trend > 0 ? 'up' : ( $trend < 0 ? 'down' : 'neutral' );
		$icon = $trend > 0 ? 'arrow-up-alt' : ( $trend < 0 ? 'arrow-down-alt' : 'minus' );
		?>
		<span class="stats-card-trend trend-<?php echo esc_attr( $class ); ?>">
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
			<span class="trend-value"><?php echo esc_html( abs( $trend ) . '%' ); ?></span>
			<?php if ( $this->config['trend_label'] ) : ?>
				<span class="trend-label"><?php echo esc_html( $this->config['trend_label'] ); ?></span>
			<?php endif; ?>
		</span>
		<?php
	}

}