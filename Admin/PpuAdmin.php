<?php

namespace PelemanProductUploader\Admin;

use Automattic\WooCommerce\Client;
use PelemanProductUploader\Services\ScriptTimerService;

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
			'size' => filesize(get_attached_file($imageId)),
			'dimensions' => !(empty($imageInformation['width'] && !empty($imageInformation['height']))) ? $imageInformation['width'] . 'x' . $imageInformation['height'] : 'no dimensions found'
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
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		foreach ($data->images as $image) {
			$filename = $image->name;
			$altText = $image->alt;
			$contentText = $image->content;
			$excerptText = $image->description;
			$base64ImageString = $image->base64image;
			$img = str_replace('data:image/jpeg;base64,', '', $base64ImageString);
			$img = str_replace(' ', '+', $img);
			$decoded = base64_decode($img);
			$file_type = 'image/jpeg';
			$upload_dir  = wp_upload_dir();
			$upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

			file_put_contents($upload_path . $filename, $decoded);

			$attachment = array(
				'post_mime_type' => $file_type,
				'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
				'post_content'   => $contentText,
				'post_excerpt'   => $excerptText,
				'post_status'    => 'inherit',
				'guid'           => $upload_dir['url'] . '/' . basename($filename)
			);

			$imageExists = $this->getImageIdByName($filename);
			if ($imageExists) {
				$response['message'] = "Updated existing image";
				$attachment['ID'] = $imageExists;
			} else {
				$response['message'] = "Created new image";
			}

			try {
				$attachment_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $filename, 0, true);
				!empty($altText) ? update_post_meta($attachment_id, '_wp_attachment_image_alt', $altText) : '';
				$attach_data = wp_generate_attachment_metadata($attachment_id, $upload_path . $filename);

				if (empty($attach_data)) {
					$response['status'] = 'error';
					$response['message'] = 'Image upload failed';
					throw new \Exception('attach_data returned an empty array.');
				} else {
					wp_update_attachment_metadata($attachment_id, $attach_data);
				}
				$response['status'] = 'success';
				$response['id'] = $attachment_id;
				$response['image_path'] = get_post_meta($attachment_id, '_wp_attached_file', true);
			} catch (\Throwable $th) {
				$response['status'] = 'error';
				$response['exception'] = $th->getMessage();
				$response['message'] = $th->getMessage();
			}

			if ($response['status'] !== 'error') {
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
					'message' => $response['message'],
				));
			}
			$response = array();
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;

		wp_send_json($finalResponse, $statusCode);
		return;
	}


	/**	
	 * Register post menu endpoint
	 */
	public function registerPostMenuEndpoint()
	{
		register_rest_route('ppu/v1', '/menu', array(
			'methods' => 'POST',
			'callback' => array($this, 'postMenu'),
			'permission_callback' => '__return_true'
		));
	}

	public function postMenu($request)
	{
		$data = json_decode($request->get_body());
		$this->handleMenuUpload($data);
	}

	public function uploadMenuViaForm()
	{
		check_admin_referer('upload_menu');

		$jsonData = file_get_contents($_FILES['ppu-upload']['tmp_name']);
		$data = json_decode($jsonData);

		$this->handleMenuUpload($data);
	}

	private function createMenuContainer($menu_name)
	{
		if (empty($menu_name)) return false;

		$uniqueMenuName = $menu_name . '_' . time();
		$menuId = wp_create_nav_menu($uniqueMenuName);

		if (isset($menuId->errors)) {
			return false;
		}
		return ['menu_id' => $menuId, 'menu_name' => $uniqueMenuName];
	}

	public function handleMenuUpload($data)
	{
		$response = [];

		$createdMenus = [];
		foreach ($data->menus as $menu) {
			if ($createdMenu = $this->createMenuContainer($menu->menu_name)) {
				$menuId = $createdMenu['menu_id'];
				$menuName = $createdMenu['menu_name'];
				if ($menu->lang === '') $createdMenus[] = ['id' => $menuId, 'name' => $menu->menu_name, 'lang' => 'en'];
				if ($menu->lang !== '') $createdMenus[] = ['id' => $menuId, 'name' => $menu->menu_name, 'lang' => $menu->lang];
			} else {
				$response[$menu->menu_name]['status'] = 'error';
				$response[$menu->menu_name]['message'] = "Error creating menu container";
				wp_send_json($response, 400);
			}

			$parentItems = array_filter($menu->items, function ($item) {
				return $item->parent_menu_item_name === '';
			});
			$childItems = array_filter($menu->items, function ($item) {
				return $item->parent_menu_item_name !== '';
			});

			foreach ($parentItems as $item) {
				if (!is_int($this->create_menu_item($menuId, $item))) {
					$response[$menu->menu_name]['status'] = 'error';
					$response[$menu->menu_name]['message'] = "Problem creating parent item";
					$response[$menu->menu_name]['menu_item_name'] = $item->menu_item_name;
					break;
				}
			}

			while (!empty($childItems)) {
				$currentNumberOfChildItems = count($childItems);

				$currentMenuItems = wp_get_nav_menu_items($menuName);
				foreach ($childItems as $key => $item) {
					// if parent is found: get parentID, create item, and remove from array
					if (in_array($item->parent_menu_item_name, array_column($currentMenuItems, 'title'))) {
						$parentItem = $currentMenuItems[array_search($item->parent_menu_item_name, array_column($currentMenuItems, 'title'))];
						$item->parentId = $parentItem->ID;

						if (!is_int($this->create_menu_item($menuId, $item, $item->parentId))) {
							$response[$menu->menu_name]['status'] = 'error';
							$response[$menu->menu_name]['message'] = "Problem creating child item";
							$response[$menu->menu_name]['menu_item_name'] = $item->menu_item_name;
							break;
						}

						unset($childItems[$key]);
					}
				}
				$newCurrentNumberOfChildItems = count($childItems);

				if ($newCurrentNumberOfChildItems === $currentNumberOfChildItems) {
					$response[$menu->menu_name]['status'] = 'error';
					$response[$menu->menu_name]['message'] = "Error creating item(s)";
					$response[$menu->menu_name]['items'] = array_values($childItems);
					break;
				}
			}
		}

		$this->joinCreatedMenus($createdMenus);

		foreach ($response as $key => $value) {
			if (isset($value) && $value['status'] === 'error') {
				$this->deleteAllCreatedMenus($createdMenus);
				wp_send_json($response, 400);
			}
		}

		$response['status'] = 'success';
		$response['message'] = 'menu(\'s) created successfully';
		$response['menus'] = $createdMenus;

		wp_send_json($response, 200);
	}

	private function joinCreatedMenus($menuArray)
	{
		global $wpdb;
		$defaultLanguageMenuArray = [];
		// Get current term_relationships for menu ID's
		$existingRelationships = [];
		foreach ($menuArray as $menu) {
			if ($menu['lang'] !== 'en') {
				// get existing terms for secondary languages
				$tempResult[$menu['lang']] = $wpdb->get_results("SELECT object_id FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = {$menu['id']};");
				$mappedResult[$menu['lang']] = array_map(function ($e) {
					return $e->object_id;
				}, $tempResult[$menu['lang']]);
				$existingRelationships[$menu['lang']] = implode(',', $mappedResult[$menu['lang']]);
			}
			if ($menu['lang'] === 'en') {
				$defaultLanguageMenuArray = $menu;
			}
		}

		// update menu items langauges
		$stringTranslationsTable = $wpdb->prefix . 'icl_translations';
		foreach ($existingRelationships as $language => $relationshipsString) {
			$updateMenuItemsSql = "UPDATE $stringTranslationsTable SET language_code = '$language', source_language_code = 'en' WHERE element_id in ($relationshipsString);";
			$wpdb->get_results($updateMenuItemsSql);
		}

		$tridSql = "SELECT trid FROM $stringTranslationsTable WHERE language_code = 'en' AND element_id = {$defaultLanguageMenuArray['id']};";
		$trid = $wpdb->get_results($tridSql)[0]->trid;

		// update menu container languages and sync trid's of menu container
		foreach ($menuArray as $menu) {
			if ($menu['lang'] === 'en') continue;
			$updateMenuContainersSql = "UPDATE $stringTranslationsTable SET language_code = '{$menu['lang']}', trid = $trid, source_language_code = 'en' WHERE element_id = {$menu['id']};";
			$wpdb->get_results($updateMenuContainersSql);
		}
	}

	private function deleteAllCreatedMenus($menuArray)
	{
		foreach ($menuArray as $menuItem) {
			wp_delete_nav_menu($menuItem['id']);
		}
	}

	private function create_menu_item($menuId, $item, $parentId = 0)
	{
		if (!empty($item->category_slug) && empty($item->custom_url) && empty($item->product_sku)) {
			$term = get_term_by('slug', $item->category_slug, 'product_cat');

			if ($term === false) {
				$response['status'] = 'error';
				$response['message'] = "Error finding existing category";
				$response['menu_item_name'] = $item->menu_item_name;
				$response['category_slug'] = $item->category_slug;
				wp_send_json($response, 400);
			}

			$menuObject = [
				'menu-item-type'			=> 'taxonomy',
				'menu-item-object'			=> $term->taxonomy,
				'menu-item-object-id'		=> $term->term_id,
				'menu-item-title'			=> $item->menu_item_name, // display name
				'menu-item-position'	    => $item->position,
				'menu-item-attr-title'      => $item->menu_item_name, // css title attribute
				'menu-item-parent-id' 	    => $parentId,
				'menu-item-status'			=> 'publish',
			];
		} else if (empty($item->category_slug) && !empty($item->custom_url) && empty($item->product_sku)) {
			$menuObject = [
				'menu-item-type'          => 'custom',
				'menu-item-position'      => $item->position,
				'menu-item-title'		  => $item->menu_item_name, // display name
				'menu-item-url'           => $item->custom_url,
				'menu-item-attr-title'    => $item->menu_item_name, // css title attribute
				'menu-item-parent-id'     => $parentId,
				'menu-item-status'        => 'publish'
			];
		} else if (empty($item->category_slug) && empty($item->custom_url) && !empty($item->product_sku)) {
			$productId = wc_get_product_id_by_sku($item->product_sku);

			if ($productId === 0) {
				$response['status'] = 'error';
				$response['message'] = "Error finding product";
				$response['menu_item_name'] = $item->menu_item_name;
				$response['category_slug'] = $item->product_sku;
				wp_send_json($response, 400);
			}

			$menuObject = [
				'menu-item-type'          => 'post_type',
				'menu-item-object'		  => 'product',
				'menu-item-object-id'	  => $productId,
				'menu-item-title'		  => $item->menu_item_name, // display name
				'menu-item-position'      => $item->position,
				'menu-item-attr-title'    => $item->menu_item_name, // css title attribute
				'menu-item-parent-id'     => $parentId,
				'menu-item-status'        => 'publish',
			];
		} else {
			$response['status'] = 'error';
			$response['message'] = "Error defining menu item type";
			$response['menu_item_name'] = $item->menu_item_name;
			$response['category_slug'] = $item->category_slug;
			wp_send_json($response, 400);
		}

		try {
			$response = wp_update_nav_menu_item(
				$menuId,
				0, // current menu item ID - 0 for new item,
				$menuObject
			);
		} catch (\Throwable $th) {
			$response['status'] = 'error';
			$response['message'] = $th->getMessage();
		}
		return $response;
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
				$this->handleCategoriesAndTags($items, 'cat', 'category');
				break;
			case 'attributes':
				$this->handleAttributes($items);
				break;
			case 'terms':
				$this->handleAttributeTerms($items);
				break;
			case 'tags':
				$this->handleCategoriesAndTags($items, 'tag', 'tag');
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
		$scriptTimerService = new ScriptTimerService();
		$api = $this->apiClient();
		$endpoint = 'products/';
		$currentAttributes = $this->getFormattedArrayOfExistingItems('products/attributes/', 'attributes');
		$finalResponse = array();
		$response = array();

		foreach ($dataArray as $item) {
			// set reviews to false
			$item->reviews_allowed = 0;

			// parent or translation
			$isParentProduct = empty($item->lang);

			$productId = wc_get_product_id_by_sku($item->sku);
			$parentProductId = null;
			$childProductId = null;
			$isNewProduct = false;

			// save the sku for the response 
			$response_sku = $item->sku;

			// if it's a parent, check if it exists
			if ($isParentProduct) { // 
				// if wc_get_product_id_by_sku returns an id -> existing product: "update", else new product: "create"
				$isNewProduct = ($productId === 0 || $productId === null);
				// if it's a parent -> the product ID IS the product ID 
				$parentProductId = $productId;
			} else {
				// if it's a child -> the parent ID IS the product ID 
				$parentProductId = $productId;
				if ($parentProductId === null || $parentProductId === 0) {
					$response['status'] = 'error';
					$response['message'] = "Parent product not found (you are trying to upload a translated product, but I can't find its default language counterpart)";
				}

				// get the child's product ID
				$childProductId = apply_filters('wpml_object_id', $parentProductId, 'post', false, $item->lang);

				// if it's a child, we know the parentProductId - does the translatedProductId exist?
				$isNewProduct = ($childProductId === 0 || $childProductId === null);

				// clear SKU for translated/child products to avoid 'duplicate SKU' errors - woocommerce sets this itself
				unset($item->sku);
				// set product as translation of the parent
				$item->translation_of = $parentProductId;
			}
			// if child is null, product ID = parentId, else product ID = child ID
			$productId = $childProductId === null ? $parentProductId : $childProductId;

			// get id's for all categories, tags, attributes, and images.
			if (isset($item->categories) && $item->categories != null) {
				foreach ($item->categories as $category) {
					if (!is_int($category->slug)) {
						$category->id = get_term_by('slug', $category->slug, 'product_cat')->term_id;
						if ($category->id === null) {
							$response['status'] = 'error';
							$response['message'] = "Category $category->slug not found";
						}
					}
				}
			}

			if (isset($item->tags) && $item->tags != null) {
				foreach ($item->tags as $tag) {
					if (!is_int($tag->slug)) {
						$tag->id = get_term_by('slug', $tag->slug, 'product_tag')->term_id;
						if ($tag->id === null) {
							$response['status'] = 'error';
							$response['message'] = "Tag $category->tag not found";
						}
					}
				}
			}

			// for each attribute, take the first option and add to default_attr
			$item->default_attributes = [];
			if (isset($item->attributes) && $item->attributes != null) {
				foreach ($item->attributes as $key => $attribute) {

					$attributeLookup = $this->getAttributeIdBySlug($attribute->slug, $currentAttributes['attributes']);
					if ($attributeLookup['result'] == 'error') {
						$response['status'] = 'error';
						$response['message'] = "Attribute {$attributeLookup['slug']} not found";
					} else {
						$attribute->id = $attributeLookup['id'];
						// set default attributes
						if ($attribute->default !== false) {
							if (!empty($attribute->default)) {
								// use the given default
								$item->default_attributes[$key]->id = $attribute->id;
								$item->default_attributes[$key]->option = $attribute->default;
							}
							if (empty($attribute->default)) {
								// first option is the default
								$item->default_attributes[$key]->id = $attribute->id;
								$item->default_attributes[$key]->option = $attribute->options[0];
							}
						}
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

			// handle up- & cross-sell products
			if ($item->upsell_skus !== null) {
				$item->upsell_ids = $this->get_product_ids_for_sku_array($item->upsell_skus);
			}
			if ($item->cross_sell_skus !== null) {
				$item->cross_sell_ids = $this->get_product_ids_for_sku_array($item->cross_sell_skus);
			}

			if (!isset($response['status'])) {
				try {
					if ($isNewProduct) {
						// this logic route creates a product ID
						$response = (array) $api->post($endpoint, $item);
						$response['status'] = 'success';
						$response['action'] = 'create product';
					} else {
						// this logic route has the product ID and edits it
						$response = (array) $api->put($endpoint . $productId, $item);
						$response['status'] = 'success';
						$response['action'] = 'modify product';
					}
				} catch (\Throwable $th) {
					$response['status'] = 'error';
					$response['message'] = $th->getMessage();
					$response['error_detail'] = json_decode($th->getResponse()->getBody(), true) ?? null;
				}
			}

			if (isset($response['status']) && $response['status'] == 'success') {
				array_push($finalResponse, array(
					'status' => $response['status'],
					'action' => $response['action'],
					'id' => $response['id'],
					'product' => $response_sku,
					'lang' => $item->lang
				));
			} else {
				array_push($finalResponse, array(
					'status' => $response['status'],
					'message' => $response['message'],
					'error_detail' => $response['error_detail'] ?? '',
					'product' => $response_sku,
					'lang' => $item->lang
				));
			}
			$response = array();
		}
		$scriptTimerService->stopAndLogDuration(__FUNCTION__, __DIR__);

		wp_send_json($finalResponse, 200);
	}

	private function get_product_ids_for_sku_array($skuArray)
	{
		$productIdArray = [];
		foreach ($skuArray as $sku) {
			array_push($productIdArray, wc_get_product_id_by_sku($sku));
		}

		return $productIdArray;
	}

	/**
	 * Upload handler: categories
	 */
	private function handleCategoriesAndTags($dataArray, $shortObjectName, $longObjectName)
	{
		$scriptTimerService = new ScriptTimerService();
		$finalResponse = array();
		$iclType = $shortObjectName === 'cat' ? 'category' : 'post_tag';

		foreach ($dataArray as $item) {
			$slug = $item->slug;
			$hasParentLanguage = !empty($item->english_slug);

			try {
				// child
				if ($hasParentLanguage) {
					$parentObject = get_term_by('slug', $item->english_slug, 'product_' . $shortObjectName);
					/**
					 * once a translation exists, a child is seemingly removed from the 'main' tags/categories.  Searching on it's slug or ID will return it's parent.
					 * therefore we use icl_object_id
					 */

					$translatedObjectTermId = icl_object_id($parentObject->term_id, $iclType, false, $item->language_code);
					// item already exists
					if ($translatedObjectTermId) {
						$updateResponse = $this->updateTranslatedTagOrCategory($translatedObjectTermId, $item, $shortObjectName);
						if ($updateResponse === false) {
							$tempResponse['status'] = 'error';
							$tempResponse['message'] = "error encountered updating terms & terms taxonomy in database";
						} else {
							// get taxonomyId

							global $wpdb;
							$term_taxonomy = $wpdb->get_results("SELECT term_taxonomy_id FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = 'product_{$shortObjectName}' AND term_id = {$translatedObjectTermId};")[0];

							$this->joinTranslatedTagOrCategoryWithParent($parentObject, $item, $term_taxonomy->term_taxonomy_id, $shortObjectName);
							$response['term_id'] = $translatedObjectTermId;
							$response['term_taxonomy_id'] = $term_taxonomy->term_taxonomy_id;
							$tempResponse['action'] = 'modify child ' . $longObjectName;
						}
					}
					// new item
					if (!$translatedObjectTermId) {
						$response = wp_insert_term($item->name, 'product_' . $shortObjectName, array(
							'name' => $item->name,
							'description' => $item->description,
							'slug' => $slug
						));
						$this->joinTranslatedTagOrCategoryWithParent($parentObject, $item, $response['term_taxonomy_id'], $shortObjectName);
						$tempResponse['action'] = 'create child ' . $longObjectName;
					}
				} else {
					$object = get_term_by('slug', $item->slug, 'product_' . $shortObjectName);
					// item already exists
					if ($object) {
						$response = wp_update_term($object->term_id, 'product_' . $shortObjectName, array(
							'name' => $item->name,
							'description' => $item->description,
						));
						$tempResponse['action'] = 'modify parent ' . $longObjectName;
					}
					// new item
					if (!$object) {
						$response = wp_insert_term($item->name, 'product_' . $shortObjectName, array(
							'name' => $item->name,
							'description' => $item->description,
							'slug'    => $item->slug
						));
						$tempResponse['action'] = 'create parent ' . $longObjectName;
					}
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
					'term_id' => $response['term_id'],
					'term_taxonomy_id' => $response['term_taxonomy_id'],
					$longObjectName => $item->name
				));
			}
			$response = array();
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;
		$scriptTimerService->stopAndLogDuration(__FUNCTION__, __DIR__);

		wp_send_json($finalResponse, $statusCode);
	}

	private function joinTranslatedTagOrCategoryWithParent($parentObject, $item, $termTaxonomyId, $shortObjectName)
	{
		$type = $shortObjectName === 'cat' ? 'tax_product_cat' : 'tax_product_tag';

		global $wpdb;
		$table = $wpdb->dbname . '.' . $wpdb->prefix . 'icl_translations';

		// get parent trid
		$sql = "SELECT * FROM {$table} WHERE element_id = {$parentObject->term_taxonomy_id} AND language_code = 'en' AND element_type = '{$type}';";
		$result = $wpdb->get_results($sql)[0];
		$trid =  $result->trid;

		$updateQuery = "UPDATE {$table} SET language_code = '{$item->language_code}', source_language_code = 'en', trid = {$trid} WHERE element_type = '{$type}' AND element_id = {$termTaxonomyId};";
		$wpdb->get_results($updateQuery);
	}

	private function updateTranslatedTagOrCategory($termId, $termObject, $shortObjectName)
	{
		/**
		 * do wp_terms AND wp_terms_taxonomy
		 */
		$type = $shortObjectName === 'cat' ? 'product_cat' : 'product_tag';

		global $wpdb;
		$termsTable = $wpdb->prefix . 'terms';
		$termsTaxonomyTable = $wpdb->prefix . 'term_taxonomy';

		$termsResult = $wpdb->update($termsTable, ['name' => $termObject->name, 'slug' => $termObject->slug], ['term_id' => $termId]);
		$termsTaxonomyResult = $wpdb->update($termsTaxonomyTable, ['description' => $termObject->description], ['term_id' => $termId, 'taxonomy' => $type]);

		if ($termsResult === false || $termsTaxonomyResult === false) {
			return false;
		}
		return true;
	}

	/**
	 * Upload handler: product variations
	 */
	private function handleProductVariations($dataArray)
	{
		$scriptTimerService = new ScriptTimerService();
		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$finalResponse = array();

		// get all current attributes
		$tempCurrentAttributes = wc_get_attribute_taxonomies();
		$currentAttributesArray = array();
		foreach ($tempCurrentAttributes as $attribute) {
			$currentAttributesArray['pa_' . $attribute->attribute_name] = array(
				'id' => $attribute->attribute_id,
				'slug' => $attribute->attribute_name,
			);
		}

		// get all terms per current attribute
		$allTerms = array();
		foreach ($currentAttributesArray as $attrKey => $attrValue) {
			$attributeTerms = $this->getAllTermsForSlug($attrValue['slug']);
			if (empty($attributeTerms)) continue;
			foreach ($attributeTerms as $term) {
				array_push($allTerms, $term->name);
			}
		}

		$currentAttributes = $this->getFormattedArrayOfExistingItems($endpoint, 'attributes');

		// Products loop
		foreach ($dataArray as $item) {

			// Variations loop
			foreach ($item->variations as $variation) {
				// is it a parent variation?
				$isParentVariation = empty($variation->lang);
				$productId = wc_get_product_id_by_sku($item->parent_product_sku);

				$parentVariationId = null;

				$variation_sku = $variation->sku;

				$variationId = wc_get_product_id_by_sku($variation->sku);
				if ($isParentVariation) {
					// set to productID for parent product, or if translation, for child product
					$isNewVariation = ($variationId === 0 || $variationId === null);
				} else {
					// if it's a translation, get childProductID for endpoint, not parentProductId
					$productId = apply_filters('wpml_object_id', $productId, 'post', TRUE, $variation->lang);

					$parentVariationId = $variationId;
					if ($parentVariationId === null || $parentVariationId === 0) {
						$response['status'] = 'error';
						$response['message'] = "Parent product not found (you are trying to upload a translated variation, but I can't find its default language counterpart)";
					}

					// this returns the parent ID if no child is found
					$variationId = apply_filters('wpml_object_id', $parentVariationId, 'post', TRUE, $variation->lang);

					$isNewVariation = $parentVariationId === $variationId;
					if ($isNewVariation) {
						unset($variationId);
					}
					// clear SKU for translated products to avoid 'duplicate SKU' errors
					unset($variation->sku);
					// set product as translation of the parent
					$variation->translation_of = $parentVariationId;
				}

				$endpoint = 'products/' . $productId . '/variations/';

				// Attributes loop
				// get all product terms
				$allProductTerms = [];
				foreach ($currentAttributes['slugs'] as $attributeSlug) {
					$termsPerAttribute = get_the_terms($productId, $attributeSlug);
					if (empty($termsPerAttribute)) continue;
					$allProductTerms = array_merge($allProductTerms, array_column($termsPerAttribute, 'name'));
				}

				if (isset($variation->attributes) && $variation->attributes != null) {
					foreach ($variation->attributes as $variationAttribute) {
						$attributeLookup = $this->getAttributeIdBySlug($variationAttribute->slug, $currentAttributes['attributes']);
						if ($attributeLookup['result'] == 'error') {
							$response['status'] = 'error';
							$response['message'] = "Attribute {$attributeLookup['slug']} not found";
						} else if (!in_array($variationAttribute->option, $allTerms)) {
							$response['status'] = 'error';
							$response['message'] = "Attribute term {$variationAttribute->option} not found";
						} else if (!in_array($variationAttribute->option, $allProductTerms)) {
							$response['status'] = 'error';
							$response['message'] = "Attribute term {$variationAttribute->option} is not defined as a term for product SKU {$item->parent_product_sku}";
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
						$response['error_detail'] = json_decode($th->getResponse()->getBody(), true) ?? null;
					}
				}
				if ($response['status'] == 'success') {
					array_push($finalResponse, array(
						'status' => $response['status'],
						'action' => $response['action'],
						'id' => $response['id'],
						'product' => $variation_sku,
						'lang' => $variation->lang
					));
				} else {
					array_push($finalResponse, array(
						'status' => $response['status'],
						'message' => $response['message'],
						'product' => $variation_sku,
						'error_detail' => $response['error_detail'] ?? '',
						'lang' => $variation->lang
					));
				}
				$response = array();
			}
		}
		$statusCode = !in_array('error', array_column($finalResponse, 'status')) ? 200 : 207;
		$scriptTimerService->stopAndLogDuration(__FUNCTION__, __DIR__);

		wp_send_json($finalResponse, $statusCode);
	}

	/**
	 * Upload handler: attributes
	 */
	private function handleAttributes($dataArray)
	{
		$scriptTimerService = new ScriptTimerService();
		$finalResponse = array();

		$api = $this->apiClient();
		$endpoint = 'products/attributes/';
		$currentAttributes = wc_get_attribute_taxonomies();

		$currentAttributesArray = array();
		foreach ($currentAttributes as $attribute) {
			$currentAttributesArray[$attribute->attribute_name] = array(
				'id' => $attribute->attribute_id,
				'slug' => $attribute->attribute_name,
				'name' => $attribute->attribute_label
			);
		}

		foreach ($dataArray as $item) {
			$isParentAttribute = empty($item->english_slug);

			try {
				if (key_exists($item->slug, $currentAttributesArray)) {
					if ($isParentAttribute) {
						$response = (array) $api->put($endpoint . $currentAttributesArray[$item->slug]['id'], $item);
					} else {
						$this->addOrUpdateTranslatedAttribute($item, $currentAttributesArray[$item->english_slug]['name']);
					}
					$tempResponse['action'] = 'modify attribute';
				} else {
					if ($isParentAttribute) {
						$response = (array) $api->post($endpoint, $item);
					} else {
						$this->addOrUpdateTranslatedAttribute($item, $currentAttributesArray[$item->english_slug]['name']);
					}
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
		$scriptTimerService->stopAndLogDuration(__FUNCTION__, __DIR__);

		wp_send_json($finalResponse, $statusCode);
	}

	private function addOrUpdateTranslatedAttribute($attribute, $parentAttributeName)
	{
		$languageCode = $attribute->language_code;
		$translatedAttributeName = $attribute->name;

		global $wpdb;

		// get parent string results
		$singularSelectStringQuery = "SELECT `id`, `name`, `value` FROM `{$wpdb->prefix}icl_strings` WHERE `name` = 'taxonomy singular name: " . $parentAttributeName . "' AND `value` = '" . $parentAttributeName . "' AND `context` = 'WordPress' LIMIT 1";
		$pluralSelectStringQuery = "SELECT `id`, `name`, `value` FROM `{$wpdb->prefix}icl_strings` WHERE `name` = 'taxonomy general name: Product " . $parentAttributeName . "' AND `value` = 'Product " . $parentAttributeName . "' AND `context` = 'WordPress' LIMIT 1";
		$singularStringResult = $wpdb->get_results($singularSelectStringQuery);
		$pluralStringResult = $wpdb->get_results($pluralSelectStringQuery);

		// if parent exists
		if (count($singularStringResult) > 0) {
			// get specific language results
			$singularTranslatedStringResult = $wpdb->get_results("SELECT `id`, `string_id`, `language`, `value` FROM `{$wpdb->prefix}icl_string_translations` WHERE `string_id` = '" . $singularStringResult[0]->id . "' AND `language` = '" . $languageCode . "' LIMIT 1");
			$pluralTranslatedStringResult = $wpdb->get_results("SELECT `id`, `string_id`, `language`, `value` FROM `{$wpdb->prefix}icl_string_translations` WHERE `string_id` = '" . $pluralStringResult[0]->id . "' AND `language` = '" . $languageCode . "' LIMIT 1");

			if (count($singularTranslatedStringResult) == 0) {
				$wpdb->query($wpdb->prepare("INSERT INTO `{$wpdb->prefix}icl_string_translations` ( `string_id`, `language`, `status`, `value` ) VALUES ( %d, %s, %d, %s )", $singularStringResult[0]->id, $languageCode, 10, $translatedAttributeName));
				$wpdb->query($wpdb->prepare("INSERT INTO `{$wpdb->prefix}icl_string_translations` ( `string_id`, `language`, `status`, `value` ) VALUES ( %d, %s, %d, %s )", $pluralStringResult[0]->id, $languageCode, 10, $translatedAttributeName));
			} else {
				if ($singularTranslatedStringResult[0]->value != $translatedAttributeName) {
					$wpdb->query($wpdb->prepare("UPDATE `{$wpdb->prefix}icl_string_translations` SET `value` = %s WHERE `id` = %d", $translatedAttributeName, $singularTranslatedStringResult[0]->id));
					$wpdb->query($wpdb->prepare("UPDATE `{$wpdb->prefix}icl_string_translations` SET `value` = %s WHERE `id` = %d", $translatedAttributeName, $pluralTranslatedStringResult[0]->id));
				}
			}
		}
	}

	/**
	 * Upload handler: attribute terms
	 */
	private function handleAttributeTerms($dataArray)
	{
		$scriptTimerService = new ScriptTimerService();
		$finalResponse = array();

		// get all current attributes
		$currentAttributes = wc_get_attribute_taxonomies();
		$currentAttributesArray = array();
		foreach ($currentAttributes as $attribute) {
			$currentAttributesArray['pa_' . $attribute->attribute_name] = array(
				'id' => $attribute->attribute_id,
				'slug' => $attribute->attribute_name,
			);
		}

		// get all terms per current attribute
		$allTerms = array();
		foreach ($currentAttributesArray as $attrKey => $attrValue) {
			$attributeTerms = $this->getAllTermsForSlug($attrValue['slug']);

			if (empty($attributeTerms)) continue;

			$tempArray = array();
			foreach ($attributeTerms as $term) {
				$tempArray[$term->slug] = array(
					'attributeId' => $attrValue['id'],
					'attributeSlug' => $attrKey,
					'id' => $term->term_taxonomy_id,
				);
			}
			$allTerms[$attrKey] = $tempArray;
		}

		$api = $this->apiClient();
		foreach ($dataArray as $item) {
			$hasParentLanguage = !empty($item->english_slug);
			try {
				$attrName = 'pa_' . strtolower($item->attribute);
				if (key_exists($attrName, $currentAttributesArray)) {
					// get id of attribute, regardless of whether the term is found
					foreach ($currentAttributes as $attribute) {
						if ($attribute->attribute_name == strtolower($item->attribute)) {
							$attrId = $attribute->attribute_id;
							break;
						}
					}
					$foundTermId = $this->getExistingSlugId($attrName, $item->slug);
					$foundParentTermId = $this->getExistingSlugId($attrName, $item->english_slug);
					if (!$foundTermId) { //term doesn't exist
						$endpoint = 'products/attributes/' . $attrId . '/terms';
						$tempResponse['action'] = 'create term';
						$response = (array) $api->post($endpoint, $item);
						if ($item->menu_order > 0) $this->updateTermOrder($response['id'], $item->menu_order);
					} else { // term exists
						// for a child/translation, the WC endpoint it will edit the parent, not the translation.  This causes problems
						// if item is a child/translation - do the edit in a different way
						if ($hasParentLanguage === true) { // if it's a translated/childItem, get the parent's details to link to parent
							$this->updateExistingTranslatedTerm($foundTermId, $item);
							$response['id'] = intval($foundTermId);
						} else {
							$endpoint = 'products/attributes/' . $attrId . '/terms/' . $foundTermId;
							$response = (array) $api->put($endpoint, $item);
							if ($item->menu_order > 0) $this->updateTermOrder($response['id'], $item->menu_order);
						}
						$tempResponse['action'] = 'modify term';
					}
					if ($hasParentLanguage === true) { // if it's a translated/childItem, get the parent's details to link to parent
						$this->joinTranslatedTermWithParent($attrName, $foundParentTermId, $this->getExistingSlugId($attrName, $item->slug), $item->language_code);
					}
					$tempResponse['status'] = 'success';
					$tempResponse['id'] = $response['id'];
					// set the Haru term meta color
					if ($item->extra !== '' && $item->extra->hex_code !== '') $this->setColor($response['id'], $item->extra->hex_code);
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
		$scriptTimerService->stopAndLogDuration(__FUNCTION__, __DIR__);

		wp_send_json($finalResponse, $statusCode);
	}

	private function setColor($id, $hexCode)
	{
		global $wpdb;
		// check if line exists
		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}haru_termmeta WHERE haru_term_id = $id AND meta_key = 'product_attribute_color';");
		if (empty($results)) {
			$wpdb->insert(
				$wpdb->prefix . 'haru_termmeta',
				[
					'term_id' => 0,
					'haru_term_id' => $id,
					'meta_key' => 'product_attribute_color',
					'meta_value' => $hexCode
				]
			);
			return true;
		}
		$wpdb->update(
			$wpdb->prefix . 'haru_termmeta',
			[
				'meta_value' => $hexCode
			],
			[
				'haru_term_id' => $id,
				'meta_key' => 'product_attribute_color',
			]
		);
		return true;
	}

	private function updateTermOrder($termId, $order)
	{
		global $wpdb;
		$termMetaTable = $wpdb->prefix . 'termmeta';

		// delete all term order lines
		$condition = $wpdb->esc_like('order') . '%';
		$sql  = $wpdb->prepare("SELECT meta_id FROM $termMetaTable WHERE term_id = $termId AND meta_key LIKE %s;", $condition);
		$currentTermsOrderResult = $wpdb->get_results($sql);

		foreach ($currentTermsOrderResult as $result) {
			$wpdb->delete($termMetaTable, ['meta_id' => $result->meta_id]);
		}

		// create a proper one
		$wpdb->insert($termMetaTable, ['meta_key' => 'order', 'term_id' => $termId, 'meta_value' => $order]);
	}

	private function updateExistingTranslatedTerm($termId, $termItem)
	{
		global $wpdb;
		$termsTable = $wpdb->prefix . 'terms';
		$termsTaxonomyTable = $wpdb->prefix . 'term_taxonomy';

		$termsResult = $wpdb->update($termsTable, ['name' => $termItem->name, 'slug' => $termItem->slug], ['term_id' => $termId]);
		$termsTaxonomyResult = $wpdb->update($termsTaxonomyTable, ['description' => $termItem->description], ['term_id' => $termId]);

		if ($termsResult === false || $termsTaxonomyResult === false) {
			return false;
		}
		return true;
	}

	private function getExistingSlugId($attrName, $slug, $name = null)
	{
		global $wpdb;
		$table = $wpdb->dbname . '.' . $wpdb->prefix . 'term_taxonomy';
		$joinTable = $wpdb->dbname . '.' . $wpdb->prefix . 'terms';

		// get parent trid
		$sql = "SELECT {$table}.term_taxonomy_id, {$table}.term_id FROM {$table} INNER JOIN {$joinTable} on {$table}.term_id = {$joinTable}.term_id WHERE taxonomy = '{$attrName}' AND slug = '{$slug}'";
		if (is_null($name)) {
			$sql .= ";";
		} else {
			$sql .= " AND name = '{$name}';";
		}
		$result = $wpdb->get_results($sql);

		if (empty($result)) {
			return false;
		}

		return $result[0]->term_id;
	}

	private function getAllTermsForSlug($slug)
	{
		global $wpdb;
		$table = $wpdb->dbname . '.' . $wpdb->prefix . 'term_taxonomy';
		$joinTable = $wpdb->dbname . '.' . $wpdb->prefix . 'terms';

		// get parent trid
		$sql = "SELECT {$table}.term_taxonomy_id, {$table}.term_id, {$table}.taxonomy, {$joinTable}.name, {$joinTable}.slug FROM {$table} INNER JOIN {$joinTable} on {$table}.term_id = {$joinTable}.term_id WHERE taxonomy = 'pa_{$slug}';";
		return $wpdb->get_results($sql);
	}

	private function joinTranslatedTermWithParent($type, $parentTermId, $childId, $languageCode)
	{
		global $wpdb;
		$table = $wpdb->dbname . '.' . $wpdb->prefix . 'icl_translations';
		$elementType = "tax_{$type}";
		// get parent trid
		$sql = "SELECT * FROM {$table} WHERE element_id = {$parentTermId} AND language_code = 'en' AND element_type = '{$elementType}';";
		$result = $wpdb->get_results($sql)[0];
		$trid = $result->trid;

		$updateQuery = "UPDATE {$table} SET language_code = '{$languageCode}', source_language_code = 'en', trid = {$trid} WHERE element_type = '{$elementType}' AND element_id = {$childId};";
		$wpdb->get_results($updateQuery);
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
		$api = $this->apiClient();
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

		$api = $this->apiClient();
		wp_send_json($api->get($endpoint), 200);
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

		$api = $this->apiClient();
		wp_send_json($api->get($endpoint), 200);
	}

	/**
	 * Show one variation
	 */
	public function showVariations()
	{
		if (isset($_POST['variation_id']) && $_POST['variation_id'] != '') {
			$variationId = $_POST['variation_id'];
			$variation = wc_get_product($variationId);
			$product = wc_get_product($variation->get_parent_id());
			$endpoint = 'products/' . $product->get_id() . "/variations/" . $_POST['variation_id'];
		} else {
			print('<pre>' . __FILE__ . ':' . __LINE__ . PHP_EOL . print_r(['result' => 'no variation found'], true) . '</pre>');
			die();
		}

		$api = $this->apiClient();
		wp_send_json($api->get($endpoint), 200);
	}

	/**
	 * Disable image downscaling - this add the word "scaled" to images - return false to disable
	 */
	public function disableImageDownscaling()
	{
		return false;
	}
}
