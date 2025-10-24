<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://edwiser.org
 * @since      1.3.4
 * @package    Edwiser Bridge
 */

namespace app\wisdmlabs\edwiserBridge;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
/**
 * Eb admin handler.
 */
class Eb_Admin_Notice_Handler
{

	/**
	 * Check if installed.
	 */
	public function check_if_moodle_plugin_installed()
	{
		$plugin_installed   = 1;
		$connection_options = get_option('eb_connection');
		$eb_moodle_url      = '';
		if (isset($connection_options['eb_url'])) {
			$eb_moodle_url = $connection_options['eb_url'];
		}
		$eb_moodle_token = '';
		if (isset($connection_options['eb_access_token'])) {
			$eb_moodle_token = $connection_options['eb_access_token'];
		}
		$request_url = $eb_moodle_url . '/webservice/rest/server.php?wstoken=';

		$moodle_function = 'auth_edwiserbridge_get_course_progress';
		$request_url    .= $eb_moodle_token . '&wsfunction=' . $moodle_function . '&moodlewsrestformat=json';
		$response        = wp_remote_post($request_url);

		if (is_wp_error($response)) {
			$plugin_installed = 0;
		} elseif (
			200 === wp_remote_retrieve_response_code($response) ||
			300 === wp_remote_retrieve_response_code($response)
		) {
			$body = json_decode(wp_remote_retrieve_body($response));

			if ('accessexception' === $body->errorcode) {
				$plugin_installed = 0;
			}
		} else {
			$plugin_installed = 0;
		}

		return $plugin_installed;
	}

	/**
	 * Get Moodle plugin Info.
	 * Currently only version is provided.
	 */
	public function eb_get_mdl_plugin_info()
	{

		$moodle_function = 'auth_edwiserbridge_get_edwiser_plugins_info';

		$response = edwiser_bridge_instance()->connection_helper()->connect_moodle_helper($moodle_function);

		if (isset($response['success']) && $response['success']) {
			$plugin_info = $response['response_data'];
			return $plugin_info;
		} else {
			return false;
		}
	}



	/**
	 * Show admin feedback notice.
	 *
	 * @since 1.3.1
	 */
	public function eb_admin_update_moodle_plugin_notice()
	{

		// show notice.
		$redirection = add_query_arg('eb-update-notice-dismissed', true);
		$plugin_data = get_option('eb_mdl_plugin_update_check');
		if (! get_option('eb_mdl_plugin_update_notice_dismissed') && is_array($plugin_data) && ! empty($plugin_data)) {
?>
			<div class="notice  eb_admin_update_notice_message_cont">
				<div class="eb_admin_update_notice_message">
					<div class="eb_admin_update_notice_message_logo">
						<img src="<?php echo esc_url(plugins_url('images/logo.png', dirname(__FILE__))); ?>" alt="Edwiser Bridge Logo" />
					</div>
					<div class="eb_update_notice_content">
						<p>
							<?php esc_html_e('Thanks for using Edwiser Bridge plugin. To avoid any malfunctioning please', 'edwiser-bridge'); ?> <strong><?php esc_html_e('make sure you have also installed and activated our latest associated Moodle Plugin', 'edwiser-bridge'); ?></strong>
							<?php esc_html_e('on your Moodle site.', 'edwiser-bridge'); ?>
						</p>
						<p>
							<?php esc_html_e('New version of the following plugins are available for the Moodle site.', 'edwiser-bridge'); ?>
						</p>
						<ul>
							<?php
							foreach ($plugin_data as $plugin) {
							?>
								<li>
									<?php
									echo '<strong>' . esc_html($plugin['name']) . '</strong> ' . esc_html($plugin['new_version']) . ' ' . esc_html__('is available.', 'edwiser-bridge');
									?>
									<a href="<?php echo esc_url($plugin['url']); ?>"><?php esc_html_e(' Click here ', 'edwiser-bridge'); ?></a>
									<?php esc_html_e(' to download plugin.', 'edwiser-bridge'); ?>
								</li>
							<?php
							}
							?>
						</ul>
					</div>
				</div>
				<div class="eb_admin_update_dismiss_notice_message">
					<a href="<?php echo esc_url($redirection); ?>">
						<span class="dashicons dashicons-no-alt eb_update_notice_hide"></span>
					</a>
				</div>
			</div>
		<?php
		}
	}

	/**
	 * Check if Moodle plugin update is available.
	 *
	 * @since 2.2.6
	 */
	public function eb_check_mdl_plugin_update()
	{
		$plugin_info = $this->eb_get_mdl_plugin_info();
		$url         = 'https://edwiser.org/edwiserdemoimporter/bridge-free-plugin-info.json';

		// set user agent.
		$args        = array(
			'timeout' => 15,
			'headers' => array(
				'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
			),
		);
		$response    = wp_remote_get($url, $args);
		$update_data = array();

		if (200 === wp_remote_retrieve_response_code($response) && $plugin_info) {
			$responce = json_decode(wp_remote_retrieve_body($response));
			if (empty($responce)) {
				return;
			}
			if (isset($plugin_info->plugin_name) && 'edwiser_bridge' === $plugin_info->plugin_name) {
				$update_data[] = array(
					'slug'        => 'moodle_edwiser_bridge',
					'name'        => $responce->moodle_edwiser_bridge->name,
					'new_version' => $responce->moodle_edwiser_bridge->version,
					'old_version' => $plugin_info->version,
					'url'         => $responce->moodle_edwiser_bridge->url,
				);
			} else {
				$plugin_info = $plugin_info->plugins;
				foreach ($plugin_info as $plugin) {
					$plugin_name = $plugin->plugin_name;
					if ('moodle_edwiser_bridge_pro' === $plugin_name) {
						update_option('moodle_edwiser_bridge_pro', $plugin->version);
						continue;
					}
					if (version_compare($plugin->version, $responce->{$plugin_name}->version, '<')) {
						$update_data[] = array(
							'slug'        => $plugin_name,
							'name'        => $responce->{$plugin_name}->name,
							'new_version' => $responce->{$plugin_name}->version,
							'old_version' => $plugin->version,
							'url'         => $responce->{$plugin_name}->url,
						);
					}
				}
			}
		}
		update_option('eb_mdl_plugin_update_check', $update_data);
		// delete option dismiss notice.
		delete_option('eb_mdl_plugin_update_notice_dismissed');
	}



