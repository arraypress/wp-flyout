<?php
/**
 * Notes Panel Component - Simplified Version
 *
 * Displays activity logs, notes, or comments in a chronological list format.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Flyout;
use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class NotesPanel
 *
 * Renders a panel of notes with optional add/delete functionality.
 * AJAX handling should be done by the implementing plugin.
 */
class NotesPanel {
    use Renderable;

    /**
     * Notes array
     *
     * @var array
     */
    private array $notes = [];

    /**
     * Panel configuration
     *
     * @var array
     */
    private array $config = [
            'class'       => 'wp-flyout-notes-panel',
            'editable'    => false,
            'object_type' => '',
            'object_id'   => '',
            'ajax_prefix' => 'wp_flyout_notes',
            'nonce'       => '',
            'empty_text'  => 'No notes available.',
            'placeholder' => 'Add a note...',
            'button_text' => 'Add Note'
    ];

    /**
     * Constructor
     *
     * @param array $notes  Initial notes array
     * @param array $config Optional configuration
     */
    public function __construct( array $notes = [], array $config = [] ) {
        $this->config = array_merge( $this->config, $config );
        $this->notes  = $notes;

        // Generate nonce if not provided and editable
        if ( $this->config['editable'] && empty( $this->config['nonce'] ) ) {
            $this->config['nonce'] = wp_create_nonce( $this->config['ajax_prefix'] . '_nonce' );
        }
    }

    /**
     * Add a note to display
     *
     * @param string $content  Note content
     * @param array  $metadata Optional metadata
     *
     * @return self
     */
    public function add_note( string $content, array $metadata = [] ): self {
        $this->notes[] = array_merge( [
                'id'        => uniqid(),
                'content'   => $content,
                'author'    => '',
                'timestamp' => '',
                'date'      => '',
                'type'      => 'note',
                'deletable' => true
        ], $metadata );

        return $this;
    }

    /**
     * Render the notes panel
     *
     * @return string Generated HTML
     */
    public function render(): string {
        Flyout::enqueue_component_assets( 'notes' );

        $attributes = [];

        if ( $this->config['editable'] ) {
            $attributes['data-object-type'] = $this->config['object_type'];
            $attributes['data-object-id']   = $this->config['object_id'];
            $attributes['data-editable']    = 'true';
            $attributes['data-ajax-prefix'] = $this->config['ajax_prefix'];
            $attributes['data-nonce']       = $this->config['nonce'];
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
                <?php echo $this->render_attributes( $attributes ); ?>>

            <div class="notes-list">
                <?php if ( empty( $this->notes ) ): ?>
                    <p class="no-notes"><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                <?php else: ?>
                    <?php foreach ( $this->notes as $note ): ?>
                        <?php echo $this->render_note( $note ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $this->config['editable'] ): ?>
                <?php echo $this->render_add_form(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single note
     *
     * @param array $note Note data
     *
     * @return string Generated HTML
     */
    private function render_note( array $note ): string {
        $classes = [ 'note-item' ];
        if ( ! empty( $note['type'] ) ) {
            $classes[] = 'note-type-' . $note['type'];
        }

        // Format timestamp if needed
        $timestamp = '';
        if ( ! empty( $note['date'] ) ) {
            $timestamp = $note['date'];
        } elseif ( ! empty( $note['timestamp'] ) ) {
            $timestamp = human_time_diff( strtotime( $note['timestamp'] ), current_time( 'timestamp' ) ) . ' ago';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-note-id="<?php echo esc_attr( $note['id'] ?? '' ); ?>">
            <div class="note-header">
                <?php if ( ! empty( $note['author'] ) ): ?>
                    <span class="note-author"><?php echo esc_html( $note['author'] ); ?></span>
                <?php endif; ?>

                <?php if ( $timestamp ): ?>
                    <span class="note-timestamp"><?php echo esc_html( $timestamp ); ?></span>
                <?php endif; ?>

                <?php if ( $this->config['editable'] && ! empty( $note['deletable'] ) ): ?>
                    <button class="delete-note"
                            data-note-id="<?php echo esc_attr( $note['id'] ?? '' ); ?>"
                            title="Delete note">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="note-content">
                <?php echo wp_kses_post( $note['content'] ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the add note form
     *
     * @return string Generated HTML
     */
    private function render_add_form(): string {
        ob_start();
        ?>
        <div class="note-input-wrapper">
            <textarea class="note-input"
                      placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"></textarea>
            <button class="add-note button button-primary">
                <?php echo esc_html( $this->config['button_text'] ); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render HTML attributes
     *
     * @param array $attributes Attributes array
     *
     * @return string HTML attributes string
     */
    private function render_attributes( array $attributes ): string {
        $output = [];
        foreach ( $attributes as $key => $value ) {
            if ( $value !== null && $value !== '' ) {
                $output[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
            }
        }

        return implode( ' ', $output );
    }

    /**
     * Create a static notes panel (read-only)
     *
     * @param array $notes  Notes to display
     * @param array $config Optional configuration
     *
     * @return self
     */
    public static function static( array $notes, array $config = [] ): self {
        $config['editable'] = false;

        return new self( $notes, $config );
    }

    /**
     * Create an editable notes panel
     *
     * @param string $object_type Object type identifier
     * @param mixed  $object_id   Object ID
     * @param array  $notes       Initial notes to display
     * @param array  $config      Optional configuration (including ajax_prefix and nonce)
     *
     * @return self
     */
    public static function editable( string $object_type, $object_id, array $notes = [], array $config = [] ): self {
        $config = array_merge( $config, [
                'editable'    => true,
                'object_type' => $object_type,
                'object_id'   => (string) $object_id
        ] );

        return new self( $notes, $config );
    }
}