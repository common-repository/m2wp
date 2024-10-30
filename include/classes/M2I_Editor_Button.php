<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I_Editor_Button is used for adding button near WP content editor <br>
 * for futher inserting selected shortcode there
 * 
 * @since 1.0.6
 */
class M2I_Editor_Button {

	/** Added all needed hooks for the M2I_Editor_Button */
	function __construct() {
		add_action( 'load-post-new.php', 'M2I_External::launch' );
		add_action( 'load-post.php', 'M2I_External::launch' );
		add_action( 'media_buttons', array($this, 'add') );
		add_action( 'wp_ajax_m2i_get_shortcode', array($this, 'm2i_get_shortcode') );
	}

	/**
	 * Function for adding shortcode button
	 */
	function add( $editor_id = 'content' ) {
		static $instance = 0;

		if ( M2I_External::was_launched() ) {

			wp_enqueue_script( 'm2i_editor', M2I_URL_JS . '/admin_editor.js', array('jquery-ui-tabs'), M2I_PLUGIN_VERSION, true );
			wp_enqueue_style( 'm2i_editor', M2I_URL_CSS . '/admin_editor.css', array(), M2I_PLUGIN_VERSION, 'all' );

			$img = '<span class="dashicons dashicons-products"></span> ';
			$id_thickbox_content = 'm2i-shortcodes-options-modal';

			if ( ! $instance ) {
				add_thickbox();
				$this->shortcodes_options_modal( $id_thickbox_content,
                    array(
                        'M2I_Product_Widget',
                        'M2I_Block_Widget',
                        'M2I_Slider_Widget',
                        'M2I_Cart_Widget'
                    )
                );
				$id_attribute = ' id="m2i-insert-product-slider"';
			}

			printf( '<a title="%s" href="#TB_inline&inlineId=%s&width=500&height=500" %s class="button add_media thickbox m2i-add-shortcode" data-editor="%s">%s</a>',
                __( 'Magento 2 Shortcodes', 'm2wp' ),
                $id_thickbox_content,
                $id_attribute,
                esc_attr( $editor_id ),
                $img . __( 'Add Magento 2 Shortcode', 'm2wp' )
			);
		}
	}

	/**
	 * Output content with widgets options (options for shortcodes) as modal tabs.
	 * 
	 * @param string|int $id Id for the modal div.
	 * @param array $widgets_classes Array of widgets classes names. <br> It will be used for dynamic creation of options panel for each class (shortcode).
	 */
	function shortcodes_options_modal( $id, array $widgets_classes ) {
		printf( '<div id="%s" style="display:none;">', $id );

		$widgets = array_filter( array_map( function($class) {
				return is_a( $class, 'M2I_Abstract_Widget', true ) ? (new $class) : null;
			}, $widgets_classes ), function($v) {
			return ! is_null( $v );
		} );

		print('<div id="m2i-modal-tabs"><ul>' );
		foreach ( $widgets as $widget )
			printf( '<li><a href="#modal-tabs-%s">%s</a></li>', $this->get_spec_name( $widget ), $widget->name );
		print('</ul>' );
		foreach ( $widgets as $widget )
			printf( '<div id="modal-tabs-%s" data-class="%s">%s</div>', $this->get_spec_name( $widget ), get_class( $widget ), $this->get_widget_options( $widget ) );
		print('</div>' );
		?>
		<div id="m2i-modal-footer">
			<button type="button" class="button button-primary button-large"><?php _e( 'Insert shortcode', 'm2wp' ); ?></button>
		</div>
		<script type="text/javascript">var m2i_shortcodes_options_modal = "<?php echo $id; ?>";</script>
		<?php
		print('</div>' );
	}

	/**
	 * @param M2I_Abstract_Widget $widget
	 * @return string HTML content without <b>title</b> input option
	 */
	function get_widget_options( M2I_Abstract_Widget $widget ) {
		ob_start();
		$widget->form( array() );
		return preg_replace( '#<p>\s*<label[^>]+--title[^>]+>[^<]*</label>\s*<input[^<]*>\s*</p>#m', '', ob_get_clean() );
	}

	/**
	 * @param M2I_Abstract_Widget $widget
	 * @return string
	 */
	function get_spec_name( M2I_Abstract_Widget $widget ) {
		return strtolower( explode( '_', get_class( $widget ) )[1] );
	}

	function m2i_get_shortcode() {
		$widget_opts = array();

		foreach ( $_POST as $key => $val ) {
			if ( strpos( $key, 'widget-m2i' ) !== false ) {
				$widget_opts = $val;
				break;
			}
		}

		if ( ! empty( $_POST['class'] ) && class_exists( $class_name = $_POST['class'] ) ) {
			/* @var $widget M2I_Abstract_Widget */
			$widget = new $class_name;
			$simulate_instance = array();

			foreach ( $widget_opts as $arr ) {
				$key = key( $arr );
				$val = current( $arr );
				if ( isset( $simulate_instance[$key] ) ) {
					if ( ! is_array( $simulate_instance[$key] ) )
						$simulate_instance[$key] = array($simulate_instance[$key]);
					$simulate_instance[$key][] = $val[0];
				} else {
					$simulate_instance[$key] = $val;
				}
			}

			wp_die( $widget->get_generated_shortcode( $simulate_instance ) );
		}

		wp_die( 0 );
	}

}

new M2I_Editor_Button;
