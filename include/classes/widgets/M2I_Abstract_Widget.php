<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No script kiddies please!' );
}

/**
 * M2I_Abstract_Widget
 *
 * 
 * @since 0.4.5
 */
abstract class M2I_Abstract_Widget extends WP_Widget {

	/** @var string Name of the shortcode be used in widget */
	protected $shortcode_name;

	/**
	 *  @var string Name of the widget 
	 *  @since 1.0.6
	 */
	public $name;

	/**
	 * Implement compatibility with WP versions older then 4.4
	 *
	 * @param string $field_name
	 * @return string
	 * 
	 * @see parent::get_field_name
	 * @since 1.1
	 */
	public function get_field_name( $field_name ) {
		global $wp_version;

		if ( version_compare( $wp_version, '4.4.0', '<' ) ) {
			if ( false === $pos = strpos( $field_name, '[' ) ) {
				return 'widget-' . $this->id_base . '[' . $this->number . '][' . $field_name . ']';
			} else {
				return 'widget-' . $this->id_base . '[' . $this->number . '][' . substr_replace( $field_name, '][', $pos, strlen( '[' ) );
			}
		} else {
			return parent::get_field_name( $field_name );
		}
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 * @since 1.1 => Moved here from other children classes
	 */
	public function widget( $args, $instance ) {
		$shortcode_res = do_shortcode( $this->get_generated_shortcode( $instance ) );

		if ( ! empty( $shortcode_res ) ) {
			echo $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			}

			echo $shortcode_res;
			echo $args['after_widget'];
		}
	}

	/**
	 *  Renders number input 
	 * 
	 *  @param string $key Key used in the Widget
	 *  @param string|int $value Value for the input
	 *  @param string $desc Description for label
	 *  @param int|string $min 1 default
	 *  @param int|string $max 1000 default
	 */
	protected function render_number_input( $key, $value, $desc, $min = 1, $max = 1000 ) {
		$id = esc_attr( $this->get_field_id( $key ) );
		$name = esc_attr( $this->get_field_name( $key ) );

		print('<p>' );
		printf( '<label for="%s">%s </label>', $id, esc_attr( $desc ) );
		printf( '<input id="%s" type="number" name="%s" value="%s" min="%d" max="%d">', $id, $name, $value, $min, $max );
		print('</p>' );
	}

	/**
	 *  Renders text input 
	 * 
	 *  @param string $key Key used in the Widget
	 *  @param string $value Value for the input
	 *  @param string $desc Description for label
	 *  @param string $class 'widefat' default
	 */
	protected function render_text_input( $key, $value, $desc, $class = 'widefat' ) {
		$id = esc_attr( $this->get_field_id( $key ) );
		$name = esc_attr( $this->get_field_name( $key ) );

		print('<p>' );
		printf( '<label for="%s">%s </label>', $id, esc_attr( $desc ) );
		printf( '<input id="%s" class="%s" type="text" name="%s" value="%s">', $id, $class, $name, $value );
		print('</p>' );
	}

	/**
	 *  Renders select of two types
	 * 
	 *  @param string $key Key used in the Widget
	 *  @param array $data Data for creating select <b>array( id => name ... )</b>
	 *  @param array $value Selected values/value for the select <b>array( id ... )</b>
	 *  @param string $desc Description for label
	 *  @param bool|int|string $size Size for multiple type or false
	 *  @param string $class 'widefat' default
	 */
	protected function render_select( $key, array $data, array $value, $desc, $size = false, $class = 'widefat' ) {
		$multiple = (bool) $size;
		$key .= ($multiple ? '[]' : '');
		$id = esc_attr( $this->get_field_id( $key ) );
		$name = esc_attr( $this->get_field_name( $key ) );

		print('<p>' );
		printf( '<label for="%s">%s </label>', $id, esc_attr( $desc ) );
		printf( '<select %s class="%s" name="%s" id="%s" %s>', $size ? 'size="' . $size . '"' : '', $class, $name, $id, $multiple ? 'multiple' : ''  );
		foreach ( $data as $id => $item_value ) {
			printf( '<option value="%s" %s>%s</option>', $id, in_array( $id, $value ) ? 'selected' : '', $item_value );
		}
		print('</select>' );
		print('</p>' );
	}

	/**
	 *  Renders checkbox input
	 *
	 *  @param string $key Key used in the Widget
	 *  @param string $value Value for the input
	 *  @param string $desc Description for label
	 *  @param string $class 'widefat' default
	 */
	protected function render_checkbox_input( $key, $value, $desc, $class = 'widefat' ) {
		$id = esc_attr( $this->get_field_id( $key ) );
		$name = esc_attr( $this->get_field_name( $key ) );

		print('<p>' );
		printf( '<input id="%s" class="%s" type="checkbox" name="%s" %s>', $id, $class, $name, $value );
		printf( '<label for="%s">%s </label>', $id, esc_attr( $desc ) );
		print('</p>' );
	}

	/**
	 * Generate a shortcode by <b>$instance</b> (data from "<form>" tag etc...)
	 * 
	 * @param array $instance 
	 * @return string
	 * @since 1.0.6
	 */
	abstract function get_generated_shortcode( array $instance );
}
