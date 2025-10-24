<?php

/**
 * EDW Product Settings
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Edwiser Bridge
 * @subpackage Edwiser Bridge/admin
 */

namespace app\wisdmlabs\edwiserBridge;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (! class_exists('Eb_Settings_Templates')) {
    /**
     * Eb_Settings_Templates.
     */
    class Eb_Settings_Templates extends EB_Settings_Page
    {
        /**
         * Addon licensing.
         *
         * @var array $addon_licensing addon licensing
         */
        public $addon_licensing;

        /**
         * Constructor.
         */
        public function __construct()
        {
            $this->addon_licensing = array('test');
            $this->_id = 'templates';
            $this->label = __('Templates', 'edwiser-bridge');

            add_filter('eb_settings_tabs_array', array($this, 'add_settings_page'), 20);
            add_action('eb_settings_' . $this->_id, array($this, 'output'));
            add_action('eb_settings_save_' . $this->_id, array($this, 'save'));
            add_action('eb_sections_' . $this->_id, array($this, 'output_sections'));
        }

        /**
         * Get sections.
         *
         * @since  1.0.0
         *
         * @return array
         */
        public function get_sections()
        {
            $sections = array(
                ''           => __('Gutenberg Templates', 'edwiser-bridge'),
            );

            if (function_exists('is_plugin_active') && is_plugin_active('edwiser-bridge-pro/edwiser-bridge-pro.php')) {
                $sections['elementor-templates'] = __('Elementor Pro Templates', 'edwiser-bridge');
            }

            return apply_filters('eb_get_sections_' . $this->_id, $sections);
        }

        /**
         * Output the settings.
         *
         * @since  1.0.0
         */
        public function output()
        {
            global $current_section;

            // Output settings fields if needed
            $templates = $this->get_settings($current_section);
            if (!empty($templates)) {
                Eb_Admin_Settings::output_fields($templates);
            }
            // Check if we're in a specific section
            if ('elementor-templates' === $current_section) {
                // Hide the save button for elementor templates
                $GLOBALS['hide_save_button'] = true;

                $this->handle_elementor_template_actions();

                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/html-elementor-templates.php';
            } else {
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/html-gutenberg-templates.php';
            }
        }

        /**
         * Save settings.
         *
         * @since  1.0.0
         */
        public function save()
        {

            global $current_section;
            $settings = $this->get_settings($current_section);
            Eb_Admin_Settings::save_fields($settings);
            $checkout_page_alternate = get_option('woocommerce_checkout_page_id', false);
            if ('elementor-templates' !== $current_section) {
                update_option('eb_enabled_templates', $_POST['eb_enabled_templates']);

                $templates = array('shop', 'cart', 'checkout', 'single_product', 'thank_you', 'single_course', 'all_courses');
                foreach ($templates as $template) {
                    $option_name = 'eb_pro_enable_' . $template . '_override';
                    $option_value = isset($_POST['eb_enabled_templates'][$template]) ? '1' : '0';
                    update_option($option_name, $option_value);

                    if ($template !== 'all_courses' && $template !== 'single_course') {
                        $option_name = 'eb_woo_gutenberg_pages';
                        $value = get_option($option_name);
                        $option_value = isset($_POST['eb_pro_' . $template . '_page_id']) ? $_POST['eb_pro_' . $template . '_page_id'] : $value['eb_pro_' . $template . '_page_id'];
                        $value['eb_pro_' . $template . '_page_id'] = $option_value;
                    } else {
                        $option_name = 'eb_gutenberg_pages';
                        $value = get_option($option_name);
                        $option_value = isset($_POST['eb_' . $template . '_page_id']) ? $_POST['eb_' . $template . '_page_id'] : $value[$template];
                        $value[$template] = $option_value;
                    }

                    update_option($option_name, $value);
                    $template_value = get_option('eb_pro_enable_' . $template . '_override', false);
                    if ($template === 'checkout' && '1' == $template_value) {
                        $woo_gutenberg_pages = get_option('eb_woo_gutenberg_pages', array());
                        if (false === get_option('woocommerce_checkout_page__id_old', false)) {
                            update_option('woocommerce_checkout_page__id_old', $checkout_page_alternate);
                        }
                        update_option('woocommerce_checkout_page_id', $woo_gutenberg_pages['eb_pro_checkout_page_id']);
                    } elseif ($template === 'checkout' && '0' == $template_value) {
                        $checkout_page_alternate = get_option('woocommerce_checkout_page__id_old', false);
                        if (!empty($checkout_page_alternate)) {
                            update_option('woocommerce_checkout_page_id', $checkout_page_alternate);
                            delete_option('woocommerce_checkout_page__id_old');
                        }
                    }
                }

                // Handle special templates that don't have switches
                $new_templates = array('enroll_students', 'user_account', 'my_courses');
                $eb_general = get_option('eb_general');

                $eb_general['mucp_group_enrol_page_id'] = isset($_POST['eb_pro_enroll_students_page_id']) ? $_POST['eb_pro_enroll_students_page_id'] : $eb_general['mucp_group_enrol_page_id'];
                $eb_general['eb_useraccount_page_id'] = isset($_POST['eb_user_account_page_id']) ? $_POST['eb_user_account_page_id'] : $eb_general['eb_useraccount_page_id'];
                $eb_general['eb_my_courses_page_id'] = isset($_POST['eb_my_courses_page_id']) ? $_POST['eb_my_courses_page_id'] : $eb_general['eb_my_courses_page_id'];

                update_option('eb_general', $eb_general);

                // foreach ($new_templates as $template) {
                //     if ($template === 'enroll_students') {
                //         $option_name = 'eb_woo_gutenberg_pages';
                //         $value = get_option($option_name);
                //         $option_value = isset($_POST['eb_pro_' . $template . '_page_id']) ? $_POST['eb_pro_' . $template . '_page_id'] : 0;
                //         $value['eb_pro_' . $template . '_page_id'] = $option_value;
                //         update_option($option_name, $value);
                //     } else {
                //         $option_name = 'eb_gutenberg_pages';
                //         $value = get_option($option_name);
                //         $option_value = isset($_POST['eb_' . $template . '_page_id']) ? $_POST['eb_' . $template . '_page_id'] : 0;
                //         $value[$template] = $option_value;
                //         update_option($option_name, $value);
                //     }
                // }

                if (class_exists('\app\wisdmlabs\edwiserBridgePro\includes\Edwiser_Bridge_Pro') && get_option('edd_edwiser_bridge_pro_license_key', false)) {
                    $license_key = get_option('edd_edwiser_bridge_pro_license_key');
                    $args = array(
                        'website_url' => home_url(),
                        'license_key' => $license_key,
                        'shop'  => isset($_POST['eb_enabled_templates']['shop']) ? 'yes' : 'no',
                        'cart'  => isset($_POST['eb_enabled_templates']['cart']) ? 'yes' : 'no',
                        'product'  => isset($_POST['eb_enabled_templates']['single_product']) ? 'yes' : 'no',
                        'checkout'  => isset($_POST['eb_enabled_templates']['checkout']) ? 'yes' : 'no',
                        'thank_you'  => isset($_POST['eb_enabled_templates']['thank_you']) ? 'yes' : 'no',
                    );
                    $edd_api_url = 'https://edwiser.org/wp-json/bridge-settings/v1/settings';
                    $response = wp_remote_post($edd_api_url, array(
                        'body' => $args,
                        'method' => 'POST',
                        'timeout'     => 60,
                        'sslverify'   => false,
                        'blocking' => false,
                    ));
                }
            }
        }

