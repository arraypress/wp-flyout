<?php
/**
 * Placeholder Image Generator Utility
 *
 * Generates SVG placeholder images for avatars, thumbnails, and icons.
 *
 * @package     ArrayPress\WPFlyout\Utils
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Utils;

/**
 * Class PlaceholderImage
 *
 * Generates SVG-based placeholder images as data URIs.
 */
class Placeholder {

	/**
	 * Generate an avatar placeholder with a letter/initials
	 *
	 * @param string $text       Text to display (usually initials)
	 * @param string $bg_color   Background color (hex without #)
	 * @param string $text_color Text color (hex without #)
	 * @param int    $size       Size in pixels
	 *
	 * @return string Data URI of the SVG image
	 */
	public static function avatar(
		string $text = 'U',
		string $bg_color = '6366F1',
		string $text_color = 'FFFFFF',
		int $size = 100
	): string {
		$font_size = (int) ( $size * 0.4 );

		$svg = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">' .
			'<rect width="%d" height="%d" fill="#%s"/>' .
			'<text x="50%%" y="50%%" font-family="-apple-system, system-ui, sans-serif" font-size="%d" fill="#%s" text-anchor="middle" dominant-baseline="middle">%s</text>' .
			'</svg>',
			$size, $size, $size, $size,
			$size, $size,
			esc_attr( $bg_color ),
			$font_size,
			esc_attr( $text_color ),
			esc_html( mb_substr( $text, 0, 2 ) )
		);

		return self::svg_to_data_uri( $svg );
	}

	/**
	 * Generate a thumbnail placeholder with text
	 *
	 * @param string $text          Text to display
	 * @param string $bg_color      Background color (hex without #)
	 * @param string $text_color    Text color (hex without #)
	 * @param int    $width         Width in pixels
	 * @param int    $height        Height in pixels
	 * @param int    $border_radius Border radius
	 *
	 * @return string Data URI of the SVG image
	 */
	public static function thumbnail(
		string $text = 'IMG',
		string $bg_color = '4F46E5',
		string $text_color = 'FFFFFF',
		int $width = 60,
		int $height = 60,
		int $border_radius = 4
	): string {
		$font_size = min( (int) ( $height * 0.25 ), 14 );

		$svg = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">' .
			'<rect width="%d" height="%d" rx="%d" fill="#%s"/>' .
			'<text x="50%%" y="50%%" font-family="-apple-system, system-ui, sans-serif" font-size="%d" font-weight="600" fill="#%s" text-anchor="middle" dominant-baseline="middle">%s</text>' .
			'</svg>',
			$width, $height, $width, $height,
			$width, $height,
			$border_radius,
			esc_attr( $bg_color ),
			$font_size,
			esc_attr( $text_color ),
			esc_html( mb_substr( $text, 0, 4 ) )
		);

		return self::svg_to_data_uri( $svg );
	}

	/**
	 * Generate an icon placeholder
	 *
	 * @param string $icon       Dashicon class name (without 'dashicons-')
	 * @param string $bg_color   Background color (hex without #)
	 * @param string $icon_color Icon color (hex without #)
	 * @param int    $size       Size in pixels
	 *
	 * @return string Data URI of the SVG image
	 */
	public static function icon(
		string $icon = 'admin-generic',
		string $bg_color = 'F3F4F6',
		string $icon_color = '6B7280',
		int $size = 40
	): string {
		// This would require mapping dashicons to SVG paths
		// For now, return a simple placeholder
		return self::thumbnail( '?', $bg_color, $icon_color, $size, $size, $size / 2 );
	}

	/**
	 * Convert SVG string to data URI
	 *
	 * @param string $svg SVG content
	 *
	 * @return string Data URI
	 */
	private static function svg_to_data_uri( string $svg ): string {
		// Method 1: Base64 encoding (most compatible)
		if ( function_exists( 'base64_encode' ) ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		// Method 2: URL encoding (fallback, slightly smaller)
		$svg = str_replace( [ "\n", "\r", "\t" ], '', $svg );
		$svg = str_replace( '"', "'", $svg );

		return 'data:image/svg+xml,' . rawurlencode( $svg );
	}

	/**
	 * Get a WordPress default avatar URL
	 *
	 * @param string $email Email for gravatar
	 * @param int    $size  Size in pixels
	 *
	 * @return string Avatar URL
	 */
	public static function get_wp_avatar( string $email = '', int $size = 100 ): string {
		return get_avatar_url( $email, [
			'size'    => $size,
			'default' => 'mystery',
			'rating'  => 'g'
		] );
	}

}