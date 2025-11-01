<?php
/**
 * File Manager Component - Simplified
 *
 * Minimal markup using WordPress native elements
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @version     3.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Interactive;

use ArrayPress\WPFlyout\Traits\Renderable;

class FileManager {
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
            'name'        => 'files',
            'files'       => [],
            'max_files'   => 0,
            'reorderable' => true,
            'removable'   => true,
            'browseable'  => true,
            'add_text'    => 'Add File',
            'class'       => ''
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
            $this->config['id'] = 'file-manager-' . wp_generate_uuid4();
        }

        // Ensure files is array
        if ( ! is_array( $this->config['files'] ) ) {
            $this->config['files'] = [];
        }
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-file-manager' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-prefix="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-template='<?php echo $this->get_template(); ?>'>

            <div class="file-manager-list">
                <?php if ( empty( $this->config['files'] ) ) : ?>
                    <?php $this->render_empty_item(); ?>
                <?php else : ?>
                    <?php foreach ( $this->config['files'] as $index => $file ) : ?>
                        <?php $this->render_file_item( $file, $index ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $this->config['max_files'] === 0 || count( $this->config['files'] ) < $this->config['max_files'] ) : ?>
                <p>
                    <button type="button" class="button" data-action="add">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php echo esc_html( $this->config['add_text'] ); ?>
                    </button>
                </p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single file item
     *
     * @param array $file  File data
     * @param int   $index Item index
     */
    private function render_file_item( array $file, int $index ): void {
        ?>
        <div class="file-manager-item" data-index="<?php echo $index; ?>">
            <?php if ( $this->config['reorderable'] ) : ?>
                <span class="file-handle dashicons dashicons-menu-alt2" title="Drag to reorder"></span>
            <?php endif; ?>

            <div class="file-fields">
                <input type="text"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][name]"
                       value="<?php echo esc_attr( $file['name'] ?? '' ); ?>"
                       placeholder="File name"
                       data-field="name"
                       class="regular-text">

                <input type="url"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][url]"
                       value="<?php echo esc_attr( $file['url'] ?? '' ); ?>"
                       placeholder="File URL"
                       data-field="url"
                       class="widefat">

                <input type="hidden"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][id]"
                       value="<?php echo esc_attr( $file['id'] ?? '' ); ?>"
                       data-field="id">
            </div>

            <div class="file-actions">
                <?php if ( $this->config['browseable'] ) : ?>
                    <button type="button"
                            class="button button-small"
                            data-action="browse"
                            title="Browse Media Library">
                        <span class="dashicons dashicons-admin-media"></span>
                    </button>
                <?php endif; ?>

                <?php if ( $this->config['removable'] ) : ?>
                    <button type="button"
                            class="button button-small"
                            data-action="remove"
                            title="Remove">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render empty file item
     */
    private function render_empty_item(): void {
        $this->render_file_item( [], 0 );
    }

    /**
     * Get item template for JavaScript
     *
     * @return string
     */
    private function get_template(): string {
        ob_start();
        ?>
        <div class="file-manager-item" data-index="{{index}}">
        <?php if ( $this->config['reorderable'] ) : ?>
            <span class="file-handle dashicons dashicons-menu-alt2" title="Drag to reorder"></span>
        <?php endif; ?>
        <div class="file-fields">
            <input type="text" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][name]" value="" placeholder="File name" data-field="name" class="regular-text">
            <input type="url" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][url]" value="" placeholder="File URL" data-field="url" class="widefat">
            <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][id]" value="" data-field="id">
        </div>
        <div class="file-actions">
            <?php if ( $this->config['browseable'] ) : ?>
                <button type="button" class="button button-small" data-action="browse" title="Browse Media Library">
                    <span class="dashicons dashicons-admin-media"></span>
                </button>
            <?php endif; ?>
            <?php if ( $this->config['removable'] ) : ?>
                <button type="button" class="button button-small" data-action="remove" title="Remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            <?php endif; ?>
        </div>
        </div><?php
        $html = ob_get_clean();

        // Remove newlines and excess whitespace but keep the HTML structure intact
        $html = str_replace(array("\r", "\n", "\t"), '', $html);
        $html = preg_replace('/>\s+</', '><', $html);

        return trim($html);
    }
}