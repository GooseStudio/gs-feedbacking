<?php

namespace GooseStudio\Feedbacking\Controllers;

use GooseStudio\Feedbacking\Dependencies\Regenerator;
use WP_Comment;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

class Routes {
	public static function init(): void {
		$route = new self();

		add_action(
			'rest_api_init',
			function () use ( $route ) {
				add_action( 'rest_insert_gs_feedback', [ $route, 'insert_feedback_meta' ], 10, 2 );
				add_filter( 'rest_prepare_comment', [ $route, 'rest_prepare_comment' ], 10, 2 );
				register_rest_route(
					'wp/v2/gs_feedback',
					'/paths/',
					[
						'methods'             => 'GET',
						'callback'            => [ $route, 'get_paths' ],
						'permission_callback' => function () {
							return current_user_can( 'read_feedback' );
						},
					]
				);
				register_rest_route(
					'wp/v2/gs_feedback',
					'/(?P<id>\d+)/comments/',
					[
						'methods'             => 'GET',
						'callback'            => [ $route, 'get_comments' ],
						'permission_callback' => function () {
							return current_user_can( 'read_feedback' ) || current_user_can( 'read_others_feedback' );
						},
					]
				);
				register_rest_route(
					'wp/v2/gs_feedback',
					'/frontend/(?P<id>\d+)',
					[
						'methods'             => 'GET',
						'callback'            => [ $route, 'get_feedback' ],
						'permission_callback' => function () {
							return current_user_can( 'read_feedback' );
						},
					]
				);

				register_rest_route(
					'wp/v2/gs_feedback',
					'/frontend/',
					[
						'methods'             => 'GET',
						'callback'            => [ $route, 'get_feedbacks' ],
						'permission_callback' => function () {
							return current_user_can( 'read_feedback' );
						},
					]
				);
			}
		);

	}

	public function rest_prepare_comment( WP_REST_Response $response, WP_Comment $comment ): WP_REST_Response {
		if ( 'gs_feedback' === get_post_type( $comment->comment_post_ID ) ) {
			$response->data['date']     = date_i18n( 'G:i, M jS, Y', strtotime( $comment->comment_date ) );
			$response->data['date_gmt'] = date_i18n( 'G:i, F jS, Y', strtotime( $comment->comment_date_gmt ), true );
		}

		return $response;
	}

	public function insert_feedback_meta( WP_Post $post, WP_REST_Request $request ): void {
		$metas = $request->get_param( 'meta' );
		if ( is_array( $metas ) ) {
			foreach ( $metas as $name => $value ) {
				if ( in_array( $name, [ '_element_path', '_browser_info', '_url' ], true ) ) {
					update_post_meta( $post->ID, $name, $value );
				}
			}
		}
		$screenshot = $request->get_param( 'screenshot' );
		if ( $screenshot ) {
			$this->add_featured_image( $post, $screenshot );
		}
	}

