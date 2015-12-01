<?php
/**
 * Smartsupp Live Chat.
 *
 * @package   Smartsupp_Admin
 * @author    Smartsupp <vladimir@smartsupp.com>
 * @license   GPL-2.0+
 * @link      http://www.smartsupp.com
 * @copyright 2014 smartsupp.com
 */

class Smartsupp_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     0.1.0
	 */
	private function __construct() {
		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = Smartsupp::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();


		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	public function add_action_links($links)
	{
		$settings_link = '<a href="options-general.php?page=' . $this->plugin_slug . '">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
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

	public static function install()
	{
		global $wp_version;
		if (version_compare($wp_version, "3.1", "<")) {
			deactivate_plugins(basename( __FILE__ ));
			wp_die("Your Wordpress version is not compatible with Smartsupp plugin which requires at least version 3.1. Please update your Wordpress or insert Smartsupp chat code into your website manually (you will find the chat code in the email we have sent you upon registration");
		}
		if (version_compare(phpversion(), "5.3.0", "<")) {
			deactivate_plugins(basename( __FILE__ ));
			wp_die("This plugin requires at least PHP version 5.3.0, your version: " . PHP_VERSION . ". Please ask your hosting company to bring your PHP version up to date.");


		}

		$smartsupp = array();
		$smartsupp['active'] = true;
		$smartsupp['chat-id'] = null;
		$smartsupp['active-vars'] = true;
		$smartsupp['optional-code'] = null;
		$smartsupp['wp-vars']['name'] = true;
		$smartsupp['wp-vars']['username'] = true;
		$smartsupp['wp-vars']['role'] = true;
		$smartsupp['wp-vars']['email'] = true;

		if(Smartsupp::is_woocommerce_active()) {
			$smartsupp['woocommerce-vars']['spent'] = true;
			$smartsupp['woocommerce-vars']['orders'] = true;
			$smartsupp['woocommerce-vars']['location'] = true;
		}

		update_option('smartsupp', $smartsupp);
	}


	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Smartsupp Live Chat - Settings', $this->plugin_slug ),
			__( 'Smartsupp Live Chat', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

		add_menu_page(
			__( 'Smartsupp Live Chat - Settings', $this->plugin_slug ),
			__( 'Smartsupp Chat', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' ),
			plugins_url( 'images/icon-20x20.png', dirname(__FILE__))
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}


	/**
	 * Register settings, add sections ad fields.
	 *
	 * @since    0.1.0
	 */
	public function register_settings() {

		$smartsupp = get_option( 'smartsupp' );

		$fields = array();

		$fields['general-settings'] = array(
			'active' => array(
				'title' => __('Show on website', $this->plugin_slug),
				'field_options' => array(
					'type' => 'checkbox',
					'list' => array(
						array(
							'name' => 'smartsupp[active]',
							'value' => $smartsupp['active'],
							'title' => ''
						)
					)
				)
			),
			'chat-id' => array(
				'title' => __('Smartsupp key', $this->plugin_slug),
				'field_options' => array(
					'type' => 'text',
					'name' => 'smartsupp[chat-id]',
					'value' => $smartsupp['chat-id'],
					'size' => 50
				)
			),
			'optional-code' => array(
				'title' => __('Enter optional API code', $this->plugin_slug ),
				'field_options' => array(
					'type' => 'textarea',
					'name' => 'smartsupp[optional-code]',
					'value' => $smartsupp['optional-code'],
				)
			),
		);

		$fields['variables-settings'] = array(
			'active-vars' => array(
				'title' => __( 'Visitor identification', $this->plugin_slug ),
				'field_options' => array(
					'type' => 'checkbox',
					'list' => array(
						array(
							'name' => 'smartsupp[active-vars]',
							'value' => $smartsupp['active-vars'],
							'title' => __("Display detailed visitor info in Smartsupp dashboard, so you always see it while chatting. To show the info, visitor has to be signed in on your website.", $this->plugin_slug)
						)
					)
				)
			),
			'wp-vars' => array(
				'title' => __('Wordpress variables', $this->plugin_slug),
				'field_options' => array(
					'type' => 'checkbox',
					'list' => array(
						array(
							'name' => 'smartsupp[wp-vars][name]',
							'value' => $smartsupp['wp-vars']['name'],
							'title' => __('Visitor\'s name', $this->plugin_slug)
						),
						array(
							'name' => 'smartsupp[wp-vars][username]',
							'value' => $smartsupp['wp-vars']['username'],
							'title' => __('Visitor\'s username', $this->plugin_slug)
						),
						array(
							'name' => 'smartsupp[wp-vars][role]',
							'value' => $smartsupp['wp-vars']['role'],
							'title' => __('Visitor\'s role', $this->plugin_slug)
						),
						array(
							'name' => 'smartsupp[wp-vars][email]',
							'value' => $smartsupp['wp-vars']['email'],
							'title' => __('Visitor\'s e-mail', $this->plugin_slug)
						),
					)
				)
			),
		);

		if ( Smartsupp::is_woocommerce_active() ) {

		    $fields['variables-settings']['woocommerce-vars'] = array(
	    		'title' => __('Woocommerce variables', $this->plugin_slug),
	    		'field_options' => array(
	    			'type' => 'checkbox',
	    			'list' => array(
	    				array(
	    					'name' => 'smartsupp[woocommerce-vars][spent]',
	    					'value' => $smartsupp['woocommerce-vars']['spent'],
	    					'title' => __('Visitor\'s spent', $this->plugin_slug)
	    				),
	    				array(
	    					'name' => 'smartsupp[woocommerce-vars][orders]',
	    					'value' => $smartsupp['woocommerce-vars']['orders'],
	    					'title' => __('Visitor\'s orders amount', $this->plugin_slug)
	    				),
	    				array(
	    					'name' => 'smartsupp[woocommerce-vars][location]',
	    					'value' => $smartsupp['woocommerce-vars']['location'],
	    					'title' => __('Visitor\'s location', $this->plugin_slug)
	    				),
	    			)
	    		)
		    );

		}


		
		register_setting( 'smartsupp_settings', 'smartsupp' );
		add_settings_section( 'general-settings', '', array($this, 'general_setting_section_callback_function'), $this->plugin_slug );
		add_settings_section( 'variables-settings', __( 'Visitor identification', $this->plugin_slug ), array($this, 'variables_setting_section_callback_function'), $this->plugin_slug );


		foreach ($fields as $section => $field) {
			foreach ($field as $name => $options) {
				add_settings_field( $name, $options['title'], array( $this, 'input_callback' ), $this->plugin_slug, $section, $options['field_options'] );
			}
		}

	}

	function general_setting_section_callback_function() {
		echo __("Don't have a Smartsupp account? <a href=\"http://www.smartsupp.com\" target=\"_blank\">Sign up for free</a>", $this->plugin_slug);
		echo "<br /><br /><button onclick=\"window.open('https://dashboard.smartsupp.com','_blank');\" type=\"button\">" . __('Go to Smartsupp dashboard', $this->plugin_slug) . "</button> ";
	}

	function variables_setting_section_callback_function() {
		echo "<script>
		jQuery(document).ready(function() {
			var code_tr = jQuery('textarea[name=\"smartsupp[optional-code]\"]').closest('tr');
			var chat_id_tr = jQuery('input[name=\"smartsupp[chat-id]\"]').closest('tr');
			var code_block = jQuery('#optional-code-block').html();

			jQuery('#optional-code-block').remove();
			chat_id_tr.parent().append('<tr><td colspan=\"2\">' + code_block + '</td></tr>');

			if(!jQuery('textarea[name=\"smartsupp[optional-code]\"]').val()) {
				code_tr.hide();
			}

			jQuery('a#optional-code').click(function() {
				code_tr.show(400);
				return false;
			});
		});
		</script>";
		echo __("<div id=\"optional-code-block\"><a id=\"optional-code\">Optional API code</a> (API documentation at <a href=\"http://developers.smartsupp.com\" target=\"_blank\">developers.smartsupp.com</a>)</div>", $this->plugin_slug);
		echo '<img src="' . plugins_url( 'images/screen.png', dirname(__FILE__) ) . '" > ';
	}

	/**
	 * Universal callback for input fields.
	 *
	 * @since    0.1.0
	 */
	public function input_callback( $args ) {

		switch ( $args['type'] ) {
			case 'text':
				echo '<input type="text" name="' . $args['name'] . '" value="' . esc_attr( $args['value'] ) . '" size="' . $args['size'] . '" />';
				break;
			case 'textarea':
				echo '<textarea id="' . $args['name'] . '" name="' . $args['name'] . '" cols="60" rows="10">' . esc_attr( $args['value'] ) . '</textarea>';
				break;
			case 'checkbox':
				foreach ($args['list'] as $list) {
					echo '<input type="checkbox" name="' . $list['name'] . '" id=' . $list['name'] . ' value="1" ' . checked( $list['value'], '1', false ) . ' />';
					echo '<label for="' . $list['name'] . '">' . $list['title'] . '</label>';
					if ( isset( $list['description'] ) ) {
						echo '<p class="description">' . $list['description'] . '</p>';
					}
					echo "<br>";
				}
				break;
			default:
				# code...
				break;
		}

	}

}
