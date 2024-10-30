=== Plugin Name ===
Magento 2 WordPress Integration
Contributors: yaroslav0yachmenov, ayachmenev, maximtkachuk, modernminds
Donate link: https://www.paypal.com/donate/?hosted_button_id=DXCYEM458DRU2
Tags: magento, magento2, e-commerce, ecommerce, integration, shop
Requires at least: 4.0
Tested up to: 6.0
Requires PHP: 7.3
Stable tag: 1.4.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Combine Magento 2 with the CMS capabilities of WordPress. Seamless user experience for visitors by integrating the design of Magento and WordPress.

== Description ==

Combine the powerful e-commerce solution Magento 2 with the excellent CMS capabilities of WordPress. The Magento 2 WordPress Integration Plugin integrates Magento 2 with WordPress so users will have an unified user experience. Share session/cart data, navigation menus, header, footer, products, layout elements and static blocks by using shortcodes or functions.

This plugin is not meant to replace Magento 2, instead it will allow you to create a seamless user experience for your visitors by integrating the design of Magento and WordPress.

= Features =
* Include Magento 2 blocks in your WordPress theme
* Use shortcodes to include products, cart, layout blocks, and static blocks in the WordPress editor ("Add Magento2 Shortcode" button)
* Share session and cart data between Magento 2 and WordPress
* Include product information in your WordPress editor
* Seamlessly integrate your Magento 2 and WordPress theme
* Display cart/mini cart with session data
* 4 Widgets to display static blocks, product slider, single product or cart
* Slider and Single Product templates can be overridden in current theme
* Auto adding possibility of header and footer from your Magento 2 (only if your theme is using HTML5 approach)

= Basic Usage =

You can use the following functions in your theme files:

* Get HTML content (as string) of the header: m2i_get_header()
* Get HTML content (as string) of the footer: m2i_get_footer()
* Get HTML content (as string) for CSS files: m2i_get_links_css_tags()
* Get HTML content (as string) for JS files: m2i_get_scripts_from_head() or m2i_get_scripts_from_body()
* Get HTML content (as string) of the parts (elements) of the site by CSS selector: m2i_get_els_by_css_selector($css_selector)
* Get HTML content (as string) of the part (element) of the site by CSS selector: m2i_get_el_by_css_selector($css_selector)
* Get Product Data as array: m2i_get_product($sku_or_id, $by_id = false)
* Get Categories List as array: m2i_get_cat_collection($is_active = true, $level = false, $sort_by = false, $page_size = false)
* Get Store Views List as array: m2i_get_stores()
* Get Store Views List as array: m2i_get_blocks()
* Get Customer Info as array: m2i_get_customer_info()
* Get HTML content (as string) of the CMS block : m2i_get_cms_block($attrs), where $attrs['name'] - name of the CMS block in your Magento2
* Get HTML content (as string) of the Cart: m2i_get_cart($attrs), if in $attrs is set key 'hide_if_empty' it will be hidden if empty.

You can use the following shortcodes in your WordPress editor:

* Show Block: [m2i_cms_block name="name_in_layout"]
* Show Slider: [m2i_category_slider dom_id="unique-id" cats_ids="categories_ids_separated_by_comas" qty="quantity_of_products" margin="in_number"]
* Show Single Product: [m2i_product_block id="product id"] or [m2i_product_block sku="product sku"]
* Show Cart: [m2i_cart] or [m2i_cart hide_if_empty]

You can override Slider and Single Product templates in your current theme directory
CURRENT_THEME_DIR/m2i-templates/product-view.php or slider.php

== Installation ==

You can only use this plugin if your WordPress installation is on the same server as your Magento installation. This plugin requires several Magento store files to be included using PHP, this means it needs to be on the same server as your WordPress Installation. In addition, session data is stored per server and thus only available if both platforms are installed on the same server. Nevertheless, in most setups you can use different subdomains with this plugin.

1. Download the WordPress plugin
2. Upload the contents of the zip to your plugin directory
3. Enable the plugin in your WordPress admin
4. Complete the settings in the plugin settings page

= Session sharing additional setup =

If you want to be sure about session sharing (especially when a user is logged in) between Magento and WordPress, you need to add the next code to Magento index.php file before Bootstrap line:

