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
	public function uploadJsonViaForm()
	{
		check_admin_referer('upload_json');

		$jsonData = file_get_contents($_FILES['ppu-upload']['tmp_name']);
		$data = json_decode($jsonData);

		$items = $data->items;

		switch ($data->type) {
			case 'products':
				$this->handleProducts($items);
				break;
			case 'variations':
				$this->handleProductVariations($items);
				break;
			case 'categories':
				$this->handleCategories($items);
				break;
			case 'attributes':
				$this->handleAttributes($items);
				break;
			case 'terms':
				$this->handleAttributeTerms($items);
				break;
			case 'tags':
				$this->handleTags($items);
				break;
			case 'images':
				break;
		}
		wp_safe_redirect($_POST['_wp_http_referer']);
	}

	/**
	 * Create an API client to handle uploads
	 */
	private function apiClient()
	{
		$siteUrl = get_site_url();
		return new Client(
			$siteUrl,
			get_option('ppu-wc-key'),
			get_option('ppu-wc-secret'),
			[
				'wp_api' => true,
				'version' => 'wc/v3'
			]
		);
	}

	/**
	 * Upload handler: products
	 */
	private function handleProducts($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems('products/attributes/', 'attributes');

		foreach ($dataArray as $item) {
			// if wc_get_product_id_by_sku returns an id, "update", otherwise "create"
			$productId = wc_get_product_id_by_sku($item->sku);

			foreach ($item->categories as $category) { // match category slug to id
				if (!is_int($category->slug)) {
					$category->id = get_term_by('slug', $category->slug, 'product_cat')->term_id ?? 'uncategorized';
				}
			}

			foreach ($item->tags as $tag) { // match category slug to id
				if (!is_int($tag->slug)) {
					$tag->id = get_term_by('slug', $tag->slug, 'product_tag')->term_id ?? 'uncategorized';
				}
			}

			foreach ($item->attributes as $attribute) {
				$attribute->id = $this->getAttributeIdBySlug($attribute->slug, $currentAttributes['attributes']);
			}

			// how to match images

			if ($productId != null) {
				$api->put($endpoint . $productId, $item);
			} else {
				$api->post($endpoint, $item);
			}
		}
	}

	/**
	 * Upload handler: categories
	 */
	private function handleCategories($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/categories/';
		foreach ($dataArray as $item) {

			$categoryId = get_term_by('slug', $item->slug, 'product_cat')->term_id;
			if (isset($item->image->name)) {
				$imageId = $this->getImageIdByName($item->image->name);
				$item->image->id = $imageId;
			}

			if ($categoryId != null) {
				$api->put($endpoint . $categoryId, $item);
			} else {
				$api->post($endpoint, $item);
			}
		}
	}

	/**
	 * Upload handler: product variations
	 */
	private function handleProductVariations($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');

		// Products loop
		foreach ($dataArray as $item) {
			$productId = wc_get_product_id_by_sku($item->parent_product_sku);
			$endpoint = 'products/' . $productId . '/variations/';

			// Variations loop
			foreach ($item->variations as $variation) {
				$variationId = wc_get_product_id_by_sku($variation->sku);

				// Attributes loop
				foreach ($variation->attributes as $variationAttribute) {
					$variationAttribute->id = $this->getAttributeIdBySlug($variationAttribute->slug, $currentAttributes['attributes']);
				}

				if ($variationId != null || $variationId != 0) {
					$api->put($endpoint . $variationId, $variation);
				} else {
					$api->post($endpoint, $variation);
				}
			}
		}
	}


	/**
	 * Upload handler: attributes
	 */
	private function handleAttributes($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');

		foreach ($dataArray as $item) {
			if (in_array('pa_' . $item->slug, $currentAttributes['slugs'])) {
				$id = $this->getAttributeIdBySlug($item->slug, $currentAttributes['attributes']);
				$api->put($endpoint . $id, $item);
			} else {
				$api->post($endpoint, $item);
			}
		}
	}

	/**
	 * Upload handler: attribute terms
	 */
	private function handleAttributeTerms($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');

		$currentAttributesWithTermsArray = array();
		foreach ($currentAttributes['attributes'] as $attributes) {
			$termsEndpoint = 'products/attributes/' . $attributes->id . '/terms';
			$attributeTerms = $api->get($termsEndpoint);

			if (empty($attributeTerms)) {
				array_push($currentAttributesWithTermsArray, array(
					'attributeId' => $attributes->id,
					'attributeSlug' => str_replace('pa_', '', $attributes->slug),
				));
			} else {
				foreach ($attributeTerms as $term) {
					array_push($currentAttributesWithTermsArray, array(
						'attributeId' => $attributes->id,
						'attributeSlug' => str_replace('pa_', '', $attributes->slug),
						'termId' => $term->id,
						'termSlug' => $term->slug
					));
				}
			}
		}

		foreach ($dataArray as $item) {
			if (in_array($item->slug, array_column($currentAttributesWithTermsArray, 'termSlug'))) {
				$foundArrayKey = array_search($item->slug, array_column($currentAttributesWithTermsArray, 'termSlug'));
				$attributeId = $currentAttributesWithTermsArray[$foundArrayKey]['attributeId'];
				$termId = $currentAttributesWithTermsArray[$foundArrayKey]['termId'];
				$endpoint = 'products/attributes/' . $attributeId . '/terms/' . $termId;
				try {
					$api->put($endpoint, $item);
				} catch (\Throwable $th) {
					error_log(__FILE__ . ': ' . __LINE__ . ' ' . print_r($th->getMessage(), true) . PHP_EOL, 3, __DIR__ . '/Log.txt');
				}
			} else {
				if (in_array($item->attribute, array_column($currentAttributesWithTermsArray, 'attributeSlug'))) {
					$foundArrayKey = array_search($item->attribute, array_column($currentAttributesWithTermsArray, 'attributeSlug'));
					$attributeId = $currentAttributesWithTermsArray[$foundArrayKey]['attributeId'];
					$endpoint = 'products/attributes/' . $attributeId . '/terms';
					$api->post($endpoint, $item);
				}
			}
		}
	}

	/**
	 * Upload handler: tags
	 */
	private function handleTags($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/tags/';
		$currentTags = $this->getFormattedArrayOfExistingItems($endpoint, 'tags');

		foreach ($dataArray as $item) {
			if (in_array($item->slug, $currentTags['slugs'])) {
				$foundArrayKey = (array_search($item->slug, array_column($currentTags['tags'], 'slug')));
				$id = $currentTags['tags'][$foundArrayKey]->id;
				$api->put($endpoint . $id, $item);
			} else {
				$api->post($endpoint, $item);
			}
		}
	}

	/**
	 * Facilitaties linking images to categories, products, etc
	 */
	private function getImageIdByName($imageName)
	{
		global $wpdb;
		$sql = "SELECT post_id FROM " . $wpdb->base_prefix . "postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%" . $imageName . "%';";
		$result = $wpdb->get_results($sql);

		return $result[0]->post_id;
	}

	/**
	 * Get current attributes and return 2 arrays: a flattened
	 */
	private function getFormattedArrayOfExistingItems($endpoint, $type)
	{
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

		$currentArrayItems = $api->get($endpoint);
		$currentArrayItemsSlugs = array_map(function ($e) {
			return $e->slug;
		}, $currentArrayItems);

		return array($type => $currentArrayItems, 'slugs' => $currentArrayItemsSlugs);
	}

	/**
	 * Get attribute ID by slug
	 */
	private function getAttributeIdBySlug($slug, $attributeArray)
	{
		$foundArrayKey = (array_search('pa_' . $slug, array_column($attributeArray, 'slug')));
		return $attributeArray[$foundArrayKey]->id;
	}

	/**
	 * Show one or all orders
	 */
	public function showOrders()
	{
		if (isset($_POST['order_id']) && $_POST['order_id'] != '') {
			$endpoint = 'orders/' . esc_attr($_POST['order_id']);
		} else {
			$endpoint = 'orders';
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
		$result = $api->get($endpoint);

		print('<pre>' . __FILE__ . ':' . __LINE__ . PHP_EOL . print_r($result, true) . '</pre>');
	}

	/**
	 * Show one or all products
	 */
	public function showProducts()
	{
		if (isset($_POST['product_id']) && $_POST['product_id'] != '') {
			$endpoint = 'products/' . esc_attr($_POST['product_id']);
		} else {
			$endpoint = 'products';
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
		$result = $api->get($endpoint);

		print('<pre>' . __FILE__ . ':' . __LINE__ . PHP_EOL . print_r($result, true) . '</pre>');
	}
}
