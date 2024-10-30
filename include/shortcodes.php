<?php

defined( 'ABSPATH' ) || exit;

/**
 *  add shortcode [m2i_cms_block name="name_in_layout"] to show static block
 */
add_shortcode( 'm2i_cms_block', 'm2i_get_cms_block' );

/**
 *  @since 0.3
 *
 *  @param array $attrs Shortcode attributes
 *
 *  @return string
 */
function m2i_get_cms_block( $attrs = array() ) {
	if ( ! m2i_is_success() ) {
		return '';
	}

	$attrs = shortcode_atts( array(
	    'name' => ''
	), $attrs );

	if ( ! ( $layout = M2I_External::get_layout() ) ) {
		return '';
	}

	M2I_External::$needs_mage_translate = true;
	$block = $layout->getBlock( $attrs['name'] );
	$html = $block ? $block->toHtml() : '';

	if ( ! $html ) {
		try {
			$html = $layout->renderNonCachedElement( $attrs['name'] );
		} catch ( Exception $e ) {
			/* Nothing for now */
		}
	}

	if ( ! $html ) {
		$block = $layout->createBlock( Magento\Cms\Block\Block::class )->setBlockId( $attrs['name'] );
		$html = $block ? $block->toHtml() : '';
	}

	M2I_External::$needs_mage_translate = false;
	return $html;
}

/**
 *  return array of product properties by sku number (by default) or id (if by_id == true)
 *
 *  @param string $sku_or_id
 *  @param bool $by_id
 *
 *  @return array|false
 */
function m2i_get_product( $sku_or_id, $by_id = false ) {
	if ( ! m2i_is_success() ) {
		return array();
	}

	$obj = M2I_External::get_bootstrap()->getObjectManager();
	M2I_External::$needs_mage_translate = true;

	try {
		if ( $by_id ) {
			$product = $obj->get( 'Magento\Catalog\Model\Product' )->load( $sku_or_id );
		} else {
			$product = $obj->get( 'Magento\Catalog\Model\ProductFactory' )->create()->loadByAttribute( 'sku', $sku_or_id );
		}
	} catch ( Exception $e ) {
		M2I_External::$needs_mage_translate = false;
		return false;
	}

	$data = $product->__toArray();
	M2I_External::$needs_mage_translate = false;
	return $data;
}

/**
 *  add shortcode to show slider with products from categories
 *  [m2i_category_slider cats_ids="list of dirs ids, separated by commas" dom_id="unique id on html page"
 *                       qty="quantity of products on slide" margin="between products boxes"]
 */
add_shortcode( 'm2i_category_slider', 'm2i_print_category_slider' );

/**
 * @param array $attrs Shortcode attributes
 *
 * @return string
 */
function m2i_print_category_slider( $attrs = array() ) {
	if ( ! m2i_is_success() ) {
		return '';
	}

	$attrs = shortcode_atts( array(
	    'cats_ids' => 0,
	    'dom_id'   => 'm2i-slider-' . rand(),
	    'qty'      => 3,
	    'margin'   => 5
	) , $attrs );

	if ( floatval( $attrs['margin'] ) < 1 ) {
		$attrs['margin'] = strval( 100 * floatval( $attrs['margin'] ) );
	}

	if ( strpos( $attrs['margin'], '%' ) > 0 ) {
		$attrs['margin'] = substr( $attrs['margin'], 0, strpos( $attrs['margin'], '%' ) );
	}

	M2I_External::$needs_mage_translate = true;
	$obj = M2I_External::get_bootstrap()->getObjectManager();
	/* @var $block \Magento\Catalog\Block\Product\AbstractProduct */
	$block = $obj->get( '\Magento\Catalog\Block\Product\AbstractProduct' );
	$mediaBaseUrl = M2I_External::get_base_url( \Magento\Framework\UrlInterface::URL_TYPE_MEDIA );

	ob_start();
	m2i_load_template( 'slider.php', false, array(
	    'block'        => $block,
	    'mediaBaseUrl' => $mediaBaseUrl,
	    'obj'          => $obj,
	    'attrs'        => $attrs
	) );
	M2I_External::$needs_mage_translate = false;
	return ob_get_clean();
}

