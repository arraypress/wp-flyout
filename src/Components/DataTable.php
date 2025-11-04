<?php
/**
 * DataTable Component
 *
 * Renders structured data in a table format with optional features.
 *
 * @package     ArrayPress\WPFlyout\Components\Data
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;
use ArrayPress\WPFlyout\Traits\Formatter;

class DataTable implements Renderable {
    use Formatter;

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'datatable-' . wp_generate_uuid4();
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'          => '',
                'class'       => 'wp-list-table widefat fixed striped',
                'columns'     => [],
                'data'        => [],
                'empty_text'  => __( 'No data found.', 'arraypress' ),
                'sortable'    => false,
                'responsive'  => true,
                'hover'       => true,
                'striped'     => true,
                'bordered'    => false,
                'condensed'   => false,
                'footer'      => false,
                'caption'     => '',
                'empty_value' => '—'
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['columns'] ) ) {
            return '';
        }

        $classes = $this->get_classes();

        ob_start();
        ?>
        <div class="datatable-wrapper<?php echo $this->config['responsive'] ? ' table-responsive' : ''; ?>">
            <?php if ( ! empty( $this->config['caption'] ) ) : ?>
                <caption class="screen-reader-text"><?php echo esc_html( $this->config['caption'] ); ?></caption>
            <?php endif; ?>

            <table id="<?php echo esc_attr( $this->config['id'] ); ?>"
                   class="<?php echo esc_attr( $classes ); ?>"
                    <?php echo $this->config['sortable'] ? 'data-sortable="true"' : ''; ?>>

                <thead>
                <tr>
                    <?php foreach ( $this->config['columns'] as $key => $column ) : ?>
                        <?php $this->render_header_cell( $key, $column ); ?>
                    <?php endforeach; ?>
                </tr>
                </thead>

                <tbody>
                <?php if ( ! empty( $this->config['data'] ) ) : ?>
                    <?php foreach ( $this->config['data'] as $row ) : ?>
                        <tr>
                            <?php foreach ( $this->config['columns'] as $key => $column ) : ?>
                                <?php $this->render_body_cell( $key, $column, $row ); ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count( $this->config['columns'] ); ?>" class="text-center">
                            <?php echo esc_html( $this->config['empty_text'] ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>

                <?php if ( $this->config['footer'] ) : ?>
                    <tfoot>
                    <tr>
                        <?php foreach ( $this->config['columns'] as $key => $column ) : ?>
                            <?php $this->render_footer_cell( $key, $column ); ?>
                        <?php endforeach; ?>
                    </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get table classes
     *
     * @return string
     */
    private function get_classes(): string {
        $classes = [ $this->config['class'] ];

        if ( $this->config['hover'] && ! str_contains( $this->config['class'], 'hover' ) ) {
            $classes[] = 'hover';
        }

        if ( $this->config['bordered'] && ! str_contains( $this->config['class'], 'bordered' ) ) {
            $classes[] = 'bordered';
        }

        if ( $this->config['condensed'] ) {
            $classes[] = 'condensed';
        }

        return implode( ' ', array_filter( $classes ) );
    }

    /**
     * Render header cell
     *
     * @param string $key    Column key
     * @param mixed  $column Column config
     */
    private function render_header_cell( string $key, $column ): void {
        $label    = is_array( $column ) ? ( $column['label'] ?? $key ) : $column;
        $sortable = is_array( $column ) && ( $column['sortable'] ?? false );
        $class    = is_array( $column ) ? ( $column['class'] ?? '' ) : '';
        $width    = is_array( $column ) ? ( $column['width'] ?? '' ) : '';

        $attrs = [];
        if ( $class ) {
            $attrs[] = 'class="' . esc_attr( $class ) . '"';
        }
        if ( $width ) {
            $attrs[] = 'style="width: ' . esc_attr( $width ) . '"';
        }
        if ( $sortable && $this->config['sortable'] ) {
            $attrs[] = 'data-sortable="true"';
        }
        ?>
        <th <?php echo implode( ' ', $attrs ); ?>>
            <?php echo esc_html( $label ); ?>
            <?php if ( $sortable && $this->config['sortable'] ) : ?>
                <span class="sorting-indicator" aria-hidden="true"></span>
            <?php endif; ?>
        </th>
        <?php
    }

    /**
     * Render body cell
     *
     * @param string $key    Column key
     * @param mixed  $column Column config
     * @param array  $row    Row data
     */
    private function render_body_cell( string $key, $column, array $row ): void {
        $value    = $row[ $key ] ?? '';
        $class    = is_array( $column ) ? ( $column['class'] ?? '' ) : '';
        $callback = is_array( $column ) ? ( $column['callback'] ?? null ) : null;

        if ( is_callable( $callback ) ) {
            $value = call_user_func( $callback, $value, $row );
        } elseif ( empty( $value ) ) {
            $value = $this->format_value( $this->config['empty_value'] );
        } else {
            $value = esc_html( $value );
        }
        ?>
        <td <?php echo $class ? 'class="' . esc_attr( $class ) . '"' : ''; ?>>
            <?php echo $value; ?>
        </td>
        <?php
    }

    /**
     * Render footer cell
     *
     * @param string $key    Column key
     * @param mixed  $column Column config
     */
    private function render_footer_cell( string $key, $column ): void {
        $footer_text = '';
        if ( is_array( $column ) && isset( $column['footer'] ) ) {
            $footer_text = $column['footer'];
        }
        ?>
        <td><?php echo esc_html( $footer_text ); ?></td>
        <?php
    }

}