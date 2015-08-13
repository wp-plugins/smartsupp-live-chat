<?php
/**
 *
 * @package   Smartsupp
 * @author    Tom Wawrosz <tom@smartsupp.com>
 * @license   GPL-2.0+
 * @link      http://www.smartsupp.com
 * @copyright 2014 Smartsupp.com
 *
 * Plugin Name:       Smartsupp Live Chat
 * Plugin URI:        http://www.smartsupp.com
 * Description:       Adds Smartsupp Live Chat code to your Wordpress.
 * Version:           0.2.1
 * Author:            Tom Wawrosz
 * Author URI:        http://www.smartsupp.com
 * Text Domain:       smartsupp
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-smartsupp.php' );



add_action( 'plugins_loaded', array( 'Smartsupp', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-smartsupp-admin.php' );
	add_action( 'plugins_loaded', array( 'Smartsupp_Admin', 'get_instance' ) );

}
