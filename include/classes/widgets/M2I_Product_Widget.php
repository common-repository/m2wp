<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No script kiddies please!' );
}

/**
 * M2I_Product_Widget
 *
 * 
 * @since 1.0.6
 */
class M2I_Product_Widget extends M2I_Abstract_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		add_action( 'wp_ajax_search_products', array($this, 'search_products') );

		$this->shortcode_name = 'm2i_product_block';
		$this->name = __( 'Magento Product', 'm2i' );

		$widget_ops = array(
		    'classname' => 'm2i_product_widget',
		    'description' => __( 'Show any Magento product', 'm2i' ),
		);
		parent::__construct( 'm2i_product_widget', $this->name, $widget_ops );
	}

	/** @inheritdoc */
	public function get_generated_shortcode( array $instance ) {
		return "[{$this->shortcode_name}  sku=\"{$instance['sku']}\" ]";
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		global $m2i_options;
		$values = wp_parse_args( $instance, array(
		    'title' => __( 'Title' ),
		    'sku' => ''
		) );

		/* For the further normal work of getting blocks  */
		if ( is_ajax() ) {
			M2I_External::launch();
		}

		$this->render_text_input( 'title', $values['title'], __( 'Title' ) . ':' );
		echo '<script>var m2i_search = {nonce:"' . wp_create_nonce( "m2i_search_nonce" ) . '"};</script>';
		if ( $m2i_options['disable_select2'] === 'on' ) {
			$this->render_text_input( 'sku', $values['sku'], __( 'SKU', 'm2i' ) . ':', 'widefat m2i-product-sku-input' );
		} else {
			$this->render_select( 'sku', $values['sku'] ? array($values['sku'] => $values['sku']) : array(), array($values['sku']), __( 'SKU or ID', 'm2i' ) . ':', null, 'widefat m2i-product-search' );
			echo "<script>if (typeof jQuery.m2i_create_select === 'function') jQuery.m2i_create_select();</script>";
		}
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array Instance
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = wp_parse_args(
			wp_parse_args( $new_instance, $old_instance ), array(
		    'title' => '',
		    'sku' => ''
		) );

		$instance['title'] = strip_tags( $instance['title'] );
		$instance['sku'] = strip_tags( $instance['sku'] );

		return $instance;
	}

	public function search_products() {
		if ( ! wp_verify_nonce( $_POST['security'], 'm2i_search_nonce' ) ) {
			wp_die();
		}

		$query = wp_kses_data( $_POST['data']['q'] );
		$page = isset( $_POST['data']['page'] ) ? intval( $_POST['data']['page'] ) : 1;
		$response = [];
		M2I_External::$needs_mage_translate = true;
		$obj = m2i_get_object_manager();
		if ( $obj ) {
			$products = $obj->create( Magento\Catalog\Model\ResourceModel\Product\Collection::class );
			$products->addAttributeToFilter(
				array(
					array( 'attribute' => 'entity_id', 'like' => $query . '%' ),
					array( 'attribute' => 'sku', 'like' => $query . '%' )
				)
			)->setPageSize( 30 )->load();
			$total = $products->getSize();
			$products->setPageSize( 10 )->setCurPage( $page )->load();
			$products = $products->toArray();
			$response = array( 'items' => array(), 'total_count' => $total, 'incomplete_results' => false );
			foreach ( $products as $k => $p ) {
				$response['items'][] = array( 'id' => $p['sku'], 'text' => $p['sku'] );
			}
		}
		M2I_External::$needs_mage_translate = false;
		wp_die( json_encode( $response ) );
	}

}
