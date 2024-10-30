<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I_Widgets
 *
 * 
 * @since 0.4.5
 */
class M2I_Widgets {

	/**
	 *  @var string Path to widgets dir. 
	 * 
	 *  TODO: In the next versions replaced it with const approach as in <b>PHP 5.6</b>
	 */
	protected $path;

	/** @var array Widgets pathes stored here for futher registration */
	protected $widgets;

	/** Constructs ability to use <b>M2I</b> widgets */
	function __construct() {
		$this->path = __DIR__ . '/widgets';
		$this->widgets = glob( $this->path . "/*.php" );
		add_action( 'current_screen', array($this, 'screen') );
		add_action( 'widgets_init', array($this, 'register_widgets') );
		add_action( 'wp_enqueue_scripts', array($this, 'init_widgets_media') );
	}

	/** Registers all defined widgets */
	function register_widgets() {
		$this->widgets = array_filter( $this->widgets, function($widget) {
			include_once $widget;

			$reflect = new ReflectionClass( basename( $widget, '.php' ) );
			if ( $reflect->isAbstract() || $reflect->isInterface() ) {
				return false;
			}

			return true;
		} );

		foreach ( $this->widgets as $widget ) {
			register_widget( basename( $widget, '.php' ) );
		}
	}

	/**
	 *  Method does all needed for Widgets screen 
	 * 
	 *  @since 0.4.9
	 */
	function screen() {
		$current_screen = get_current_screen();

		if ( $current_screen->id === 'widgets' ) {
			M2I_External::launch();
		}
	}

	/**
	 * @since 1.11
	 */
	function init_widgets_media() {
		wp_enqueue_style( 'bxslider', M2I_URL . '/bxslider/css/jquery.bxslider.css', array(), M2I_PLUGIN_VERSION );
		wp_enqueue_script( 'bxslider', M2I_URL . '/bxslider/js/jquery.bxslider.min.js', array('jquery'), M2I_PLUGIN_VERSION );
	}

}

new M2I_Widgets;
