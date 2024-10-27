<?php
/*
Plugin Name: Phanes 3DP Multiverse WP Edition
Plugin URI: https://phanes.co/
Description: Phanes 3DP Multiverse WP Edition
Version: 2
License: GPLv2 or later
Text Domain: p3dpmvwp
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Phanes_3DP_Multiverse_WP_Edition' ) ) :

/**
 * Plugin main class defination
 */
class Phanes_3DP_Multiverse_WP_Edition {

	/**
	 * Plugin version number
	 *
	 * @var string
	 */
	const VERSION = '2';

	/**
	 * Plugin slug, used in script handle and db
	 *
	 * @var string
	 */
	const SLUG = 'p3dpmvwp';

	/**
	 * Shortcode render counter
	 *
	 * Render only once in a page or post
	 *
	 * @var integer
	 */
	private static $shortcode_render_counter = 1;

	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	private static $options = array();

	/**
	 * Initialize all the functionality
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', __CLASS__ . '::register_shortcode' );
		add_action( 'admin_head', __CLASS__ . '::register_mce_extension' );
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_scripts' );

		add_action( 'admin_menu', __CLASS__ . '::add_plugin_page' );
		add_action( 'admin_init', __CLASS__ . '::register_settings' );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __CLASS__ . '::add_settings_page_link' );
	}

	/**
	 * Add settings page link to plugin page
	 *
	 * @param array $links
	 * @return array
	 */
	public static function add_settings_page_link( $links ) {
		$settings_link = sprintf( '<a href="options-general.php?page=%s">Settings</a>', self::SLUG );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Enqueue required scripts
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script(
			self::SLUG,
			'https://phanes3dp.com/libs/widget/main.min.js',
			array(),
			self::VERSION,
			true
		);

		$options = get_option( self::SLUG, array() );
		wp_localize_script(
			self::SLUG,
			'PhanesClient',
			array(
				'wrapper' => '#phanesWidgetWrapper',
				'merchant_id' => isset( $options['merchant_id'] ) ? $options['merchant_id'] : '',
				'access_token' => isset( $options['access_token'] ) ? $options['access_token'] : '',
			)
		);
	}

	/**
	 * Register shortcode
	 *
	 * @return void
	 */
	public static function register_shortcode() {
		add_shortcode( 'phanes_3dp_multiverse', __CLASS__ . '::render_shortcode' );
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts
	 * @param string $content
	 * @return string
	 */
	public static function render_shortcode( $atts, $content = null ) {
		if ( self::$shortcode_render_counter > 1 ) {
			return;
		}

		++self::$shortcode_render_counter;
		return '<div id="phanesWidgetWrapper"></div>';
	}

	/**
	 * Register tinymce button and js extension
	 *
	 * @return void
	 */
	public static function register_mce_extension() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		// check if WYSIWYG is enabled
		if ( 'true' === get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_buttons',  __CLASS__ . '::register_mce_button' );
			add_filter( 'mce_external_plugins',  __CLASS__ . '::register_mce_plugin' );
		}
	}

	/**
	 * Register tinymce button
	 *
	 * @param array $buttons Without the shortcode button
	 * @return array With the shortcode button
	 */
	public static function register_mce_button( $buttons ) {
		array_push( $buttons, 'separator', self::SLUG );
		return $buttons;
	}

	/**
	 * Register tinymce plugin
	 *
	 * @param array $plugins Without the custom tinymce plugin
	 * @return array With the custom tinymce plugin
	 */
	public static function register_mce_plugin( $plugins ) {
		$plugins[ self::SLUG ] = plugin_dir_url( __FILE__ ) . 'tinymce.js';
		return $plugins;
	}

	/**
	 * Add plugin settings to menu
	 *
	 * @return void
	 */
	public static function add_plugin_page() {
		add_options_page(
			'Phanes 3dp Multiverse WP Settings',
			'Phanes 3dp Multiverse',
			'manage_options',
			self::SLUG,
			__CLASS__ . '::render_plugin_page'
		);
	}

	/**
	 * Render plugin settings page
	 *
	 * @return void
	 */
	public static function render_plugin_page() {
		self::$options = get_option( self::SLUG, array() );
		?>
		<div class="wrap">
			<h1>Phanes 3DP Multiverse WP Edition</h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( self::SLUG . '-group' );
				do_settings_sections( self::SLUG );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register plugin settings field
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::SLUG . '-group',
			self::SLUG,
			__CLASS__ . '::sanitize'
		);

		add_settings_section(
			self::SLUG . '-section',
			'API Settings',
			__CLASS__ . '::print_section_info',
			self::SLUG
		);

		add_settings_field(
			'merchant_id',
			'Merchant ID',
			__CLASS__ . '::field_cb_merchant_id',
			self::SLUG,
			self::SLUG . '-section'
		);

		add_settings_field(
			'access_token',
			'Access Token',
			__CLASS__ . '::field_cb_access_token',
			self::SLUG,
			self::SLUG . '-section'
		);
	}

	/**
	 * Sanitize settings data
	 *
	 * @param array $input
	 * @return array Sanitized output
	 */
	public static function sanitize( $input ) {
		$output = array();

		if ( isset( $input['access_token'] ) ) {
			$output['access_token'] = sanitize_text_field( $input['access_token'] );
		}

		if ( isset( $input['merchant_id'] ) ) {
			$output['merchant_id'] = sanitize_text_field( $input['merchant_id'] );
		}

		return $output;
	}

	/**
	 * Render section description
	 *
	 * @return void
	 */
	public static function print_section_info() {
		printf( 'Add API credentials below. To get the api credentials, go to %s and setup a free account and your account settings.',
		'<a href="'. esc_url( 'phanes3dp.com' ). '" target="_blank">phanes3dp.com</a>'
		);
	}

	/**
	 * Render settings input field
	 *
	 * @return void
	 */
	public static function field_cb_merchant_id() {
		printf(
			'<input type="text" id="merchant_id" class="regular-text" name="%s[merchant_id]" value="%s" />',
			esc_attr( self::SLUG ),
			isset( self::$options['merchant_id'] ) ? esc_attr( self::$options['merchant_id'] ) : ''
		);
	}

	/**
	 * Render settings input field
	 *
	 * @return void
	 */
	public static function field_cb_access_token() {
		printf(
			'<input type="text" id="access_token" class="regular-text" name="%s[access_token]" value="%s" />',
			esc_attr( self::SLUG ),
			isset( self::$options['access_token'] ) ? esc_attr( self::$options['access_token'] ) : ''
		);
	}

}

Phanes_3DP_Multiverse_WP_Edition::init();

endif;
