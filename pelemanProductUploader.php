<?php

namespace PelemanProductUploader;

use PelemanProductUploader\Includes\PpuActivator;
use PelemanProductUploader\Includes\PpuDeactivator;
use PelemanProductUploader\Includes\Plugin;

require 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . '/Includes/PpuActivator.php';
require_once plugin_dir_path(__FILE__) . '/Includes/PpuDeactivator.php';

/**
 * @wordpress-plugin
 * Plugin Name:       Peleman product uploader
 * Plugin URI:        https://www.peleman.com
 * Description:       Plugin to enable easy uploading of Peleman products
 * Version:           1.0.0
 * Author:            Jason Goossens
 * Text Domain:       peleman-product-uploader
 * Domain Path:       /Languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Require WooCommerce
// TODO require ppi
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (!in_array('woocommerce/woocommerce.php', $active_plugins)) {
	echo '<h1 class="error">This plugin requires WooCommerce (v5.0.0) to work properly.<br>To remove this message, please install and activate WooCommerce</h1>';
	wp_die();
}

// Constants definition
define('PELEMAN_PRODUCT_UPLOADER_VERSION', '1.0.0');
/**
 * The code that runs during plugin activation.
 */
function activate_pelemanProductUploader()
{
	PpuActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_pelemanProductUploader()
{
	PpuDeactivator::deactivate();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\activate_pelemanProductUploader');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivate_pelemanProductUploader');

/**
 * Begins execution of the plugin.
 */
function run_peleman_product_uploader()
{
	$plugin = new Plugin();
	$plugin->run();
}
run_peleman_product_uploader();
