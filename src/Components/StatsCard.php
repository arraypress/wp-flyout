<?php
/**
 * Stats Card Component
 *
 * Displays metrics and KPIs in a card format with optional trend indicators.
 * Useful for dashboards and analytics displays.
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
 * Class StatsCard
 *
 * Creates metric cards with values, labels, and optional trend indicators.
 *
 * @since 1.0.0
 */
class StatsCard {
    use Renderable;

    /**
     * Stats data
     *
     * @since 1.0.0
     * @var array
     */
    private array $stats = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'class'      => 'wp-flyout-stats-card',
            'layout'     => 'grid', // 'grid', 'list', 'inline'
            'columns'    => 3,
            'show_trend' => true,
            'show_icon'  => true
    ];

    /**
     * Constructor
     *
     * @param array $stats  Statistics data
     * @param array $config Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $stats = [], array $config = [] ) {
        $this->stats  = $stats;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Add a statistic
     *
     * @param string $label Stat label
     * @param string $value Stat value
     * @param array  $meta  Additional metadata
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_stat( string $label, string $value, array $meta = [] ): self {
        $this->stats[] = array_merge( [
                'label'       => $label,
                'value'       => $value,
                'trend'       => null, // 'up', 'down', 'neutral'
                'trend_value' => null, // e.g., '+12%'
                'icon'        => null,
                'color'       => null, // 'success', 'warning', 'error', 'info'
                'description' => null
        ], $meta );

        return $this;
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        if ( empty( $this->stats ) ) {
            return '';
        }

        $class = $this->config['class'] . ' layout-' . $this->config['layout'];
        if ( $this->config['layout'] === 'grid' ) {
            $class .= ' columns-' . $this->config['columns'];
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <?php foreach ( $this->stats as $stat ) : ?>
                <?php echo $this->render_stat( $stat ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single stat
     *
     * @param array $stat Stat data
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_stat( array $stat ): string {
        $stat_class = 'stat-item';
        if ( ! empty( $stat['color'] ) ) {
            $stat_class .= ' stat-' . $stat['color'];
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $stat_class ); ?>">
            <?php if ( $this->config['show_icon'] && ! empty( $stat['icon'] ) ) : ?>
                <div class="stat-icon">
                    <span class="dashicons dashicons-<?php echo esc_attr( $stat['icon'] ); ?>"></span>
                </div>
            <?php endif; ?>

            <div class="stat-content">
                <div class="stat-value">
                    <?php echo esc_html( $stat['value'] ); ?>

                    <?php if ( $this->config['show_trend'] && ! empty( $stat['trend'] ) ) : ?>
                        <span class="stat-trend trend-<?php echo esc_attr( $stat['trend'] ); ?>">
							<?php if ( $stat['trend'] === 'up' ) : ?>
                                <span class="dashicons dashicons-arrow-up-alt"></span>
                            <?php elseif ( $stat['trend'] === 'down' ) : ?>
                                <span class="dashicons dashicons-arrow-down-alt"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-minus"></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $stat['trend_value'] ) ) : ?>
                                <?php echo esc_html( $stat['trend_value'] ); ?>
                            <?php endif; ?>
						</span>
                    <?php endif; ?>
                </div>

                <div class="stat-label"><?php echo esc_html( $stat['label'] ); ?></div>

                <?php if ( ! empty( $stat['description'] ) ) : ?>
                    <div class="stat-description"><?php echo esc_html( $stat['description'] ); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Create a revenue stats card
     *
     * @param array $data Revenue data
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function revenue( array $data ): self {
        $card = new self( [], [ 'columns' => 4 ] );

        if ( isset( $data['today'] ) ) {
            $card->add_stat( 'Today', $data['today']['value'], [
                    'trend'       => $data['today']['trend'] ?? null,
                    'trend_value' => $data['today']['change'] ?? null,
                    'icon'        => 'chart-line'
            ] );
        }

        if ( isset( $data['week'] ) ) {
            $card->add_stat( 'This Week', $data['week']['value'], [
                    'trend'       => $data['week']['trend'] ?? null,
                    'trend_value' => $data['week']['change'] ?? null,
                    'icon'        => 'calendar'
            ] );
        }

        if ( isset( $data['month'] ) ) {
            $card->add_stat( 'This Month', $data['month']['value'], [
                    'trend'       => $data['month']['trend'] ?? null,
                    'trend_value' => $data['month']['change'] ?? null,
                    'icon'        => 'calendar-alt'
            ] );
        }

        if ( isset( $data['year'] ) ) {
            $card->add_stat( 'This Year', $data['year']['value'], [
                    'trend'       => $data['year']['trend'] ?? null,
                    'trend_value' => $data['year']['change'] ?? null,
                    'icon'        => 'chart-area'
            ] );
        }

        return $card;
    }

    /**
     * Create StatsCard from array of stats
     *
     * @param array $stats  Array of stat configurations
     * @param array $config Optional component configuration
     *
     * @return self
     * @since 1.0.0
     */
    public static function fromArray( array $stats, array $config = [] ): self {
        $card = new self( [], $config );

        foreach ( $stats as $stat ) {
            // Support both full config and simple label/value
            if ( is_array( $stat ) ) {
                $card->add_stat(
                        $stat['label'] ?? '',
                        $stat['value'] ?? '',
                        $stat
                );
            }
        }

        return $card;
    }

}