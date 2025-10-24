<?php
/**
 * Accordion Component
 *
 * Multiple collapsible sections (accordion behavior)
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Layout;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\IconRenderer;

/**
 * Class Accordion
 *
 * Creates accordion with multiple collapsible sections.
 *
 * @since 1.0.0
 */
class Accordion {
	use Renderable;
	use IconRenderer;

	/**
	 * Accordion sections
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $sections = [];

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'id'             => '',
		'class'          => 'wp-flyout-accordion',
		'allow_multiple' => false, // Allow multiple sections open at once
		'first_open'     => true,   // Open first section by default
	];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );

		// Auto-generate ID if not provided
		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'accordion-' . uniqid();
		}
	}

	/**
	 * Add a section to the accordion
	 *
	 * @param string $title   Section title
	 * @param string $content Section content (HTML)
	 * @param array  $options Section options
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function add_section( string $title, string $content, array $options = [] ): self {
		$section = array_merge( [
			'id'       => sanitize_key( $title ),
			'title'    => $title,
			'content'  => $content,
			'open'     => false,
			'icon'     => null,
			'disabled' => false,
		], $options );

		$this->sections[] = $section;

		return $this;
	}

	/**
	 * Render the accordion
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		if ( empty( $this->sections ) ) {
			return '';
		}

		// Set first section open if configured
		if ( $this->config['first_open'] && ! $this->has_open_section() ) {
			$this->sections[0]['open'] = true;
		}

		ob_start();
		?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( $this->config['class'] ); ?>"
             data-allow-multiple="<?php echo esc_attr( $this->config['allow_multiple'] ? 'true' : 'false' ); ?>">

			<?php foreach ( $this->sections as $index => $section ) : ?>
				<?php echo $this->render_section( $section, $index ); ?>
			<?php endforeach; ?>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single accordion section
	 *
	 * @param array $section Section data
	 * @param int   $index   Section index
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_section( array $section, int $index ): string {
		$section_id  = $this->config['id'] . '-section-' . $index;
		$is_open     = $section['open'];
		$is_disabled = $section['disabled'];

		ob_start();
		?>
        <div class="accordion-section <?php echo $is_open ? 'is-open' : ''; ?> <?php echo $is_disabled ? 'is-disabled' : ''; ?>"
             data-section-id="<?php echo esc_attr( $section['id'] ); ?>">

            <button type="button"
                    class="accordion-header"
                    aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr( $section_id ); ?>"
				<?php echo $is_disabled ? 'disabled' : ''; ?>>

				<?php if ( $section['icon'] ) : ?>
					<?php echo $this->render_icon( $section['icon'] ); ?>
				<?php endif; ?>

                <span class="accordion-title"><?php echo esc_html( $section['title'] ); ?></span>

                <span class="accordion-indicator">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</span>
            </button>

            <div id="<?php echo esc_attr( $section_id ); ?>"
                 class="accordion-content"
                 role="region"
				<?php echo ! $is_open ? 'style="display: none;"' : ''; ?>>
                <div class="accordion-content-inner">
					<?php echo $section['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if any section is marked as open
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	private function has_open_section(): bool {
		foreach ( $this->sections as $section ) {
			if ( $section['open'] ) {
				return true;
			}
		}

		return false;
	}

}