/**
 *  return array of products wich belong to selected categories with selected ids
 *  returns array of products indexed by product id
 *
 *  @param array $cats_ids
 *  @return array
 */
function m2i_get_category_products( $cats_ids ) {
	if ( ! m2i_is_success() ) {
		return array();
	}

	$ids = preg_split( "%,\s*%", $cats_ids );
	$obj = M2I_External::get_bootstrap()->getObjectManager();
	$products = $obj->create( 'Magento\Catalog\Model\ResourceModel\Product\Collection' );
	//$products = $cat->getProductCollection();
	$products->addAttributeToSelect( '*' );
	$products->addCategoriesFilter( array('in' => $ids) );
	$products = $products->getItems();
	return $products;
}

/**
 *  return array of all categories
 *
 *  @return array
 */
function m2i_get_categories() {
	if ( ! m2i_is_success() ) {
		return array();
	}

	$obj = M2I_External::get_bootstrap()->getObjectManager();
	$categories = $obj->create( 'Magento\Catalog\Model\ResourceModel\Category\CollectionFactory' )->create();
	$categories->addAttributeToSelect( '*' );
	$categories = $categories->exportToArray();
	foreach ( $categories as $key => $value ) {
		$cur_category = $obj->create( 'Magento\Catalog\Model\Category' )->load( $value['entity_id'] );
		$categories[$key]['count'] = $cur_category->getProductCount();
	}
	return $categories;
}

/**
 *  add shortcode [m2i_product_block id="product id" sku="product sku"] to show single product
 *
 *  @since 1.0.6
 */
add_shortcode( 'm2i_product_block', 'm2i_get_product_block' );

/**
 * @param array $attrs Shortcode`s attributes
 *
 * @return string
 */
function m2i_get_product_block( $attrs = array() ) {
	if ( ! m2i_is_success() ) {
		return '';
	}

	if ( ! ( $layout = M2I_External::get_layout() ) ) {
		return '';
	}

	$attrs = shortcode_atts( array(
	    'id' => '',
	    'sku' => ''
	) , $attrs );

	M2I_External::$needs_mage_translate = true;
	$obj = M2I_External::get_bootstrap()->getObjectManager();
	$productRepository = $obj->create( 'Magento\Catalog\Model\ProductRepository' );

	try {
		$product = $attrs['sku'] ? $productRepository->get( $attrs['sku'] ) : $productRepository->getById( $attrs['id'] );
	} catch ( Exception $e ) {
		M2I_External::$needs_mage_translate = false;
		return '';
	}

	$mediaBaseUrl = M2I_External::get_base_url( \Magento\Framework\UrlInterface::URL_TYPE_MEDIA );
	$mediaBaseUrl = $mediaBaseUrl . 'catalog/product/';
	/* @var $block \Magento\Catalog\Block\Product\AbstractProduct */
	$block = $obj->get( '\Magento\Catalog\Block\Product\AbstractProduct' );

	ob_start();
	m2i_load_template( 'product-view.php', false, array(
	    'product'      => $product,
	    'block'        => $block,
	    'mediaBaseUrl' => $mediaBaseUrl,
	    'obj'          => $obj
	) );
	M2I_External::$needs_mage_translate = false;
	return ob_get_clean();
}

/**
 *  add shortcode [m2i_cart] to show magento2 cart
 *  use [m2i_cart hide_if_empty] if you won`t display empty cart
 *  @since 1.0.6
 */
add_shortcode( 'm2i_cart', 'm2i_get_cart' );

function m2i_get_cart( $attrs = array() ) {
	if ( ! m2i_is_success() ) {
		return '';
	}

	$attrs = shortcode_atts( array(
	    'hide_if_empty' => false
	) , $attrs );

	$cart = M2I_External::get_bootstrap()->getObjectManager()->create( 'Magento\Checkout\Model\Cart' );
	if ( $attrs['hide_if_empty'] && $cart->getItemsCount() == 0 ) {
		return '';
	}

	M2I_External::add_handle( 'checkout_cart_index' );
	return m2i_get_cms_block( array('name' => 'checkout.cart') );
}

