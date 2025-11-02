<?php
// src/Traits/CurrencyFormatter.php

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Traits;

trait CurrencyFormatter {

	/**
	 * Format currency amount
	 *
	 * Formats amount to currency string using wp-currencies library.
	 * Accepts both cents (int) and dollar amounts (float).
	 * Falls back to $this->config['currency'] or 'USD' if currency not provided.
	 *
	 * @param int|float|string $amount    Amount in cents (int) or dollars (float/string).
	 *                                    Examples: 1999 (int) = $19.99, 19.99 (float) = $19.99, "19.99" (string) = $19.99
	 * @param string|null      $currency  Currency code (e.g., 'USD', 'EUR'). Optional.
	 *
	 * @return string Formatted currency string
	 */
	protected function format_currency( $amount, ?string $currency = null ): string {
		// Determine currency to use
		$currency = $currency ?? ( $this->config['currency'] ?? 'USD' );

		// Convert to numeric
		$amount_numeric = is_string( $amount ) ? (float) $amount : $amount;

		// Determine if amount is in dollars or cents
		// If it's a float, or a small number with decimals, it's likely in dollars
		if ( is_float( $amount_numeric ) ||
		     ( $amount_numeric < 100 && $amount_numeric != floor( $amount_numeric ) ) ) {
			$amount_in_cents = (int) round( $amount_numeric * 100 );
		} else {
			// Otherwise assume it's already in cents
			$amount_in_cents = (int) $amount_numeric;
		}

		// If format_currency function exists (from wp-currencies), use it
		if ( function_exists( 'format_currency' ) ) {
			return format_currency( $amount_in_cents, $currency );
		}

		// Fallback formatting
		$symbols = [
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
			'CAD' => '$',
			'AUD' => '$'
		];

		$symbol = $symbols[ $currency ] ?? $currency . ' ';
		$decimals = ( $currency === 'JPY' ) ? 0 : 2;
		$amount_in_dollars = $amount_in_cents / 100;

		return $symbol . number_format( $amount_in_dollars, $decimals );
	}

}