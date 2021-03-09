<?php

namespace PelemanProductUploader\Admin;

use Automattic\WooCommerce\Client;

class PpuAdmin
{

	/**
	 * The ID of this plugin.
	 *
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/style.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts()
	{
		//wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin-ui.js', array('jquery'), $this->version, true);
	}

	/**
	 * Register plugin menu item
	 */
	public function ppu_add_admin_menu()
	{
		add_menu_page('Peleman Product Uploader', 'Peleman Products Uploader', 'manage_options', 'ppu-menu.php', array($this, 'require_admin_page'),  'dashicons-welcome-write-blog');
	}

	/**
	 * Register plugin admin page
	 */
	public function require_admin_page()
	{
		require_once 'partials/ppu-menu.php';
	}


	/**
	 * Register plugin settings
	 */
	function ppu_register_plugin_settings()
	{
		register_setting('ppu_custom_settings', 'ppu-wc-key');
		register_setting('ppu_custom_settings', 'ppu-wc-secret');
	}

	/**
	 * Process products JSON
	 */
	public function uploadJson()
	{
		check_admin_referer('upload_json');

		$jsonData = file_get_contents($_FILES['ppu-upload']['tmp_name']);
		$data = json_decode($jsonData);

		$siteUrl = get_site_url();
		$api = new Client(
			$siteUrl,
			get_option('ppu-wc-key'),
			get_option('ppu-wc-secret'),
			[
				'wp_api' => true,
				'version' => 'wc/v3'
			]
		);

		$items = $data->items;

		switch ($data->type) {
			case 'products':
				$endpoint = 'products/';
				foreach ($items as $item) {
					// if wc_get_product_id_by_sku returns an id, "update", otherwise "create"
					$productId = wc_get_product_id_by_sku($item->sku);

					foreach ($item->categories as $category) { // match category slug to id
						if (!is_int($category->id)) {
							$category->id = get_term_by('slug', $category->id, 'product_cat')->term_id;
						}
					}

					// match images

					if ($productId != null) {
						$api->put($endpoint . $productId, $item);
					} else {
						$api->post($endpoint, $item);
					}
				}
				break;
			case 'variations':
				$productId = 1; // to be filled in
				$endpoint = 'products/' . $productId . '/variations';
				foreach ($items as $item) {

					//$api->$action($endpoint, $item);
				}
				break;
			case 'attributes':
				$endpoint = 'products/attributes';
				foreach ($items as $item) {
					$api->post($endpoint, $item);
				}
				break;
			case 'terms':
				$attributeId = 1; // to be filled in
				$endpoint = 'products/attributes/' . $attributeId . '/terms';
				foreach ($items as $item) {
					$api->post($endpoint, $item);
				}
				break;
		}

		wp_safe_redirect($_POST['_wp_http_referer']);
	}

	/**
	 * Show one or all orders
	 */
	public function showOrders()
	{
		if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
			$endPoint = 'orders/' . esc_attr($_POST['order_id']);
		} else {
			$endPoint = 'orders';
		}

		$siteUrl = get_site_url();

		$api = new Client(
			$siteUrl,
			get_option('ppu-wc-key'),
			get_option('ppu-wc-secret'),
			[
				'wp_api' => true,
				'version' => 'wc/v3'
			]
		);
		$result = $api->get($endPoint);

		print('<pre>' . __FILE__ . ':' . __LINE__ . PHP_EOL . print_r($result, true) . '</pre>');
	}
}
