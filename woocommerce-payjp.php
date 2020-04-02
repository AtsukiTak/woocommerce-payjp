<?php
/**
 * Plugin Name: WooCommerce PAY.JP Payment Gateway
 * Description: PAY.JP Payment Gateway Plugin for WooCommerce
 * Version: 1.0.0
 * Author: bravemaster619
 * Author URI: https://bravemaster619.github.io
 * Text Domain: woocommerce-payjp
 * Domain Path: /languages/
 * @package woocommerce-payjp
 */

function is_woocommerce_active() {
    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}

if ( is_woocommerce_active() ) {

    require_once __DIR__ . "/vendor/autoload.php";

    function add_payjp_payment( $gateways  ) {
        require_once __DIR__ . "/includes/class-wc-payjp-gateway.php";
        $gateways[] = 'WC_Payjp_Gateway'; // your class name is here
        return $gateways;
    }

    function load_payjp_textdomain() {
        load_plugin_textdomain( 'woocommerce-payjp', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
    }

    add_action( 'plugins_loaded', 'load_payjp_textdomain' );

    add_filter( 'woocommerce_payment_gateways', 'add_payjp_payment' );

}

function payjp_missing_dependency_notice() {
    ?>
        <div class="error notice">
                <p>
                    <strong>Error: </strong>
                    woocommerce-payjp requires WooCommerce plugin.
                    You can download <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> here.
                </p>
        </div>
    <?php
}

function payjp_check_dependency() {
    if (!is_woocommerce_active()) {
        payjp_missing_dependency_notice();
        @trigger_error("WooCommerce PAY.JP requires WooCommerce plugin", E_USER_ERROR);
    }
}

function payjp_uninstall() {
    global $wpdb;
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'woocommerce_payjp_settings';" );
    wp_cache_flush();
}

register_activation_hook(__FILE__, 'payjp_check_dependency');
register_uninstall_hook(__FILE__, 'payjp_uninstall');
