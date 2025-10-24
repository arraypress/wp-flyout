<?php
/**
 * Data Table Component
 *
 * Creates consistent tables for displaying key-value pairs and metadata.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Data;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\EmptyValueFormatter;

/**
 * Class DataTable
 *
 * Renders tables for key-value data display.
 */
class DataTable {
    use Renderable;
    use EmptyValueFormatter;

    /**
     * Table data
     *
     * @var array
     */
    private array $data = [];

    /**
     * Table configuration
     *
     * @var array
     */
    private array $config = [
            'headers'     => [ 'Key', 'Value' ],
            'class'       => 'wp-flyout-data-table wp-list-table widefat fixed striped',
            'show_code'   => true, // Wrap values in <code> tags
            'empty_text'  => '—',
            'format_json' => true,
            'show_empty'  => true,
            'empty_state' => null
    ];

    /**
     * Constructor
     *
     * @param array $data   Data array (key => value pairs)
     * @param array $config Optional configuration
     */
    public function __construct( array $data = [], array $config = [] ) {
        $this->data   = $data;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Create a metadata table
     *
     * @param array $metadata Metadata array
     *
     * @return self
     */
    public static function metadata( array $metadata ): self {
        return new self( $metadata, [
                'headers'   => [ 'Key', 'Value' ],
                'show_code' => true,
                'class'     => 'metadata-table wp-list-table widefat fixed striped'
        ] );
    }

    /**
     * Create a properties table
     *
     * @param array $properties Properties array
     *
     * @return self
     */
    public static function properties( array $properties ): self {
        return new self( $properties, [
                'headers'   => [ 'Property', 'Value' ],
                'show_code' => false,
                'class'     => 'properties-table wp-list-table widefat fixed striped'
        ] );
    }

    /**
     * Add a row to the table
     *
     * @param string $key   Row key
     * @param mixed  $value Row value
     *
     * @return self
     */
    public function add_row( string $key, $value ): self {
        $this->data[ $key ] = $value;

        return $this;
    }

    /**
     * Set empty state
     *
     * @param EmptyState $empty_state Empty state component
     *
     * @return self
     */
    public function set_empty_state( EmptyState $empty_state ): self {
        $this->config['empty_state'] = $empty_state;

        return $this;
    }

    /**
     * Render the table
     *
     * @return string Generated HTML
     */
    public function render(): string {
        if ( empty( $this->data ) ) {
            if ( $this->config['empty_state'] ) {
                return $this->config['empty_state']->render();
            }
            if ( ! $this->config['show_empty'] ) {
                return '';
            }

            return '<p>' . esc_html( $this->config['empty_text'] ) . '</p>';
        }

        ob_start();
        ?>
        <table class="<?php echo esc_attr( $this->config['class'] ); ?>">
            <thead>
            <tr>
                <th><?php echo esc_html( $this->config['headers'][0] ); ?></th>
                <th><?php echo esc_html( $this->config['headers'][1] ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $this->data as $key => $value ): ?>
                <tr>
                    <td><strong><?php echo esc_html( $key ); ?></strong></td>
                    <td>
                        <?php if ( $this->config['show_code'] ): ?>
                            <code><?php echo esc_html( $this->format_table_value( $value ) ); ?></code>
                        <?php else: ?>
                            <?php echo esc_html( $this->format_table_value( $value ) ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Format a value for table display
     *
     * This extends the trait's format_value to handle JSON formatting
     *
     * @param mixed $value Value to format
     *
     * @return string Formatted value
     */
    private function format_table_value( $value ): string {
        if ( is_null( $value ) ) {
            return $this->config['empty_text'];
        }

        if ( is_bool( $value ) ) {
            return $this->format_boolean( $value );
        }

        if ( is_array( $value ) || is_object( $value ) ) {
            if ( $this->config['format_json'] ) {
                return json_encode( $value, JSON_PRETTY_PRINT );
            }

            return print_r( $value, true );
        }

        return $this->format_value( $value, $this->config['empty_text'] );
    }

    /**
     * Quick render of data table
     *
     * @param array $data Data array.
     *
     * @return string Rendered HTML.
     * @since 1.0.0
     */
    public static function quick( array $data ): string {
        return ( new self( $data ) )->render();
    }

}