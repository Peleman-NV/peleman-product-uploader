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
	 * Process products JSON
	 */
	public function uploadProductsJson()
	{

		$jsonData = file_get_contents($_FILES['ppu-upload']['tmp_name']);
		$data = json_decode($jsonData);
	}
}