        /**
         * Get settings array.
         *
         * @since  1.0.0
         *
         * @param string $current_section name of the section.
         * @return array
         */
        public function get_settings($current_section = '')
        {
            if ('elementor-templates' === $current_section) {
                $settings = array();
            } else {
                $template_woo_pages    = get_option('eb_woo_gutenberg_pages', array());
                $template_course_pages    = get_option('eb_gutenberg_pages', array());
                $eb_general = get_option('eb_general', array());

                $settings_items = array();

                $pro_pages = array(
                    'shop' => array(
                        'title' => __('Shop page (Product archive page)', 'edwiser-bridge'),
                        'desc'  => __('A clean, modern shop page for better course browsing.', 'edwiser-bridge'),
                        'img'   => 'shop-archive.png',
                        'is_pro' => true,
                        'template_id' => isset($template_woo_pages['eb_pro_shop_page_id']) ? $template_woo_pages['eb_pro_shop_page_id'] : '',
                        'page_option' => 'eb_pro_shop_page_id',
                    ),
                    'single_product' => array(
                        'title' => __('Single product page (Product landing page)', 'edwiser-bridge'),
                        'desc'  => __('A structured layout to showcase course details effectively.', 'edwiser-bridge'),
                        'img'   => 'single-product.png',
                        'is_pro' => true,
                        'page_option' => 'eb_pro_single_product_page_id',
                        'template_id' => isset($template_woo_pages['eb_pro_single_product_page_id']) ? $template_woo_pages['eb_pro_single_product_page_id'] : '',
                    ),
                    'cart' => array(
                        'title' => __('Cart page', 'edwiser-bridge'),
                        'desc'  => __('A simplified cart page for a smoother checkout and enrollment process.', 'edwiser-bridge'),
                        'img'   => 'cart.png',
                        'is_pro' => true,
                        'template_id' => isset($template_woo_pages['eb_pro_cart_page_id']) ? $template_woo_pages['eb_pro_cart_page_id'] : '',
                        'page_option' => 'eb_pro_cart_page_id',
                    ),
                    'checkout' => array(
                        'title' => __('Checkout Page', 'edwiser-bridge'),
                        'desc'  => __('A simplified checkout page for a smoother checkout process.', 'edwiser-bridge'),
                        'note' => __('Stripe and Paypal payment gateways have been tested and verified to work reliably. For any other gateways, please ensure thorough testing before enabling this template on your site.', 'edwiser-bridge'),
                        'img'   => 'checkout.png',
                        'is_pro' => true,
                        'template_id' => isset($template_woo_pages['eb_pro_checkout_page_id']) ? $template_woo_pages['eb_pro_checkout_page_id'] : '',
                        'page_option' => 'eb_pro_checkout_page_id',
                    ),
                    'thank_you' => array(
                        'title' => __('Thank you page template', 'edwiser-bridge'),
                        'desc'  => __('Thank you page to enhance the post-enrollment experience.', 'edwiser-bridge'),
                        'img'   => 'thank-you.png',
                        'is_pro' => true,
                        'page_option' => 'eb_pro_thank_you_page_id',
                        'template_id' => isset($template_woo_pages['eb_pro_thank_you_page_id']) ? $template_woo_pages['eb_pro_thank_you_page_id'] : '',
                    ),
                    'enroll_students' => array(
                        'title' => __('Enrollment page (Group management)', 'edwiser-bridge'),
                        'desc'  => __('Manage groups effortlessly.', 'edwiser-bridge'),
                        'img'   => 'enroll-students.png',
                        'is_pro' => true,
                        'page_option' => 'eb_pro_enroll_students_page_id',
                        'template_id' =>  isset($eb_general['mucp_group_enrol_page_id']) ? $eb_general['mucp_group_enrol_page_id'] : 0,
                        'default_page_id' => isset($eb_general['mucp_group_enrol_page_id']) ? $eb_general['mucp_group_enrol_page_id'] : 0,
                        'hide_switch' => true,
                    ),
                );

                $free_pages = array(
                    'all_courses' => array(
                        'title' => __('All courses page template', 'edwiser-bridge'),
                        'desc'  => __('Display all available courses in an organized and modern design.', 'edwiser-bridge'),
                        'img'   => 'course-listing.png',
                        'is_pro' => false,
                        'page_option' => 'eb_all_courses_page_id',
                        'template_id' => isset($template_course_pages['all_courses']) ? $template_course_pages['all_courses'] : '',
                    ),
                    'single_course' => array(
                        'title' => __('Single course page template', 'edwiser-bridge'),
                        'desc'  => __('Showcase course details, pricing, and description in a clean, structured layout.', 'edwiser-bridge'),
                        'img'   => 'single-course.png',
                        'is_pro' => false,
                        'page_option' => 'eb_single_course_page_id',
                        'template_id' => isset($template_course_pages['single_course']) ? $template_course_pages['single_course'] : '',
                    ),
                    'user_account' => array(
                        'title' => __('User account page', 'edwiser-bridge'),
                        'desc'  => __('Manage your account settings and view your courses.', 'edwiser-bridge'),
                        'img'   => 'user-account.png',
                        'is_pro' => false,
                        'page_option' => 'eb_user_account_page_id',
                        'template_id' => isset($eb_general['eb_useraccount_page_id']) ? $eb_general['eb_useraccount_page_id'] : 0,
                        'default_page_id' => isset($eb_general['eb_useraccount_page_id']) ? $eb_general['eb_useraccount_page_id'] : 0,
                        'hide_switch' => true,
                    ),
                    'my_courses' => array(
                        'title' => __('My courses page', 'edwiser-bridge'),
                        'desc'  => __('View your enrolled courses and progress.', 'edwiser-bridge'),
                        'img'   => 'my-courses.png',
                        'is_pro' => false,
                        'page_option' => 'eb_my_courses_page_id',
                        'template_id' => isset($eb_general['eb_my_courses_page_id']) ? $eb_general['eb_my_courses_page_id'] : 0,
                        'default_page_id' => isset($eb_general['eb_my_courses_page_id']) ? $eb_general['eb_my_courses_page_id'] : 0,
                        'hide_switch' => true,
                    ),
                );

                if (class_exists('\app\wisdmlabs\edwiserBridgePro\includes\Edwiser_Bridge_Pro')) {
                    $settings_items = array_merge($pro_pages, $free_pages);
                } else {
                    $settings_items = array_merge($free_pages, $pro_pages);
                }

                $settings = apply_filters('eb_gutenberg_template_settings', $settings_items);
            }

            return apply_filters('eb_get_settings_' . $this->_id, $settings, $current_section);
        }

        /**
         * Handle elementor template actions.
         *
         * @since  1.0.0
         */
        public function handle_elementor_template_actions()
        {
            if (isset($_GET['action']) && isset($_GET['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'eb_pro_elementor_template')) {
                $template = isset($_GET['template']) ? sanitize_text_field(wp_unslash($_GET['template'])) : '';

                if (class_exists('\app\wisdmlabs\edwiserBridgePro\includes\Eb_Pro_Activator')) {
                    if ('product_archive' === $template) {
                        $activator = new \app\wisdmlabs\edwiserBridgePro\includes\Eb_Pro_Activator();
                        $post_id = $activator::create_elementor_shop_page_template();
                        if ($post_id) {
                            wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=elementor'));
                            exit;
                        }
                    } elseif ('product_single' === $template) {
                        $activator = new \app\wisdmlabs\edwiserBridgePro\includes\Eb_Pro_Activator();
                        $post_id = $activator::create_elementor_product_page_template();
                        if ($post_id) {
                            wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=elementor'));
                            exit;
                        }
                    }
                }
            }
        }
    }
}

return new Eb_Settings_Templates();
