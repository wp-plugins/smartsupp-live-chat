<?php
/**
 * Smartsupp Live Chat - admin view
 *
 * @package   Smartsupp
 * @author    Tom Wawrosz <tom@smartsupp.com>
 * @license   GPL-2.0+
 * @link      http://www.smartsupp.com
 * @copyright 2014 smartsupp.com
 */

?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<form method="post" action="options.php">
		<?php 
			settings_fields( 'smartsupp_settings' );
			do_settings_sections( 'smartsupp' );
			submit_button();
		 ?>
	</form>

</div>
