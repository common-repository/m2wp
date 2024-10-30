<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I_DOMDocument Used to prevent html schema errors
 * 
 * @since 1.1
 */
class M2I_DOMDocument extends DOMDocument {

	/**
	 * @inheritdoc
	 */
	function loadHTML( $source, $options = 0 ) {

		libxml_use_internal_errors( true );
		$res = parent::loadHTML( '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?>' . "\n" . $source, $options );
		libxml_clear_errors();

		return $res;
	}

}
