<?php


namespace GooseStudio\Feedbacking\Providers;

/**
 * Interface IProvider
 *
 * @package GooseStudio\Feedbacking\FeedbackProviders
 */
interface IProvider {

	/**
	 * @return string
	 */
	public function get_name() : string;

	public function get_authentication_method():array;
}