	/**
	 * NOT USED FUNCTION
	 * handle notice dismiss
	 *
	 * @deprecated since 2.0.1 discontinued.
	 * @since 1.3.1
	 */
	public function eb_admin_discount_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-discount-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			$user_id = get_current_user_id();
			add_user_meta($user_id, 'eb_discount_notice_dismissed', 'true', true);
		}
	}


	/**
	 * NOT USED FUNCTION
	 * show admin feedback notice
	 *
	 * @since 1.3.1
	 */
	public function eb_admin_discount_notice()
	{
		$redirection = add_query_arg('eb-discount-notice-dismissed', true);

		$user_id = get_current_user_id();
		if (! get_user_meta($user_id, 'eb_discount_notice_dismissed')) {
			echo '  <div class="notice  eb_admin_discount_notice_message">
						<div class="eb_admin_discount_notice_message_cont">
							<div class="eb_admin_discount_notice_content">
								' . esc_html__('Get all Premium Edwiser Products at Flat 20% Off!', 'edwiser-bridge') . '

								<div style="font-size:13px; padding-top:4px;">
									<a href="' . esc_html($redirection) . '">
										' . esc_html__(' Dismiss this notice', 'edwiser-bridge') . '
									</a>
								</div>
							</div>
							<div>
								<a class="eb_admin_discount_offer_btn" href="https://edwiser.org/edwiser-lifetime-kit/?utm_source=WordPress&utm_medium=notif&utm_campaign=inbridge"  target="_blank">' . esc_html__('Avail Offer Now!', 'edwiser-bridge') . '</a>
							</div>
						</div>
						<div class="eb_admin_discount_dismiss_notice_message">
							<span class="dashicons dashicons-dismiss eb_admin_discount_notice_hide"></span>
						</div>
					</div>';
		}
	}





	/**
	 * Handle notice dismiss
	 *
	 * @since 1.3.1
	 */
	public function eb_admin_update_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-update-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			update_option('eb_mdl_plugin_update_notice_dismissed', 'true', true);
		}
	}




	/**
	 * NOT USED FUNCTION
	 * show admin feedback notice
	 *
	 * @since 1.3.1
	 */
	public function eb_admin_feedback_notice()
	{
		$redirection       = add_query_arg('eb-feedback-notice-dismissed', true);
		$user_id           = get_current_user_id();
		$feedback_usermeta = get_user_meta($user_id, 'eb_feedback_notice_dismissed', true);
		if ('eb_admin_feedback_notice' !== get_transient('edwiser_bridge_admin_feedback_notice') && (! $feedback_usermeta || 'remind_me_later' !== $feedback_usermeta) && 'dismiss_permanantly' !== $feedback_usermeta) {
			echo '  <div class="notice eb_admin_feedback_notice_message_cont">
							<div class="eb_admin_feedback_notice_message">'
				. esc_html__('Enjoying Edwiser bridge, Please  ', 'edwiser-bridge') . '
								<a href="https://WordPress.org/plugins/edwiser-bridge/">'
				. esc_html__(' click here ', 'edwiser-bridge') .
				'</a>'
				. esc_html__(' to rate us.', 'edwiser-bridge') . '
								<div style="padding-top:8px; font-size:13px;">
									<span class="eb_feedback_rate_links">
										<a href="' . esc_html($redirection) . '=remind_me_later">
										' . esc_html__('Remind me Later!', 'edwiser-bridge') . '
										</a>
									</span>
									<span class="eb_feedback_rate_links">
										<a href="' . esc_html($redirection) . '=dismiss_permanantly">
										' . esc_html__('Dismiss Notice', 'edwiser-bridge') . '
										</a>
									</span>
								</div>
							</div>
							<div class="eb_admin_feedback_dismiss_notice_message">
								<span class="dashicons dashicons-dismiss"></span>
							</div>
						</div>';
		}
	}


	/**
	 * Handle notice dismiss
	 *
	 * @since 1.3.1
	 */
	public function eb_admin_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-feedback-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			$user_id = get_current_user_id();
			add_user_meta($user_id, 'eb_feedback_notice_dismissed', filter_input(INPUT_GET, 'eb-feedback-notice-dismissed', FILTER_VALIDATE_BOOLEAN), true);
		}
	}






	/**
	 * SHow notfi.
	 *
	 * @param text $curr_plugin_meta_data curr_plugin_meta_data.
	 * @param text $new_plugin_meta_data new_plugin_meta_data.
	 */
	public function eb_show_inline_plugin_update_notification($curr_plugin_meta_data, $new_plugin_meta_data)
	{
		ob_start();
		?>
		<p>
			<strong><?php echo esc_html__('Important Update Notice:', 'edwiser-bridge'); ?></strong>
			<?php echo esc_html__('Please download and update associated edwiserbridge Moodle plugin.', 'edwiser-bridge'); ?>
			<a href="https://edwiser.org/bridge-wordpress-moodle-integration/"><?php echo esc_html__('Click here ', 'edwiser-bridge'); ?></a>
			<?php echo esc_html__(' to download', 'edwiser-bridge'); ?>

		</p>

		<?php
		echo wp_kses(ob_get_clean(), \app\wisdmlabs\edwiserBridge\wdm_eb_get_allowed_html_tags());

		// added this just for commit purpose.
		unset($curr_plugin_meta_data);
		unset($new_plugin_meta_data);
	}

	/**
	 * Bfcm notice dismiss handler.
	 */
	public function eb_admin_bfcm_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-admin-bfcm-pre-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			$user_id = get_current_user_id();
			add_user_meta($user_id, 'eb_admin_bfcm_pre_notice_dismissed', filter_input(INPUT_GET, 'eb-admin-bfcm-pre-notice-dismissed', FILTER_VALIDATE_BOOLEAN), true);
		}
		if (true === filter_input(INPUT_GET, 'eb-admin-bfcm-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			$user_id = get_current_user_id();
			add_user_meta($user_id, 'eb_admin_bfcm_notice_dismissed', filter_input(INPUT_GET, 'eb-admin-bfcm-notice-dismissed', FILTER_VALIDATE_BOOLEAN), true);
		}
	}
	/**
	 * BFCM admin notice
	 *
	 * @since 2.2.4
	 */
	public function eb_admin_bfcm_notice()
	{

		$user_id = get_current_user_id();
		global $pagenow;
		$screen = get_current_screen();
		if (is_admin() && ('index.php' === $pagenow || 'eb_course_page_eb-settings' === $screen->id)) {

			$eb_plugin_url = \app\wisdmlabs\edwiserBridge\wdm_edwiser_bridge_plugin_url();

			// chek if current date is between 4th and 23rd of november.
			$bfcm_pre_start_date = strtotime('2022-11-04');
			$bfcm_pre_end_date   = strtotime('2022-11-23');
			$bfcm_start_date     = strtotime('2022-11-24');
			$bfcm_end_date       = strtotime('2022-11-30');
			$current_date        = strtotime(wp_date('Y-m-d'));

			// pre bfcm banner.
			if ($current_date >= $bfcm_pre_start_date && $current_date <= $bfcm_pre_end_date && ! get_user_meta($user_id, 'eb_admin_bfcm_pre_notice_dismissed')) {
				$redirection = add_query_arg('eb-admin-bfcm-pre-notice-dismissed', true);
		?>
				<div class="notice eb-admin-bfcm-notice-message">
					<div class="eb-admin-bfcm-notice-message-content" style="background-image: url('<?php echo esc_url($eb_plugin_url); ?>images/bfcm-pre.png');">
						<p class="title"><?php esc_html_e('Play a Game with Edwiser to get ahead of the Black Friday Sale!', 'edwiser-bridge'); ?></p>
						<p class="desc"><?php esc_html_e('Spin the Wheel to Win Free Access or Discounts on our Premium Moodle theme & plugins', 'edwiser-bridge'); ?></p>
						<a class="button" href="https://edwiser.org/edwiser-black-friday-giveaway/?utm_source=giveaway&utm_medium=spinthewheel&utm_campaign=bfcm22" target="_blank"><?php esc_html_e('Spin and Win', 'edwiser-bridge'); ?></a>
					</div>
					<div class="eb-admin-bfcm-notice-message-dismiss">
						<a href="<?php echo esc_html($redirection); ?>">
							<span class="dashicons dashicons-no-alt eb_admin_bfcm_notice_hide"></span>
						</a>
					</div>
				</div>
			<?php
			} elseif ($current_date >= $bfcm_start_date && $current_date <= $bfcm_end_date && ! get_user_meta($user_id, 'eb_admin_bfcm_notice_dismissed')) {
				// bfcm banner.
				$extensions = array(
					'woocommerce-integration/bridge-woocommerce.php',
					'selective-synchronization/selective-synchronization.php',
					'edwiser-bridge-sso/sso.php',
					'edwiser-multiple-users-course-purchase/edwiser-multiple-users-course-purchase.php',
				);
				foreach ($extensions as $plugin_path) {
					if (is_plugin_active($plugin_path)) {
						$free = false;
					} else {
						$free = true;
						break;
					}
				}
				$redirection = add_query_arg('eb-admin-bfcm-notice-dismissed', true);
			?>
				<div class="notice eb-admin-bfcm-notice-message">
					<div class="eb-admin-bfcm-notice-message-content" style="background-image: url('<?php echo esc_url($eb_plugin_url); ?>images/bfcm.png');">
						<p class="title"><?php esc_html_e('Get the power of selling courses via WooCommerce!', 'edwiser-bridge'); ?></p>
						<?php
						if ($free) {
						?>
							<p class="desc"><?php esc_html_e('Get amazing Black Friday discounts on Edwiser Bridge Pro', 'edwiser-bridge'); ?></p>
							<a class="button" href="https://edwiser.org/bridge-wordpress-moodle-integration/?utm_source=freeplugin&utm_medium=banner&utm_campaign=bfcm22" target="_blank"><?php esc_html_e('Upgrade Now', 'edwiser-bridge'); ?></a>
						<?php
						} else {
						?>
							<p class="desc"><?php esc_html_e('Get amazing Black Friday discounts on Edwiser Bridge Pro Lifetime license', 'edwiser-bridge'); ?></p>
							<a class="button" href="https://edwiser.org/bridge-wordpress-moodle-integration/?utm_source=proplugin&utm_medium=banner&utm_campaign=bfcm22" target="_blank"><?php esc_html_e('Upgrade tO Lifetime', 'edwiser-bridge'); ?></a>
						<?php
						}
						?>
					</div>
					<div class="eb-admin-bfcm-notice-message-dismiss">
						<a href="<?php echo esc_html($redirection); ?>">
							<span class="dashicons dashicons-no-alt eb_admin_bfcm_notice_hide"></span>
						</a>
					</div>
				</div>
			<?php
			}
		}
	}

	/**
	 * Dismiss the notice.
	 */
	public function eb_admin_remui_demo_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-admin-remui-notice-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			$user_id = get_current_user_id();
			add_user_meta($user_id, 'eb_admin_remui_demo_notice_dismissed', filter_input(INPUT_GET, 'eb-admin-remui-notice-notice-dismissed', FILTER_VALIDATE_BOOLEAN), true);
		}
	}

	/**
	 * Remui demo notice.
	 */
	public function eb_admin_remui_demo_notice()
	{
		$redirection = add_query_arg('eb-admin-remui-notice-notice-dismissed', true);
		$user_id     = get_current_user_id();
		if (! get_user_meta($user_id, 'eb_admin_remui_demo_notice_dismissed')) {
			?>
			<div class="notice  eb_admin_remui_demo_notice">
				<a style="text-decoration:none;" target="_blank" href="https://demo.tryremui.edwiser.org/?utm_source=remui4.0-launch-bridge-thankyoupage&utm_medium=remui4.0-launch-bridge-thankyoupage-bannerctaclicks&utm_campaign=remui-4.0-launch">
					<div class="eb_admin_remui_demo_notice_message">
						<div class="eb_admin_remui_demo_notice_message_logo">
							<img src="<?php echo esc_url(plugins_url('images/logo.png', dirname(__FILE__))); ?>" alt="Edwiser Bridge Logo" />
						</div>
						<div class="eb_remui_demo_notice_content" style="background-image:url('<?php echo esc_url(plugins_url('images/remui_back.png', dirname(__FILE__))); ?>'">
							<p class="remui-title">
								<?php esc_html_e('We’ve something new for you to help you design an amazingly personalized Moodle - faster & easier.', 'edwiser-bridge'); ?> <b><?php esc_html_e(' Our fully redesigned Edwiser RemUI theme is LAUNCHED!', 'edwiser-bridge'); ?></b>
							</p>
							<p class="remui-content">
								<?php esc_html_e('Check out the demo to see the modern layouts, ready-to-use homepages, brilliantly upgraded dashboard & more', 'edwiser-bridge'); ?>
							</p>
							<button class="remui-button" href="#">Explore the demo</button>
						</div>
					</div>
				</a>
				<img class="eb_admin_remui_demo_image" src="<?php echo esc_url(plugins_url('images/remui-demo.png', dirname(__FILE__))); ?>" alt="Edwiser Remu UI demo">
				<div class="eb_admin_remui_demo_dismiss_notice_message">
					<a href="<?php echo esc_url($redirection); ?>">
						<span class="dashicons dashicons-no-alt eb_remui_demo_notice_hide"></span>
					</a>
				</div>
			</div>
		<?php
		}
	}

	/**
	 * Dismiss the notice.
	 */
	public function eb_admin_upgrade_to_pro_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-admin-upgrade-to-pro-notice-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			$user_id = get_current_user_id();
			add_user_meta($user_id, 'eb_admin_upgrade_to_pro_notice_dismissed', filter_input(INPUT_GET, 'eb-admin-upgrade-to-pro-notice-notice-dismissed', FILTER_VALIDATE_BOOLEAN), true);
		}
	}

	/**
	 * Remui demo notice.
	 */
	public function eb_admin_upgrade_to_pro_notice()
	{
		$redirection = add_query_arg('eb-admin-upgrade-to-pro-notice-notice-dismissed', true);
		$user_id     = get_current_user_id();
		if (! get_user_meta($user_id, 'eb_admin_upgrade_to_pro_notice_dismissed')) {
		?>
			<div class="notice  eb_admin_remui_demo_notice">
				<a style="text-decoration:none;" target="_blank" href="https://edwiser.org/edwiser-bridge-pro/?utm_source=inproduct&utm_medium=pro_banner&utm_campaign=wordpress_bridge_listing">
					<div class="eb_admin_remui_demo_notice_message">
						<div class="eb_remui_demo_notice_content">
							<h1 class="upgrade-pro-title" style="font-size: 28px; font-weight: 800;">
								<span style="color: #f00;"><?php _e('Want more?', 'edwiser-bridge'); ?></span> <?php _e('Unlock the full potential of your Moodle-WordPress integration!', 'edwiser-bridge'); ?>
							</h1>
							<div class="upgrade-pro-content">
								<p style="font-size: 18px; font-weight: 400; color: #133F3F;">
									✅ <strong><?php _e('Sync only what you need with Selective Synchronization', 'edwiser-bridge'); ?></strong>
								</p>
								<p style="font-size: 18px; font-weight: 400; color: #133F3F;">
									✅ <strong><?php _e('Sell subscription courses with WooCommerce integration', 'edwiser-bridge'); ?></strong>
								</p>
								<p style="font-size: 18px; font-weight: 400; color: #133F3F;">
									✅ <strong><?php _e('Log in once to access both WordPress and Moodle with Single Sign-On', 'edwiser-bridge'); ?></strong>
								</p>
								<p style="font-size: 18px; font-weight: 400; color: #133F3F;">
									✅ <strong><?php _e('Power up with Bulk Enrollments & Course Bundles', 'edwiser-bridge'); ?></strong>
								</p>
								<p style="font-size: 18px; font-weight: 400; color: #133F3F;">
									<strong style="font-style: italic; color: #008B91;"><?php _e('& much more!', 'edwiser-bridge'); ?></strong>
								</p>
							</div>
							<a class="remui-button" href="https://edwiser.org/edwiser-bridge-pro/?utm_source=inproduct&utm_medium=pro_banner&utm_campaign=wordpress_bridge_listing" target="_blank" style="background-color: #F75D25; color: #fff; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500; text-decoration: none; display: inline-block;">
								<?php _e('Explore Edwiser Bridge PRO', 'edwiser-bridge'); ?>
							</a>
						</div>

					</div>
				</a>
				<div class="eb_admin_remui_demo_dismiss_notice_message">
					<a href="<?php echo esc_url($redirection); ?>" style="color: #133F3F;">
						<span class="dashicons dashicons-no-alt eb_remui_demo_notice_hide"></span>
					</a>
				</div>
			</div>
		<?php
		}
	}

	/**
	 * Dismiss the notice.
	 */
	public function eb_admin_pro_notice_dismiss_handler()
	{
		if (true === filter_input(INPUT_GET, 'eb-admin-pro-notice-notice-dismissed', FILTER_VALIDATE_BOOLEAN)) {
			update_option('eb_pro_consolidated_plugin_notice_dismissed', filter_input(INPUT_GET, 'eb-admin-pro-notice-notice-dismissed', FILTER_VALIDATE_BOOLEAN));
		}
	}

	/**
	 * Notice/popup for edwiser bridge pro after activation.
	 */
	public function eb_admin_pro_notice()
	{

		global $eb_plugin_data;
		$transient = get_transient('_eb_pro_consolidated_plugin_notice');
		$dismissed = get_option('eb_pro_consolidated_plugin_notice_dismissed');
		// if transient is set then show popup.
		if ($transient) {
			delete_transient('_eb_pro_consolidated_plugin_notice');
		?>
			<div class="eb-admin-pro-popup">
				<div class='eb-admin-pro-popup-content'>
					<div class="eb-admin-pro-popup-success-msg"> <span class="dashicons dashicons-yes-alt"></span> <?php echo sprintf(esc_html__('Edwiser Bridge version %s activated successfully', 'edwiser-bridge'), $eb_plugin_data['version']); // @codingStandardsIgnoreLine 
																													?></div>
					<p class="eb-admin-pro-popup-title"><?php echo esc_html__('Introducing', 'edwiser-bridge') . '<strong>' . esc_html__(' consolidated Edwiser Bridge Pro Plugin', 'edwiser-bridge') . '</strong>' . esc_html__(' for smoother configuration experience', 'edwiser-bridge'); ?></p>
					<p class="eb-admin-pro-popup-text"><?php echo esc_html__('Starting from Edwiser Bridge version 3.0.0,  all the Edwiser Bridge Pro add-on plugins  have been combined into a', 'edwiser-bridge') . '<strong>' . esc_html__(' single plugin -Edwiser Bridge Pro', 'edwiser-bridge') . '</strong>' . esc_html__(' to provide a smoother and better experience for installing, configuring and updating Edwiser Bridge Pro.', 'edwiser-bridge'); ?></p>
					<p class="eb-admin-pro-popup-text"><?php echo esc_html__('To install and activate the', 'edwiser-bridge') . '<strong>' . esc_html__(' new Edwiser Bridge Pro plugin', 'edwiser-bridge') . '</strong>' . esc_html__(' click below', 'edwiser-bridge'); ?></p>
					<a class="eb-admin-pro-popup-button" href="<?php echo esc_url(admin_url('admin.php?page=eb-settings&tab=licensing')); ?>"><?php esc_html_e('Activate Now', 'edwiser-bridge'); ?> </a>
				</div>
			</div>
		<?php
		} elseif ((! $dismissed && ! $transient)) {
			$redirection = add_query_arg('eb-admin-pro-notice-notice-dismissed', true);
		?>
			<div class="notice  eb_admin_update_notice_message_cont">
				<div class="eb_admin_update_notice_message">
					<div class="eb_admin_update_notice_message_logo">
						<img src="<?php echo esc_url(plugins_url('images/logo.png', dirname(__FILE__))); ?>" alt="Edwiser Bridge Logo" />
					</div>
					<div class="eb_update_notice_content">
						<p>
							<?php esc_html_e('Introducing', 'edwiser-bridge'); ?> <strong><?php esc_html_e('consolidated Edwiser Bridge Pro Plugin', 'edwiser-bridge'); ?></strong> <?php esc_html_e('for smoother configuration experience', 'edwiser-bridge'); ?>
						</p>
						<p>
							<?php esc_html_e('Starting from Edwiser Bridge version 3.0.0,  all the Edwiser Bridge Pro add-on plugins  have been combined into a single plugin -Edwiser Bridge Pro to provide a smoother and better experience for installing, configuring and updating Edwiser Bridge Pro.', 'edwiser-bridge'); ?>
						</p>
						<p>
							<?php esc_html_e('To install and activate the ', 'edwiser-bridge'); ?> <strong><?php esc_html_e('new Edwiser Bridge Pro plugin', 'edwiser-bridge'); ?> </strong><?php esc_html_e(' click below', 'edwiser-bridge'); ?>
						</p>
						<p class="eb-admin-pro-popup-button-wrap">
							<a class="eb-admin-pro-popup-button" href="<?php echo esc_url(admin_url('admin.php?page=eb-settings&tab=licensing')); ?>"><?php esc_html_e('Activate Now', 'edwiser-bridge'); ?> </a>
							<a class="eb-admin-pro-popup-dismiss-button" href="<?php echo esc_url($redirection); ?>"><?php esc_html_e('Dismiss Forever', 'edwiser-bridge'); ?> </a>
						</p>
					</div>
				</div>
				<div class="eb_admin_update_dismiss_notice_message">
					<!-- <a href="<?php echo esc_url($redirection); ?>"> -->
					<span class="dashicons dashicons-no-alt eb_update_notice_hide"></span>
					<!-- </a> -->
				</div>
			</div>
		<?php
		}
	}

	/**
	 * Check for plugin update and show templates modal
	 * 
	 * @since 4.1.0
	 */
	public function check_for_template_modal()
	{
		// Free plugin version
		$free_version = '4.1.0';
		$free_shown_version = get_option('eb_free_template_modal_shown', '0.0.0');

		if ($free_version !== $free_shown_version) {
			update_option('eb_free_template_modal_shown', $free_version);
			update_option('eb_show_free_template_modal', 'yes');
		}

		// Pro plugin version
		if (is_plugin_active('edwiser-bridge-pro/edwiser-bridge-pro.php')) {
			$pro_version = '4.1.0';
			$pro_shown_version = get_option('eb_pro_template_modal_shown', '0.0.0');

			if ($pro_version !== $pro_shown_version) {
				update_option('eb_pro_template_modal_shown', $pro_version);
				update_option('eb_show_pro_template_modal', 'yes');
			}
		}
	}

	/**
	 * Show template modal if needed
	 * 
	 * @since 4.1.0
	 */
	public function show_template_modal()
	{
		$show_free = get_option('eb_show_free_template_modal', 'no') === 'yes';
		$show_pro  = get_option('eb_show_pro_template_modal', 'no') === 'yes';

		if ($show_free || $show_pro) {
			add_action('admin_enqueue_scripts', array($this, 'enqueue_template_modal_assets'));

			add_action('admin_footer', function () use ($show_free, $show_pro) {
				if ($show_free) {
					$this->render_free_template_modal();
				}
				if ($show_pro) {
					$this->render_pro_template_modal();
				}
			});
		}
	}

	/**
	 * Enqueue template modal assets
	 * 
	 * @since 4.1.0
	 */
	public function enqueue_template_modal_assets()
	{
		// Add data for the JS
		wp_register_script('eb-template-modal-script', '', [], '', true);

		wp_localize_script(
			'eb-template-modal-script',
			'ebModalData',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('eb_template_modal_nonce'),
				'templatesUrl' => admin_url('admin.php?page=eb-settings&tab=templates')
			)
		);
		wp_enqueue_script('eb-template-modal-script');

		wp_add_inline_script('eb-template-modal-script', "
			jQuery(document).ready(function($) {
				$('.eb__modal-close').on('click', function () {
					var container = $(this).closest('.eb__modal-container');
					var modalType = container.hasClass('pro') ? 'pro' : 'free';

					$.post(ebModalData.ajaxurl, {
						action: 'eb_mark_template_modal_as_viewed',
						nonce: ebModalData.nonce,
						modal_type: modalType
					});

					container.closest('.eb__modal-overlay').fadeOut();
				});
			});
		");
	}

	/**
	 * Render template modal for Pro users
	 * 
	 * @since 4.1.0
	 */
	private function render_pro_template_modal()
	{
		?>
		<div class="eb__modal-overlay">
			<div class="eb__modal-container pro">
				<button class="eb__modal-close"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
						<path d="M18 6 6 18" />
						<path d="m6 6 12 12" />
					</svg></button>

				<div class="eb__modal-content">
					<h1><?php _e("Upgrade your store's look today!", 'edwiser-bridge'); ?></h1>
					<p><?php _e("We've introduced new templates for key WooCommerce pages in Edwiser Bridge!", 'edwiser-bridge'); ?></p>

					<div class="eb__feature-list">
						<div class="eb__feature-item">
							<span class="eb__check-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chevron-right-icon lucide-square-chevron-right">
									<rect width="18" height="18" x="3" y="3" rx="2" />
									<path d="m10 8 4 4-4 4" />
								</svg></span>
							<span class="eb__feature-text"><?php _e("Fully customizable with WordPress Gutenberg", 'edwiser-bridge'); ?></span>
						</div>
						<div class="eb__feature-item">
							<span class="eb__check-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chevron-right-icon lucide-square-chevron-right">
									<rect width="18" height="18" x="3" y="3" rx="2" />
									<path d="m10 8 4 4-4 4" />
								</svg></span>
							<span class="eb__feature-text"><?php _e("Improved design for a better user experience", 'edwiser-bridge'); ?></span>
						</div>
						<div class="eb__feature-item">
							<span class="eb__check-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-chevron-right-icon lucide-square-chevron-right">
									<rect width="18" height="18" x="3" y="3" rx="2" />
									<path d="m10 8 4 4-4 4" />
								</svg></span>
							<span class="eb__feature-text"><?php _e("Easy to apply from the Template Settings", 'edwiser-bridge'); ?></span>
						</div>
					</div>

					<a href="<?php echo admin_url('admin.php?page=eb-settings&tab=templates'); ?>" class="eb__modal-cta"><?php _e("View New Templates", 'edwiser-bridge'); ?></a>
				</div>

				<div class="eb__modal-image">
					<img src="<?php echo esc_url(plugins_url('images/templates-pro-popup.png', dirname(__FILE__))); ?>" alt="WooCommerce Templates Preview">
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Render template modal for Free users
	 * 
	 * @since 4.1.0
	 */
	private function render_free_template_modal()
	{
	?>
		<div class="eb__modal-overlay">
			<div class="eb__modal-container free">
				<button class="eb__modal-close"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
						<path d="M18 6 6 18" />
						<path d="m6 6 12 12" />
					</svg></button>

				<div class="eb__modal-content">
					<h1><?php _e("Your course pages just got an upgrade!", 'edwiser-bridge'); ?></h1>
					<p><?php _e("We’ve given the Single Course page and Course archive page a fresh new look! Enjoy a cleaner design and improved layout for a better course browsing experience.", 'edwiser-bridge'); ?></p>

					<a href="<?php echo admin_url('admin.php?page=eb-settings&tab=templates'); ?>" class="eb__modal-cta"><?php _e("Check out the new pages!", 'edwiser-bridge'); ?></a>
				</div>

				<div class="eb__modal-image">
					<img src="<?php echo esc_url(plugins_url('images/templates-free-popup.png', dirname(__FILE__))); ?>" alt="Templates Preview">
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * AJAX handler to mark template modal as viewed
	 * 
	 * @since 4.1.0
	 */
	public function eb_mark_template_modal_as_viewed()
	{
		check_ajax_referer('eb_template_modal_nonce', 'nonce');

		if (isset($_POST['modal_type']) && $_POST['modal_type'] === 'free') {
			update_option('eb_show_free_template_modal', 'no');
		} elseif (isset($_POST['modal_type']) && $_POST['modal_type'] === 'pro') {
			update_option('eb_show_pro_template_modal', 'no');
		}

		wp_send_json_success();
	}

	/**
	 * Check if update modal should be shown for version 4.3.0
	 * 
	 * @since 4.3.0
	 */
	public function check_for_update_modal()
	{
		$show_update_modal = get_option('eb_show_update_modal_4_3_0', 'no') === 'yes';

		if ($show_update_modal) {
			add_action('admin_enqueue_scripts', array($this, 'enqueue_update_modal_assets'));
			add_action('admin_footer', array($this, 'show_update_modal'));
		}
	}

	/**
	 * Show update modal for version 4.3.0
	 * 
	 * @since 4.3.0
	 */
	public function show_update_modal()
	{
		$gutenberg_pages = get_option('eb_gutenberg_pages', array());
		$user_account_url = '';
		$my_courses_url = '';
		if (!empty($gutenberg_pages['user_account'])) {
			$user_account_url = get_permalink($gutenberg_pages['user_account']);
		}
		if (!empty($gutenberg_pages['my_courses'])) {
			$my_courses_url = get_permalink($gutenberg_pages['my_courses']);
		}
	?>

		<div class="eb-modal-overlay">
			<div class="eb-modal--enroll-update">
				<button class="eb-modal__close" id="eb-modal-enroll-close" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
						<path d="M18 6 6 18" />
						<path d="m6 6 12 12" />
					</svg></button>
				<div class="eb-modal-initial-body">
					<div class="eb-modal__body">
						<div class="eb-modal__visual">
							<img src="<?php echo plugins_url('admin/assets/images/update-preview.png', dirname(__FILE__)); ?>" alt="Update Preview" class="eb-modal__image" />
						</div>
						<div class="eb-modal__content">
							<h2 class="eb-modal__title"><?php _e('Explore the New Look of User-Account & My Courses Page!', 'edwiser-bridge'); ?></h2>
							<p class="eb-modal__desc">
								<?php _e('We\'ve redesigned the', 'edwiser-bridge'); ?> <a href="<?php echo esc_url($user_account_url); ?>" class="eb-modal__link" target="_blank"><?php _e('User Account', 'edwiser-bridge'); ?></a> <?php _e('and', 'edwiser-bridge'); ?> <a href="<?php echo esc_url($my_courses_url); ?>" class="eb-modal__link" target="_blank"><?php _e('My Courses', 'edwiser-bridge'); ?></a> <?php _e('pages to be cleaner, faster, and easier to use — without changing how things work.', 'edwiser-bridge'); ?>
							</p>
							<div class="eb-modal__note">
								<strong><?php _e('NOTE:', 'edwiser-bridge'); ?></strong>
								<ul>
									<li><?php _e('You can switch designs anytime:', 'edwiser-bridge'); ?> <br>
										<span class="eb-modal__note-path"><?php _e('WP Admin &gt; Edwiser Bridge &gt; Settings &gt; Templates', 'edwiser-bridge'); ?></span>
									</li>
									<li><?php _e('Any customizations made to the current design will stay on the old version', 'edwiser-bridge'); ?></li>
								</ul>
							</div>
							<div class="eb-modal__actions">
								<button class="eb-modal__btn eb-modal__btn--secondary" id="eb-let-me-select"><?php _e('Let me select', 'edwiser-bridge'); ?></button>
								<button class="eb-modal__btn eb-modal__btn--primary" id="eb-switch-to-new"><?php _e('Switch to new design', 'edwiser-bridge'); ?></button>
							</div>
						</div>
					</div>
				</div>
				<div class="eb-modal-success-body" style="display: none;">
					<h2 class="eb-modal__title"><?php _e('Congratulations!', 'edwiser-bridge'); ?></h2>
					<p><?php _e('You\'ve successfully switched to the revamped version of', 'edwiser-bridge'); ?> <strong><?php _e('User Account', 'edwiser-bridge'); ?></strong> <?php _e('and', 'edwiser-bridge'); ?> <strong><?php _e('My Courses', 'edwiser-bridge'); ?></strong> <?php _e('pages', 'edwiser-bridge'); ?></p>
					<a href="<?php echo esc_url(admin_url('admin.php?page=eb-settings&tab=templates#user_account')); ?>" class="eb-modal__btn eb-modal__btn--primary"><?php _e('View settings', 'edwiser-bridge'); ?></a>
				</div>
			</div>
		</div>
<?php
	}

	/**
	 * Enqueue update modal assets
	 * 
	 * @since 4.3.0
	 */
	public function enqueue_update_modal_assets()
	{
		// Add data for the JS
		wp_register_script('eb-update-modal-script', '', [], '', true);

		wp_localize_script(
			'eb-update-modal-script',
			'ebUpdateModalData',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('eb_update_modal_nonce'),
				'settingsUrl' => admin_url('admin.php?page=eb-settings&tab=templates#user_account'),
				'templatesUrl' => admin_url('admin.php?page=eb-settings&tab=templates#user_account')
			)
		);
		wp_enqueue_script('eb-update-modal-script');

		wp_add_inline_script('eb-update-modal-script', "
			jQuery(document).ready(function($) {
				$('.eb-modal__close').on('click', function () {
					// Mark modal as viewed
					$.post(ebUpdateModalData.ajaxurl, {
						action: 'eb_mark_update_modal_as_viewed',
						nonce: ebUpdateModalData.nonce
					});
					
					// Close the modal
					$('.eb-modal-overlay').fadeOut(300);
				});

				$('#eb-let-me-select').on('click', function () {
					// Mark modal as viewed and redirect to settings
					$.post(ebUpdateModalData.ajaxurl, {
						action: 'eb_mark_update_modal_as_viewed',
						nonce: ebUpdateModalData.nonce
					}, function() {
						// Close modal and redirect
						$('.eb-modal-overlay').fadeOut(300, function() {
							window.location.href = ebUpdateModalData.settingsUrl;
						});
					});
				});

				$('#eb-switch-to-new').on('click', function () {
					// Disable button to prevent multiple clicks
					$(this).prop('disabled', true).text('Switching...');
					
					// Switch to new design
					$.post(ebUpdateModalData.ajaxurl, {
						action: 'eb_switch_to_new_design',
						nonce: ebUpdateModalData.nonce
					}, function(response) {
						if (response.success) {
							// Show success message
							$('.eb-modal-initial-body').fadeOut(300, function() {
								$('.eb-modal-success-body').fadeIn(300);
							});
						} else {
							// Re-enable button if there was an error
							$('#eb-switch-to-new').prop('disabled', false).text('Switch to new design');
						}
					}).fail(function() {
						// Re-enable button if AJAX failed
						$('#eb-switch-to-new').prop('disabled', false).text('Switch to new design');
					});
				});

				// Close modal when clicking View settings button
				$(document).on('click', '.eb-modal-success-body .eb-modal__btn', function () {
					// Mark modal as viewed
					$.post(ebUpdateModalData.ajaxurl, {
						action: 'eb_mark_update_modal_as_viewed',
						nonce: ebUpdateModalData.nonce
					});
					
					// Close modal and redirect to templates page
					$('.eb-modal-overlay').fadeOut(300, function() {
						window.location.href = ebUpdateModalData.templatesUrl;
					});
				});

				// Prevent modal click from closing when clicking inside modal
				$(document).on('click', '.eb-modal', function (e) {
					e.stopPropagation();
				});
			});
		");
	}

	/**
	 * Mark update modal as viewed
	 * 
	 * @since 4.3.0
	 */
	public function eb_mark_update_modal_as_viewed()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'eb_update_modal_nonce')) {
			wp_die('Security check failed');
		}

		update_option('eb_show_update_modal_4_3_0', 'no');
		wp_send_json_success();
	}

	/**
	 * Switch to new design
	 * 
	 * @since 4.3.0
	 */
	public function eb_switch_to_new_design()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'eb_update_modal_nonce')) {
			wp_die('Security check failed');
		}

		// Update template settings to use new design
		$general_settings = get_option('eb_general', array());
		$template_pages = get_option('eb_gutenberg_pages', array());

		$general_settings['eb_useraccount_page_id'] = $template_pages['user_account'];
		$general_settings['eb_my_courses_page_id'] = $template_pages['my_courses'];
		update_option('eb_general', $general_settings);

		// Mark modal as viewed
		update_option('eb_show_update_modal_4_3_0', 'no');

		wp_send_json_success();
	}
}
