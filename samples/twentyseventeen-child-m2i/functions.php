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

add_action( 'wp_enqueue_scripts', 'twentyseventeen_child_m2i_disable_conflict_media', 20 );

function twentyseventeen_child_m2i_disable_conflict_media() {
	wp_dequeue_script( 'jquery-scrollto' );
}

function twentyseventeen_child_m2i_header() {
	global $m2i_options;
	echo m2i_get_header();
	if ( ! $m2i_options['use_mage_layout_names'] ) {
		echo m2i_get_block_html( 'navigation.sections' );
	}
}

function twentyseventeen_child_m2i_footer() {
	global $m2i_options;
	echo m2i_get_footer();
	if ( ! $m2i_options['use_mage_layout_names'] ) {
		echo m2i_get_block_html( 'copyright' );
	}
}
