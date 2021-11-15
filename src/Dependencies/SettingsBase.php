<?php
/**
 *
 *
 * @noinspection ALL
 */

/**
 *
 *
 * @noinspection ReturnTypeCanBeDeclaredInspection
 */

namespace GooseStudio\Feedbacking\Dependencies;

/**
 * For further details please visit http://docs.easydigitaldownloads.com/article/383-automatic-upgrades-for-wordpress-plugins
 */
abstract class SettingsBase {
	protected $settings_page;
	/**
	 * @var string
	 */
	private $license_key_option;
	private $name;
	/**
	 * @var string
	 */
	private $slug;
	protected $basename;
	private $current_version;
	protected $doc_url;

	/**
	 * @var bool
	 */
	private $enable_beta_support;

	/**
	 * @var array
	 */
	private $pages;

	/**
	 * SettingsBase constructor.
	 *
	 * @param $basename
	 * @param $name
	 * @param $current_version
	 * @param $doc_url
	 */
	public function __construct( $basename, $name, $current_version, $doc_url, $use_settings_page = true ) {
		$this->slug              = dirname( $basename );
		$this->name              = $name;
		$this->basename          = $basename;
		$this->current_version   = $current_version;
		$this->use_settings_page = $use_settings_page;
		$this->settings_page     = "{$this->slug}-options";
		$this->doc_url           = $doc_url;
	}

	/**
	 * Initialize hooks
	 */
	public function init() {
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		if ( $this->use_settings_page ) {
			add_action( 'admin_menu', [ $this, 'settings_menu' ] );
			add_action( 'network_admin_menu', [ $this, 'network_settings_menu' ] );
			add_filter( "plugin_action_links_{$this->basename}", [ $this, 'add_settings_link' ] );
			add_filter( "network_admin_plugin_action_links_{$this->basename}", [ $this, 'add_network_settings_link' ] );

			if ( $this->is_saving_settings() ) {
				add_action(
					'admin_init',
					function () {
						$this->save_settings();
					}
				);
			}
		}
	}

	public function add_settings_page( $key, $label, $sections = [] ) {
		$this->pages[] = [ $key, $label, $sections ];
	}

	private function is_saving_settings() {
		return isset( $_REQUEST['page'], $_REQUEST['action'] ) && $this->settings_page === $_REQUEST['page'] && 'update' === $_REQUEST['action'];//phpcs:ignore
	}

	private function save_settings() {
		check_admin_referer( $this->settings_page . '-options' );
		$whitelist_options = apply_filters( 'whitelist_options', [] );
		$options           = isset( $whitelist_options[ $this->settings_page ] ) ? $whitelist_options[ $this->settings_page ] : [ $this->settings_page ];
		$old_values        = get_option( $this->settings_page, [] );
		$new_values        = [];
		// @codingStandardsIgnoreStart
		if ( isset( $_POST[ $this->settings_page ] ) ) {
			$new_values = is_array( $_POST[ $this->settings_page ] ) ? $_POST[ $this->settings_page ] : trim( $new_values ) ;
		}
        // @codingStandardsIgnoreEnd

		/**
		 * @var array $options ;
		 */
		foreach ( $options as $option ) {
			if ( ! isset( $new_values[ $option ] ) ) {
				continue;
			}
			$value                 = $new_values[ $option ];
			$value                 = apply_filters( "sanitize_option_{$option}", $value );
			$new_values[ $option ] = $value;
		}
		$new_values = array_merge( $old_values, $new_values );
		update_option( $this->settings_page, $new_values );
		global $gs_setting_errors;
		if ( ! $gs_setting_errors ) {
			add_settings_error( $this->settings_page, 'settings_updated', 'Settings updated', 'updated' );
		}

		set_transient( 'settings_errors', get_settings_errors(), 30 );
		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		wp_safe_redirect( $goback );
		exit;
	}

	private $options;

	public function get_value( $section, $key = '', $default = '' ) {
		if ( ! isset( $this->options ) ) {
			$this->options = get_option( $this->settings_page, [] );
		}

		if ( $key ) {
			return isset( $this->options[ $section ][ $key ] ) ? $this->options[ $section ][ $key ] : $default;
		}

		return isset( $this->options[ $section ] ) ? $this->options[ $section ] : [];
	}

	public function settings_menu() {
		add_options_page(
			$this->name,
			$this->name,
			'manage_options',
			( $this->settings_page ),
			[
				$this,
				'settings_page',
			]
		);
	}

