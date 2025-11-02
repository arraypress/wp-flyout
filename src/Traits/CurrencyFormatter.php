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

	protected function format_currency( $amount, ?string $currency = null ): string {
		// Determine currency to use
		$currency = $currency ?? ( $this->config['currency'] ?? 'USD' );

		// FIXED: Only convert to cents if it's a float/dollar amount
		// If the amount is less than 100 and is a float, assume it's in dollars
		if ( is_float( $amount ) || ( $amount < 100 && floor( $amount ) != $amount ) ) {
			$amount_in_cents = (int) round( $amount * 100 );
		} else {
			// Otherwise assume it's already in cents
			$amount_in_cents = (int) $amount;
		}

		return format_currency( $amount_in_cents, $currency );
	}

}