<?php

namespace PelemanProductUploader\Admin;

use Automattic\WooCommerce\Client;
use WP_REST_Response;

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
	 * Register get attributes endpoint
	 */
	public function registerGetAttributesEndpoint()
	{
		register_rest_route('ppu/v1', '/attributes', array(
			'methods' => 'GET',
			'callback' => array($this, 'getAttributes'),
			'permission_callback' => '__return_true'
		));
	}

	public function getAttributes()
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		return $api->get($endpoint);
	}

	/**	
	 * Register get tags endpoint
	 */
	public function registerGetTagsEndpoint()
	{
		register_rest_route('ppu/v1', '/tags(?:/(?P<page>\d+))?', array(
			'methods' => 'GET',
			'callback' => array($this, 'getTags'),
			'args' => array('page'),
			'permission_callback' => '__return_true'
		));
	}

	public function getTags($request)
	{
		$page = $request['page'];
		if (!isset($page) || $page == '') $page = 1;
		$api = $this->apiClient();
		$endpoint = 'products/tags/';
		return $api->get($endpoint,	array(
			'page' => $page,
			'per_page' => 100
		));
	}

	/**	
	 * Register get categories endpoint
	 */
	public function registerGetCategoriesEndpoint()
	{
		register_rest_route('ppu/v1', '/categories(?:/(?P<page>\d+))?', array(
			'methods' => 'GET',
			'callback' => array($this, 'getCategories'),
			'args' => array('page'),
			'permission_callback' => '__return_true'
		));
	}

	public function getCategories($request)
	{
		$page = $request['page'];
		if (!isset($page) || $page == '') $page = 1;
		$api = $this->apiClient();
		$endpoint = 'products/categories/';
		return $api->get($endpoint,	array(
			'page' => $page,
			'per_page' => 100
		));
	}

	/**	
	 * Register get products endpoint
	 */
	public function registerGetProductsEndpoint()
	{
		register_rest_route('ppu/v1', '/products(?:/(?P<page>\d+))?', array(
			'methods' => 'GET',
			'callback' => array($this, 'getProducts'),
			'args' => array('page'),
			'permission_callback' => '__return_true'
		));
	}

	public function getProducts($request)
	{
		$page = $request['page'];
		if (!isset($page) || $page == '') $page = 1;
		$api = $this->apiClient();
		$endpoint = 'products/';
		return $api->get($endpoint,	array(
			'page' => $page,
			'per_page' => 100
		));
	}

	/**	
	 * Register get terms endpoint
	 */
	public function registerGetTermsEndpoint()
	{
		register_rest_route('ppu/v1', '/terms(?:/(?P<page>\d+))?', array(
			'methods' => 'GET',
			'callback' => array($this, 'getTerms'),
			'args' => array('page'),
			'permission_callback' => '__return_true'
		));
	}

	public function getTerms($request)
	{
		$page = $request['page'];
		if (!isset($page) || $page == '') $page = 1;
		$api = $this->apiClient();
		$endpoint = 'products/attributes';
		$currentAttributes = $api->get(
			$endpoint,
			array(
				'page' => $page,
				'per_page' => 100
			)
		);
		$attributeIds = array_map(function ($e) {
			return $e->id;
		}, $currentAttributes);

		$termsArray = array();
		foreach ($attributeIds as $attributeId) {
			$endpoint = 'products/attributes/' . $attributeId . '/terms/';
			$result = $api->get($endpoint);
			array_push($termsArray, $result);
		}
		return $termsArray;
	}

	/**	
	 * Register get product variations endpoint
	 */
	public function registerGetVariationsEndpoint()
	{
		register_rest_route('ppu/v1', '/product/(?P<sku>\w+)/variations(?:/(?P<page>\d+))?', array(
			'methods' => 'GET',
			'callback' => array($this, 'getProductVariations'),
			'args' => array('sku', 'page'),
			'permission_callback' => '__return_true'
		));
	}

	public function getProductVariations($request)
	{
		$productId = wc_get_product_id_by_sku($request['sku']);
		$page = $request['page'];
		if (!isset($page) || $page == '') $page = 1;
		$api = $this->apiClient();

		$endpoint = 'products/' . $productId . '/variations/';
		return $api->get($endpoint,	array(
			'page' => $page,
			'per_page' => 100
		));
	}

	/**	
	 * Register post product variations endpoint
	 */
	public function registerPostAttributesEndpoint()
	{
		register_rest_route('ppu/v1', '/attributes', array(
			'methods' => 'POST',
			'callback' => array($this, 'postAttributes'),
			'permission_callback' => '__return_true'
		));
	}

	public function postAttributes($request)
	{
		$items = json_decode($request->get_body())->items;
		$this->handleAttributes($items);
	}

	/**	
	 * Register post tags endpoint
	 */
	public function registerPostTagsEndpoint()
	{
		register_rest_route('ppu/v1', '/tags', array(
			'methods' => 'POST',
			'callback' => array($this, 'postTags'),
			'permission_callback' => '__return_true'
		));
	}

	public function postTags($request)
	{
		$items = json_decode($request->get_body())->items;
		$this->handleTags($items);
	}

	/**	
	 * Register post categories endpoint
	 */
	public function registerPostCategoriesEndpoint()
	{
		register_rest_route('ppu/v1', '/categories', array(
			'methods' => 'POST',
			'callback' => array($this, 'postCategories'),
			'permission_callback' => '__return_true'
		));
	}

	public function postCategories($request)
	{
		$items = json_decode($request->get_body())->items;
		$this->handleCategories($items);
	}

	/**	
	 * Register post products endpoint
	 */
	public function registerPostProductsEndpoint()
	{
		register_rest_route('ppu/v1', '/products', array(
			'methods' => 'POST',
			'callback' => array($this, 'postProducts'),
			'permission_callback' => '__return_true'
		));
	}

	public function postProducts($request)
	{
		$items = json_decode($request->get_body())->items;
		$this->handleProducts($items);
	}

	/**	
	 * Register post variations endpoint
	 */
	public function registerPostVariationsEndpoint()
	{
		register_rest_route('ppu/v1', '/variations', array(
			'methods' => 'POST',
			'callback' => array($this, 'postVariations'),
			'permission_callback' => '__return_true'
		));
	}

	public function postVariations($request)
	{
		$items = json_decode($request->get_body())->items;
		$this->handleProductVariations($items);
	}

	/**	
	 * Register post terms endpoint
	 */
	public function registerPostTermsEndpoint()
	{
		register_rest_route('ppu/v1', '/terms', array(
			'methods' => 'POST',
			'callback' => array($this, 'postTerms'),
			'permission_callback' => '__return_true'
		));
	}

	public function postTerms($request)
	{
		$items = json_decode($request->get_body())->items;
		$this->handleAttributeTerms($items);
	}

	/**	
	 * Register delete attributes endpoint
	 */
	public function registerDeleteAttributesEndpoint()
	{
		register_rest_route('ppu/v1', '/attributes/(?P<slug>\w+)', array(
			'methods' => 'DELETE',
			'callback' => array($this, 'deleteAttributes'),
			'args' => array('slug'),
			'permission_callback' => '__return_true'
		));
	}

	public function deleteAttributes($request)
	{
		$slug = $request['slug'];
		return $slug;
	}

	/**	
	 * Register delete categories endpoint
	 */
	public function registerDeleteCategoriesEndpoint()
	{
		register_rest_route('ppu/v1', '/categories/(?P<slug>\w+)', array(
			'methods' => 'DELETE',
			'callback' => array($this, 'deleteCategories'),
			'args' => array('slug'),
			'permission_callback' => '__return_true'
		));
	}

	public function deleteCategories($request)
	{
		$slug = $request['slug'];
		return $slug;
	}

	/**	
	 * Register delete tags endpoint
	 */
	public function registerDeleteTagsEndpoint()
	{
		register_rest_route('ppu/v1', '/tags/(?P<slug>\w+)', array(
			'methods' => 'DELETE',
			'callback' => array($this, 'deleteTags'),
			'args' => array('slug'),
			'permission_callback' => '__return_true'
		));
	}

	public function deleteTags($request)
	{
		$slug = $request['slug'];
		return $slug;
	}

	/**	
	 * Register delete product endpoint
	 */
	public function registerDeleteProductsEndpoint()
	{
		register_rest_route('ppu/v1', '/product/(?P<sku>\w+)', array(
			'methods' => 'DELETE',
			'callback' => array($this, 'deleteProducts'),
			'args' => array('sku'),
			'permission_callback' => '__return_true'
		));
	}

	public function deleteProducts($request)
	{
		$slug = $request['slug'];
		return $slug;
	}

	/**	
	 * Register delete terms endpoint
	 */
	public function registerDeleteTermsEndpoint()
	{
		register_rest_route('ppu/v1', '/terms/(?P<slug>\w+)', array(
			'methods' => 'DELETE',
			'callback' => array($this, 'deleteTerms'),
			'args' => array('slug'),
			'permission_callback' => '__return_true'
		));
	}

	public function deleteTerms($request)
	{
		$slug = $request['slug'];
		return $slug;
	}

	/**	
	 * Register delete variations endpoint
	 */
	public function registerDeleteVariationsEndpoint()
	{
		register_rest_route('ppu/v1', '/variations/(?P<sku>\w+)', array(
			'methods' => 'DELETE',
			'callback' => array($this, 'deleteVariations'),
			'args' => array('sku'),
			'permission_callback' => '__return_true'
		));
	}

	public function deleteVariations($request)
	{
		$slug = $request['slug'];
		return $slug;
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
				$response = $this->handleProductVariations($items);
				break;
			case 'categories':
				$response = $this->handleCategories($items);
				break;
			case 'attributes':
				$response = $this->handleAttributes($items);
				break;
			case 'terms':
				$response = $this->handleAttributeTerms($items);
				break;
			case 'tags':
				$response = $this->handleTags($items);
				break;
			case 'images':
				break;
		}
		// TODO fix the redirect
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
		$mainResponse = array();

		foreach ($dataArray as $item) {
			$response->status = 'success';
			// if wc_get_product_id_by_sku returns an id, "update", otherwise "create"
			$productId = wc_get_product_id_by_sku($item->sku);

			if (isset($item->categories) && $item->categories != null) {
				foreach ($item->categories as $category) {
					if (!is_int($category->slug)) {
						$category->id = get_term_by('slug', $category->slug, 'product_cat')->term_id ?? 'uncategorized';
					}
				}
			}

			if (isset($item->tags) && $item->tags != null) {
				foreach ($item->tags as $tag) {
					if (!is_int($tag->slug)) {
						$tag->id = get_term_by('slug', $tag->slug, 'product_tag')->term_id ?? 'uncategorized';
					}
				}
			}

			if (isset($item->attributes) && $item->attributes != null) {
				foreach ($item->attributes as $attribute) {
					$attributeLookup = $this->getAttributeIdBySlug($attribute->slug, $currentAttributes['attributes']);
					if ($attributeLookup['result'] == 'error') {
						$response->status = 'error';
						$response->message = "Attribute {$attributeLookup['slug']} not found";
					} else {
						$attribute->id = $attributeLookup['id'];
					}
				}
			}
			// how to match images
			if ($response->status != 'error') {
				try {
					if ($productId != 0 || $productId != null) {
						$response = $api->put($endpoint . $productId, $item);
						$response->status = 'success';
						$response->action = 'modify product';
					} else {
						$response = $api->post($endpoint, $item);
						$response->status = 'success';
						$response->action = 'create product';
					}
				} catch (\Throwable $th) {
					$response->status = 'error';
					$response->message = $th->getMessage();
				}
			}

			if ($response->status == 'success') {
				array_push($mainResponse, array(
					'status' => $response->status,
					'action' => $response->action,
					'id' => $response->id,
					'product' => $item->name
				));
			} else {
				array_push($mainResponse, array(
					'status' => $response->status,
					'message' => $response->message,
					'product' => $item->name
				));
			}
			$response = array();
		}

		wp_send_json($mainResponse, 200);
	}

	/**
	 * Upload handler: categories
	 */
	private function handleCategories($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/categories/';
		$mainResponse = array();

		foreach ($dataArray as $item) {
			$categoryId = get_term_by('slug', $item->slug, 'product_cat')->term_id;
			if (isset($item->image->name)) {
				$imageId = $this->getImageIdByName($item->image->name);
				$item->image->id = $imageId;
			}

			try {
				if ($categoryId != null) {
					$response = $api->put($endpoint . $categoryId, $item);
					$response->status = 'success';
					$response->action = 'modify category';
				} else {
					$response = $api->post($endpoint, $item);
					$response->status = 'success';
					$response->action = 'create category';
				}
			} catch (\Throwable $th) {
				$response->status = 'error';
				$response->message = $th->getMessage();
			}

			if ($response->status == 'success') {
				array_push($mainResponse, array(
					'status' => $response->status,
					'action' => $response->action,
					'id' => $response->id,
					'category' => $item->name
				));
			} else {
				array_push($mainResponse, array(
					'status' => $response->status,
					'message' => $response->message,
					'category' => $item->name
				));
			}
			$response = array();
		}

		wp_send_json($mainResponse, 200);
	}

	/**
	 * Upload handler: product variations
	 */
	private function handleProductVariations($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');
		$mainResponse = array();

		// Products loop
		foreach ($dataArray as $item) {
			$response->status = 'success';
			$productId = wc_get_product_id_by_sku($item->parent_product_sku);
			$endpoint = 'products/' . $productId . '/variations/';

			// Variations loop
			foreach ($item->variations as $variation) {
				$variationId = wc_get_product_id_by_sku($variation->sku);

				// Attributes loop
				if (isset($variation->attributes) && $variation->attributes != null) {
					foreach ($variation->attributes as $variationAttribute) {
						$attributeLookup = $this->getAttributeIdBySlug($variationAttribute->slug, $currentAttributes['attributes']);
						if ($attributeLookup['result'] == 'error') {
							$response->status = 'error';
							$response->message = "Attribute {$attributeLookup['slug']} not found";
						} else {
							$variationAttribute->id = $attributeLookup['id'];
						}
					}
				}

				if ($response->status != 'error') {
					try {
						if ($variationId != 0 || $variationId != null) {
							$response = $api->put($endpoint . $variationId, $variation);
							$response->status = 'success';
							$response->action = 'modify variation';
						} else {
							$response = $api->post($endpoint, $variation);
							$response->status = 'success';
							$response->action = 'create variation';
						}
					} catch (\Throwable $th) {
						$response->status = 'error';
						$response->message = $th->getMessage();
					}
				}
				if ($response->status == 'success') {
					array_push($mainResponse, array(
						'status' => $response->status,
						'action' => $response->action,
						'id' => $response->id,
						'product' => $variation->sku
					));
				} else {
					array_push($mainResponse, array(
						'status' => $response->status,
						'message' => $response->message,
						'product' => $variation->sku
					));
				}
				$response = array();
			}
		}

		wp_send_json($mainResponse, 200);
	}

	/**
	 * Upload handler: attributes
	 */
	private function handleAttributes($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');
		$mainResponse = array();

		foreach ($dataArray as $item) {
			try {
				if (in_array('pa_' . $item->slug, $currentAttributes['slugs'])) {
					$id = $this->getAttributeIdBySlug($item->slug, $currentAttributes['attributes'])['id'];
					$response = $api->put($endpoint . $id, $item);
					$response->status = 'success';
					$response->action = 'modify attribute';
				} else {
					$response = $api->post($endpoint, $item);
					$response->status = 'success';
					$response->action = 'create attribute';
				}
			} catch (\Throwable $th) {
				$response->status = 'error';
				$response->message = $th->getMessage();
			}

			if ($response->status == 'success') {
				array_push($mainResponse, array(
					'status' => $response->status,
					'action' => $response->action,
					'id' => $response->id,
					'attribute' => $response->name
				));
			} else {
				array_push($mainResponse, array(
					'status' => $response->status,
					'message' => $response->message,
					'attribute' => $response->name
				));
			}
			$response = array();
		}

		wp_send_json($mainResponse, 200);
	}

	/**
	 * Upload handler: attribute terms
	 */
	private function handleAttributeTerms($dataArray)
	{
		$api = $this->apiClient();
		$mainResponse = array();

		// get all current attributes
		$currentAttributes = $this->getFormattedArrayOfExistingItems('products/attributes/', 'attributes');
		$currentAttributesArray = array();
		foreach ($currentAttributes['attributes'] as $attributes) {
			array_push($currentAttributesArray, array(
				'attributeId' => $attributes->id,
				'attributeSlug' => str_replace('pa_', '', $attributes->slug)
			));
		}

		// get all current terms
		global $wpdb;
		$sql = "SELECT REPLACE(wp_term_taxonomy.taxonomy, 'pa_', '') as attribute, wp_terms.term_id as termId,
			wp_terms.slug FROM wordpresstest.wp_term_taxonomy 
			inner JOIN wordpresstest.wp_terms ON wp_term_taxonomy.term_id = wordpresstest.wp_terms.term_id 
			WHERE taxonomy LIKE 'pa_%';";
		$currentTerms = $wpdb->get_results($sql);

		foreach ($dataArray as $item) {
			// per upload item, if it exists, get the current term
			$tempArray = array_filter($currentTerms, function ($currentTerm) use ($item) {
				if ($currentTerm->slug == strtolower($item->slug)) {
					return true;
				} else {
					return false;
				}
			});

			// get the attribute ID
			$attributeArrayKey = array_search($item->attribute, array_column($currentAttributesArray, 'attributeSlug'));
			$attributeId = array_column($currentAttributesArray, 'attributeId')[$attributeArrayKey];

			try {
				// if the term doesn't exist, POST
				if (empty($tempArray)) {
					// term slug not found
					$endpoint = 'products/attributes/' . $attributeId . '/terms';
					$response = $api->post($endpoint, $item);
					$response->status = 'success';
					$response->action = 'create attribute';
				} else {
					// if the term exists, PUT
					$tempArray = reset($tempArray);
					$endpoint = 'products/attributes/' . $attributeId . '/terms/' . $tempArray->termId;
					$response = $api->put($endpoint, $item);
					$response->status = 'success';
					$response->action = 'modify attribute';
				}
			} catch (\Throwable $th) {
				$response->status = 'error';
				$response->message = $th->getMessage();
			}

			if ($response->status == 'success') {
				array_push($mainResponse, array(
					'status' => $response->status,
					'action' => $response->action,
					'id' => $response->id,
					'term' => $item->name
				));
			} else {
				array_push($mainResponse, array(
					'status' => $response->status,
					'message' => $response->message,
					'term' => $item->name
				));
			}
			$response = array();
		}

		wp_send_json($mainResponse, 200);
	}

	/**
	 * Upload handler: tags
	 */
	private function handleTags($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/tags/';
		$mainResponse = array();

		global $wpdb;

		$sql = "SELECT wp_terms.term_id as tagId,
			wp_terms.slug FROM devshop_peleman.wp_term_taxonomy 
			inner JOIN devshop_peleman.wp_terms ON wp_term_taxonomy.term_id = devshop_peleman.wp_terms.term_id 
			WHERE taxonomy LIKE 'product_tag';";
		$currentTags = $wpdb->get_results($sql);

		foreach ($dataArray as $item) {
			if (empty($currentTags)) {
				try {
					$response = $api->post($endpoint, $item);
					$response->status = 'success';
					$response->action = 'create tag';
				} catch (\Throwable $th) {
					$response->status = 'error';
					$response->message = $th->getMessage();
				}
			} else {
				if (array_search($item->slug, array_column($currentTags, 'slug'))) {
					$foundArrayKey = (array_search($item->slug, array_column($currentTags, 'slug')));
					$id = $currentTags[$foundArrayKey]->tagId;
					try {
						$response = $api->put($endpoint . $id, $item);
						$response->status = 'success';
						$response->action = 'modify tag';
					} catch (\Throwable $th) {
						$response->status = 'error';
						$response->message = $th->getMessage();
					}
				} else {
					try {
						$response = $api->post($endpoint, $item);
						$response->status = 'success';
						$response->action = 'create tag';
					} catch (\Throwable $th) {
						$response->status = 'error';
						$response->message = $th->getMessage();
					}
				}
			}

			if ($response->status == 'success') {
				array_push($mainResponse, array(
					'status' => $response->status,
					'action' => $response->action,
					'id' => $response->id,
					'tag_name' => $item->name,
					'tag_slug' => $item->slug
				));
			} else {
				array_push($mainResponse, array(
					'status' => $response->status,
					'message' => $response->message,
					'tag_name' => $item->name,
					'tag_slug' => $item->slug
				));
			}
			$response = "";
		}

		wp_send_json($mainResponse, 200);
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

		$currentArrayItems = $api->get($endpoint, array(
			'per_page' => 100
		));

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
		if (gettype($foundArrayKey) == 'boolean' && !$foundArrayKey) return array('result' => 'error', 'slug' => $slug);
		return array('result' => 'success', 'id' => $attributeArray[$foundArrayKey]->id);
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
