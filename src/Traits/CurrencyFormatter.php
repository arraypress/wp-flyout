<?php
/**
 * Currency Formatter Trait
 *
 * Provides currency formatting using the wp-currencies library.
 * Centralizes currency display logic for components dealing with prices.
 *
 * @package     ArrayPress\WPFlyout\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

/**
 * Trait CurrencyFormatter
 *
 * Formats currency values using wp-currencies library.
 *
 * @since 1.0.0
 */
trait CurrencyFormatter {

	/**
	 * Format currency amount
	 *
	 * Formats amount in cents to currency string using wp-currencies library.
	 * Falls back to $this->config['currency'] or 'USD' if currency not provided.
	 *
	 * @param int         $amount_in_cents Amount in cents (e.g., 1999 for $19.99)
	 * @param string|null $currency        Currency code (e.g., 'USD', 'EUR'). Optional.
	 *
	 * @return string Formatted currency string
	 * @since 1.0.0
	 *
	 */
	protected function format_currency( int $amount_in_cents, ?string $currency = null ): string {
		$currency = $currency ?? ( $this->config['currency'] ?? 'USD' );

		return format_currency( $amount_in_cents, $currency );
	}

}