<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://lsdplugins.com
 * @since             1.0.0
 * @package           Lsddonation_Wablas
 *
 * @wordpress-plugin
 * Plugin Name:       LSDDonation - WooWandroid
 * Plugin URI:        https://github.com/lsdplugins/lsddonation-woowandroid
 * Description:       WhatsApp Notification for LSDDonation using woowandroid Service
 * Version:           1.0.0
 * Author:            LSD Plugins
 * Author URI:        https://lsdplugins.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       lsdd-woowandroid
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @package LSDDonation
 * @subpackage Addon
 * Require LSDDonation
 */
add_action( 'admin_init',  'lsdd_woowandroid_addon_check' );
function lsdd_woowandroid_addon_check() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'lsddonation/lsddonation.php' ) ) {
		add_action( 'admin_notices', function(){
			echo '<div class="error"><p>' . __( 'LSDDonation is required. Please activate it before activating LSDDonation - WooWandroid.', 'lsdd-woowandroid' ) . '</p></div>';
		});

		deactivate_plugins( plugin_basename( __FILE__ ) ); 

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

/**
 * @package LSDDonation
 * @subpackage Addon
 * Require Parent to Active
 */
include_once( ABSPATH.'wp-admin/includes/plugin.php' );
if( is_plugin_active( 'lsddonation/lsddonation.php' ) ){
	add_action( 'plugins_loaded', function(){
		require_once plugin_dir_path( __FILE__ ) . 'class-woowandroid.php';
	});
}
