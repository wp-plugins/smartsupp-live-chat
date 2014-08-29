<?php
/**
 * Smartsupp Live Chat
 *
 * @package   Smartsupp
 * @author    Tom Wawrosz <tom@smartsupp.com>
 * @license   GPL-2.0+
 * @link      http://www.smartsupp.com
 * @copyright 2014 smartsupp.com
 */

class Smartsupp{

	/**
	 * Plugin version
	 *
	 * @since   0.1.0
	 *
	 * @var     string
	 */
	const VERSION = '0.1.0';

	/**
	 * Plugin slug
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'smartsupp';

	/**
	 * Instance of this class.
	 *
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     0.1.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'wp_footer', array( $this, 'add_chat_script' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    0.1.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since     0.1.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	public static function is_woocommerce_active() {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}


	/**
	 * Insert chat script to WP footer
	 *
	 * @since     0.1.0
	 */
	public function add_chat_script() {

		global $wpdb;

		$smartsupp = get_option( 'smartsupp');

		if ( $smartsupp['active'] != '1' )
			return;

		$chat_id = esc_attr( $smartsupp['chat-id'] );

		if ( $chat_id == '' || ! is_string( $chat_id ) )
			return;

		?>
		<script type="text/javascript">		
			var _smartsupp = _smartsupp || {};
			_smartsupp.key = '<?php echo $chat_id; ?>';
			_smartsupp.cookieDomain = ".<?php echo parse_url( get_site_url(), PHP_URL_HOST ); ?>";
			window.smartsupp||(function(d) {
				var o=smartsupp=function(){ o._.push(arguments)},s=d.getElementsByTagName('script')[0],c=d.createElement('script');o._=[];
				c.async=true;c.type='text/javascript';c.charset='utf-8';c.src='//www.smartsuppchat.com/loader.js';s.parentNode.insertBefore(c,s);
			})(document);
		</script>
		<?php

		$js_output = '';
		$dashboard_name = '';

		if ( is_user_logged_in() ) {

			$user = wp_get_current_user();

			$dashboard_name = $user->display_name . ' (' . $user->ID . ')';

			if( $smartsupp['active-vars'] == '1' ) {
				
				

				foreach ($smartsupp['wp-vars'] as $key => $value) {

					if ( $value == '1' ) {

						switch ( $key ) {
							case 'username':
								$js_var = 'userName : { label: "' . __('Username') . '", value:"' . $user->user_login . '" }';
								break;
							case 'email':
								$js_var = 'email : { label: "' . __('Email') . '", value:"' . $user->user_email . '" }';
								break;
							case 'role':
								$js_var = 'role : { label: "' . __('Role') . '", value:"' . implode( ' ,', $user->roles ) . '" }';
								break;
							case 'name':
								$js_var = 'name : { label: "' . __('Name') . '", value:"' . $user->first_name  . ' ' . $user->last_name . '" }';
								break;					
							default:
								# code...
								break;
						}
					}

					$js_output .= $js_var . ',';

				}

				if ( Smartsupp::is_woocommerce_active() ) {

					foreach ($smartsupp['woocommerce-vars'] as $key => $value) {

						if ( $value == '1' ) {

							switch ( $key ) {
								case 'location':
									$country_code = get_user_meta( $user->ID, 'billing_country', true );
									$city = get_user_meta( $user->ID, 'billing_city', true );
									if ( $city || $country_code ) {
										$location = $city . ', ' . $country_code;
									} else {
										$location = '-';
									}
									
									$js_var = 'billingLocation : { label: "' . __('Billing Location') . '", value:"' . $location . '" }';
									break;
								case 'spent':
									if ( ! $spent = get_user_meta( $user->ID, '_money_spent', true ) ) {

										$spent = $wpdb->get_var( "SELECT SUM(meta2.meta_value)
											FROM $wpdb->posts as posts

											LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
											LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id

											WHERE 	meta.meta_key 		= '_customer_user'
											AND 	meta.meta_value 	= $user->ID
											AND 	posts.post_type 	= 'shop_order'
											AND 	posts.post_status 	= 'wc-completed'
											AND     meta2.meta_key 		= '_order_total'
										" );

										update_user_meta( $user->ID, '_money_spent', $spent );
									}

									if ( ! $spent )
										$spent = 0;

									$formatted_spent = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $spent );

									$js_var = 'spent : { label: "' . __('Spent') . '", value:"' . $formatted_spent . '" }';
									break;
								case 'orders':
									if ( ! $count = get_user_meta( $user->ID, '_order_count', true ) ) {

										$count = $wpdb->get_var( "SELECT COUNT(*)
											FROM $wpdb->posts as posts

											LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id

											WHERE 	meta.meta_key 		= '_customer_user'
											AND 	posts.post_type 	= 'shop_order'
											AND 	posts.post_status 	= 'wc-completed'
											AND 	meta_value 			= $user->ID
										" );

										update_user_meta( $user->ID, '_order_count', $count );
									}

									$count = absint( $count );

									$js_var = 'orders : { label: "' . __('Orders') . '", value:"' . $count . '" }';
									break;				
								default:
									# code...
									break;
							}
						}

						$js_output .= $js_var . ',';

					}

				}
			}
		}

		?>
		<script type="text/javascript">
			wpSmartsuppVars = {
				<?php echo $js_output; ?>
			};

			smartsupp(function() {
				smartsupp('variables', wpSmartsuppVars);
				smartsupp('name', '<?php echo $dashboard_name; ?>');
			});
		</script>
		<?php

	}

}
