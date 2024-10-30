<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No script kiddies please!' );
}

/**
 * M2I_Cart_Widget
 *
 * 
 * @since 1.0.6
 */
class M2I_Cart_Widget extends M2I_Abstract_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$this->shortcode_name = 'm2i_cart';
		$this->name = __( 'Magento Cart', 'm2i' );

		/* if($m2i_content->get_scripts_action() == 'wp_footer'){
		  remove_action('wp_footer', array($m2i_content, 'add_scripts_js_head'), $m2i_content->priority);
		  add_action('wp_head', array($m2i_content, 'add_scripts_js_head'), $m2i_content->priority);
		  } */

		$widget_ops = array(
		    'classname' => 'm2i_cart_widget',
		    'description' => __( 'Show Cart', 'm2i' ),
		);
		parent::__construct( 'm2i_cart_widget', $this->name, $widget_ops );
	}

	/** @inheritdoc */
	public function get_generated_shortcode( array $instance ) {
		if ( ! empty( $instance['hide_if_empty'] ) ) {
			return "[{$this->shortcode_name} hide_if_empty]";
		}

		return "[{$this->shortcode_name}]";
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$values = wp_parse_args( $instance, array(
		    'title' => __( 'Title' ),
		    'hide_if_empty' => ''
		) );

		/* For the further normal work of getting blocks  */
		if ( is_ajax() ) {
			M2I_External::launch();
		}

		$this->render_text_input( 'title', $values['title'], __( 'Title' ) . ':' );
		$this->render_checkbox_input( 'hide_if_empty', $values['hide_if_empty'], __( 'Hide if cart is empty', 'm2i' ) );
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
		    'hide_if_empty' => ''
		) );

		$instance['title'] = strip_tags( $instance['title'] );
		$instance['hide_if_empty'] = empty( $new_instance['hide_if_empty'] ) ? '' : 'checked';
		return $instance;
	}

}
