<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No script kiddies please!' );
}

/**
 * M2I_Block_Widget
 *
 * 
 * @since 0.4.5
 */
class M2I_Block_Widget extends M2I_Abstract_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {

		$this->shortcode_name = 'm2i_cms_block';
		$this->name = __( 'Magento Block', 'm2i' );

		$widget_ops = array(
		    'classname' => 'm2i_block_widget',
		    'description' => __( 'Show any Magento block', 'm2i' ),
		);
		parent::__construct( 'm2i_block_widget', $this->name, $widget_ops );
	}

	/** @inheritdoc */
	public function get_generated_shortcode( array $instance ) {
		if ( ! empty( $instance['block'] ) ) {
			return "[{$this->shortcode_name}  name=\"{$instance['block']}\" ]";
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
		    'title' => __( 'Title' ),
		    'block' => ''
		) );

		/* For the further normal work of getting blocks  */
		if ( is_ajax() ) {
			M2I_External::launch();
		}

		$blocks = m2i_get_blocks();
		sort( $blocks );

		$this->render_text_input( 'title', $values['title'], __( 'Title' ) . ':' );
		$this->render_select( 'block', array_combine( $blocks, $blocks ), array($values['block']), __( 'Block', 'm2i' ) . ':' );
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
		    'block' => ''
		) );

		$instance['title'] = strip_tags( $instance['title'] );
		$instance['block'] = strip_tags( $instance['block'] );

		return $instance;
	}

}
