<?php
/**
 * Smartsupp Live Chat - uninstall
 *
 * @package   Smartsupp
 * @author    Smartsupp <info@smartsupp.com>
 * @license   GPL-2.0+
 * @link      http://www.smartsupp.com
 * @copyright 2014 smartsupp.com
 */


// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option('smartsupp');
