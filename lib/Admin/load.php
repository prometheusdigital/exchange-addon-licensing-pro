<?php
/**
 * Bootstrap the admin.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin;
use ITELIC\Plugin;
use ITELIC\Admin\Tab\Dispatch;

/**
 * Register admin menus.
 *
 * @since 1.0
 */
function register_admin_menus() {

	add_submenu_page( 'it-exchange', __( "Licensing", Plugin::SLUG ), __( "Licensing", Plugin::SLUG ),
		apply_filters( 'it_exchange_admin_menu_capability', 'manage_options' ), Dispatch::PAGE_SLUG, array(
			new Dispatch(),
			'dispatch'
		) );
}

add_action( 'admin_menu', 'ITELIC\Admin\register_admin_menus', 85 );


/**
 * Save the per page option for the licenses list table.
 *
 * @since 1.0
 *
 * @param $status string
 * @param $option string
 * @param $value  string
 *
 * @return string|boolean
 */
function save_licenses_per_page( $status, $option, $value ) {

	if ( 'itelic_licenses_list_table_per_page' == $option ) {
		return $value;
	}

	return false;
}

add_filter( 'set-screen-option', 'ITELIC\Admin\save_licenses_per_page', 10, 3 );

/**
 * Load the tabs.
 */
require_once( Plugin::$dir . 'lib/Admin/Tab/load.php' );

/**
 * Load the licenses.
 */
require_once( Plugin::$dir . 'lib/Admin/Licenses/load.php' );

/**
 * Load the releases.
 */
require_once( Plugin::$dir . 'lib/Admin/Releases/load.php' );