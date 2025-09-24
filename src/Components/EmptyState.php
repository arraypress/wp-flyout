<?php
/**
 * Empty State Component
 *
 * Displays helpful empty state messages with optional actions for
 * empty tabs, missing data, or zero results.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare(strict_types=1);

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class EmptyState
 *
 * Renders empty state messages with icons and optional actions.
 */
class EmptyState {
	use Renderable;

	/**
	 * Empty state configuration
	 *
	 * @var array
	 */
	private array $config = [
		'icon' => 'admin-page',
		'title' => 'No items found',
		'description' => '',
		'action_text' => '',
		'action_url' => '',
		'action_class' => 'button',
		'class' => ''
	];

	/**
	 * Constructor
	 *
	 * @param string $title  Title text
	 * @param array  $config Optional configuration
	 */
	public function __construct(string $title, array $config = []) {
		$this->config = array_merge($this->config, $config);
		$this->config['title'] = $title;
	}

	/**
	 * Create a "no data" empty state
	 *
	 * @param string $description Optional description
	 * @return self
	 */
	public static function no_data(string $description = ''): self {
		return new self('No Data Available', [
			'icon' => 'chart-bar',
			'description' => $description ?: 'There is no data to display at this time.'
		]);
	}

	/**
	 * Create a "no files" empty state
	 *
	 * @param string $action_text Text for add action
	 * @return self
	 */
	public static function no_files(string $action_text = 'Add File'): self {
		return new self('No Files', [
			'icon' => 'media-document',
			'description' => 'No files have been added yet.',
			'action_text' => $action_text,
			'action_class' => 'button add-file-trigger'
		]);
	}

	/**
	 * Create a "coming soon" empty state
	 *
	 * @param string $feature Feature name
	 * @return self
	 */
	public static function coming_soon(string $feature = ''): self {
		$title = $feature ? $feature . ' Coming Soon' : 'Coming Soon';
		return new self($title, [
			'icon' => 'clock',
			'description' => 'This feature is under development and will be available soon.'
		]);
	}

	/**
	 * Render the empty state
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		$classes = ['wp-flyout-empty-state'];
		if ($this->config['class']) {
			$classes[] = $this->config['class'];
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
			<?php if ($this->config['icon']): ?>
				<span class="empty-state-icon dashicons dashicons-<?php echo esc_attr($this->config['icon']); ?>"></span>
			<?php endif; ?>

			<h3 class="empty-state-title"><?php echo esc_html($this->config['title']); ?></h3>

			<?php if ($this->config['description']): ?>
				<p class="empty-state-description"><?php echo esc_html($this->config['description']); ?></p>
			<?php endif; ?>

			<?php if ($this->config['action_text']): ?>
				<?php if ($this->config['action_url']): ?>
					<a href="<?php echo esc_url($this->config['action_url']); ?>"
					   class="<?php echo esc_attr($this->config['action_class']); ?>">
						<?php echo esc_html($this->config['action_text']); ?>
					</a>
				<?php else: ?>
					<button type="button" class="<?php echo esc_attr($this->config['action_class']); ?>">
						<?php echo esc_html($this->config['action_text']); ?>
					</button>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}