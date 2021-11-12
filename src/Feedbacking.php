<?php


namespace GooseStudio\Feedbacking;

use GooseStudio\Feedbacking\Controllers\Routes;

class Feedbacking {

	public function init(): void {
		$this->add_hooks();
		Routes::init();
		( new FeedbackPostType() )->init();
		do_action( 'gs_feedbacking_init' );
	}

	/**
	 * Add hooks
	 */
	private function add_hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets' ] );

		add_action( 'wp_loaded', [ $this, 'upgrade_check' ] );
		if ( is_admin() ) {
			( new Settings() )->init();
		}
	}

	public function register_assets() : void {
		wp_register_script( 'gs-sf-frontend', plugins_url( '/dist/frontend/main.js', GS_SF_PLUGIN_BASENAME ), [ 'react', 'react-dom', 'underscore' ], GS_SF_ASSET_VERSION . '.1', true );
		wp_register_style( 'gs-sf-frontend', plugins_url( '/dist/frontend/style.css', GS_SF_PLUGIN_BASENAME ), [], GS_SF_ASSET_VERSION . '.1' );
	}
	/**
	 * Adds frontend CSS and JS assets.
	 */
	public function frontend_assets() : void {
		if ( current_user_can( 'publish_feedback' ) ) {
			wp_localize_script(
				'gs-sf-frontend', 
				'gs_sf_data', 
				[
					'endpoint'          => rest_url( '/wp/v2/gs_feedback' ),
					'endpoint_comments' => rest_url( '/wp/v2/comments' ),
					'elementPaths'      => [],
					'nonce'             => wp_create_nonce( 'wp_rest' ),
				] 
			);
			wp_enqueue_style( 'gs-sf-frontend' );
			wp_enqueue_script( 'gs-sf-frontend' );
			add_action(
				'wp_footer', 
				function() {?>
					<div id="feedback-app"></div>
					<?php
				} 
			);
		}

	}

	/**
	 * Adds admin CSS and JS
	 */
	public function admin_assets() : void {
		wp_register_script( 'gs-sf-admin', plugins_url( '/dist/admin/main.js', GS_SF_PLUGIN_BASENAME ), array( 'jquery' ), GS_SF_ASSET_VERSION, true );
		wp_register_style( 'gs-sf-admin', plugins_url( '/dist/admin/style.css', GS_SF_PLUGIN_BASENAME ), array(), GS_SF_ASSET_VERSION );
		wp_register_script( 'gs-sf-featherlight', plugins_url( '/dist/admin/featherlight.min.js', GS_SF_PLUGIN_BASENAME ), array( 'jquery' ), GS_SF_ASSET_VERSION, true );
		wp_register_style( 'gs-sf-featherlight', plugins_url( '/dist/admin/featherlight.min.css', GS_SF_PLUGIN_BASENAME ), array(), GS_SF_ASSET_VERSION );
		$screen = get_current_screen();
		if ( 'settings_page_site-feedback-options' === $screen->base ) {
			wp_enqueue_style( 'gs-sf-admin' );
		}
	}

	public function upgrade_check() : void {
		require_once ABSPATH . '/wp-includes/pluggable.php';
		if ( is_admin() && current_user_can( 'manage_options' ) && get_option( 'gs_sf_version' ) !== GS_SF_VERSION ) {
			$this->upgrade();
		}
	}

	private function upgrade(): void {
		update_option( 'gs_sf_version', GS_SF_VERSION );
		$capabilities = array(
			'edit_post'          => 'edit_feedback',
			'read_post'          => 'read_feedback',
			'delete_post'        => 'delete_feedback',
			'edit_posts'         => 'edit_feedback',
			'edit_others_posts'  => 'edit_others_feedback',
			'publish_posts'      => 'publish_feedback',
			'read_private_posts' => 'read_private_feedback',
		);
		$admin        = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $capabilities as $new ) {
				$admin->add_cap( $new );
			}
		}
		add_role( 'feedback', 'Feedback role', array_values( $capabilities ) );
	}
}
