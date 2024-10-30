<?php
/**
 * Plugin Name: Magento 2 WordPress Integration
 *
 * Author: Yaroslav Yachmenov
 *
 * Description: Integrate Magento 2 with WordPress so users will have a unified user experience. Share session/cart data, navigation menus, header, footer, products, layout elements and static blocks by using shortcodes, widgets or functions.
 *
 * Version: 1.4.1
 *
 * Author URI: https://www.linkedin.com/in/yaroslav-yachmenov-797945121/
 *
 * Text Domain: m2wp
 *
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'M2I_URL', plugins_url( '', __FILE__ ) );
define( 'M2I_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'M2I_PATH_LANGUAGES', basename( M2I_PATH ) . DIRECTORY_SEPARATOR . 'languages' );

const M2I_URL_JS = M2I_URL . '/js';
const M2I_URL_IMG = M2I_URL . '/img';
const M2I_URL_CSS = M2I_URL . '/css';
const M2I_PATH_PHP = M2I_PATH . DIRECTORY_SEPARATOR . 'include';
const M2I_PATH_CLASSES = M2I_PATH_PHP . DIRECTORY_SEPARATOR . 'classes';
const M2I_EXTERNAL_DIR = 'external';
const M2I_TEMPLATES_DIR = 'templates';
const M2I_TEMPLATES_IN_THEME_DIR = 'm2i-templates';
const M2I_CACHE_EXPIRATION = DAY_IN_SECONDS;
const M2I_PLUGIN_VERSION = '1.4.1';

add_action( 'plugins_loaded', 'm2i_load_plugin_textdomain' );

/**
 * Loaded textdomain for M2WP plugin
 * @since 1.1
 */
function m2i_load_plugin_textdomain() {
	load_plugin_textdomain( 'm2wp', false, M2I_PATH_LANGUAGES );
}

add_action( 'setup_theme', 'm2i_plugin_init' );

/** Entry point for the plugin */
function m2i_plugin_init() {

	if ( ! m2i_is_php_version_compatible() ) {
		add_action( 'admin_notices', 'm2i_admin_php_version__error' );
		return;
	}

	/**
	 * @since 1.2.2 Added checking for xml extension loaded.
	 */
	if ( ! extension_loaded( 'xml' ) ) {
		add_action( 'admin_notices', 'm2i_admin_xml_required__error' );
		return;
	}

	require_once M2I_PATH_CLASSES . '/M2I_Error_Helper.php';
	require_once M2I_PATH_CLASSES . '/M2I_Settings.php';
	require_once M2I_PATH_CLASSES . '/M2I_External.php';
	require_once M2I_PATH_PHP . '/functions.php';

	/**
	 * Action before Magento2 init
	 * @since 1.1
	 */
	do_action( 'm2i_before_init' );

	M2I_External::init();

	/**
	 * Action after Magento2 init
	 * @since 1.1
	 */
	do_action( 'm2i_after_init' );
}

add_action( 'm2i_before_init', 'm2i_before_init_mix' );

/**
 *  Mix of different calls before init. Magento2
 *  @since 1.1
 */
function m2i_before_init_mix() {
	include_once M2I_PATH_PHP . '/shortcodes.php';

	add_action( 'admin_enqueue_scripts', 'm2i_init_admin_media' );
	add_action( 'wp_ajax_m2i_check_magento', 'm2i_check_magento' );
	add_action( 'wp_ajax_m2i_notices', 'm2i_notices' );

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'm2i_add_action_links' );
}

add_action( 'm2i_after_init', 'm2i_after_init_launch' );

/**
 *  Action for launching Magento2 if it's front-end
 *  @since 1.1
 */
function m2i_after_init_launch() {
	if ( ! is_admin() && apply_filters( 'm2i_launch_on_frontend', true ) ) {
		M2I_External::launch();
	}
}

add_action( 'm2i_after_init', 'm2i_after_init_replace_magento_autoloader' );
add_action( 'm2i_after_init', 'm2i_after_init_external_composer_api_launch' );
add_action( 'm2i_after_init', 'm2i_after_init_rest_api_launch' );

/**
 * @access private
 * @since 1.2
 * @since 1.2.2 Moved to the magento2-integration.php
 *
 * @param mixed $autoloader Autoloader value registered with the spl_autoload_register()
 * @see spl_autoload_register()
 *
 * @return bool
 */
function _m2i_is_magento_autoloader( $autoloader ) {
	return (
		is_array( $autoloader ) &&
		2 == count( $autoloader ) &&
		is_object( $autoloader[0] ) &&
		is_a( $autoloader[0], 'Magento\Framework\Code\Generator\Autoloader' ) &&
		is_string( $autoloader[1] ) &&
		'load' == $autoloader[1]
	);
}

