<?php


namespace GooseStudio\Feedbacking\Dependencies;

use WP_Error;
use WP_Image_Editor;
use WP_Post;

/**
 * Regenerate Thumbnails: Attachment regenerator class
 *
 * @package RegenerateThumbnails
 * @since 3.0.0
 */

/**
 * Regenerates the thumbnails for a given attachment.
 *
 * @since 3.0.0
 *///phpcs:ignorefile
class Regenerator {

	/**
	 * The WP_Post object for the attachment that is being operated on.
	 *
	 * @since 3.0.0
	 *
	 * @var WP_Post
	 */
	public $attachment;

	/**
	 * The full path to the original image so that it can be passed between methods.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $fullsizepath;

	/**
	 * An array of thumbnail size(s) that were skipped during regeneration due to already existing.
	 * A class variable is used so that the data can later be used to merge the size(s) back in.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public $skipped_thumbnails = array();

	/**
	 * Generates an instance of this class after doing some setup.
	 *
	 * MIME type is purposefully not validated in order to be more future proof and
	 * to avoid duplicating a ton of logic that already exists in WordPress core.
	 *
	 * @since 3.0.0
	 *
	 * @param int $attachment_id Attachment ID to process.
	 *
	 * @return Regenerator|WP_Error A new instance of Regenerator on success, or WP_Error on error.
	 */
	public static function get_instance( $attachment_id ) {
		$attachment = get_post( $attachment_id );

		if ( ! $attachment ) {
			return new WP_Error(
				'regenerate_thumbnails_regenerator_attachment_doesnt_exist',
				__( 'No attachment exists with that ID.', 'regenerate-thumbnails' ),
				array(
					'status' => 404,
				)
			);
		}

		// We can only regenerate thumbnails for attachments.
		if ( 'attachment' !== get_post_type( $attachment ) ) {
			return new WP_Error(
				'regenerate_thumbnails_regenerator_not_attachment',
				__( 'This item is not an attachment.', 'regenerate-thumbnails' ),
				array(
					'status' => 400,
				)
			);
		}

		// Don't touch any attachments that are being used as a site icon. Their thumbnails are usually custom cropped.
		if ( self::is_site_icon( $attachment ) ) {
			return new WP_Error(
				'regenerate_thumbnails_regenerator_is_site_icon',
				__( "This attachment is a site icon and therefore the thumbnails shouldn't be touched.", 'regenerate-thumbnails' ),
				array(
					'status'     => 415,
					'attachment' => $attachment,
				)
			);
		}

		return new Regenerator( $attachment );
	}

	/**
	 * The constructor for this class. Don't call this directly, see get_instance() instead.
	 * This is done so that WP_Error objects can be returned during class initiation.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $attachment The WP_Post object for the attachment that is being operated on.
	 */
	private function __construct( WP_Post $attachment ) {
		$this->attachment = $attachment;
	}

	/**
	 * Returns whether the attachment is or was a site icon.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $attachment The WP_Post object for the attachment that is being operated on.
	 *
	 * @return bool Whether the attachment is or was a site icon.
	 */
	public static function is_site_icon( WP_Post $attachment ) {
		return ( 'site-icon' === get_post_meta( $attachment->ID, '_wp_attachment_context', true ) );
	}

	/**
	 * Get the path to the fullsize attachment.
	 *
	 * @return string|WP_Error The path to the fullsize attachment, or a WP_Error object on error.
	 */
	public function get_fullsizepath() {
		if ( $this->fullsizepath ) {
			return $this->fullsizepath;
		}

		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$this->fullsizepath = wp_get_original_image_path( $this->attachment->ID );
		} else {
			$this->fullsizepath = get_attached_file( $this->attachment->ID );
		}

		if ( false === $this->fullsizepath || ! file_exists( $this->fullsizepath ) ) {
			$error = new WP_Error(
				'regenerate_thumbnails_regenerator_file_not_found',
				sprintf(
				/* translators: The relative upload path to the attachment. */
					__( "The fullsize image file cannot be found in your uploads directory at <code>%s</code>. Without it, new thumbnail images can't be generated.", 'regenerate-thumbnails' ),
					_wp_relative_upload_path( $this->fullsizepath )
				),
				array(
					'status'       => 404,
					'fullsizepath' => _wp_relative_upload_path( $this->fullsizepath ),
					'attachment'   => $this->attachment,
				)
			);

			$this->fullsizepath = $error;
		}

