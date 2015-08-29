<?php
/**
 * Main init file.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

/**
 * Load the DBs
 */
require_once( Plugin::$dir . 'lib/DB/load.php' );

/**
 * Load key types API methods.
 */
require_once( Plugin::$dir . 'api/key-types.php' );

/**
 * Load keys API methods.
 */
require_once( Plugin::$dir . 'api/keys.php' );

/**
 * Load activations API methods.
 */
require_once( Plugin::$dir . 'api/activations.php' );

/**
 * Load renewals API methods.
 */
require_once( Plugin::$dir . 'api/renewals.php' );

/**
 * Load the Theme API methods.
 */
require_once( Plugin::$dir . 'api/theme/load.php' );

/**
 * Load the main plugin functions.
 */
require_once( Plugin::$dir . 'lib/functions.php' );

/**
 * Load the main plugin hooks
 */
require_once( Plugin::$dir . 'lib/hooks.php' );

/**
 * Initialize the plugin settings.
 */
Settings::init();

/**
 * Load the key types.
 */
require_once( Plugin::$dir . 'lib/Key/load.php' );

/**
 * Load the product features.
 */
require_once( Plugin::$dir . 'lib/Product/Feature/load.php' );

/**
 * Load the admin.
 */
require_once( Plugin::$dir . 'lib/Admin/load.php' );

/**
 * Load the renewal reminders.
 */
require_once( Plugin::$dir . 'lib/Renewal/load.php' );

/**
 * Load the REST API.
 */
require_once( Plugin::$dir . 'lib/API/load.php' );

/**
 * Run the upgrade routine if necessary.
 */
Plugin::upgrade();