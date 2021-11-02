<?php


namespace GooseStudio\Feedbacking;

use GooseStudio\Feedbacking\Controllers\FeedbackController;

class FeedbackPostType {
	public const POST_TYPE = 'gs_feedback';
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ], 0 );
		add_action( 'admin_init', [ $this, 'admin_init' ], 0 );
		add_action( 'save_post', [ $this, 'save' ] );
		add_filter( 'manage_gs_feedback_posts_columns', [ $this, 'add_img_column' ], 10, 0 );
		add_filter( 'manage_posts_custom_column', [ $this, 'manage_img_column' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 20, 0 );
		add_action( 'wp_ajax_change_feedback_status', [ $this, 'change_feedback_status' ] );
	}

	public function enqueue():void {
		wp_enqueue_style( 'gs-sf-admin' );
		wp_enqueue_script( 'gs-sf-admin' );
	}

	public function admin_init():void {
		$this->remove_feedback_metaboxes();
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg_posts' ], 10, 2 );
		add_action( 'edit_form_after_title', [ $this, 'add_feedback_data' ] );
		remove_post_type_support( 'gs_feedback', 'title' );
		remove_post_type_support( 'gs_feedback', 'editor' );
	}
	public function remove_feedback_metaboxes():void {
		remove_meta_box( 'authordiv', 'gs_feedback', 'normal' ); // Author Metabox
		remove_meta_box( 'commentstatusdiv', 'gs_feedback', 'normal' ); // Comments Status Metabox
		// remove_meta_box( 'commentsdiv','gs_feedback','normal' ); // Comments Metabox
		remove_meta_box( 'postcustom', 'gs_feedback', 'normal' ); // Custom Fields Metabox
		remove_meta_box( 'postexcerpt', 'gs_feedback', 'normal' ); // Excerpt Metabox
		remove_meta_box( 'revisionsdiv', 'gs_feedback', 'normal' ); // Revisions Metabox
		remove_meta_box( 'slugdiv', 'gs_feedback', 'normal' ); // Slug Metabox
		remove_meta_box( 'trackbacksdiv', 'gs_feedback', 'normal' ); // Trackback Metabox
	}

	public function disable_gutenberg_posts( $current_status, $post_type ):bool {

		// Disabled post types
		$disabled_post_types = array( 'gs_feedback' );

		// Change $can_edit to false for any post types in the disabled post types array
		if ( in_array( $post_type, $disabled_post_types, true ) ) {
			$current_status = false;
		}

		return $current_status;
	}

	public function register_post_type() : void {
		$labels       = array(
			'name'                  => _x( 'Feedback', 'Post Type General Name', 'gs-sf' ),
			'singular_name'         => _x( 'Feedback', 'Post Type Singular Name', 'gs-sf' ),
			'menu_name'             => __( 'Feedback', 'gs-sf' ),
			'name_admin_bar'        => __( 'Feedback', 'gs-sf' ),
			'archives'              => __( 'Feedback Archives', 'gs-sf' ),
			'attributes'            => __( 'Feedback Attributes', 'gs-sf' ),
			'parent_item_colon'     => __( 'Parent Feedback:', 'gs-sf' ),
			'all_items'             => __( 'All Feedback', 'gs-sf' ),
			'add_new_item'          => __( 'Add New Feedback', 'gs-sf' ),
			'add_new'               => __( 'Add New Feedback', 'gs-sf' ),
			'new_item'              => __( 'New Feedback', 'gs-sf' ),
			'edit_item'             => __( 'Edit Feedback', 'gs-sf' ),
			'update_item'           => __( 'Update Feedback', 'gs-sf' ),
			'view_item'             => __( 'View Feedback', 'gs-sf' ),
			'view_items'            => __( 'View Feedback', 'gs-sf' ),
			'search_items'          => __( 'Search Feedback', 'gs-sf' ),
			'not_found'             => __( 'Not found', 'gs-sf' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'gs-sf' ),
			'featured_image'        => __( 'Screenshot', 'gs-sf' ),
			'set_featured_image'    => __( 'Set screenshot image', 'gs-sf' ),
			'remove_featured_image' => __( 'Remove screenshot', 'gs-sf' ),
			'use_featured_image'    => __( 'Use as screenshot', 'gs-sf' ),
			'uploaded_to_this_item' => __( 'Uploaded to this feedback', 'gs-sf' ),
			'items_list'            => __( 'Feedback list', 'gs-sf' ),
			'items_list_navigation' => __( 'Feedback list navigation', 'gs-sf' ),
			'filter_items_list'     => __( 'Filter feedback list', 'gs-sf' ),
		);
		$capabilities = array(
			'edit_post'          => 'edit_feedback',
			'read_post'          => 'read_feedback',
			'read_others_posts'  => 'read_others_feedback',
			'delete_post'        => 'delete_feedback',
			'edit_posts'         => 'edit_feedback',
			'edit_others_posts'  => 'edit_others_feedback',
			'publish_posts'      => 'publish_feedback',
			'read_private_posts' => 'read_private_feedback',
		);
		$args         = array(
			'label'                 => __( 'Feedback', 'gs-sf' ),
			'description'           => __( 'Post Type Description', 'gs-sf' ),
			'labels'                => $labels,
			'supports'              => array( 'author', 'thumbnail', 'title', 'editor', 'comments' ),
			'taxonomies'            => array(),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 75,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => false,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'capabilities'          => $capabilities,
			'show_in_rest'          => true,
			'register_meta_box_cb'  => [ $this, 'register_feedback_meta_boxes' ],
			'rest_controller_class' => FeedbackController::class,
		);
		register_post_type( 'gs_feedback', $args );
		$object_type = 'gs_feedback'; // The object type.
		// For custom post types, this is 'post', for custom comment types, this is 'comment'.

		$args1 = array(
			'type'         => 'string',
			'description'  => 'Path to commented element.', // Shown in the schema for the meta key.
			'single'       => true, // Return a single value of the type. Default: false.
			'show_in_rest' => true, // Show in the WP REST API response. Default: false.
		);

		register_meta( $object_type, '_element_path', $args1 );


		$args2 = array(
			'type'         => 'string', // Validate and sanitize the meta value as a string.
			'description'  => 'URL to where feedback was givet.', // Shown in the schema for the meta key.
			'single'       => true, // Return an array with the type used as the items type. Default: false.
			'show_in_rest' => true, // Show in the WP REST API response. Default: false.
		);

		register_meta( $object_type, '_url', $args2 );
		$args2 = array(
			'type'         => 'string', // Validate and sanitize the meta value as a string.
			'description'  => 'Browser information detected when commenting.', // Shown in the schema for the meta key.
			'single'       => true, // Return an array with the type used as the items type. Default: false.
			'show_in_rest' => true, // Show in the WP REST API response. Default: false.
		);

		register_meta( $object_type, '_browser_info', $args2 );

		$args2 = array(
			'type'         => 'string', // Validate and sanitize the meta value as a string.
			'description'  => 'Status of feedback.', // Shown in the schema for the meta key.
			'single'       => true, // Return an array with the type used as the items type. Default: false.
			'show_in_rest' => true, // Show in the WP REST API response. Default: false.
		);

		register_meta( $object_type, '_feedback_status', $args2 );
	}

	public function register_feedback_meta_boxes():void {
		add_meta_box( 'feedback_data', __( 'Data', 'gs_sf' ), [ $this, 'add_data_meta_box' ], 'gs_feedback', 'side', 'high' );
		add_meta_box( 'feedback_status', __( 'Status', 'gs_sf' ), [ $this, 'add_status_meta_box' ], 'gs_feedback', 'side', 'high' );
		remove_meta_box( 'titlediv', 'gs_feedback', 'core' );
		remove_meta_box( 'submitdiv', 'gs_feedback', 'core' );
	}

	/**
	 * @param $post
	 */
	public function add_data_meta_box( $post ):void {
		$browser_info = get_post_meta( $post->ID, '_browser_info', true ); ?>
			<ul class="browser-info">
				<li><span class="label"><?php esc_html_e( 'Browser', 'gs-sf' ); ?>:</span> <span class="value"><?php echo esc_html( $browser_info['browser']['name'] ); ?></span></li>
				<li><span class="label"><?php esc_html_e( 'OS', 'gs-sf' ); ?>:</span> <span class="value"><?php echo esc_html( $browser_info['os']['name'] ); ?> <?php echo esc_html( $browser_info['os']['version'] ); ?></span></li>
				<li><span class="label"><?php esc_html_e( 'Platform', 'gs-sf' ); ?>:</span> <span class="value"><?php echo esc_html( $browser_info['platform']['type'] ); ?> <?php echo isset( $browser_info['platform']['vendor'] ) ? esc_html( $browser_info['platform']['vendor'] ) : ''; ?></span></li>
				<li><span class="label"><?php esc_html_e( 'Screen', 'gs-sf' ); ?>:</span> <span class="value"><?php echo esc_html( $browser_info['screen']['width'] ); ?>x<?php echo esc_html( $browser_info['screen']['height'] ); ?></span></li>
				<li><span class="label"><?php esc_html_e( 'Resolution', 'gs-sf' ); ?>:</span> <span class="value"><?php echo esc_html( $browser_info['resolution']['width'] ); ?>x<?php echo esc_html( $browser_info['resolution']['height'] ); ?></span></li>
				<li><span class="label"><?php esc_html_e( 'User Agent', 'gs-sf' ); ?>:</span><br /> <span class="value"><?php echo esc_html( $browser_info['user_agent'] ); ?></span></li>
			</ul>
			<?php
	}
	/**
	 * @param $post
	 */
	public function add_status_meta_box( $post ): void {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'change_feedback_status', '_feedback_status_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_feedback_status', true );

		// Display the form, using the current value.
		?>
		<label for="feedback_feedback_status" class="aria-hidden">
			<?php esc_html_e( 'Working status of this feedback.', 'gs_sf' ); ?>
		</label>
		<select id="feedback_feedback_status" name="_feedback_status">
			<option <?php echo selected( $value, 'open' ); ?> value="open">Open</option>
			<option <?php echo selected( $value, 'review' ); ?> value="review">Review</option>
			<option <?php echo selected( $value, 'in_progress' ); ?> value="in_progress">In Progress</option>
			<option <?php echo selected( $value, 'closed' ); ?> value="closed">Closed</option>
		</select>
		<?php
	}

	public function add_feedback_data( $post ):void {
		if ( get_post_type( $post ) !== 'gs_feedback' ) {
			return;
		}
		?>
		<div class="feedback-container">
			<div class="inside">
				<div class="feedback-text">
					<?php echo wp_kses_post( wpautop( $post->post_content ) ); ?>
					<ul>
						<li>Path: <?php echo esc_html( get_post_meta( $post->ID, '_element_path', true ) ); ?></li>
						<li>URL: <a target="_blank" href="<?php echo esc_url( get_post_meta( $post->ID, '_url', true ) ); ?>"><?php echo esc_url( get_post_meta( $post->ID, '_url', true ) ); ?></a>
						</li>
					</ul>
				</div>
				<div class="screenshot"><?php the_post_thumbnail( 'medium' ); ?></div>
			</div>
		</div>
		<?php
	}
	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( int $post_id ): int {
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */
		// Check if our nonce is set.
		if ( ! isset( $_POST['_feedback_status_nonce'] ) ) {
			return $post_id;
		}
		if ( ! isset( $_POST['_feedback_status'] ) ) {
			return $post_id;
		}

		$nonce = sanitize_text_field( $_POST['_feedback_status_nonce'] );

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'change_feedback_status' ) ) {
			return $post_id;
		}

		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'gs_feedback' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, it's safe for us to save the data now. */

		// Sanitize the user input.
		$_feedback_status = sanitize_text_field( $_POST['_feedback_status'] );

		// Update the meta field.
		update_post_meta( $post_id, '_feedback_status', $_feedback_status );
		return $post_id;
	}

	public function add_img_column():array {
		$columns               = [];
		$columns['cb']         = '<input type="checkbox" />';
		$columns['status']     = __( 'Status' );
		$columns['screenshot'] = __( 'Screenshot' );
		$columns['title']      = __( 'Summary' );
		$columns['url']        = __( 'URL' );
		$columns['author']     = __( 'User' );
		$columns['date']       = __( 'Date' );
		$columns['comments']   = '<span class="vers comment-grey-bubble" title="' . __( 'Replies' ) . '"><span class="screen-reader-text">' . __( 'Replies' ) . '</span></span>';
		return $columns;
	}

	public function manage_img_column( $column_name, $post_id ) {
		if ( 'screenshot' === $column_name ) {
			echo get_the_post_thumbnail( $post_id, 'thumbnail' );
		}
		if ( 'url' === $column_name ) {
			echo '<a target="_blank" href="',esc_url( get_post_meta( $post_id, '_url', true ) ),'">',esc_url( get_post_meta( $post_id, '_url', true ) ),'</a>';
		}
		if ( 'status' === $column_name ) {
			$status   = get_post_meta( $post_id, '_feedback_status', true ) ?: 'open';
			$feedback = [
				'open'        => __( 'Open' ),
				'in_progress' => __( 'In progress' ),
				'review'      => __( 'Review' ),
				'closed'      => __( 'Closed' ),
			];
			echo '<span class="',esc_attr( $status ),'">', esc_html( $feedback[ $status ] ),'</span>';
		}
		return $column_name;
	}

	public function change_feedback_status():void {
		if ( ! isset( $_POST['_feedback_status_nonce'] ) ) {
			die( 0 );
		}

		$nonce = sanitize_text_field( $_POST['_feedback_status_nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'change_feedback_status' ) ) {
			die( 'Nonce value cannot be verified.' );
		}

		// The $_REQUEST contains all the data sent via ajax
		if ( isset( $_REQUEST['status'], $_REQUEST['post_id'] ) ) {

			$status  = sanitize_text_field( $_REQUEST['status'] );
			$post_id = sanitize_text_field( $_REQUEST['post_id'] );
			if ( in_array( $status, [ 'open', 'review', 'in_progress', 'closed' ], true ) ) {
				update_post_meta( $post_id, '_feedback_status', $status );
				die( 1 );
			}
		}

		// Always die in functions echoing ajax content
		die( 0 );
	}
}
