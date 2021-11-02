<?php
/**
 * Plugin Name: GS Feedbacking
 * Plugin URI: https://goose.studio/products/feedbacking/
 * Description: Prerelease version. Enable users to comment your site design.
 * Version: 0.1
 * Author: Goose Studio
 * Author URI: https://goose.studio/
 * Copyright: Andreas Nurbo
 * Text Domain: gs-sf
 * License: GPLv3
 * Domain Path: /lang
 */

/**
 *
 * Comment and give feedback on site parts
 * Copyright (C) 2021  Andreas Nurbo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use GooseStudio\Feedbacking\Feedbacking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( version_compare( PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			$message = sprintf( 'Feedbacking requires at least PHP version 7.4. You currently have %s. ', PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION );
			echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
		}
	);

	return;
}

define( 'GS_SF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'GS_SF_PLUGIN_FILE__FILE', __FILE__ );
define( 'GS_SF_DIR', __DIR__ );
define( 'GS_SF_UUID', 'aca5a1ae-808e-4907-8d36-aa0cc817ae57' );
define( 'GS_SF_VERSION', '0.1' );
define( 'GS_SF_ASSET_VERSION', GS_SF_VERSION . '.1' );

if ( ! defined( 'GS_STORE_URL' ) ) {
	define( 'GS_STORE_URL', 'https://goose.studio' );
}

if ( ! defined( 'GS_DOCUMENTATION_URL' ) ) {
	define( 'GS_DOCUMENTATION_URL', 'https://docs.goose.studio' );
}

require __DIR__ . '/autoloader.php';

( new Feedbacking() )->init();

if ( ! function_exists( 'RandomCompat_substr' ) ) {
	/**
	 * @param string   $binary_string
	 * @param int      $start
	 * @param int|null $length
	 *
	 * @return string
	 * @throws TypeError If params are of wrong type error is thrown.
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	function RandomCompat_substr( $binary_string, $start, $length = null ) {//phpcs:ignore
		if ( ! is_string( $binary_string ) ) {
			throw new TypeError(
				'RandomCompat_substr(): First argument should be a string'
			);
		}

		if ( ! is_int( $start ) ) {
			throw new TypeError(
				'RandomCompat_substr(): Second argument should be an integer'
			);
		}

		if ( ! ( null === $length ) ) {
			if ( ! is_int( $length ) ) {
				throw new TypeError(
					'RandomCompat_substr(): Third argument should be an integer, or omitted'
				);
			}

			return (string) substr( $binary_string, $start, $length );
		}

		return (string) substr( $binary_string, $start );
	}
}