	public function network_settings_menu() {
		add_submenu_page(
			'settings.php',
			$this->name,
			$this->name,
			'manage_options',
			( $this->settings_page ),
			[
				$this,
				'settings_page',
			]
		);
	}

	public function settings_page() {
		?>
		<style>
			.gs-settings-page .tab-content {
				display: none;
			}

			.gs-settings-page .tab-content.active {
				display: block;
			}
		</style>
		<div class="wrap gs-settings-page">
			<h1>
			<?php
				/* translators: %s plugin name */
				echo esc_html( sprintf( __( '%s Options' ), $this->name ) );
			?>
				</h1>
			<h2 class="nav-tab-wrapper">
				<?php
				$nav_tabs    = $this->pages;
				$current     = isset( $_REQUEST['current'] ) ? sanitize_text_field( $_REQUEST['current'] ) : $nav_tabs[0][0];//phpcs:ignore
				$current_tab = '';
				foreach ( $nav_tabs as [ $key, $label, ] ) {
					if ( $current === $key ) {
						$current_tab = $key;
					}
					?>
					<a id="tab-<?php echo esc_attr( $key ); ?>" data-tab="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( admin_url( sprintf( 'options-general.php?page=%s&current=%s', $this->settings_page, $key ) ) ); ?>" class="nav-tab gs-nav-tab <?php echo esc_attr( $current === $key ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php } ?>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . $this->settings_page ) ); ?>">
				<?php settings_fields( $this->settings_page ); ?>
				<?php $this->do_settings_sections( $this->settings_page, $current_tab ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
		add_action(
			'admin_print_footer_scripts',
			function () {
				?>
				<script type="text/javascript">
					(function () {
						jQuery(document).on('click', '.gs-settings-page .gs-nav-tab', function () {
							if (jQuery('.nav-tab-active').hasClass('fs-tab')) {
								return true;
							}
							jQuery('.nav-tab').removeClass('nav-tab-active');
							jQuery('.tab-content').hide();
							jQuery(this).addClass('nav-tab-active');
							jQuery("#" + jQuery(this).data('tab')).show();
							let input_url;
							let input = jQuery('input[name="_wp_http_referer"]');
							if (input.data('url'))
								input_url = input.data('url');
							else
								input_url = input.data('url', input.val()).val();
							input.val(input_url + '&current=' + jQuery(this).data('tab'));
							return false;
						})
					})();
				</script>
				<?php
			}
		);
	}

	/**
	 * @param $links
	 *
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . ( $this->settings_page ) . '">' . __( 'Settings' ) . '</a>';
		$links[]       = $settings_link;

		return $links;
	}

	/**
	 * @param $links
	 *
	 * @return array
	 */
	public function add_network_settings_link( $links ) {
		$settings_link = '<a href="settings.php?page=' . ( $this->settings_page ) . '">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $plugin_file !== $this->basename ) {
			return $plugin_meta;
		}
		$plugin_meta[] = sprintf( '<a href="%s">Documentation</a>', $this->doc_url );

		return $plugin_meta;
	}

	private function do_settings_sections( $page, $current_tab ) {
		global $wp_settings_sections;
		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}
		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			if ( $this->is_subsection( $section['id'] ) ) {
				continue;
			}
			echo '<div id="', esc_attr($section['id']), '" class="tab-content ', esc_attr( ( $section['id'] === $current_tab ? 'active' : '' ) ), ' ">'; //phpcs:ignore

			if ( $section['title'] ) {
				echo '<h2>', esc_html( $section['title'] ), '</h2>';
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			echo '<table class="form-table" role="presentation">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';
			$subsections = $this->get_subsections( $section['id'] );
			foreach ( $subsections as $subsection ) {
				$this->do_settings_subsection( $page, $subsection );
			}
			echo '</div>';
		}
	}

	private function get_subsections( $section_id ) {
		foreach ( $this->pages as $page ) {
			if ( $section_id === $page[0] ) {
				return $page[2];
			}
		}

		return [];
	}

	private function is_subsection( $section_id ) {
		foreach ( $this->pages as $page ) {
			if ( in_array( $section_id, array_keys( $page[2] ), true ) ) {
				return true;
			}
		}

		return false;
	}

	private function do_settings_subsection( $page, $section ) {
		if ( $section['title'] ) {
			echo '<h3>', esc_html( $section['title'] ),"</h3>\n";
		}
		echo '<table class="form-table" role="presentation">';
		do_settings_fields( $page, $section['id'] );
		echo '</table>';
	}

	/**
	 * @return string
	 */
	public function get_settings_page(): string {
		return $this->settings_page;
	}
}