`
if ( isset( $_COOKIE['PHPSESSID'] ) ) {
	/* To share logged in user session with WP frontend */
	setcookie( 'PHPSESSID', $_COOKIE['PHPSESSID'], time() + 3600, '/', '.local.wordpress', 0 );
	/* To share logged in user session with WP backend */
	setcookie( 'PHPSESSID', $_COOKIE['PHPSESSID'], time() + 3600, '/wp-admin', '.local.wordpress', 0 );
}
`

= Custom replacement of translation function =

If for some reasons our new automatic replacement functionality doesn't work, you have to replace WordPress translation function in your own (it conflicts with Magento, so modified version is required). Apply the following patch to WordPress to avoid conflicts between WordPress and Magento 2:

File: WORDPRESS_ROOT/wp-includes/l10n.php

WordPress __() function is used for translation but is in conflict with Magento 2. Therefore, please find this function at around line 296 and

REPLACE

`
function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}
`

WITH

`
function __( $text, $domain = 'default' ) {
	if ( defined( 'M2I_MAGE_DIR' ) && class_exists( 'M2I_External' ) && M2I_External::$needs_mage_translate ) {
		return M2I_External::translate( func_get_args() );
	} else {
		return translate( $text, $domain );
	}
}
`

== Frequently Asked Questions ==

= How could I disable automatic translation function replacement? =

You have to add the next code snippet to your wp-config.php file:

`
define( 'M2I_DISABLE_TRANSLATION_FUNC_MODIFY', true );
`

= What should be the absolute path of my Magento directory? =

An example could be: /data/web/magento2/
It should go the main directory of your Magento 2 installation

= Does it support Magento 1? =

No, for support of Magento 1 there are several other plugins available

= Does it support Magento 2.3 or 2.4? =

Yes, since 1.3 plugin version

= What are the server requirements? =

Magento 2 and WordPress should be able to read each other location. Therefore, it does not matter if you use a subdirectory for example, as long as they are on the same server and can be accessed by the same user.

== Screenshots ==

1. General settings
2. Advanced settings
3. Magento 2 WordPress Integration

== Changelog ==

= 1.4.1 =

* Added the missed files (widgets, shortcodes)

= 1.4 =

* Added a possibility for automatic replacement of the translation function "__()"! It's enabled by default.
* Premium version with footer support and a couple of other features goes open source!
* Other minor changes, but which might affect you if you are using PHP < 7.3. Plugin will not function.
* Tested with Magento 2.4.3
* Tested with WordPress 6.0

= 1.3.1 =

* Tested with WordPress 5.2.2
* Tested with Magento 2.3.1
* Added "Auto store view selection" option for selection of the right store view dynamically according to the domain, only Magento >= 2.3.0
* Improved store view selection for the Magento >= 2.3.0
* Added touchEnabled=false setting for product slider by default, because of incompatibility with Chrome browser

= 1.3.0.1 =

* Tested with WordPress 5.1
* Added more info (via question mark) to "Exclude JS files" option

= 1.3 =

* Fixed Magento 2.3.0 integration issue
* Minor changes in php doc. of methods of widgets
* Fixed fatal error inside ajax request of M2WP Product Widget
* Fixed showing of unavailable product and add to cart message (product-view.php template updated)
* Fixed add to cart message (slider.php template updated)
* Tested with Magento 2.3.0, 2.2.7 and 2.2.6

= 1.2.7 =

* Stores list for the settings page will be fetched even when error has been occurred
* Store code showing near the store name in the stores list on the settings page, might be helpful if there are same store names
* Fixed path when magento is configured without url rewrites
* Fixed <script> tags parsing problems (DOMDocument error parsing of script code with HTML tags inside).
* Fixed fatal error with messages unserialization in Magento cookies
* Fixed issue with text/x-magento-init scripts loading for Magento 2.2.7+ but temporary disabled the functionality for preventing selected scripts from loading in the front of WordPress
* Tested with Magento 2.2.6, 2.2.7

= 1.2.6 =

* Tested with Magento 2.2.6
* M2I_CACHE_EXPIRATION constant value changed to DAY_IN_SECONDS
* Tooltips updated
* get_store method introduced in the M2I_External core class
* Adding of all M2WP JS files via header in WP backend
* Fix for the cache (preventing fatal error) when the DOMElement could not be cached with object-cache, using string with html instead