/**
 * return array of customer data
 * @since 1.0.6
 * @return null|array
 */
function m2i_get_customer_info() {
	$customerSession = M2I_External::get_bootstrap()->getObjectManager()->create( 'Magento\Customer\Model\Session' );
	if ( ! $customerSession->isLoggedIn() ) {
		return null;
	}

	M2I_External::$needs_mage_translate = true;
	$customer = $customerSession->getCustomerData();
	$fields = array(
	    'confirmation',
	    'created_at',
	    'updated_at',
	    'created_in',
	    'dob',
	    'email',
	    'firstname',
	    'gender',
	    'group_id',
	    'lastname',
	    'middlename',
	    'prefix',
	    'store_id',
	    'suffix',
	    'taxvat',
	    'website_id',
	    'default_billing',
	    'default_shipping',
	    'addresses',
	);
	$customerInfo = array();
	foreach ( $fields as $f ) {
		$callback = 'get' . implode( '', array_map( 'ucfirst', explode( '_', $f ) ) );
		$customerInfo[$f] = call_user_func( array($customer, $callback) );
	}
	foreach ( $customerInfo['addresses'] as $k => $v ) {
		$customerInfo['addresses'][$k] = m2i_get_address_info( $v );
	}
	M2I_External::$needs_mage_translate = false;
	return $customerInfo;
}

/**
 * convert Magento2 address object into array
 *  @since 1.0.6
 *  @return array
 */
function m2i_get_address_info( $address ) {
	$fields = array(
	    'region_id',
	    'country_id',
	    'street',
	    'company',
	    'telephone',
	    'fax',
	    'postcode',
	    'city',
	    'firstname',
	    'lastname',
	    'middlename',
	    'prefix',
	    'suffix',
	    'vat_id',
	    'default_billing',
	    'default_shipping'
	);
	$addressInfo = array();
	foreach ( $fields as $f ) {
		$prefix = in_array( $f, ['default_billing', 'default_shipping'] ) ? 'is' : 'get';
		$callback = $prefix . implode( '', array_map( 'ucfirst', explode( '_', $f ) ) );
		$addressInfo[$f] = call_user_func( array($address, $callback) );
	}
	return $addressInfo;
}

/**
 * Load template from the plugin or theme (parent or child)
 * Plugin: ./include/templates/
 * Theme: ./m2i-templates/
 *
 * @param string $template_name Template name with an extension (e.g. php)
 * @param bool   $require_once  Whether to require_once or require. Default true.
 * @param array  $args          Arguments/variables to be passed to the template. Default empty array.
 *
 * @global WP_Query $wp_query
 *
 * @since 1.1.3
 */
function m2i_load_template( $template_name, $require_once = true, $args = array() ) {
	global $wp_query;

	if ( $args ) {
		foreach ( $args as $key => $value ) {
			$wp_query->set( $key, $value );
		}
	}

	if ( has_filter( 'm2i_template_name' ) ) {
		$template_name = apply_filters( 'm2i_template_name', $template_name, $require_once, $args );
	}

	M2I_External::$needs_mage_translate = true;
	if ( ! locate_template( M2I_TEMPLATES_IN_THEME_DIR . DIRECTORY_SEPARATOR . $template_name, true, $require_once ) ) {
		/*
		 * If neither the child nor parent theme have overridden the template,
		 * we load the template from the 'templates' sub-directory of the directory this file is in.
		 */
		$filename = M2I_PATH_PHP . DIRECTORY_SEPARATOR . M2I_TEMPLATES_DIR . DIRECTORY_SEPARATOR . $template_name;
		if ( file_exists( $filename ) ) {
			load_template( $filename, $require_once );
		}
	}
	M2I_External::$needs_mage_translate = false;

	if ( $args ) {
		foreach ( $args as $key => $value ) {
			unset( $wp_query->query_vars[$key] );
		}
	}
}
