<?php
/**
 * Plugin Name:     Order Bumps
 * Plugin URI:      https://github.com/upnrunnHQ/order-bumps
 * Description:     Display order bumps on the checkout page with AJAX updates and complex conditions.
 * Author:          Kishores
 * Author URI:      https://profiles.wordpress.org/kishores
 * Text Domain:     order-bumps
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Order_Bumps
 */

defined('ABSPATH') || exit;

// Define constants.
define( 'ORDER_BUMPS_VERSION', '0.1.0' );
define( 'ORDER_BUMPS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'ORDER_BUMPS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'ORDER_BUMPS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );


require_once dirname( __FILE__ ) . '/includes/class-order-bumps.php';


/**
 * Main instance of Order_Bumps.
 *
 * Returns the main instance of Formnx to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Order_Bumps
 */
function ORDERBUMPS() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
    return Order_Bumps::instance();
}

$GLOBALS['order_bumps'] = ORDERBUMPS();