/**
 * Action to replace Magento 2 native autoloader with other one extended from Magento 2 but WP oriented.
 * Action helps prevent several conflicts with WP plugins (for example Yoast SEO).
 *
 * @since 1.2
*/
function m2i_after_init_replace_magento_autoloader() {
	if ( ( $found = array_filter( spl_autoload_functions(), '_m2i_is_magento_autoloader' ) ) ) {
		$autoloader = current( $found );
		require_once M2I_PATH_CLASSES . '/M2I_Mage_Autoloader.php';
		$obj = M2I_External::get_bootstrap()->getObjectManager();
		$generator = $obj->get( \Magento\Framework\Code\Generator::class );
		if ( $generator ) {
			$autoloader[0] = new M2I_Mage_Autoloader( $generator );
			spl_autoload_unregister( $autoloader );
			spl_autoload_register( $autoloader );
		}
	}
}

/**
 * Action for launching external composer libs needed by M2I plugin.
 * @since 1.1.3
 */
function m2i_after_init_external_composer_api_launch() {
	require_once M2I_PATH_PHP . DIRECTORY_SEPARATOR . M2I_EXTERNAL_DIR . '/vendor/autoload.php';
}

/**
 *  Action for activation rest M2I APIs (Widgets, Content integration, Editor shortcodes, etc...)
 *  @since 1.1
 */
function m2i_after_init_rest_api_launch() {
	include_once M2I_PATH_CLASSES . '/M2I_Content.php';
	include_once M2I_PATH_CLASSES . '/M2I_Widgets.php';
	include_once M2I_PATH_CLASSES . '/M2I_Editor_Button.php';
}

/**
 * @return bool
 *
 * @since 0.5
 */
function m2i_is_php_version_compatible() {
	$is_compatible = true;
	$phpversion = phpversion();

	if ( version_compare( $phpversion, '7.3', '<' ) ) {
		$is_compatible = false;
	}

	return $is_compatible;
}

function m2i_init_admin_media( $page ) {
	global $m2i_options;

	wp_enqueue_script( 'm2i_notices', M2I_URL_JS . '/notices.js', array('jquery'), M2I_PLUGIN_VERSION );
	if ( ! $m2i_options['disable_select2'] ) {
		wp_enqueue_script( 'm2i_select2', M2I_URL_JS . '/select2.full.min.js', array('jquery'), M2I_PLUGIN_VERSION );
		wp_enqueue_style( 'm2i_select2', M2I_URL_CSS . '/select2.min.css', M2I_PLUGIN_VERSION );
	}
	wp_enqueue_script( 'm2i_ajaxsearch', M2I_URL_JS . '/ajaxsearch.js', array('m2i_select2'), M2I_PLUGIN_VERSION );
	wp_enqueue_style( 'm2i_settings_css', M2I_URL_CSS . '/admin_settings.css', M2I_PLUGIN_VERSION );

	if ( strpos( $page, 'M2I_Settings' ) === false ) {
		return;
	}

	wp_enqueue_script( 'm2i_settings_script', M2I_URL_JS . '/admin_settings.js', array('jquery'), M2I_PLUGIN_VERSION );
	wp_enqueue_script( 'tooltips', M2I_URL_JS . '/tooltip.js', array('jquery-ui-tooltip'), M2I_PLUGIN_VERSION );

	wp_localize_script( 'm2i_settings_script', 'm2i_urls', array('js' => M2I_URL_JS, 'img' => M2I_URL_IMG) );
	wp_localize_script( 'm2i_settings_script', 'm2i_options', $m2i_options );

	wp_localize_script( 'tooltips', 'tooltips', include M2I_PATH_PHP . '/tooltips.php' );
}

/**
 * Function for ajax online checking if main options are configured in the right way
 *
 * @since 0.2
 */
function m2i_check_magento() {

	M2I_External::init( true );
	M2I_External::launch();

	if ( m2i_is_success() ) {
		$base_url = M2I_External::get_base_url();

		if ( empty( $base_url ) ) {
			wp_send_json_error( new WP_Error(
				'base_url__error',
				__( 'Magento 2 Integration has got wrong baseUrl', 'm2wp' )
			) );
		}

		/* Success body */
		if ( $base_url !== get_option( 'm2i_mage_base_url' ) ) {
			update_option( 'm2i_mage_base_url', $base_url );
		}

		wp_send_json_success( __( 'Magento 2 Integration has done all steps successfully!', 'm2wp' ) );
	}

	wp_send_json_error( M2I_External::get_error_helper()->add_error_to_errors_container( new WP_Error(
		'customer__notice',
        __( 'See debug.log for detailed output with WP_DEBUG_LOG constant enabled.', 'm2wp' )
	) )->get_errors_container() );
}

