<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I_Content object trying to paste all styles and scripts from Magento 2 to your frontend in the end of "<head>" tag and "<footer>" tag, <br>
 * add automaticaly "<header>" and "<footer>" to the current WordPress theme.
 *
 * @property string $template
 * @property int $priority
 */
class M2I_Content {

	/**
	 *  @var string Full path to current template, which would be defined, if 'mage_auto_adding' is on
	 *  @since 1.0.1
	 */
	protected $template = '';

	/** @var int Primary priority */
	protected $priority = 100;

	/** Construct init. for wp_head actions */
	final function __construct() {

		if ( m2i_is_success() ) {
			add_action( 'wp_head', array($this, 'add_styles_css'), $this->priority );
			add_action( $this->get_scripts_action(), array($this, 'add_scripts_js_head'), $this->priority );
			add_action( 'wp_footer', array($this, 'add_scripts_js_footer'), $this->priority + 1 );

			if ( m2i_get_options()['mage_auto_adding'] == 'on' ) {
				add_action( 'template_include', array($this, 'template_override'), $this->priority );
			}
		}
	}

	/** Callback for pasting css */
	function add_styles_css() {
		global $m2i_options;

		if ( $m2i_options['mage_styles_flag'] == 'on' ) {
			echo m2i_get_links_css_tags();
		}
		
		wp_enqueue_style( 'm2i-templates', M2I_URL_CSS . '/templates.css', array(), M2I_PLUGIN_VERSION );
	}

	/**  Callback for pasting scripts to head tag */
	function add_scripts_js_head() {
		global $m2i_options;

		if ( $m2i_options['mage_scripts_head_flag'] == 'on' && $m2i_options['mage_js_flag'] == 'on' ) {
			echo m2i_get_scripts_from_head();
		}
	}

	/** Callback for pasting scripts to footer */
	function add_scripts_js_footer() {
		global $m2i_options;

		if ( $m2i_options['mage_scripts_body_flag'] == 'on' && $m2i_options['mage_js_flag'] == 'on' ) {
			echo m2i_get_scripts_from_body();
		}
	}

	/**
	 *  Hook for making all WordPress pages through template.php 
	 *  
	 *  @return string
	 *  @since 1.0.1
	 */
	function template_override( $template ) {
		$this->template = $template;
		return M2I_PATH_PHP . '/template.php';
	}

	/**
	 * @return string Name of the action, which will be used for outputting Magento2 scripts
	 * @since 1.1
	 */
	function get_scripts_action() {
		foreach ( get_option( 'sidebars_widgets' ) as $sidebar ) {
			if ( is_array( $sidebar ) && strpos( implode( $sidebar ), 'm2i_cart_widget' ) !== false ) {
				return 'wp_head';
			}
		}

		return empty( m2i_get_options()['mage_move_scripts_to_footer_flag'] ) ? 'wp_head' : 'wp_footer';
	}

	/**
	 * Magic access for protected properties 
	 * 
	 * @return mixed
	 * @since 1.1
	 */
	function __get( $name ) {
		if ( property_exists( $this, $name ) && (new ReflectionProperty( $this, $name ) )->isProtected() ) {
			return $this->{$name};
		}

		return null;
	}

}

/**
 * @global M2I_Content
 * @since 1.1
 */
global $m2i_content;

$m2i_content = new M2I_Content;

/**
 * @since 1.1
 * @return M2I_Content
 */
function m2i_get_content_obj() {
	global $m2i_content;
	return $m2i_content;
}
