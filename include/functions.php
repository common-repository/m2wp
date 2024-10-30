<?php

defined( 'ABSPATH' ) || exit;

use Symfony\Component\CssSelector\CssSelectorConverter;

require_once M2I_PATH_CLASSES . '/M2I_DOMDocument.php';
if ( ! class_exists( 'HTML5_Parser' ) ) {
	require_once M2I_PATH_PHP . DIRECTORY_SEPARATOR . M2I_EXTERNAL_DIR . DIRECTORY_SEPARATOR . 'html5' . DIRECTORY_SEPARATOR.  'Parser.php';
}

/**
 *  @global array Options setted on the settings page in the admin panel
 */
global $m2i_options;

$m2i_options = M2I_Settings::get_instance()->get_options();

/**
 *  Return true if Magento2 returns content
 *
 *  @return bool
 */
function m2i_is_success() {
	static $is_success = null;
	if ( is_null( $is_success ) || $is_success === false ) {
		$response = M2I_External::get_response();
		$is_success = (is_object( $response ) && $response instanceof Magento\Framework\App\ResponseInterface && ! empty( $response->getContent() )) ? true : false;
	}
	return $is_success;
}

/**
 * Get magento2 object manager
 *
 * @return \Magento\Framework\ObjectManagerInterface|null
 *
 * @since 1.2.7 Object manager or null on failure
 */
function m2i_get_object_manager() {
	$bootstrap = M2I_External::get_bootstrap();
	return $bootstrap ? $bootstrap->getObjectManager() : null;
}

/**
 *  An functional alias for  <i>M2I_Settings::get_options()</i>
 *
 *  @return array Options setted on the settings page in the admin panel
 */
function m2i_get_options() {
	global $m2i_options;
	return $m2i_options;
}

/**
 *  Content of default Magento2 page
 *
 *  @return string
 */
function m2i_get_content() {
	$content = wp_cache_get( 'content', 'm2i' );
	if ( ! $content ) {
		if ( m2i_is_success() ) {
			$content = M2I_External::get_response()->getContent();
		} else {
			$content = '<!DOCTYPE HTML><HTML><HEAD></HEAD><BODY></BODY></HTML>';
		}
		wp_cache_set( 'content', $content, 'm2i', M2I_CACHE_EXPIRATION );
	}
	return $content;
}

/**
 *  Header html content
 *
 *  @param bool $return_html
 *  @return mixed
 */
function m2i_get_header( $return_html = true ) {
	return _m2i_get_configurable_section( 'header', $return_html );
}

/**
 *  Footer html content
 *
 *  @param bool $return_html
 *  @return mixed
 */
function m2i_get_footer( $return_html = true ) {
	return _m2i_get_configurable_section( 'footer', $return_html );
}

/**
 * @param string $name Configurable section name
 * @param bool $return_html
 *
 * @since 1.2.5
 * @access private
 *
 * @return mixed
 */
function _m2i_get_configurable_section( $name, $return_html = true ) {
	global $m2i_options;

	$section = wp_cache_get( "configurable_section_$name", 'm2i' );

	if ( ! $section ) {
		if ( $m2i_options['use_mage_layout_names'] === 'on' ) {
			/**
			 * Filters opening tag
			 *
			 * @since 1.1.3
			 *
			 * @param string Opening html tag
			 */
			$local_html = apply_filters( "m2i_{$name}_before_mage_elements_html", "<div class=\"m2i-$name-with-mage-elements\">" );
			M2I_External::$needs_mage_translate = true;
			if ( ( $layout = M2I_External::get_layout() ) ) {
				foreach ( $m2i_options["mage_{$name}_block_name"] as $block_name ) {
					try {
						/**
						 * Filters magento element html
						 *
						 * @since 1.1.3
						 *
						 * @param string Element's html
						 * @param string $block_name Element's name in layout
						 */
						$local_html .= apply_filters(
							"m2i_{$name}_mage_element_{$block_name}_html",
							$layout->renderElement( $block_name ),
							$block_name
						);
					} catch ( OutOfBoundsException $e ) {}
				}
			}
			M2I_External::$needs_mage_translate = false;
			/**
			 * Filters closing tag
			 *
			 * @since 1.1.3
			 *
			 * @param string Closing html tag
			 * @param string $local_html HTML collected before, contains mage elements and before tag
			 */
			$local_html .= apply_filters( "m2i_{$name}_after_mage_elements_html", '</div>', $local_html );
			$section = m2i_get_dom_el( $local_html );
		} else {
			$section = m2i_get_el_by_css_selector( $m2i_options["mage_{$name}_css_selector"] );
		}

		if ( $section ) {
			foreach ( iterator_to_array( $section->getElementsByTagName( "script" ) ) as $node ) {
				$node->parentNode->removeChild( $node );
			}

			wp_cache_set( "configurable_section_$name", m2i_dom_el_to_html( $section ), 'm2i', M2I_CACHE_EXPIRATION );
		}
	}

	/* If it's going from the cache */
	if ( is_string( $section ) ) {
		$section = m2i_get_dom_el( $section );
	}

	return $return_html ? ( $m2i_options['mage_header_flag'] === 'on' ? m2i_dom_el_to_html( $section ) : '') : $section;
}

