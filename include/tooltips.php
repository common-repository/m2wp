<?php

defined( 'ABSPATH' ) || exit;

/**
 * File for setting up the tooltips texts on settings page in admin.
 * 
 * @since 1.1.3
 */

return array(
	'm2i_mage_auto_adding' => __( 'Should we automatically try to add the Magento header/footer to your WP theme? Will not always work with all WP themes. Disable this option, if it is not working.', 'm2wp' ),
	'm2i_use_mage_layout_names' => __( 'There are 2 ways to integrate with Magento. The Magento Layout approach uses the Magento API to retrieve the XML blocks of the layout. Try the other approach (DOM approach in other tab) if this is not working, otherwise use this option please. The Layout approach is always the preferred method.', 'm2wp' ),
	'm2i_mage_launch_from_root'=> __( 'Normally Magento 2 should run from the pub directory as explained in our FAQ on our website. Magento 2 can run from root directory in development mode in case you need this. Make sure to flip this option when going in production mode.', 'm2wp' ),
	'm2i_mage_move_scripts_to_footer_flag' => __( 'Use to avoid conflicts with external JS libraries, otherwise don\'t enable this option. Currently, incompatible with our cart widget.', 'm2wp' ),
	'm2i_disable_select2' => __( 'Use to avoid conflicts with external JS libraries on admin pages. In some cases there is a conflict with select2.js library in the WP Admin and with this option you disable our inclusion of this library so that the problem will be solved.', 'm2wp' ),
	'm2i_scripts_to_filter' => __( 'This feature works only with Magento <= 2.2.6.', 'm2wp' ),
	'm2i_use_native_dom_document' => __( 'Use PHP native DOMDocument class only to parse HTML fragments, which are going from Magento. Otherwise, HTML5 parser will be used to parse HTML.', 'm2wp' ),
	'm2i_mage_header_flag' => __( 'Enable this option if you want to show the Magento header output and include this in your WordPress theme.', 'm2wp' ), /* Show header? */
	'm2i_mage_footer_flag' => __( 'Enable this option if you want to show the Magento footer output and include this in your WordPress theme.', 'm2wp' ), /* Show footer? */
	'm2i_mage_scripts_head_flag' => __( 'Should the JS files from the head tag be included? Experiment with these options when you have issues with your JS.', 'm2wp' ), /* Include JS files from the head tag? */
	'm2i_mage_scripts_body_flag' => __( 'Should the JS files that are inside the body tag be included? Experiment with these options when you have issues with your JS.', 'm2wp' ), /* Include JS files from the footer tag? */
	'm2i_mage_styles_flag' => __( 'Should any CSS files be included? You can disable this is if you want to include them manually to solve conflicts.', 'm2wp' ), /* Include CSS files? */
	'm2i_mage_js_flag' => __( 'Should any JS files be included? You can disable this is if you want to include them manually to solve conflicts.', 'm2wp' ) /* Include JS files? */,
	'm2i_auto_store_selection' => __( 'Select Magento store in the auto mode by the WP blog domain. This feature works only with Magento >= 2.3.0.' )
);