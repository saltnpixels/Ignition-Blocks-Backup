<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              saltnpixels.com
 * @since             1.0.0
 * @package           Ignition_Blocks
 *
 * @wordpress-plugin
 * Plugin Name:       Ignition Blocks Backup
 * Plugin URI:        ignition.press
 * Description:       Save your blocks from the theme to a plugin so they dont get lost when the theme changes
 * Version:           1.0.0
 * Author:            Eric Greenfield
 * Author URI:        saltnpixels.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ignition-blocks
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'IGNITION_BLOCKS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ignition-blocks-activator.php
 */
function activate_ignition_blocks() {
	//creating the cron job
	wp_clear_scheduled_hook( 'ign_backup_blocks' );
	if ( ! wp_next_scheduled( 'ign_backup_blocks' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'ign_backup_blocks' );
	}

}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ignition-blocks-deactivator.php
 */
function deactivate_ignition_blocks() {
	wp_clear_scheduled_hook( 'ign_backup_blocks' );
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ignition-blocks-deactivator.php
 */
function uninstall_ignition_blocks() {

}


register_activation_hook( __FILE__, 'activate_ignition_blocks' );
register_deactivation_hook( __FILE__, 'deactivate_ignition_blocks' );
register_uninstall_hook( __FILE__, 'uninstall_ignition_blocks' );


function ign_backup_acf_blocks() {
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	error_log( 'running event for plugin ignition blocks' );
	global $wp_filesystem;

	if ( ! WP_Filesystem() ) {
		// Unable to connect to the filesystem, FTP credentials may be required or something.
		// You can request these with request_filesystem_credentials()
		error_log( 'could not use file system' );
		exit;
	}

	//created folder inside plugin for backing up blocks
	$target_dir = plugin_dir_path( __FILE__ ) . '/acf-blocks';
	$wp_filesystem->mkdir( $target_dir );

// Now copy all the files in the theme folder to the target directory.
	copy_dir( get_template_directory() . '/template-parts/acf-blocks', $target_dir );
	error_log( 'created backup' );

}

add_action( 'ign_backup_blocks', 'ign_backup_acf_blocks' );




if(! function_exists('ign_plugin_require_all')){
	function ign_plugin_require_all( $dir, $depth = 2 ) {

		foreach ( array_diff( scandir( $dir ), array( '.', '..' ) ) as $filename ) {
			//check if its a file
			if ( is_file( $dir . '/' . $filename ) ) {
				//only include automatically if it starts with an underscore
				if ( substr( $filename, 0, 1 ) === '_' && strpos( $filename, '.php' ) !== false ) {
					include_once( $dir . '/' . $filename );
				}

			} else {
				//if its not a file its a directory. Look through it for more underscore php partial files
				if ( $depth > 0 ) {
					ign_plugin_require_all( $dir . '/' . $filename, $depth - 1 );
				}
			}

		}
	}
}


/**
 * Add blocks back in to theme and register them and let them load
 */
add_action( 'after_setup_theme', 'ignition_blocks_load' );
function ignition_blocks_load(){
	if (!class_exists('ACF')) {
		if ( ! file_exists( get_template_directory() . '/template-parts/acf-blocks' ) ) {
			ign_plugin_require_all( plugin_dir_path( __FILE__ ) );

			$dir = plugin_dir_path( __FILE__ ) . '/acf-blocks';
			ign_plugin_require_all( $dir );
		}
	}

}


