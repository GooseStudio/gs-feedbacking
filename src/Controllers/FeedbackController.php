<?php


namespace GooseStudio\Feedbacking\Controllers;

use WP_REST_Posts_Controller;

/**
 * Class FeedbackController
 *
 * @package GooseStudio\Feedbacking\Controllers
 */
class FeedbackController extends WP_REST_Posts_Controller {
	public function check_read_permission( $post ) : bool {
		$result = parent::check_read_permission( $post );
		if ( ! $result ) {
			return false;
		}
		if ( is_user_logged_in() &&
			( ( current_user_can( 'read_feedback' ) && wp_get_current_user()->ID === (int) $post->post_author )
			|| current_user_can( 'read_others_feedback' ) ) ) {
			return true;
		}
		return false;
	}
}
