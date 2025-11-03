<?php
/**
 * Currency Formatter Trait
 *
 * Simple currency formatting using the ArrayPress Currency library.
 *
 * @package     ArrayPress\WPFlyout\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

use ArrayPress\Currencies\Currency;

trait CurrencyFormatter {

	/**
	 * Format currency amount
	 *
	 * Formats amount in cents to currency string using ArrayPress Currency library.
	 * Falls back to $this->config['currency'] or 'USD' if currency not provided.
	 *
	 * @param int         $amount_in_cents Amount in cents (e.g., 1999 = $19.99)
	 * @param string|null $currency        Currency code (e.g., 'USD', 'EUR'). Optional.
	 *
	 * @return string Formatted currency string
	 */
	protected function format_currency( int $amount_in_cents, ?string $currency = null ): string {
		// Determine currency to use
		$currency = $currency ?? ( $this->config['currency'] ?? 'USD' );

		// Use the Currency library directly
		return Currency::format( $amount_in_cents, $currency );
	}

}