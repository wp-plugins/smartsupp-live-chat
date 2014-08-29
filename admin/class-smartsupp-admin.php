<?php
/**
 * Smartsupp Live Chat.
 *
 * @package   Smartsupp_Admin
 * @author    Tom Wawrosz <tom@smartsupp.com>
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
		// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );


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
				'title' => __('Enable chat', $this->plugin_slug),
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
				'title' => __('Smartsupp Chat ID', $this->plugin_slug),
				'field_options' => array(
					'type' => 'text',
					'name' => 'smartsupp[chat-id]',
					'value' => $smartsupp['chat-id'],
					'size' => 50
				)
			),
		);

		$fields['variables-settings'] = array(
			'active-vars' => array(
				'title' => __('Enable variables', $this->plugin_slug),
				'field_options' => array(
					'type' => 'checkbox',
					'list' => array(
						array(
							'name' => 'smartsupp[active-vars]',
							'value' => $smartsupp['active-vars'],
							'title' => '',
							'description' => __("By enabling this option you will be able to see selected variables in your Smartsupp dashboard. More info <a href=\"#\">here</a>.", $this->plugin_slug)
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
							'title' => __('Name', $this->plugin_slug),
							'description' => __("Shows user's display name.", $this->plugin_slug)
						),
						array(
							'name' => 'smartsupp[wp-vars][username]',
							'value' => $smartsupp['wp-vars']['username'],
							'title' => __('Username', $this->plugin_slug),
							'description' => __("Shows user's username.", $this->plugin_slug)
						),
						array(
							'name' => 'smartsupp[wp-vars][role]',
							'value' => $smartsupp['wp-vars']['role'],
							'title' => __('Role', $this->plugin_slug),
							'description' => __("Shows user's role.", $this->plugin_slug)
						),
						array(
							'name' => 'smartsupp[wp-vars][email]',
							'value' => $smartsupp['wp-vars']['email'],
							'title' => __('E-mail', $this->plugin_slug),
							'description' => __("Shows user's email.", $this->plugin_slug)
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
	    					'title' => __('Spent', $this->plugin_slug),
	    					'description' => __("Shows how much money customer has spent.", $this->plugin_slug)
	    				),
	    				array(
	    					'name' => 'smartsupp[woocommerce-vars][orders]',
	    					'value' => $smartsupp['woocommerce-vars']['orders'],
	    					'title' => __('Orders', $this->plugin_slug),
	    					'description' => __("Shows customer's orders amount.", $this->plugin_slug)
	    				),
	    				array(
	    					'name' => 'smartsupp[woocommerce-vars][location]',
	    					'value' => $smartsupp['woocommerce-vars']['location'],
	    					'title' => __('Location', $this->plugin_slug),
	    					'description' => __("Shows customer's billing locaition.", $this->plugin_slug)
	    				),
	    			)
	    		)
		    );

		}


		
		register_setting( 'smartsupp_settings', 'smartsupp' );
		add_settings_section( 'general-settings', '', NULL, $this->plugin_slug );
		add_settings_section( 'variables-settings', __( 'Dashboard Variables', $this->plugin_slug ), NULL, $this->plugin_slug );


		foreach ($fields as $section => $field) {
			foreach ($field as $name => $options) {
				add_settings_field( $name, $options['title'], array( $this, 'input_callback' ), $this->plugin_slug, $section, $options['field_options'] );
			}
		}

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
