<?php
/**
 * Uninstall hook for MrDemonWolf Color Preview.
 *
 * Removes plugin options from the database when the plugin is deleted
 * via the WordPress admin Plugins screen.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mdw_cp_current_colors' );
