<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MyLoginForm
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the Uninstaller class.
require_once plugin_dir_path( __FILE__ ) . 'Includes/Lifecycle/Uninstaller.php';

// Run uninstall.
\MyLoginForm\Lifecycle\Uninstaller::uninstall();