/**
 * Check if main WP __() translation function has been modified by user.
 *
 * @since 1.2.5
 * @since 1.4 The `$l10n_content` parameter was added.
 *
 * @param string|null $l10n_content
 *
 * @return bool True on success
 */
function m2i_is_translation_function_modified( ?string $l10n_content = null ): bool {
	if ( ! $l10n_content && is_dir( ABSPATH . 'wp-includes' ) ) {
		$l10n_content = @file_get_contents( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'l10n.php' );
	}

    $reg = '/function\s*__\(\s*\$text,\s*\$domain\s*=\s*\'default\'\s*\)\s*\{\s*if\s*\(\s*defined\s*\(\s*\'M2I_MAGE_DIR\'\s*\)\s*\&\&\s*class_exists\s*\(\s*\'M2I_External\'\s*\)\s*\&\&\s*M2I_External\:\:\$needs_mage_translate\s*\)\s*\{\s*return\s*M2I_External\:\:translate\s*\(\s*func_get_args\s*\(\s*\)\s*\)\s*\;\s*\}\s*else\s*\{\s*return\s*translate\s*\(\s*\$text,\s*\$domain\s*\)\;\s*\}\s*\}/m';
    if ( $l10n_content && preg_match( $reg, $l10n_content ) ) {
        return true;
    }

	return false;
}

/**
 * Modifies WordPress native translation function on the fly and returns resulted l10n.php file content
 *
 * You can block modifying execution by defining a constant in wp-config.php:
 * define( 'M2I_DISABLE_TRANSLATION_FUNC_MODIFY', true );
 *
 * @since 1.4
 * @access private
 *
 * @return string|false
 */
function _m2i_modify_translation_function() {
    if ( is_dir( ABSPATH . 'wp-includes' ) ) {
        $l10n_file_path      = ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'l10n.php';
        $l10n_content        = @file_get_contents( $l10n_file_path );
        $check_with_php      = true;
        $check_is_successful = null;

        if ( $l10n_content &&
             ! ( defined( 'M2I_DISABLE_TRANSLATION_FUNC_MODIFY' ) && M2I_DISABLE_TRANSLATION_FUNC_MODIFY )
        ) {
            $l10n_content = preg_replace(
                '/function __\([^}]+}/m',
                <<<MODIFIED_FUNCTION
                function __( \$text, \$domain = 'default' ) {
                    if ( defined( 'M2I_MAGE_DIR' ) && class_exists( 'M2I_External' ) && M2I_External::\$needs_mage_translate ) {
                        return M2I_External::translate( func_get_args() );
                    } else {
                        return translate( \$text, \$domain );
                    }
                }
                MODIFIED_FUNCTION,
                $l10n_content,
                1
            );

            if ( 'Windows' == substr( php_uname(), 0, 7 ) || ! m2i_is_exec_enabled() ) {
                $check_with_php = false;
            }

            if ( $check_with_php ) {
                $tmp_file_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'm2i-l10n-temp.php';
                if ( @file_put_contents( $tmp_file_path, $l10n_content ) ) {
                    exec( "php -l $tmp_file_path", $exec_output, $exec_result_code );
                    unlink( $tmp_file_path );
                    $check_is_successful = ! $exec_result_code;
                }
            }

            if ( null === $check_is_successful || $check_is_successful ) {
                $write_result = @file_put_contents( $l10n_file_path, $l10n_content );
                if ( $write_result ) {
                    return $l10n_content;
                }
            }
        }
    }

    return false;
}

/**
 * Returns true if PHP exec function is enabled and available for use
 *
 * @since 1.4
 *
 * @see exec()
 *
 * @return bool
 */
function m2i_is_exec_enabled(): bool {
    $disabled = explode( ',', ini_get( 'disable_functions' ) );
    return ! in_array( 'exec', $disabled ) && function_exists( 'exec' );
}

/**
 * Function for ajax disabling of m2i notices in the admin panel
 *
 * @since 1.0.0
 */
function m2i_notices() {
	if ( ! empty( $_POST['id'] ) ) {
		$id = $_POST['id'];
		$notices = get_option( 'm2i_notices' );
		if ( ! $notices ) {
			$notices = array();
		}
		if ( strpos( $id, '__error' ) !== false ) {
			$notices['errors'][$id] = true;
		}
		update_option( 'm2i_notices', $notices );
		/* Success */
		wp_die( 1 );
	}
}

/* Errors messages for the admin panel */

function m2i_admin_autoload_file__error() {
	m2i_notice__error( __( 'Irks! You have not configured Magento root directory in the right way for <b>Magento 2 integration</b> plugin. Please, go to %settings%.', 'm2wp' ), __FUNCTION__ );
}