	public function add_featured_image( WP_Post $post, $screenshot ): void {

		$wp_upload_dir   = wp_upload_dir();
		$upload_path     = str_replace( '/', DIRECTORY_SEPARATOR, $wp_upload_dir['path'] ) . DIRECTORY_SEPARATOR;
		$img             = str_replace( [ 'data:image/png;base64,', ' ' ], [ '', '+' ], $screenshot );
		$decoded         = base64_decode( $img );
		$filename        = 'preview.png';
		$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$wp_filesystem->put_contents( $upload_path . $hashed_filename, $decoded, 0644 );

		$attachment = [
			'post_mime_type' => 'image/png',
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $hashed_filename ),
		];
		$attach_id  = wp_insert_attachment( $attachment, $wp_upload_dir['path'] . '/' . $hashed_filename );
		$info       = getimagesize( $upload_path . $hashed_filename );
		$meta       = [
			'width'          => $info[0],
			'height'         => $info[1],
			'hwstring_small' => "height='{$info[1]}' width='{$info[0]}'",
			'file'           => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
			'sizes'          => [],         // thumbnails etc.
			'image_meta'     => [],    // EXIF data
		];
		update_post_meta( $attach_id, '_wp_attachment_metadata', $meta );
		set_post_thumbnail( $post, $attach_id );
		$reg = Regenerator::get_instance( $attach_id );
		$reg->regenerate( $reg->get_thumbnail_sizes() );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_paths( WP_REST_Request $request ): array {
		$comments          = get_posts(
			[
				'numberposts' => - 1,
				'post_type'   => 'gs_feedback',
				'meta_key'    => '_url',
				'meta_value'  => $request->get_param( 'url' ),
			]
		);
		$response_comments = [];
		foreach ( $comments as $comment ) {
			$path = get_post_meta( $comment->ID, '_element_path', true );
			if ( ! isset( $response_comments[ md5( $path ) ] ) ) {
				$response_comments[ md5( $path ) ] = [
					'path' => $path,
					'ids'  => [],
				];
			}
			$response_comments[ md5( $path ) ]['ids'][] = $comment->ID;
		}

		return array_values( $response_comments );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_comments( WP_REST_Request $request ): array {
		$comments          = get_comments(
			[
				'post_id' => $request->get_param( 'id' ),
				'status'  => 'approve',
				'type'    => 'comment',
				'order'   => 'ASC',
				'orderby' => 'comment_date',
			]
		);
		$response_comments = [];
		/**
		 * @var WP_Comment $comment
		 */
		foreach ( $comments as $comment ) {
			$comment->comment_author_email = '';
			$comment->comment_author_IP    = '';
			$response                      = [
				'id'          => $comment->comment_ID,
				'post_ID'     => $comment->comment_post_ID,
				'author_name' => $comment->comment_author,
				'date'        => date_i18n( 'G:i, M jS, Y', strtotime( $comment->comment_date ) ),
				'date_gmt'    => date_i18n( 'G:i, F jS, Y', strtotime( $comment->comment_date_gmt ), true ),
				'content'     => wpautop( wptexturize( $comment->comment_content ) ),
			];
			$response_comments[]           = $response;
		}

		return $response_comments;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_feedback( WP_REST_Request $request ): array {
		return $this->get_feedback_array( (int) $request->get_param( 'id' ) );
	}

	public function get_feedback_array( int $id ): array {
		$feedback          = get_post( $id );
		$comments          = get_comments(
			[
				'post_id' => $id,
				'status'  => 'approve',
				'type'    => 'comment',
				'order'   => 'ASC',
				'orderby' => 'comment_date',
			]
		);
		$response_comments = [];
		/**
		 * @var WP_Comment $comment
		 */
		foreach ( $comments as $comment ) {
			$response            = [
				'id'          => $comment->comment_ID,
				'post_ID'     => $comment->comment_post_ID,
				'author_name' => $comment->comment_author,
				'date'        => date_i18n( 'G:i, M jS, Y', strtotime( $comment->comment_date ) ),
				'date_gmt'    => date_i18n( 'G:i, F jS, Y', strtotime( $comment->comment_date_gmt ), true ),
				'content'     => wpautop( wptexturize( $comment->comment_content ) ),
				'current'     => wp_get_current_user()->user_email === $comment->comment_author_email,
			];
			$response_comments[] = $response;
		}

		return [
			'id'           => $feedback->ID,
			'content'      => wpautop( wptexturize( $feedback->post_content ) ),
			'author'       => ( new WP_User( $feedback->post_author ) )->display_name,
			'date'         => date_i18n( 'G:i, M jS, Y', $feedback->comment_date ),
			'date_gmt'     => date_i18n( 'G:i, F jS, Y', $feedback->comment_date_gmt ),
			'path'         => get_post_meta( $feedback->ID, '_element_path', true ),
			'browser_info' => get_post_meta( $feedback->ID, '_browser_info', true ),
			'screenshot'   => [
				'thumbnail' => get_the_post_thumbnail_url( $feedback->ID ),
				'full'      => get_the_post_thumbnail_url( $feedback->ID, 'raw' ),
			],
			'comments'     => $response_comments,
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_feedbacks( WP_REST_Request $request ): array {
		$json  = $request->get_param( 'ids' );
		$array = json_decode( $json, true );

		return array_map( [ $this, 'get_feedback_array' ], $array );
	}
}
