<?php

namespace PelemanProductUploader\Admin;

use Automattic\WooCommerce\Client;
use WP_REST_Response;
use WC_Product_Attribute;

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
	 * Register get images endpoint
	 */
	public function registerGetImagesEndpoint()
	{
		register_rest_route('ppu/v1', '/image(?:/(?P<image_name>\S+))?', array(
			'methods' => 'GET',
			'callback' => array($this, 'getImages'),
			'args' => array('page'),
			'permission_callback' => '__return_true'
		));
	}

	public function getImages($request)
	{
		if (!empty($request['image_name'])) {
			$imageName = $request['image_name'];
			if (!$this->getImageIdByName($imageName)) {
				wp_send_json(array(
					'message' => 'No image found.',
					'name' => $imageName
				));
			} else {
				$imageId = $this->getImageIdByName($imageName);
				wp_send_json($this->getImageInformation($imageId));
			}
		}
		global $wpdb;
		$sql = "SELECT post_id FROM " . $wpdb->base_prefix . "postmeta WHERE meta_key = '_wp_attached_file'";
		$result = $wpdb->get_results($sql);
		$finalResult = array();
		foreach ($result as $image) {
			array_push($finalResult, $this->getImageInformation($image->post_id));
		}
		wp_send_json($finalResult);
	}

	private function getImageInformation($imageId)
	{
		$imageInformation = wp_get_attachment_metadata($imageId);

		return array(
			'id' => $imageId,
			'url' => wp_get_attachment_url($imageId),
			'size' => !(empty($imageInformation['width'] && !empty($imageInformation['height']))) ? $imageInformation['width'] . 'x' . $imageInformation['height'] : 'no sizes found'
		);
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
		$this->handleCategoriesAndTags($items, 'tag', 'tag');
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
		$this->handleCategoriesAndTags($items, 'cat', 'category');
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
	 * Register post image endpoint
	 */
	public function registerPostImageEndpoint()
	{
		register_rest_route('ppu/v1', '/image', array(
			'methods' => 'POST',
			'callback' => array($this, 'postImage'),
			'permission_callback' => '__return_true'
		));
	}

	public function postImage($request)
	{
		$data = json_decode($request->get_body());
		$finalResponse = array();

		foreach ($data->images as $image) {
			$filename = $image->name;
			$altText = $image->alt;
			$contentText = $image->content;
			$excerptText = $image->description;
			$base64ImageString = $image->base64image;

			$upload_dir  = wp_upload_dir();
			$upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

			$img             = str_replace('data:image/jpeg;base64,', '', $base64ImageString);
			$img             = str_replace(' ', '+', $img);
			$decoded         = base64_decode($img);
			$file_type       = 'image/jpeg';

			file_put_contents($upload_path . $filename, $decoded);

			$imageExists = $this->getImageIdByName($filename);
			if ($imageExists) {
				$response['message'] = "Updated existing image";
				$attachment = array(
					'ID' => $imageExists,
					'post_mime_type' => $file_type,
					'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
					'post_content'   => $contentText,
					'post_excerpt'   => $excerptText,
					'post_status'    => 'inherit',
					'guid'           => $upload_dir['url'] . '/' . basename($filename)
				);
			} else {
				$response['message'] = "Created new image";
				$attachment = array(
					$file_type,
					'post_mime_type' => $file_type,
					'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
					'post_content'   => $contentText,
					'post_excerpt'   => $excerptText,
					'post_status'    => 'inherit',
					'guid'           => $upload_dir['url'] . '/' . basename($filename)
				);
			}

			try {
				$attachment_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $filename);
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				$attach_data = wp_generate_attachment_metadata($attachment_id, $upload_path . $filename);
				update_post_meta($attachment_id, '_wp_attachment_image_alt', $altText);

				if (wp_update_attachment_metadata($attachment_id, $attach_data)) {
					$response['status'] = 'success';
					$response['id'] = $attachment_id;
					$response['image_path'] = get_post_meta($attachment_id, '_wp_attached_file', true);
				}
			} catch (\Throwable $th) {
				$response['status'] = 'error';
				$response['message'] = $th->getMessage();
			}

			if ($response['status'] == 'success') {
				array_push($finalResponse, array(
					'status' => $response['status'],
					'id' => $response['id'],
					'image_name' => $image->name,
					'image' => $response['image_path'],
					'message' => $response['message'],
				));
			} else {
				array_push($finalResponse, array(
					'status' => 'error',
					'image_name' => $image->name,
					'message' => "image upload failed"
				));
			}
			$response = array();
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;

		wp_send_json($finalResponse, $statusCode);
		return;
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
				$response = $this->handleCategoriesAndTags($items, 'cat', 'category');
				break;
			case 'attributes':
				$response = $this->handleAttributes($items);
				break;
			case 'terms':
				$response = $this->handleAttributeTerms($items);
				break;
			case 'tags':
				$response = $this->handleCategoriesAndTags($items, 'tag', 'tag');
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
		$finalResponse = array();
		$response = array();

		foreach ($dataArray as $item) {
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
						$response['status'] = 'error';
						$response['message'] = "Attribute {$attributeLookup['slug']} not found";
					} else {
						$attribute->id = $attributeLookup['id'];
					}
				}
			}

			if (isset($item->images) && $item->images != null) {
				foreach ($item->images as $image) {
					$imageId = $this->getImageIdByName($image->name);
					if ($imageId != null) {
						$image->id = $imageId;
					} else {
						$response['status'] = 'error';
						$response['message'] = "Image {$image->name} not found";
					}
				}
			}

			if (!isset($response['status'])) {
				try {
					if ($productId != 0 || $productId != null) {
						$response = (array) $api->put($endpoint . $productId, $item);
						$response['status'] = 'success';
						$response['action'] = 'modify product';
					} else {
						$response = (array) $api->post($endpoint, $item);
						$response['status'] = 'success';
						$response['action'] = 'create product';
					}
				} catch (\Throwable $th) {
					$response['status'] = 'error';
					$response['message'] = $th->getMessage();
				}
			}

			if (isset($response['status']) && $response['status'] == 'success') {
				array_push($finalResponse, array(
					'status' => $response['status'],
					'action' => $response['action'],
					'id' => $response['id'],
					'product' => $item->name
				));
			} else {
				array_push($finalResponse, array(
					'status' => $response['status'],
					'message' => $response['message'],
					'product' => $item->name
				));
			}

			$response = array();
		}
		wp_send_json($finalResponse, 200);
	}

	/**
	 * Upload handler: categories
	 */
	private function handleCategoriesAndTags($dataArray, $shortObjectName, $longObjectName)
	{
		$finalResponse = array();

		foreach ($dataArray as $item) {
			try {
				if (get_term_by('slug', $item->slug, 'product_' . $shortObjectName)) {
					$object = get_term_by('slug', $item->slug, 'product_' . $shortObjectName);
					$response = wp_update_term($object->term_id, 'product_' . $shortObjectName, array(
						'description' => $item->description,
						'slug'    => $item->slug
					));
					$tempResponse['action'] = 'modify ' . $longObjectName;
				} else {
					$response = wp_insert_term($item->name, 'product_' . $shortObjectName, array(
						'description' => $item->description,
						'slug'    => $item->slug
					));
					$tempResponse['action'] = 'create ' . $longObjectName;
				}
			} catch (\Throwable $th) {
				$tempResponse['status'] = 'error';
				$tempResponse['message'] = $th->getMessage();
			}

			if (is_wp_error($response)) {
				array_push($finalResponse, array(
					'status' => 'error',
					'message' => $tempResponse['message'] ?? $response->errors,
					$longObjectName => $item->name
				));
			} else {
				array_push($finalResponse, array(
					'status' => 'success',
					'action' => $tempResponse['action'],
					'id' => $response['term_id'],
					$longObjectName => $item->name
				));
			}
			$response = array();
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;

		wp_send_json($finalResponse, $statusCode);
	}

	/**
	 * Upload handler: product variations
	 */
	private function handleProductVariations($dataArray)
	{
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');
		$finalResponse = array();

		// Products loop
		foreach ($dataArray as $item) {
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
							$response['status'] = 'error';
							$response['message'] = "Attribute {$attributeLookup['slug']} not found";
						} else {
							$variationAttribute->id = $attributeLookup['id'];
						}
					}
				}

				if (isset($variation->image) && $variation->image != null) {

					$imageId = $this->getImageIdByName($variation->image);
					if ($imageId) {
						$variation->image = array('id' => $imageId);
					} else {
						$response['status'] = 'error';
						$response['message'] = "Image {$variation->image} not found";
					}
				}

				if (!isset($response['status'])) {
					try {
						if ($variationId != 0 || $variationId != null) {
							$response = (array) $api->put($endpoint . $variationId, $variation);
							$response['status'] = 'success';
							$response['action'] = 'modify variation';
						} else {
							$response = (array) $api->post($endpoint, $variation);
							$response['status'] = 'success';
							$response['action'] = 'create variation';
						}
					} catch (\Throwable $th) {
						$response['status'] = 'error';
						$response['message'] = $th->getMessage();
					}
				}
				if ($response['status'] == 'success') {
					array_push($finalResponse, array(
						'status' => $response['status'],
						'action' => $response['action'],
						'id' => $response['id'],
						'product' => $variation->sku
					));
				} else {
					array_push($finalResponse, array(
						'status' => $response['status'],
						'message' => $response['message'],
						'product' => $variation->sku
					));
				}
				$response = array();
			}
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;

		wp_send_json($finalResponse, $statusCode);
	}

	/**
	 * Upload handler: attributes
	 */
	private function handleAttributes($dataArray)
	{
		$finalResponse = array();

		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = wc_get_attribute_taxonomies();
		$currentAttributesArray = array();
		foreach ($currentAttributes as $attribute) {
			$currentAttributesArray[$attribute->attribute_name] = array(
				'id' => $attribute->attribute_id,
				'slug' => $attribute->attribute_name,
			);
		}

		foreach ($dataArray as $item) {
			try {
				if (key_exists($item->slug, $currentAttributesArray)) {
					$response = (array) $api->put($endpoint . $currentAttributesArray[$item->slug]['id'], $item);
					$tempResponse['action'] = 'modify attribute';
				} else {
					$response = (array) $api->post($endpoint, $item);
					$tempResponse['action'] = 'create attribute';
				}
				$tempResponse['status'] = 'success';
			} catch (\Throwable $th) {
				$tempResponse['status'] = 'error';
				$tempResponse['message'] = $th->getMessage();
			}

			if ($tempResponse['status'] == 'error') {
				array_push($finalResponse, array(
					'status' => 'error',
					'message' => $tempResponse['message'],
					'attribute' => $item->name,
					'slug' => $item->slug
				));
			} else {
				array_push($finalResponse, array(
					'status' => 'success',
					'action' => $tempResponse['action'],
					'id' => $response['id'],
					'attribute' => $item->name,
					'slug' => $item->slug
				));
			}
			$response = array();
		}

		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;
		wp_send_json($finalResponse, $statusCode);
	}

	/**
	 * Upload handler: attribute terms
	 */
	private function handleAttributeTerms($dataArray)
	{
		$finalResponse = array();

		// get current attributes
		$currentAttributes = wc_get_attribute_taxonomies();
		$currentAttributesArray = array();
		foreach ($currentAttributes as $attribute) {
			$currentAttributesArray['pa_' . $attribute->attribute_name] = array(
				'id' => $attribute->attribute_id,
				'slug' => $attribute->attribute_name,
			);
		}

		// get all terms per attribute
		$newCurrentTerms = array();
		foreach ($currentAttributesArray as $attrKey => $attrValue) {
			$attributeTerms = get_terms(
				array(
					'taxonomy' => $attrKey,
					'hide_empty' => false
				)
			);

			if (empty($attributeTerms)) continue;

			$tempArray = array();
			foreach ($attributeTerms as $term) {
				$tempArray[$term->slug] = array(
					'attributeId' => $attrValue['id'],
					'attributeSlug' => $attrKey,
					'id' => $term->term_id,
				);
			}
			$newCurrentTerms[$attrKey] = $tempArray;
		}

		$api = $this->apiClient();
		foreach ($dataArray as $item) {
			try {
				$attrName = 'pa_' . strtolower($item->attribute);
				if (key_exists($attrName, $newCurrentTerms)) {
					// get id of attribute, regardless of whether the term is found
					$foundTerm = $newCurrentTerms[$attrName][array_keys($newCurrentTerms[$attrName])[0]];
					$attrId = $foundTerm['attributeId'];
					if (key_exists(strtolower($item->slug), $newCurrentTerms[$attrName])) {
						// term exists
						$termId = $newCurrentTerms[$attrName][strtolower($item->slug)]['id'];
						$endpoint = 'products/attributes/' . $attrId . '/terms/' . $termId;
						$response = (array) $api->put($endpoint, $item);
						$tempResponse['action'] = 'modify term';
					} else {
						// term doesn't exist
						$endpoint = 'products/attributes/' . $attrId . '/terms';
						$response = (array) $api->post($endpoint, $item);
						$tempResponse['action'] = 'create term';
					}
					$tempResponse['status'] = 'success';
					$tempResponse['id'] = $response['id'];
				} else {
					$tempResponse['status'] = 'error';
					$tempResponse['message'] = "attribute not found";
				}
			} catch (\Throwable $th) {
				$tempResponse['status'] = 'error';
				$tempResponse['message'] = $th->getMessage();
			}

			if ($tempResponse['status'] == 'error') {
				array_push($finalResponse, array(
					'status' => 'error',
					'message' => $tempResponse['message'],
					'attribute' => $item->attribute,
					'term' => $item->name,
					'slug' => $item->slug
				));
			} else if ($tempResponse['status'] == 'success') {
				array_push($finalResponse, array(
					'status' => 'success',
					'action' => $tempResponse['action'],
					'id' => $tempResponse['id'],
					'attribute' => $item->attribute,
					'term' => $item->name,
					'slug' => $item->slug
				));
			}
			$response = array();
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;
		wp_send_json($finalResponse, $statusCode);
	}

	/**
	 * Facilitates linking images to categories, products, etc
	 */
	private function getImageIdByName($imageName)
	{
		global $wpdb;
		$sql = "SELECT post_id FROM " . $wpdb->base_prefix . "postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%/" . $imageName . "';";
		$result = $wpdb->get_results($sql);

		if (!empty($result)) {
			return $result[0]->post_id;
		}
		return false;
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