/**
 * Concatenated css links from Magento2 default page
 *
 * @return string
 */
function m2i_get_links_css_tags() {
	static $links_css_content = null;

	if ( is_null( $links_css_content ) ) {
		$html = '';
		$list = m2i_get_els_by_tag( 'link' );
		foreach ( $list as $item ) {
			if ( $item->getAttribute( 'type' ) === 'text/css' ) {
				$html .= m2i_dom_el_to_html( $item );
			}
		}
		$links_css_content = $html;
	}

	return $links_css_content;
}

/**
 *  Filters array of script blocks by "m2i_scripts_to_filter" option
 *  uses "require.js" lib, overrides require.load method
 *
 *  @todo Fix compatibility with magento 2.2.7+
 *
 *  @return string
 */
function m2i_get_scripts_filter() {
	global $m2i_options;
	$list = $m2i_options['scripts_to_filter'];
	$list = preg_replace( "/^\s*[\'\"]?/", "", $list );
	$list = preg_replace( "/[\'\"]?\s*$/", "", $list );
	$list = preg_replace( "/[\'\"]?\s*[,;]\s*[\'\"]?/", " ", $list );
	$denied_scripts = explode( " ", trim( $list ) );
	$script = '
<script type="text/javascript">
    require.load = (function(require) {
        var oldLoad = require.load;
        return function(context, moduleName, url) {';
	foreach ( $denied_scripts as $denied ) {
		$script .= '
            if (url.indexOf("' . $denied . '") > 0) {' . "
                return;
            }
            ";
	}
	$script .= '
            if (url.indexOf("jquery.js") > 0 && jQuery) {
                var node = document.createElement("script");
                node.innerHTML = "window.jQuery = window.$ = jQuery;"
                node.innerHTML += "define( \"jquery\", [], function() { return jQuery; });"
                if (require.baseElement) {
                    require.s.head.insertBefore(node, baseElement);
                } else {
                    require.s.head.appendChild(node);
                }
            } else {
                oldLoad(context, moduleName, url);
            }
    }})(require);
</script>
';
	return $script;
}

/**
 *  @return string
 */
function m2i_get_scripts_from_head() {
	$scripts = m2i_get_els_by_tag_from( 'script', 'head' );
	$html = '';

	foreach ( $scripts as $script ) {
		$script = m2i_dom_el_to_html( $script );
		$html .= $script;
		if ( version_compare( M2I_External::get_magento_version(), '2.2.7', '<' ) ) {
			if ( strpos( $script, 'requirejs/require.js' ) !== false || strpos( $script, 'requirejs/require.min.js' ) !== false ) {
				$html .= m2i_get_scripts_filter();
			}
		}
	}
	return $html;
}

/**
 *  @return string
 */
function m2i_get_scripts_from_body() {
	$scripts = m2i_get_els_by_tag_from( 'script', 'body' );
	$html = '';

	foreach ( $scripts as $script ) {
		$script = m2i_dom_el_to_html( $script );
		$html .= $script;
	}
	return $html;
}

/**
 *  Select DOM elements by tag & class
 *
 *  @param string $class_name
 *  @param string $tag_name
 *
 *  @deprecated since 1.1.3
 *  @see m2i_get_el_by_css_selector
 *
 *  @return DOMElement
 */
function m2i_get_el_by_class( $class_name = '', $tag_name = 'div' ) {
	$list = m2i_get_els_by_tag( $tag_name );
	foreach ( $list as $item ) {
		if ( $class_name == '' || strpos( $item->getAttribute( 'class' ), $class_name ) !== false ) {
			return $item;
		}
	}
	return null;
}

/**
 *  Select node by CSS selector
 *
 *  @param $css_selector
 *
 *  @since 1.1.3
 *
 *  @return DOMElement|null
 */
function m2i_get_el_by_css_selector( $css_selector ) {
	$els = m2i_get_els_by_css_selector( $css_selector );
	return $els->item( 0 );
}

/**
 *  Select nodes by CSS selector
 *
 *  @param string $css_selector CSS selector to select elements
 *
 *  @since 1.1.3
 *
 *  @return DOMNodeList
 */
function m2i_get_els_by_css_selector( $css_selector ) {
	$doc = m2i_get_parsed_html( m2i_get_content() );
	$dom_xpath = new DOMXpath( $doc );
	$converter = new CssSelectorConverter();
	$expression = $converter->toXPath( $css_selector );
	return $dom_xpath->query( $expression );
}

/**
 *  Create DOM element from html
 *
 *  @param string $html
 *  @return DOMElement|null
 */
function m2i_get_dom_el( $html ) {
	if ( empty( $html ) ) {
		return null;
	}

	$doc = m2i_get_parsed_html( $html );
	$body = $doc->getElementsByTagName( 'body' )->item( 0 );
	return $body ? $body->childNodes->item( 0 ) : null;
}

/**
 *  Select DOM elements by tag
 *
 *  @param string $tag_name
 *  @return DOMNodeList
 */