= 1.2.5.1 =

* Added new main setting "Magento 2 runs from root", because better to ask user for this.

= 1.2.5 =

* Changed base url logic to use Magento 2 native methods, not through web interface.
* Fixed infinity loading (in AJAX mode) because of index.php in path missing.
* Error reporting verbosity increased to catch more special errors during application running, etc.
* Translations domain changed to m2wp.
* Added translation error outputting not only in AJAX mode and prevented any fatal error because of not modified translation __() function.
* Fixed warnings in get_base_url_from_db in the core class when Magento2 prefix for tables in DB is specified.
* M2I_External::check_root_and_pub_mage_base_url() is deprecated.
* M2I_External::check_mage_base_url() is deprecated.
* M2I_External::add_handle() is not deprecated because is needed to use cart functionality.
* Added Throwable catches to be more oriented on PHP7+.

= 1.2.4 =

* Allowed adding of custom options to the select2 for header and footer sections.
* Customer is able to add Magento2 containers to the header and footer.
* Fixed themes samples according to the latest changes.
* Fixed add-to-cart button visual effect in the product-view.php template.

= 1.2.3 =

* Fixed Magento loading under WordPress for Magento v2.2.4.
* Added dynamic path for $_SERVER when performing M2I_External::get_converted_params(), which fixes various errors of Magento loading under WordPress.
* Removed a using of curl functions and replaced with WordPress HTTP API, so curl lib is not longer required.
* Removed not needed parts of code during M2I_External::launch().
* M2I_External::add_handle() is deprecated, because may throw fatal errors and is not safe.
* m2i_check_magento() - fixed a checking of l10n.php modification according to WordPress coding standards.

= 1.2.2 =

* Fixed fatal error on bad php version, when plugin are not able to find _m2i_is_magento_autoloader function.
* Fixed fatal, when xml/curl modules are not installed.

= 1.2.1 =

* Autoloader conflict of mage2 with other WP plugins fixed.
* was_launched can not be true if catches any Exception during launch - fixed.
* Fixed conflict with plugins, which could start sessions by session_start().
* Help tooltips updated.

= 1.2 =

* Fixed Full Page Cache issue on 2.2 Magento in production mode with internal cache.
* Improved templates loading functionality from WP side
* Added new WP filters to make plugin more flexible: "m2i_template_name", "m2i_footer_before_mage_elements_html", "m2i_footer_after_mage_elements_html", "m2i_header_before_mage_elements_html", "m2i_header_after_mage_elements_html".
* Refreshed slider and product-view templates
* Add-to-cart button bugs fixed, now it's more interactive like the native button in Magento
* Class and tag settings for DOM approach replaced with CSS selector setting both for the footer and header
* Fixed Base URL checking bug, when only minified version of require.js is presented
* Fixed charset issue for M2I_DOMDocument
* Help tab added on settings page
* Tooltips for advanced settings extended
* And other more minor but valuable improvements!

= 1.1.3 =

* Fixed conflicts with other plugins (fix for Magento 2 autoloader)
* Fixed fatal errors in debug mode or error_reporting (restore_error_handler used)

= 1.1.1 =

* Translations added (English and Ukrainian for now)
* More hooks (m2i_before_init, m2i_after_init), plugin is more flexible
* baseUrl error fixed, when Magento 2 is launching from root
* Select2 field for Store view with searchability

= 1.1 =

* Store-view field improved
* Flags options fixed
* Integrator improved

= 1.0.6 =

* Launched the base version of the plugin

== Upgrade Notice ==

= 1.4 =

We replaced free limited version with the full premium, so be ready to review all the options.
You also need to remove your premium version and replace it fully with the free one in order to allow automatic updates and custom update from WP screen.

= 1.2.6 =

We have fixed some fatal errors when the object cache is enabled for the WP side. We kindly recommend to upgrade the plugin.

= 1.2.5.1 =

User can select by his/her own the Magento 2 running from the "pub" directory or base one (root). Errors verbosity increased and a couple of fatal errors fixed. Translations domain fixed to m2wp.
