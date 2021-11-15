<?php


namespace GooseStudio\Feedbacking;

use GooseStudio\Feedbacking\Dependencies\SettingsBase;
use GooseStudio\Feedbacking\Providers\Providers;

/**
 * Class Settings
 *
 * @package GooseStudio\Feedbacking
 */
class Settings extends SettingsBase {
	/**
	 * Settings constructor.
	 */
	public function __construct() {
		parent::__construct( GS_SF_PLUGIN_BASENAME, 'Feedbacking', GS_SF_VERSION, GS_STORE_URL, GS_DOCUMENTATION_URL );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() : void {
		register_setting( $this->get_settings_page(), 'api' );

		add_action( 'admin_init', [ $this, 'register_fields' ], 0 );
		parent::init();
	}

	public function register_fields(): void {
		add_settings_section( 'integrations', 'Integrations', '', $this->get_settings_page() );
		add_settings_field(
			'pm_tool',
			'Project manager tool',
			[
				$this,
				'providers',
			], 
			$this->get_settings_page(),
			'integrations',
			[ 'label_for' => 'pm_tools' ]
		);

		if ( 'none' !== $this->get_selected_provider() ) {
			add_settings_section( 'api', Providers::get_provider( $this->get_selected_provider() )->get_name() . ' API', '', $this->get_settings_page() );
			if ( 'application_password' === Providers::get_provider( $this->get_selected_provider() )->get_authentication_method()['method'] ) {
				if ( in_array( 'api_key', Providers::get_provider( $this->get_selected_provider() )->get_authentication_method()['fields'], true ) ) {

					add_settings_field(
						'api_key',
						'API Key',
						[
							$this,
							'api_key_field',
						],
						$this->get_settings_page(),
						'api',
						[
							'label_for' => 'api_key',
							'class'     => 'api__remote-api api__group',
						] 
					);
				}
				if ( in_array( 'api_secret', Providers::get_provider( $this->get_selected_provider() )->get_authentication_method()['fields'], true ) ) {

					add_settings_field(
						'api_secret',
						'API Secret',
						[
							$this,
							'api_secret_field',
						],
						$this->get_settings_page(),
						'api',
						[
							'label_for' => 'api_secret',
							'class'     => 'api__remote-api api__group',
						] 
					);
				}
			}
			$this->add_settings_page( 'api', Providers::get_provider( $this->get_selected_provider() )->get_name() . ' API', [] );
		}
		$this->add_settings_page( 'integrations', 'Integrations', [] );
	}
	public function providers() : void {
		$providers = Providers::all();
		?>
		<ul>
		<li>
		<label>
		<input id="setting-none" <?php checked( 'none' === $this->get_selected_provider() ); ?> type="radio" name="<?php echo esc_attr( $this->get_settings_page() ); ?>[provider][provider]" value="none">
		<?php echo esc_html( __( 'None' ) ); ?>
		</label>
		</li>

		<?php foreach ( $providers as $provider => $class ) : ?>
			<li>
			<label>
			<input id="setting-'<?php echo esc_attr( $provider ); ?>" <?php checked( $provider === $this->get_selected_provider() ); ?> type="radio" name="<?php echo esc_attr( $this->get_settings_page() ); ?>[provider][provider]" value="<?php echo esc_attr( $provider ); ?>">
			<?php echo esc_html( $this->get_provider_name( $class ) ); ?>
			</label>
			</li>
		<?php endforeach; ?>
		</ul>
		<p><?php esc_html_e( '"Project manager" is an external tool that the feedback plugin integrates with.', 'gs-feedbacking' ); ?></p>
		<?php
	}

	private function get_provider_name( string $class ) {
		return ( new $class() )->get_name();
	}

	private function get_selected_provider() : string {
		return $this->get_value( 'provider', 'provider', 'none' );
	}

	public function api_endpoint_field() : void {
		echo '<input type="text" id="api_endpoint" name="', esc_attr( $this->get_settings_page() ),'[api][' . esc_attr( $this->get_selected_provider() ) . '][endpoint]" value="', esc_attr( $this->get_api_value( 'endpoint' ) ),'"  class="regular-text">';
		echo '<p>Where to push the data to.';
	}

	public function api_key_field() : void {
		echo '<input type="text" id="api_key" name="', esc_attr( $this->get_settings_page() ),'[api][' . esc_attr( $this->get_selected_provider() ) . '][key]" value="', esc_attr( $this->get_api_value( 'key' ) ),'"  class="regular-text">';
		echo '<p>The API username to connect as.</p>';
	}

	public function api_secret_field() : void {
		echo '<input type="password" id="api_secret" name="', esc_attr( $this->get_settings_page() ),'[api][' . esc_attr( $this->get_selected_provider() ) . '][secret]" value="', esc_attr( $this->get_api_value( 'secret' ) ),'"  class="regular-text">';
		echo '<p>The API application password.</p>';
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function get_api_value( string $key ):string {
		$values = $this->get_value( 'api', $this->get_selected_provider(), [] );
		return $values[ $key ] ?? '';
	}
}