function m2i_get_els_by_tag( $tag_name ) {
	$doc = m2i_get_parsed_html( m2i_get_content() );
	$list = $doc->getElementsByTagName( $tag_name );
	return $list;
}

/**
 * Get all elements by tag name from other tag name
 *
 * @param string $by_tag
 * @param string $from_tag
 *
 * @return DOMNodeList|array Empty array on empty response
 */
function m2i_get_els_by_tag_from( $by_tag, $from_tag ) {
	$list = array();
	$from = m2i_get_els_by_tag( $from_tag );

	if ( empty( $from ) )
		return $list;
	$html = m2i_dom_el_to_html( $from->item( 0 ) );
	if ( empty( $html ) )
		return $list;

	$doc = m2i_get_parsed_html( $html );
	$list = $doc->getElementsByTagName( $by_tag );
	return $list;
}

/**
 *  DOMElement html content
 *
 *  @param DOMElement|DOMDocument $item
 *  @return string
 */
function m2i_dom_el_to_html( $item ) {
	return is_object( $item ) ? ($item instanceof DOMElement ? $item->ownerDocument->saveHTML( $item ) : $item->saveHTML( $item ) ) : '';
}

/**
 *  Get Magento 2 categories collection
 *
 *  @param bool $is_active
 *  @param bool|int $level
 *  @param bool|string $sort_by
 *  @param bool|int $page_size
 *
 *  @return array
 *
 *  @since 0.4.5
 */
function m2i_get_cat_collection( $is_active = true, $level = false, $sort_by = false, $page_size = false ) {
	if ( ! M2I_External::can_launch() )
		return array();

	$obj = M2I_External::get_bootstrap()->getObjectManager();
	$collection = $obj->get( \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class )->create();
	$collection->addAttributeToSelect( '*' );

	if ( $is_active ) {
		$collection->addIsActiveFilter();
	}

	if ( $level ) {
		$collection->addLevelFilter( $level );
	}

	if ( $sort_by ) {
		$collection->addOrderField( $sort_by );
	}

	if ( $page_size ) {
		$collection->setPageSize( $page_size );
	}

	$categories = $collection->exportToArray();

	foreach ( $categories as $key => $value ) {
		$cur_category = $obj->create( \Magento\Catalog\Model\Category::class )->load( $value['entity_id'] );
		$categories[$key]['count'] = $cur_category->getProductCount();
	}

	return $categories;
}

/**
 *  Get Magento 2 stores array: code => name
 *
 *  @return array
 */
function m2i_get_stores() {
	$res = array();
	$object_manager = m2i_get_object_manager();

	if ($object_manager) {
		$stores = $object_manager->get( \Magento\Store\Model\StoreManager::class )->getStores();
		foreach ( $stores as $store ) {
			$res[ $store->getCode() ] = $store->getName();
		}
	}

	return $res;
}

/**
 *  @return array of all available blocks from Mage layout
 */
function m2i_get_blocks() {
	$blocks = array();
	if ( ! m2i_is_success() ) {
		return $blocks;
	}

	if ( ! ( $layout = M2I_External::get_layout() ) ) {
		return $blocks;
	}

	$blocks = $layout->getAllBlocks();

	return array_keys( $blocks );
}

/**
 * @param string $block_name Block name in layout
 *
 * @since 1.1.3
 *
 * @return string Block html
 */
function m2i_get_block_html( $block_name ) {
	$html = '';

	if ( ! ( $layout = M2I_External::get_layout() ) ) {
		return $html;
	}

	$block = $layout->getBlock( $block_name );
	return $block ? $block->toHtml() : '';
}

/**
 * Get product url or its parent url if product is not visible on front individually
 *
 * @since 1.1.3
 *
 * @param \Magento\Catalog\Model\Product $product
 *
 * @return string|false Product url, false on failure
 */
function m2i_get_product_url( \Magento\Catalog\Model\Product $product ) {
	$product_url = false;
	$object_manager = m2i_get_object_manager();
	if ( $object_manager ) {
		$product_url = $product->getProductUrl( $product );
		if ( ! $product->isVisibleInSiteVisibility() ) {
			$product_parent_id_arr = $object_manager->create( Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable::class )
			                                        ->getParentIdsByChild( $product->getId() );
			if ( $product_parent_id_arr ) {
				$product_repository = $object_manager->get( Magento\Catalog\Model\ProductRepository::class );
				$product_parent     = $product_repository->getById( $product_parent_id_arr[0] );
				$product_url        = $product_parent->getProductUrl( $product_parent );
			}
		}
	}
	return $product_url;
}

/**
 * Parses html and returns DOMDocument with this html
 *
 * @since 1.2.7
 *
 * @param string $html
 *
 * @return DOMDocument
 */
function m2i_get_parsed_html( $html ) {
	global $m2i_options;

	if ( 'on' === $m2i_options['use_native_dom_document'] ) {
		$doc = new M2I_DOMDocument;
		$doc->loadHTML( $html );
	} else {
		$doc = HTML5_Parser::parse( $html );
	}

	return $doc;
}
