<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No script kiddies please!' );
}

/**
 * M2I_Slider_Widget
 *
 * 
 * @since 0.4.5
 */
class M2I_Slider_Widget extends M2I_Abstract_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$this->shortcode_name = 'm2i_category_slider';
		$this->name = __( 'Magento Product Slider', 'm2i' );

		$widget_ops = array(
		    'classname' => 'm2i_slider_widget',
		    'description' => __( 'Show a product slider with your Magento 2 products', 'm2i' ),
		);
		parent::__construct( 'm2i_slider_widget', $this->name, $widget_ops );
	}

	/** @inheritdoc */
	public function get_generated_shortcode( array $instance ) {
		if ( ! empty( $instance['categories'] ) && ! empty( $instance['quantity'] ) ) {
			$attrs = sprintf( 'dom_id="%s" cats_ids="%s" qty="%d" margin="%d"', uniqid( 'm2i-slider-' ), implode( ',', apply_filters( 'm2i_slider_widget_categories', $instance['categories'] ) ), apply_filters( 'm2i_slider_widget_quantity', $instance['quantity'] ), apply_filters( 'm2i_slider_widget_margin', $instance['margin'] )
			);

			return "[{$this->shortcode_name} $attrs ]";
		}

		return '';
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$values = wp_parse_args( $instance, array(
		    'title' => __( 'Products', 'm2i' ),
		    'categories' => array(),
		    'quantity' => 3,
		    'margin' => 5
		) );

		/* For the further normal work of getting blocks  */
		if ( is_ajax() ) {
			M2I_External::launch();
		}

		$mage_categories = m2i_get_cat_collection( true, false, 'name' );
		foreach ( $mage_categories as $key => $cat ) {
			$mage_categories[$key] = $cat['name'] . '(' . $cat['count'] . ')';
		}

		$this->render_text_input( 'title', $values['title'], __( 'Title' ) . ':' );
		$this->render_select( 'categories', $mage_categories, $values['categories'], __( 'Categories' ) . ':', 5 );
		$this->render_number_input( 'quantity', $values['quantity'], __( 'Number of products to show', 'm2i' ) . ':' );
		$this->render_number_input( 'margin', $values['margin'], __( 'Margin between blocks in %', 'm2i' ) . ':', 0 );
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
		    'categories' => array(),
		    'quantity' => 3,
		    'margin' => 5
		) );

		$instance['title'] = strip_tags( $instance['title'] );

		array_walk( $instance['categories'], function($el) {
			return strip_tags( $el );
		} );

		$instance['quantity'] = intval( $instance['quantity'] );
		$instance['margin'] = intval( $instance['margin'] );

		return $instance;
	}

}
