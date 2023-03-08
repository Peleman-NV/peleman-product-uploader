<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes;

class Upload_Product
{
    private function handleProducts($dataArray)
    {
        $endpoint = 'products/';
        $currentAttributes = $this->getFormattedArrayOfExistingItems('products/attributes/', 'attributes');
        $finalResponse = array();
        $response = array();

        foreach ($dataArray as $item) {
            $item->reviews_allowed = 0; // set reviews to false
            $isParentProduct = empty($item->lang); // parent or translation?
            $productId = wc_get_product_id_by_sku($item->sku);
            $isNewProduct = ($productId === 0 || $productId === null);
            $childProductId = null;
            // save the sku for the response 
            $response_sku = $item->sku;

            if (!$isParentProduct) {
                if ($productId === null || $productId === 0) {
                    $response['status'] = 'error';
                    $response['message'] = "Parent product not found (you are trying to upload a translated product, but I can't find its default language counterpart)";
                }
                // get the child's product ID
                $childProductId = apply_filters('wpml_object_id', $productId, 'post', false, $item->lang);
                $isNewProduct = ($childProductId === 0 || $childProductId === null); // if child, does the translatedProductId exist?
                if ($childProductId !== null) $productId = $childProductId; // if child exists, work with it
                unset($item->sku); // clear SKU to avoid 'duplicate SKU' errors
                $item->translation_of = $productId; // set product as translation of the parent
            }

            // get id's for all categories, tags, attributes, and images.
            if (isset($item->categories) && $item->categories != null) {
                foreach ($item->categories as $category) {
                    if (!is_int($category->slug)) {
                        //check return value validity first to avoid error log clutter

                        $category->id = get_term_by('slug', $category->slug, 'product_cat')->term_id;
                        error_log("category id: " . $category->id);
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
                        //check return value validity first to avoid error log clutter
                        $tag->id = get_term_by('slug', $tag->slug, 'product_tag')->term_id;
                        error_log("tag id: " . $tag->id);
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
                    $api = $this->apiClient();
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
                    error_log((string)$th);
                    $response['status'] = 'error';
                    $response['message'] = $th->getMessage();
                    $response['error_detail'] = $item ?? null;
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

        wp_send_json($finalResponse, 200);
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
     * Given an array of SKU's, it returns an array of product Id's
     *
     * @param array $skuArray
     * @return array
     */
    private function get_product_ids_for_sku_array($skuArray)
    {
        $productIdArray = [];
        foreach ($skuArray as $sku) {
            array_push($productIdArray, wc_get_product_id_by_sku($sku));
        }

        return $productIdArray;
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
}