		return $this->fullsizepath;
	}

	/**
	 * Regenerate the thumbnails for this instance's attachment.
	 *
	 * @since 3.0.0
	 *
	 * @param array|string $args {
	 *     Optional. Array or string of arguments for thumbnail regeneration.
	 *
	 *     @type bool $only_regenerate_missing_thumbnails  Skip regenerating existing thumbnail files. Default true.
	 *     @type bool $delete_unregistered_thumbnail_files Delete any thumbnail sizes that are no longer registered. Default false.
	 * }
	 *
	 * @return mixed|WP_Error Metadata for attachment (see wp_generate_attachment_metadata()), or WP_Error on error.
	 */
	public function regenerate( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'only_regenerate_missing_thumbnails'  => true,
				'delete_unregistered_thumbnail_files' => false,
			) 
		);

		$fullsizepath = $this->get_fullsizepath();
		if ( is_wp_error( $fullsizepath ) ) {
			$fullsizepath->add_data( array( 'attachment' => $this->attachment ) );

			return $fullsizepath;
		}

		$old_metadata = wp_get_attachment_metadata( $this->attachment->ID );

		if ( $args['only_regenerate_missing_thumbnails'] ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes_to_only_missing_thumbnails' ), 10, 2 );
		}

		require_once ABSPATH . 'wp-admin/includes/admin.php';
		$new_metadata = wp_generate_attachment_metadata( $this->attachment->ID, $fullsizepath );

		if ( $args['only_regenerate_missing_thumbnails'] ) {
			// Thumbnail sizes that existed were removed and need to be added back to the metadata.
			foreach ( $this->skipped_thumbnails as $skipped_thumbnail ) {
				if ( ! empty( $old_metadata['sizes'][ $skipped_thumbnail ] ) ) {
					$new_metadata['sizes'][ $skipped_thumbnail ] = $old_metadata['sizes'][ $skipped_thumbnail ];
				}
			}
			$this->skipped_thumbnails = array();

			remove_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes_to_only_missing_thumbnails' ), 10 );
		}

		$wp_upload_dir = dirname( $fullsizepath ) . DIRECTORY_SEPARATOR;

		if ( $args['delete_unregistered_thumbnail_files'] ) {
			// Delete old sizes that are still in the metadata.
			$intermediate_image_sizes = get_intermediate_image_sizes();
			foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
				if ( in_array( $old_size, $intermediate_image_sizes ) ) {
					continue;
				}

				wp_delete_file( $wp_upload_dir . $old_size_data['file'] );

				unset( $new_metadata['sizes'][ $old_size ] );
			}

			$relative_path = dirname( $new_metadata['file'] ) . DIRECTORY_SEPARATOR;

			// It's possible to upload an image with a filename like image-123x456.jpg and it shouldn't be deleted.
			$whitelist = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT
					meta_value
				FROM
					{$wpdb->postmeta}
				WHERE
					meta_key = '_wp_attached_file'
					AND meta_value REGEXP %s
				/* Regenerate Thumbnails */
				",
					'^' . preg_quote( $relative_path ) . '[^' . preg_quote( DIRECTORY_SEPARATOR ) . ']+-[0-9]+x[0-9]+\.'
				) 
			);
			$whitelist = array_map( 'basename', $whitelist );

			$filelist = array();
			foreach ( scandir( $wp_upload_dir ) as $file ) {
				if ( '.' == $file || '..' == $file || ! is_file( $wp_upload_dir . $file ) ) {
					continue;
				}

				$filelist[] = $file;
			}

			$registered_thumbnails = array();
			foreach ( $new_metadata['sizes'] as $size ) {
				$registered_thumbnails[] = $size['file'];
			}

			$fullsize_parts = pathinfo( $fullsizepath );

			foreach ( $filelist as $file ) {
				if ( in_array( $file, $whitelist ) || in_array( $file, $registered_thumbnails ) ) {
					continue;
				}

				if ( ! preg_match( '#^' . preg_quote( $fullsize_parts['filename'], '#' ) . '-[0-9]+x[0-9]+\.' . preg_quote( $fullsize_parts['extension'], '#' ) . '$#', $file ) ) {
					continue;
				}

				wp_delete_file( $wp_upload_dir . $file );
			}
		} elseif ( ! empty( $old_metadata ) && ! empty( $old_metadata['sizes'] ) && is_array( $old_metadata['sizes'] ) ) {
			// If not deleting, rename any size conflicts to avoid them being lost if the file still exists.
			foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
				if ( empty( $new_metadata['sizes'][ $old_size ] ) ) {
					$new_metadata['sizes'][ $old_size ] = $old_metadata['sizes'][ $old_size ];
					continue;
				}

				$new_size_data = $new_metadata['sizes'][ $old_size ];

				if (
					$new_size_data['width'] !== $old_size_data['width']
					&& $new_size_data['height'] !== $old_size_data['height']
					&& file_exists( $wp_upload_dir . $old_size_data['file'] )
				) {
					$new_metadata['sizes'][ $old_size . '_old_' . $old_size_data['width'] . 'x' . $old_size_data['height'] ] = $old_size_data;
				}
			}
		}

		wp_update_attachment_metadata( $this->attachment->ID, $new_metadata );

		return $new_metadata;
	}

	/**
	 * Filters the list of thumbnail sizes to only include those which have missing files.
	 *
	 * @since 3.0.0
	 *
	 * @param array $sizes             An associative array of registered thumbnail image sizes.
	 * @param array $fullsize_metadata An associative array of fullsize image metadata: width, height, file.
	 *
	 * @return array An associative array of image sizes.
	 */
	public function filter_image_sizes_to_only_missing_thumbnails( $sizes, $fullsize_metadata ) {
		if ( ! $sizes ) {
			return $sizes;
		}

		$fullsizepath = $this->get_fullsizepath();
		if ( is_wp_error( $fullsizepath ) ) {
			return $sizes;
		}

		$editor = wp_get_image_editor( $fullsizepath );
		if ( is_wp_error( $editor ) ) {
			return $sizes;
		}

		$metadata = wp_get_attachment_metadata( $this->attachment->ID );

		// This is based on WP_Image_Editor_GD::multi_resize() and others.
		foreach ( $sizes as $size => $size_data ) {
			if ( empty( $metadata['sizes'][ $size ] ) ) {
				continue;
			}

			if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) ) {
				continue;
			}

			if ( ! isset( $size_data['width'] ) ) {
				$size_data['width'] = null;
			}
			if ( ! isset( $size_data['height'] ) ) {
				$size_data['height'] = null;
			}

			if ( ! isset( $size_data['crop'] ) ) {
				$size_data['crop'] = false;
			}

			$thumbnail = $this->get_thumbnail(
				$editor,
				$fullsize_metadata['width'],
				$fullsize_metadata['height'],
				$size_data['width'],
				$size_data['height'],
				$size_data['crop']
			);

			// The false check filters out thumbnails that would be larger than the fullsize image.
			// The size comparison makes sure that the size is also correct.
			if (
				false === $thumbnail
				|| (
					$thumbnail['width'] === $metadata['sizes'][ $size ]['width']
					&& $thumbnail['height'] === $metadata['sizes'][ $size ]['height']
					&& file_exists( $thumbnail['filename'] )
				)
			) {
				$this->skipped_thumbnails[] = $size;
				unset( $sizes[ $size ] );
			}
		}

		/**
		 * Filters the list of missing thumbnail sizes if you want to add/remove any.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $sizes             An associative array of image sizes that are missing.
		 * @param array  $fullsize_metadata An associative array of fullsize image metadata: width, height, file.
		 * @param object $this              The current instance of this class.
		 *
		 * @return array An associative array of image sizes.
		 */
		return apply_filters( 'regenerate_thumbnails_missing_thumbnails', $sizes, $fullsize_metadata, $this );
	}

	/**
	 * Generate the thumbnail filename and dimensions for a given set of constraint dimensions.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Image_Editor|WP_Error $editor           An instance of WP_Image_Editor, as returned by wp_get_image_editor().
	 * @param int                      $fullsize_width   The width of the fullsize image.
	 * @param int                      $fullsize_height  The height of the fullsize image.
	 * @param int                      $thumbnail_width  The width of the thumbnail.
	 * @param int                      $thumbnail_height The height of the thumbnail.
	 * @param bool                     $crop             Whether to crop or not.
	 *
	 * @return array|false An array of the filename, thumbnail width, and thumbnail height,
	 *                     or false on failure to resize such as the thumbnail being larger than the fullsize image.
	 */
	public function get_thumbnail( $editor, $fullsize_width, $fullsize_height, $thumbnail_width, $thumbnail_height, $crop ) {
		$dims = image_resize_dimensions( $fullsize_width, $fullsize_height, $thumbnail_width, $thumbnail_height, $crop );

		if ( ! $dims ) {
			return false;
		}

		[ , , , , $dst_w, $dst_h ] = $dims;

		$suffix   = "{$dst_w}x{$dst_h}";
		$file_ext = strtolower( pathinfo( $this->get_fullsizepath(), PATHINFO_EXTENSION ) );

		return array(
			'filename' => $editor->generate_filename( $suffix, null, $file_ext ),
			'width'    => $dst_w,
			'height'   => $dst_h,
		);
	}

	/**
	 * Returns an array of all thumbnail sizes, including their label, size, and crop setting.
	 *
	 * @return array An array, with the thumbnail label as the key and an array of thumbnail properties (width, height, crop).
	 */
	public function get_thumbnail_sizes():array {
		global $_wp_additional_image_sizes;

		$thumbnail_sizes = array();

		foreach ( get_intermediate_image_sizes() as $size ) {
			$thumbnail_sizes[ $size ]['label'] = $size;
			if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$thumbnail_sizes[ $size ]['width']  = (int) get_option( $size . '_size_w' );
				$thumbnail_sizes[ $size ]['height'] = (int) get_option( $size . '_size_h' );
				$thumbnail_sizes[ $size ]['crop']   = ( 'thumbnail' === $size ) ? (bool) get_option( 'thumbnail_crop' ) : false;
			} elseif ( ! empty( $_wp_additional_image_sizes ) && ! empty( $_wp_additional_image_sizes[ $size ] ) ) {
				$thumbnail_sizes[ $size ]['width']  = (int) $_wp_additional_image_sizes[ $size ]['width'];
				$thumbnail_sizes[ $size ]['height'] = (int) $_wp_additional_image_sizes[ $size ]['height'];
				$thumbnail_sizes[ $size ]['crop']   = (bool) $_wp_additional_image_sizes[ $size ]['crop'];
			}
		}

		return $thumbnail_sizes;
	}
}
