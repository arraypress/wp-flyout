<?php
/**
 * File Manager Component - Ultra Simplified
 *
 * Minimal file management with drag-and-drop sorting
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @version     4.0.0
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
            'id'         => '',
            'name'       => 'files',
            'items'      => [],
            'browseable' => true,
            'add_text'   => 'Add File',
            'class'      => ''
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

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
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
                <?php if ( empty( $this->config['items'] ) ) : ?>
                    <?php $this->render_file_item( [], 0 ); ?>
                <?php else : ?>
                    <?php foreach ( $this->config['items'] as $index => $file ) : ?>
                        <?php $this->render_file_item( $file, $index ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p>
                <button type="button" class="button" data-action="add">
                    <?php echo esc_html( $this->config['add_text'] ); ?>
                </button>
            </p>
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
            <span class="file-handle dashicons dashicons-menu" title="Drag to reorder"></span>

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

            <?php if ( $this->config['browseable'] ) : ?>
                <button type="button"
                        class="button"
                        data-action="browse"
                        title="Browse">
                    <span class="dashicons dashicons-admin-media"></span>
                </button>
            <?php endif; ?>

            <button type="button"
                    class="button"
                    data-action="remove"
                    title="Remove">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <?php
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
        <span class="file-handle dashicons dashicons-menu" title="Drag to reorder"></span>
        <input type="text" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][name]" value=""
               placeholder="File name" data-field="name" class="regular-text">
        <input type="url" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][url]" value=""
               placeholder="File URL" data-field="url" class="widefat">
        <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][id]" value=""
               data-field="id">
        <?php if ( $this->config['browseable'] ) : ?>
            <button type="button" class="button" data-action="browse" title="Browse"><span
                        class="dashicons dashicons-admin-media"></span></button>
        <?php endif; ?>
        <button type="button" class="button" data-action="remove" title="Remove"><span
                    class="dashicons dashicons-no"></span></button>
        </div><?php
        $html = ob_get_clean();

        return str_replace( array( "\r", "\n", "\t" ), '', trim( $html ) );
    }

}