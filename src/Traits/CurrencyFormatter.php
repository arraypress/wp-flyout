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
	 * Formats amount to currency string using wp-currencies library.
	 * Accepts both cents (int) and dollar amounts (float).
	 * Falls back to $this->config['currency'] or 'USD' if currency not provided.
	 *
	 * @param int|float   $amount    Amount in cents (int) or dollars (float).
	 *                               Examples: 1999 (int) = $19.99, 19.99 (float) = $19.99
	 * @param string|null $currency  Currency code (e.g., 'USD', 'EUR'). Optional.
	 *
	 * @return string Formatted currency string
	 * @since 1.0.0
	 *
	 */
	protected function format_currency( $amount, ?string $currency = null ): string {
		// Determine currency to use
		$currency = $currency ?? ( $this->config['currency'] ?? 'USD' );

		// Convert to cents if float was provided
		$amount_in_cents = is_float( $amount ) ? (int) round( $amount * 100 ) : (int) $amount;

		return format_currency( $amount_in_cents, $currency );
	}

}