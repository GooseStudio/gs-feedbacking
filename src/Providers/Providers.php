<?php


namespace GooseStudio\Feedbacking\Providers;

/**
 * Class Providers
 *
 * @package GooseStudio\Feedbacking\FeedbackProviders
 */
class Providers {
	/**
	 * @var array
	 */
	private static array $providers = array();
	private static array $cache;

	public static function register( string $provider_name, string $provider_class ) : void {
		self::$providers[ $provider_name ] = $provider_class;
	}

	/**
	 * @return array
	 */
	public static function all() : array {
		return self::$providers;
	}

	/**
	 * @param string $provider_name
	 *
	 * @return IProvider
	 */
	public static function get_provider( string $provider_name ):IProvider {
		if ( ! isset( self::$cache[ $provider_name ] ) ) {
			self::$cache[ $provider_name ] = new self::$providers[ $provider_name ]();
		}
		return self::$cache[ $provider_name ];
	}
}