function m2i_admin_bootstrap_class__error() {
	m2i_notice__error( __( 'Irks! <b>Magento 2 integration</b> plugin can not find a Bootstrap class. Please, go to %settings% and reconfigure directory parameters.', 'm2wp' ), __FUNCTION__ );
}

function m2i_admin_store_code__error() {
	m2i_notice__error( __( 'Irks! <b>Magento 2 integration</b> plugin can not find selected store. Please, reconfigure store parameter.', 'm2wp' ), __FUNCTION__ );
}

function m2i_admin_php_version__error() {
	m2i_notice__error( __( 'Irks! <b>Magento 2 integration</b> plugin requires PHP >= 5.5.9. And as for PHP 7 it requires PHP >= 7.0.8!', 'm2wp' ), __FUNCTION__ );
}

function m2i_admin_xml_required__error() {
	m2i_notice__error( __( 'Irks! <b>Magento 2 integration</b> plugin requires PHP xml extension be installed!', 'm2wp' ), __FUNCTION__ );
}

/**
 * Show translation function error
 *
 * @since 1.2.5
 */
function m2i_admin_translation_function__error() {
	m2i_notice__error( __( 'Irks! WordPress translation function __(..) is not properly modified. Please, follow <b>Magento 2 integration</b> plugin installation instructions.', 'm2wp' ), __FUNCTION__ );
}

/**
 *  Function for outputting formatted error notice, can be used in specified callbacks for wp notice actions
 *
 *  @param string $message Error message
 *  @param string $id Unique id for current notice
 *  @param string $clases Clases separated with gap
 *  @param string $custom_css Custom CSS if needed
 *
 *  @since 0.2
 *  @since 1.0.0 Added <b>$id</b> parameter
 *
 */
function m2i_notice__error( $message, $id, $clases = 'notice notice-error is-dismissible', $custom_css = '' ) {
	$notices = get_option( 'm2i_notices' );
	if ( ! $notices || ($notices && empty( $notices['errors'][$id] )) ) {
		printf( '<div class="%s" id="%s" style="%s"><p>%s</p></div>', $clases, $id, $custom_css, str_replace( '%settings%', '<a href="' . admin_url( 'options-general.php?page=M2I_Settings.php' ) . '">settings</a>', $message ) );
	}
}

/**
 *  @return array Additional links for the backend plugins menu
 *
 *  @since 0.2.5
 */
function m2i_add_action_links( $links ) {
	$mylinks = array(
	    '<a href="' . admin_url( 'options-general.php?page=M2I_Settings.php' ) . '">' . __( 'Settings' ) . '</a>'
	);

	return array_merge( $mylinks, $links );
}

register_deactivation_hook( __FILE__, 'm2i_on_deactivation' );

/**
 * Deactivation hook, used to restore noices, etc.
 *
 * @since 1.0.3
 */
function m2i_on_deactivation() {

	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );

	/* Restore all removed notices by user for using while next activation */
	$notices = get_option( 'm2i_notices' );
	if ( $notices ) {
		array_walk( $notices, function(&$item) {
			foreach ( $item as &$id ) {
				$id = false;
			}
		} );
	}
	update_option( 'm2i_notices', $notices );
	unset( $notices );
}

register_activation_hook( __FILE__, 'm2i_on_activation' );

/**
 * Activation hook
 *
 * @since 1.0.6
 */
function m2i_on_activation() {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "activate-plugin_{$plugin}" );

	m2i_verify_options();
}

/** @since 1.0.6 */
function m2i_verify_options() {

	/* It is useful for data migration after 1.0.6 version. */
	foreach ( array('m2i_mage_header_block_name', 'm2i_mage_footer_block_name') as $id ) {
		$block = get_option( $id );
		if ( $block !== false && ! is_array( $block ) )
			update_option( $id, array((string) $block) );
	}
}

if ( ! function_exists( 'is_ajax' ) ) {

	/** @since 1.0.5 */
	function is_ajax() {
		return (defined( 'DOING_AJAX' ) && DOING_AJAX);
	}

}

add_action( 'init', 'm2i_reorder_mage_autoloader', 100 );
/**
 * Place magento2 autoloader after all WP autoloaders
 *
 * @since 1.1.2
 *
 * @todo Think about possible removing of this function, because of no need in general since new functionality added.
 */
function m2i_reorder_mage_autoloader() {
	$autoloaders = spl_autoload_functions();
	$autoloader = end( $autoloaders );
	if ( ! _m2i_is_magento_autoloader( $autoloader ) ) {
		reset( $autoloaders );
		if ( ( $found = array_filter( $autoloaders, '_m2i_is_magento_autoloader' ) ) ) {
			$autoloader = current( $found );
			spl_autoload_unregister( $autoloader );
			spl_autoload_register( $autoloader );
        }
    }
}
