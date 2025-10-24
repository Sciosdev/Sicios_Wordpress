<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if( !class_exists('ReyTheme_Base') ):

	class ReyTheme_Base
	{
		const DASHBOARD_PAGE_ID = REY_THEME_NAME . '-dashboard';
		const REGISTRATION_OPTION_ID = 'rey_purchase_code';
		const SUBSCRIBE_NEWSLETTER_OPTION_ID = 'rey_subscribed_to_newsletter';

		// Statuses
		const STATUS_VALID = 'valid';
		const STATUS_NOT_FOUND = 'not_found';
		const STATUS_ALREADY_EXISTS = 'already_exists';

		/**
		 * ReyTheme_Base constructor.
		 */
		public function __construct()
		{
			add_action( 'admin_notices', [$this, 'add_registration_notice'] );
			add_action( 'admin_init', [$this, 'register_manually']);
			add_action( 'wp_ajax_rey_dashbox_run_test', [ $this, 'ajax__dashbox_run_test' ] );
		}

		function register_manually(){

			if( defined('REY_API_KEY') && ! self::get_purchase_code() ){
				$this->__store_purchase_code( REY_API_KEY );
			}

			if( is_admin() ){
				if(
					isset($_REQUEST['deregister'])
					&& 1 === absint($_REQUEST['deregister'])
					&& isset($_REQUEST['page'])
					&& rey__clean($_REQUEST['page']) === 'rey-dashboard'
					&& current_user_can('administrator')
				){
					$this->remove_purchase_code();
					wp_safe_redirect( admin_url('admin.php?page=rey-dashboard') );
				}
			}

			global $pagenow;

			if( 'update-core.php' !== $pagenow && is_admin() && isset($_REQUEST['force-check']) && 1 === absint($_REQUEST['force-check']) && current_user_can('administrator') ){
				\Rey\Plugins::refresh();
			}
		}

		function theme_api(){
			return ReyTheme_API::getInstance();
		}

		/**
		 * Add registration notice in admin pages, if unregistered.
		 *
		 * @since 1.0.0
		 */
		function add_registration_notice()
		{
			if( self::get_purchase_code() ) {
				return;
			}
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php printf( wp_kses( __('Please <a href="%s">register your copy</a> of %s, to enable importing demos, updates, premium plugins and other features.', 'rey'), ['a' => ['href' => []]] ), esc_url( add_query_arg( ['page'=>self::DASHBOARD_PAGE_ID ], admin_url('admin.php'))), strtoupper(REY_THEME_NAME) ); ?></p>
			</div>
			<?php
		}

		/**
		 * Register purchase code method
		 *
		 * @since 1.0.0
		 */
		protected function register(){

			// check if empty
			if ( empty( $_POST['rey_purchase_code'] ) ) {
				wp_send_json_error( esc_html__( 'Please enter your purchase code.', 'rey' ) );
			}

			$purchase_code = trim( $_POST['rey_purchase_code'] );

			// check if valid UUID
			if ( ! preg_match("/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/", $purchase_code ) ) {
				wp_send_json_error( esc_html__( 'Please enter the correct purchase code format, eg: 00000000-0000-0000-0000-000000000000.', 'rey' ) );
			}

			$args = [
				'purchase_code' => $purchase_code,
				'email_address' => sanitize_email( $_POST['rey_email_address'] ),
			];

			// check if newsletter is checked
			if( !empty($args['email_address']) && isset($_POST['rey_subscribe_newsletter']) && absint($_POST['rey_subscribe_newsletter']) === 1 ){
				$this->set_subscribed_newsletter();
			}

			// send registration request
			$request = $this->theme_api()->register( $args );

			// check for errors
			if ( is_wp_error( $request ) ) {
				wp_send_json_error( sprintf( '%s (%s) ', $request->get_error_message(), $request->get_error_code() ) );
			}

			// check if status is invalid
			if ( isset($request['success']) && !$request['success'] ) {
				$error_msg = $this->theme_api()->get_error_message( esc_html( $request['data'] ) );
				wp_send_json_error( $error_msg );
			}

			if( isset($request['data']['status']) ) {
				if( $request['data']['status'] === self::STATUS_VALID){
					$this->__store_purchase_code( $purchase_code );
					wp_send_json_success();
				}
				elseif( $request['data']['status'] === self::STATUS_ALREADY_EXISTS){
					$error_msg = $this->theme_api()->get_error_message( esc_html( $request['data']['status'] ) );
					wp_send_json_error( $error_msg );
				}
				else {
					wp_send_json_error( esc_html__('Purchase code seems to be invalid.', 'rey') );
				}
			}

			return false;
		}


		/**
		 * Install Rey Child theme
		 *
		 * @since 1.0.0
		 */
		public static function install_child_theme() {

			$url    = REY_THEME_DIR . '/inc/files/rey-child.zip';

			if ( ! current_user_can( 'install_themes' ) ) {
				rey__log_error( 'err024', __('Forbidden to install child themes.', 'rey') );
				return false;
			}

			if ( ! class_exists( 'Theme_Upgrader', false ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			$skin = new Automatic_Upgrader_Skin();

			$upgrader = new Theme_Upgrader( $skin, array( 'clear_destination' => true ) );
			$result   = $upgrader->install( $url );

			// There is a bug in WP where the install method can return null in case the folder already exists
			// see https://core.trac.wordpress.org/ticket/27365
			if ( $result === null && ! empty( $skin->result ) ) {
				$result = $skin->result;
			}

			if ( is_wp_error( $skin->result ) ) {
				rey__log_error( 'err023', $result->get_error_message() );
				return false;
			}

			return true;
		}

		/**
		 * Method to enable child theme
		 * @since 1.0.0
		 */
		public static function enable_child_theme() {

			$child_theme = self::get_child_theme();

			if ( $child_theme !== false ) {
				switch_theme( $child_theme->get_stylesheet() );
			}

			wp_send_json_success();
		}

		/**
		 * Check for child theme
		 * @since 1.0.0
		 */
		public static function get_child_theme()
		{
			$child_theme = false;
			$current_installed_themes = wp_get_themes();
			$active_theme      = wp_get_theme();
			$theme_folder_name = $active_theme->get_template();

			if ( is_array( $current_installed_themes ) ) {
				foreach ( $current_installed_themes as $key => $theme_obj ) {
					if ( $theme_obj->get( 'Template' ) === $theme_folder_name ) {
						$child_theme = $theme_obj;
					}
				}
			}

			return $child_theme;
		}

		/**
		 * Method used to deregister current purchase code
		 *
		 * @since 1.0.0
		 */
		protected function deregister(){

			$purchase_code = self::get_purchase_code();

			if( ! $purchase_code ){
				return false;
			}

			if( ! preg_match("/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/", $purchase_code ) ){
				$this->remove_purchase_code();
				return;
			}

			$request = $this->theme_api()->deregister([
				'purchase_code' => $purchase_code
			]);

			return !is_wp_error( $request ) && $this->remove_purchase_code();
		}

		/**
		 * Store purchase code and load plugins from API
		 *
		 * @since 1.0.0
		 */
		private function __store_purchase_code( $code )
		{
			// store the purchase code
			// check if registered with `get_purchase_code()`
			$this->set_purchase_code( $code );

			// set mandatory plugins
			\Rey\Plugins::refresh();
		}

		/**
		 * Store purchase code
		 *
		 * @since 1.0.0
		 */
		private function set_purchase_code($purchase_code){
			return update_site_option( self::REGISTRATION_OPTION_ID, $purchase_code );
		}

		/**
		 * Get purchase code
		 *
		 * @since 1.0.0
		 */
		public static function get_purchase_code() {

			$opt = trim( get_site_option( self::REGISTRATION_OPTION_ID ) );

			if( self::REGISTRATION_OPTION_ID === $opt || ! preg_match("/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/", $opt ) ){
				delete_site_option( self::REGISTRATION_OPTION_ID );
				return $opt;
			}

			return $opt;
		}

		/**
		 * Remove purchase code option
		 *
		 * @since 1.0.0
		 */
		private function remove_purchase_code() {
			return delete_site_option( self::REGISTRATION_OPTION_ID );
		}

		/**
		 * Set subscribed option
		 *
		 * @since 1.0.0
		 */
		protected function set_subscribed_newsletter(){
			return update_site_option( self::SUBSCRIBE_NEWSLETTER_OPTION_ID, 'yes' );
		}

		/**
		 * Check if subscribed to newsletter
		 *
		 * @since 1.0.0
		 */
		public function is_subscribed_to_newsletter() {
			return trim( get_site_option( self::SUBSCRIBE_NEWSLETTER_OPTION_ID ) ) === 'yes';
		}

		/**
		 * Get the purchase code, with only a few characters shown.
		 *
		 * @since 1.0.0
		 */
		public static function get_hidden_purchase_code() {
			$input_string = self::get_purchase_code();

			$start = 5;
			$length = mb_strlen( $input_string ) - $start - 5;

			$mask_string = preg_replace( '/\S/', 'x', $input_string );
			$mask_string = mb_substr( $mask_string, $start, $length );
			$input_string = substr_replace( $input_string, $mask_string, $start, $length );

			return $input_string;
		}

		/**
		 * Checks for connection.
		 *
		 * @return array|bool
		 */
		public function ajax__dashbox_run_test(){

			if( ! (current_user_can('administrator') || current_user_can('install_plugins')) ){
				wp_send_json_error('Operation not allowed!');
			}

			if( ! rey__valid_url( \ReyTheme_API::getInstance()->get_test_url() ) ){
				wp_send_json_error('Unsupported.');
			}

			wp_send_json_success();
		}

		public static function get_ips_raw(){
			return [
				'public' => (($public_ip = wp_safe_remote_get('http://ipecho.net/plain')) && ! is_wp_error($public_ip)) ? wp_remote_retrieve_body($public_ip) : '',
				'local' => gethostbyname(gethostname())
			];
		}

		public static function get_ips_data(){

			/**
			 * Use ?rey_show_server_ip in Dashboard to show the Public IP address.
			 */
			$raw_data = self::get_ips_raw();

			$data = '<div class="rey-connectionIps" data-copy-contents data-corner-label="Click to copy">';

			// Usually the one that gets blocked in firewalls
			$data .= sprintf('Public IP: <strong>%s</strong> <br>', $raw_data['public']);
			$data .= sprintf('Local IP: <strong>%s</strong> <br>', $raw_data['local']);

			$data .= '</div>';

			return $data;
		}

		public static function get_failed_connection_message(){

			$message = sprintf('<p class="__text"><small>Requests toward Rey\'s API don\'t seem to work properly so you won\'t be able to register and import demos, import templates or install Rey\'s updates. Please visit <a href="%s" target="_blank">this article</a> to follow our recommendations to fix this problem.</small></p>', rey__support_url('/kb/api-connection-fail'));

			$message .= self::get_ips_data();

			return $message;
		}

	}
	new ReyTheme_Base;

endif;
