<?php

/** 
 * Before using this theme disable "Add header/footer automatically" option.
 * 
 * WAY 1 here is for DOM approach of integration.
 * WAY 2 (default) here is for Magento Layout Approach, where you need to add those containers/block for header (in admin panel):
 *  - header.container
 *  - page.top
 * For footer:
 *  - footer-container
 *  - copyright
 */

//* Add HTML5 markup structure
add_theme_support( 'html5', array('caption', 'comment-form', 'comment-list', 'gallery', 'search-form') );

//* Add Accessibility support
add_theme_support( 'genesis-accessibility', array('404-page', 'drop-down-menu', 'headings', 'rems', 'search-form', 'skip-links') );

//* Add viewport meta tag for mobile browsers
add_theme_support( 'genesis-responsive-viewport' );

add_action( 'genesis_header', 'genesis_child_m2i_header' );

function genesis_child_m2i_header() {
	global $m2i_options;
	echo m2i_get_header();
	if ( ! $m2i_options['use_mage_layout_names'] ) {
		echo m2i_get_block_html( 'navigation.sections' );
	}
} 

add_action( 'init', 'genesis_child_m2i_remove_not_needed_footer_hooks' );

function genesis_child_m2i_remove_not_needed_footer_hooks() {
	remove_filter( 'genesis_footer_output', 'do_shortcode', 20 );
	remove_action( 'genesis_footer', 'genesis_do_footer' );
}

add_action( 'genesis_footer', 'genesis_child_m2i_footer' );

function genesis_child_m2i_footer() {
	global $m2i_options;
	echo m2i_get_footer();
	if ( ! $m2i_options['use_mage_layout_names'] ) {
		echo m2i_get_block_html( 'copyright' );
	}
}
