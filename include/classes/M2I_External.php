<?php

defined( 'ABSPATH' ) || exit;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ErrorHandler;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;

/**
 * Static class of managing the connection between WordPress and Magento 2 for further usages
 */
class M2I_External {

	/** @var Bootstrap|null */
	static protected $bootstrap = null;

	/** @var \Magento\Framework\App\Http|null */
	static protected $app = null;

	/** @var \Magento\Framework\App\ResponseInterface|null */
	static protected $response = null;

	/** @var \Magento\Framework\View\LayoutInterface|null */
	static protected $layout = null;

	/** @var array Store of real WordPress $_SERVER by key <b>'wp'</b> and Magento2 $_SERVER by key <b>'mage'</b> */
	static protected $params;

	/**
	 *  @var ErrorHandler|null Magento2 error handler
	 *
	 *  @since 0.4.9
	 */
	static protected $error_handler = null;

	/**
	 *  @var bool Is "launch" method able to do main things?
	 *
	 *  @since 0.4.9
	 */
	static protected $can_launch = false;

	/**
	 *  @var bool Is "launch" method was executed normally?
	 *
	 *  @since 0.4.9
	 */
	static protected $was_launched = false;

	/**
	 * @var null|M2I_Error_Helper
	 *
	 * @since 1.2.5
	 */
	static protected $error_helper = null;

	/**
	 * @var null|Magento\Store\Model\Store
	 *
	 * @since 1.2.6
	 */
	static protected $store = null;

	/** @var bool */
	static $is_ajax = false;

	/** @var string */
	static $mage_dir;

	/** @var bool */
	static $needs_mage_translate = false;

	/**
	 *  Init. for Magento 2 external process (can be used with ajax and normal execution)
	 *
	 *  @param bool $is_ajax Should be executed in the ajax mode?
	 *
	 *  @since 0.3.5 Added $is_ajax param.
	 */
	static function init( bool $is_ajax = false ) {

		self::$error_helper = new M2I_Error_Helper;

		self::$is_ajax = $is_ajax;

        $is_translation_func_modified = m2i_is_translation_function_modified();
        if ( ! $is_translation_func_modified ) {
            $is_translation_func_modified = m2i_is_translation_function_modified( _m2i_modify_translation_function() );
        }

		if ( $is_translation_func_modified ) {
			self::init_dir();

			self::$bootstrap = self::init_bootstrap();

			if ( is_object( self::$bootstrap ) ) {
				self::modify_server_env();
				self::$app = self::init_app();
				self::restore_server_env();
				self::$can_launch = true;
			}
		} else {
			self::translation_function_error();
		}
	}

	/**
	 *  Launches fully all Magento2 processes
	 *
	 *  @since 0.4.9
	 */
	static function launch() {
		if ( self::$can_launch && ! self::$was_launched ) {

			$error = false;
			self::modify_server_env();
			self::$needs_mage_translate = true;

			try {
				self::launch_app();
				$obj = self::$bootstrap->getObjectManager();
				self::$layout = $obj->get( \Magento\Framework\View\Layout::class );
				if ( ! self::$store ) {
					self::$store = $obj->get( Magento\Store\Model\StoreManagerInterface::class )->getStore();
				}
				self::maybe_clear_current_full_page_cache();
			} catch ( NoSuchEntityException $e ) {
				self::maybe_store_error( $e );
				self::add_new_error( 'launch__error', $e );
				$error = true;
			} catch ( Exception $e ) {
				self::add_new_error( 'launch__error', $e );
				$error = true;
			} catch ( Throwable $e ) {
				self::add_new_error( 'launch__error', $e );
				$error = true;
			}

			self::$needs_mage_translate = false;
			self::restore_server_env();
			self::$was_launched = ! $error;
		}
	}

	/**
	 * @since 1.2.5 Deprecation removed because is needed for the cart shortcode and the cart widget.
	 *
	 * @todo Refresh method according to the latest changes in plugin.
	 */
	static function add_handle( $handle, $pageUrl = '/' ) {
		self::$params['mage']['REQUEST_URI'] = self::$params['mage']['REDIRECT_URL'] = $pageUrl;
		self::modify_server_env();
		self::$needs_mage_translate = true;

		try {
			$obj = self::$bootstrap->getObjectManager();
			$resultPage = $obj->create( \Magento\Framework\View\Result\Page::class );
			$resultPage->addHandle( $handle );
		} catch ( NoSuchEntityException $e ) {
			self::maybe_store_error( $e );
			self::add_new_error( 'add_handle__error', $e );
		} catch ( Exception $e ) {
			self::add_new_error( 'add_handle__error', $e );
		} catch ( Throwable $e ) {
			self::add_new_error( 'add_handle__error', $e );
		}

		self::$needs_mage_translate = false;
		self::restore_server_env();
		self::$params['mage']['REQUEST_URI'] = self::$params['mage']['REDIRECT_URL'] = '/';
	}


	/**
	 * @param NoSuchEntityException $e Error to analyze
	 *
	 * @since 1.0.4
	 */
	static protected function maybe_store_error( NoSuchEntityException $e ) {
		$message = $e->getMessage();
		if ( stripos( $message, 'store' ) !== false && stripos( $message, 'not found' ) !== false ) {
			self::store_code_error();
		}
	}

	/** @return \Magento\Framework\App\ResponseInterface */
	static function get_response() {
		return self::$response;
	}

	/**
	 * An transformed alias for __() function in Magento2
	 *
	 * @param array Arguments
	 * @return \Magento\Framework\Phrase
	 *
	 * @since  0.2.5
	 */
	static function translate( $argc ) {
		$text = array_shift( $argc );
		if ( ! empty( $argc ) && is_array( $argc[0] ) ) {
			$argc = $argc[0];
		}
		return new \Magento\Framework\Phrase( $text, $argc );
	}

	/**
	 *  Do a basic role of bootstrap.php file
	 *
	 *  @param string $autoload_file_path Path to autoload file
	 */
	static protected function pre_load( $autoload_file_path ) {
		require_once $autoload_file_path;

		$umaskFile = BP . '/magento_umask';
		$mask = file_exists( $umaskFile ) ? octdec( file_get_contents( $umaskFile ) ) : 002;
		umask( $mask );
	}

	/**
	 * Universal init. of Magento Dir. name and base name (working both for ajax and normal execution)
	 *
	 * @since 0.2
	 */
	static protected function init_dir() {
		self::$mage_dir = M2I_MAGE_DIR;

		if ( is_ajax() && self::$is_ajax ) {
			self::$mage_dir = $_POST['m2i_mage_dir'];
		} else {
			self::$is_ajax = false;
		}
	}

	/** @return Bootstrap */
	static protected function init_bootstrap() {
		$autoload_file_path = self::$mage_dir . '/app/autoload.php';

		if ( ! is_readable( $autoload_file_path ) ) {
			self::autoload_file_error();
		} else {
			self::pre_load( $autoload_file_path );

			if ( ! class_exists( '\Magento\Framework\App\Bootstrap' ) ) {
				self::bootstrap_class_error();
			} else {
				self::$params['mage'] = self::get_converted_params();

				self::$params['mage'][Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = array(
				    DirectoryList::PUB => array(DirectoryList::URL_PATH => ''),
				    DirectoryList::MEDIA => array(DirectoryList::URL_PATH => 'media'),
				    DirectoryList::STATIC_VIEW => array(DirectoryList::URL_PATH => 'static'),
				    DirectoryList::UPLOAD => array(DirectoryList::URL_PATH => 'media/upload')
				);
				if ( self::is_mage_runs_from_root() ) {
					self::$params['mage'][Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = array(
					    DirectoryList::PUB => array(DirectoryList::URL_PATH => 'pub'),
					    DirectoryList::MEDIA => array(DirectoryList::URL_PATH => 'pub/media'),
					    DirectoryList::STATIC_VIEW => array(DirectoryList::URL_PATH => 'pub/static'),
					    DirectoryList::UPLOAD => array(DirectoryList::URL_PATH => 'pub/media/upload')
					);
				}

				self::maybe_enable_selected_store();

				/**
				 * @since 1.2.3 We need to modify server env. before Bootstrap::create
				 */
				self::modify_server_env();
				self::$bootstrap = Bootstrap::create( BP, self::$params['mage'] );
				self::restore_server_env();
			}
		}

		return self::$bootstrap;
	}

	/**
	 * Think before enabling selected store. Not enable if it is base settings page of current plugin.
	 *
	 * @since 1.0.4
	 */
	static protected function maybe_enable_selected_store() {
		global $m2i_options;

		if ( 'on' != $m2i_options['auto_store_selection'] ) {
			self::$params['mage'][ StoreManager::PARAM_RUN_TYPE ] = 'store';

			if ( is_admin() && is_ajax() && isset( $_POST['action'] ) && $_POST['action'] == 'm2i_check_magento' ) {
				self::$params['mage'][ StoreManager::PARAM_RUN_CODE ] = $_POST['m2i_mage_store'];
			} else {
				self::$params['mage'][ StoreManager::PARAM_RUN_CODE ] = $m2i_options['mage_store_code'];
			}
		}
	}

	/**
	 * Clear a full page cache of current page if it's needed.
	 *
	 * @todo Remove in the next versions of plugin because of no need in the latest versions of Magento (cache clearing is not work)
	 *
	 * @since 1.1.3
	 * @deprecated since 1.2.5
	 */
	static protected function maybe_clear_current_full_page_cache() {
		$obj = self::$bootstrap->getObjectManager();
		$cache_state = $obj->get( \Magento\Framework\App\Cache\StateInterface::class );
		if ( $cache_state->isEnabled( 'full_page' ) ) {
			$page_identifier = $obj->get( \Magento\Framework\App\PageCache\Identifier::class );
			$full_page_cache = $obj->get( \Magento\Framework\App\PageCache\Cache::class );
			$full_page_cache->remove( $page_identifier->getValue() );
		}
	}

	/** @return \Magento\Framework\AppInterface */
	static protected function init_app() {
		return self::$app = is_object( self::$bootstrap ) ? self::$bootstrap->createApplication( \Magento\Framework\App\Http::class ) : null;
	}

	/**
	 *  Launch app. and init. response object for further using of getting the html content in the functions.php
	 *
	 *  @since 0.2
	 *  @since 1.1.2 Error handler restored after launching
	 */
	static protected function launch_app() {
		/* Fix to end session opened by other WP plugin, which could bring conflict and 500 error. */
		if ( session_id() ) {
			session_write_close();
		}
		try {
			\Magento\Framework\Profiler::start( 'magento' );
			self::$error_handler = new ErrorHandler;
			set_error_handler( array(self::$error_handler, 'handler') );
			try {
				self::select_store_via_store_manager();
				self::$response = self::$app->launch();
			} catch ( Exception $e ) {
				self::add_new_error( 'application_launch__error', $e );
			} catch ( Throwable $e ) {
				self::add_new_error( 'application_launch__error', $e );
			}
			\Magento\Framework\Profiler::stop( 'magento' );
		} catch ( Exception $e ) {
			self::add_new_error( 'around_application_launch__error', $e );
		} catch ( Throwable $e ) {
			self::add_new_error( 'around_application_launch__error', $e );
		}
		restore_error_handler();
	}

	/**
	 * @since 1.3.1
	 *
	 * Select store via Magento store manager.
	 */
	static protected function select_store_via_store_manager() {
		/**
		 * @since 1.3.0.2
		 */
		$magento_version = self::get_magento_version();
		if ( !$magento_version || version_compare( $magento_version, '2.3.0', '>=' ) ) {
			global $m2i_options;
			$object_manager = self::$bootstrap->getObjectManager();
			$store_manager = $object_manager->get( \Magento\Store\Model\StoreManagerInterface::class );
			$stores = $store_manager->getStores( true, false );
			$store_code = $m2i_options['mage_store_code'];
			foreach( $stores as $store ) {
				if ( 'on' == $m2i_options['auto_store_selection'] ) {
					$store_base_url = $store->getBaseUrl();
					$blog_site_url = get_site_url();
					if ( parse_url( $store_base_url,  PHP_URL_HOST ) == parse_url( $blog_site_url,  PHP_URL_HOST ) ) {
						$store_id = $store->getId();
						$store_manager->setCurrentStore( $store_id );
						break;
					}
				} else {
					if ( $store->getCode() === $store_code ) {
						$store_id = $store->getId();
						$store_manager->setCurrentStore( $store_id );
						break;
					}
				}
			}
		}
	}

	/**
	 * Get base url from magento db
	 *
	 * @since 1.2.3
	 *
	 * @return string|false String on success, false on failure.
	 */
	static protected function get_base_url_from_db() {
		static $base_url_from_db = null;
		if ( is_null( $base_url_from_db ) ) {
			$magento_config_path = self::$mage_dir . '/app/etc/env.php';
			if ( is_readable( $magento_config_path ) ) {
				$config = include $magento_config_path;
				if ( isset( $config['db']['connection']['default'] ) && is_array( $config['db']['connection']['default'] ) ) {
					extract( $config['db']['connection']['default'] );
					if ( ! ( empty( $host ) || empty( $dbname ) || empty( $username ) || empty( $password ) || empty( $active ) ) ) {
						$table_prefix = empty( $config['db']['table_prefix'] ) ? '' : $config['db']['table_prefix'];
						$link = mysqli_connect( $host, $username, $password, $dbname );
						if ( ! mysqli_connect_errno() ) {
							$result = mysqli_query( $link, "SELECT value FROM {$table_prefix}core_config_data WHERE path = 'web/" . ( is_ssl() ? "" : "un" ) . "secure/base_url' LIMIT 1" );
							if ( $result ) {
								$value = mysqli_fetch_row( $result );
								if ( $value ) {
									$base_url_from_db = current( $value );
								}
								mysqli_free_result( $result );
								mysqli_close( $link );
							}
						}
					}
				}
			}
			if ( is_null( $base_url_from_db ) ) {
				$base_url_from_db = false;
			}
		}
		return $base_url_from_db;
	}

	/**
	 * Checking path with Magento2 DB.
	 * Allows to support Magento2 installations in subdirs. for the Magento 2.2.4
	 *
	 * @since 1.2.3
	 *
	 * @return string Path
	*/
	static protected function get_base_path() {
		$path = '/';
		$base_url = self::get_base_url( 'link' );

		if ( ! $base_url ) {
			/**
			 * @since 1.2.3 Checking path with Magento2 DB.
			 * Allows to support Magento2 installations in subdirs.
			 */
			$base_url = self::get_base_url_from_db();
		}

		if ( $base_url ) {
			/** @todo Replace parse_url() with wp_parse_url() function */
			$path_from_db = parse_url( $base_url, PHP_URL_PATH );
			if ( $path_from_db ) {
				$path = $path_from_db;
			}
		}

		return preg_match( '#index\.php/?$#', $path ) ? $path : rtrim( $path, '/' ) . '/index.php/';
	}

	/** @return array Params adapted for Magento2 environment */
	static protected function get_converted_params() {
		$params = $_SERVER;

		$params['REQUEST_URI'] = $params['REDIRECT_URL'] = self::get_base_path();

		/**
		 * @since 1.0.4
		 */
		$params['REQUEST_METHOD'] = 'POST';

		/**
		 * @since 1.3
		*/
		$raw_magento_version = self::get_raw_magento_version();
		if ( !$raw_magento_version || version_compare( $raw_magento_version, '2.3.0', '>=' ) ) {
			$params['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		}

		return $params;
	}

	/** Generate error when file for Bootstrap is not founded */
	static protected function autoload_file_error() {
		self::generate_error( 'm2i_admin_autoload_file__error' );
	}

	/** Generate error when Bootstrap class is not founded */
	static protected function bootstrap_class_error() {
		self::generate_error( 'm2i_admin_bootstrap_class__error' );
	}

	/** Generate error when store is not founded */
	static protected function store_code_error() {
		self::generate_error( 'm2i_admin_store_code__error' );
	}

	/**
	 * Generate error when translation function has not been properly modified
	 *
	 * @since 1.2.5
	 */
	static protected function translation_function_error() {
		self::generate_error( 'm2i_admin_translation_function__error' );
	}

	/**
	 *  @param string $notice_callback Name of the notice callback (will be used in the action)
	 */
	static protected function generate_error( $notice_callback ) {
		if ( self::$is_ajax ) {
			$error_message = '';
			$error_code = str_replace( 'm2i_admin_', '', $notice_callback );
			switch( $error_code ) {
				case 'autoload_file__error':
					$error_message = __( 'Magento 2 Integration cannot find autoloader file for start point.', 'm2wp' );
					break;
				case 'bootstrap_class__error':
					$error_message = __( 'Magento 2 Integration cannot find file with Bootstrap class.', 'm2wp' );
					break;
				case 'store_code__error':
					$error_message = __( 'Magento 2 Integration cannot find selected store view.', 'm2wp' );
					break;
				case 'translation_function__error':
					$error_message = __( 'WordPress translation function __(..) is not properly modified.', 'm2wp' );
					break;
			}
			wp_send_json_error( new WP_Error(
				$error_code,
				$error_message
			) );
		} else {
			add_action( 'admin_notices', $notice_callback );
		}
	}

	/**
	 *  Safety way to modify <b>$_SERVER</b> for Magento2 using
	 *
	 *  @since 1.0.0
	 */
	static protected function modify_server_env() {
		$callback = function($n) {
			return is_array( $n ) ? serialize( $n ) : $n;
		};
		if ( count( array_diff_assoc( array_map( $callback, $_SERVER ), array_map( $callback, self::$params['mage'] ) ) ) ) {
			self::$params['wp'] = $_SERVER;
			$_SERVER = self::$params['mage'];

			/* Compatibility fix for Magento. Todo: Improve. */
			$messages_cookie_name = \Magento\Theme\Controller\Result\MessagePlugin::MESSAGES_COOKIES_NAME;
			if ( isset( $_COOKIE[$messages_cookie_name] ) ) {
				$result = @unserialize( $_COOKIE[$messages_cookie_name] );
				if ( ! $result ) {
					unset( $_COOKIE[ $messages_cookie_name ] );
				}
			}
		}
	}

	/**
	 * @return bool|string String on success, false on failure
	 *
	 * @since 1.2.7
	 */
	static public function get_magento_version() {
		if ( is_object( self::$bootstrap ) ) {
			/* @var \Magento\Framework\App\ProductMetadataInterface $productMetadata */
			$productMetadata = self::$bootstrap->getObjectManager()->get( \Magento\Framework\App\ProductMetadataInterface::class );
			$version         = $productMetadata->getVersion();
			return $version;
		}
		return false;
	}

	/**
	 * Get raw magento version with the help of composer.json file. This is usable during the init. process.
	 *
	 * @return bool|string String on success, false on failure
	 *
	 * @since 1.3
	 */
	static protected function get_raw_magento_version() {
		$composer_file = self::$mage_dir . '/composer.json';
		if ( is_readable( $composer_file ) ) {
			$composer_file_content = json_decode( file_get_contents( $composer_file ), true );
			if ( $composer_file_content ) {
				if ( isset( $composer_file_content['require']['magento/product-community-edition'] ) ) {
					return $composer_file_content['require']['magento/product-community-edition'];
				}
				if ( isset( $composer_file_content['require']['magento/product-enterprise-edition'] ) ) {
					return $composer_file_content['require']['magento/product-enterprise-edition'];
				}
			}
		}
		return false;
	}

	/**
	 *  Safety way to restore <b>$_SERVER</b> for WordPress using
	 *
	 *  @since 1.0.0
	 */
	static protected function restore_server_env() {
		if ( ! empty( self::$params['wp'] ) ) {
			$_SERVER = self::$params['wp'];
		}
	}

	/** @return Bootstrap|null */
	static public function get_bootstrap() {
		return self::$bootstrap;
	}

	/** @return \Magento\Framework\App\Http|null */
	static public function get_app() {
		return self::$app;
	}

	/** @return \Magento\Framework\View\LayoutInterface|null */
	static public function get_layout() {
		return self::$layout;
	}

	/**
	 * Can Magento2 be launched?
	 *
	 * @return bool
	 * @since 1.1
	 */
	static public function can_launch() {
		return self::$can_launch;
	}

	/**
	 * Was Magento2 launched?
	 *
	 * @since 1.2.5
	 *
	 * @return bool
	 */
	static public function was_launched() {
		return self::$was_launched;
	}

	/**
	 * Get error helper obj, where errors are stored
	 *
	 * @since 1.2.5
	 *
	 * @return M2I_Error_Helper|null Null if is calling before init. method
	 */
	static public function get_error_helper() {
		return self::$error_helper;
	}

	/**
	 * @param string $type Static, media, etc...
	 *
	 * @return string
	 */
	static public function get_base_url( $type = 'static' ) {
		$base_url = '';
		if ( self::$can_launch && ( $store = self::get_store() ) ) {
			self::$needs_mage_translate = true;
			$base_url = $store->getBaseUrl( $type );
			self::$needs_mage_translate = false;
		}
		return $base_url;
	}

	/**
	 * Get current store
	 *
	 * @since 1.2.6
	 *
	 * @return null|Magento\Store\Model\Store
	 */
	static public function get_store() {
		self::$needs_mage_translate = true;
		if ( ! self::$store ) {
			$obj = self::get_bootstrap()->getObjectManager();
			try {
				self::$store = $obj->get( Magento\Store\Model\StoreManagerInterface::class )->getStore();
			} catch ( NoSuchEntityException $e ) {
				self::maybe_store_error( $e );
				self::add_new_error( 'get_store__error', $e );
			} catch ( Exception $e ) {
				self::add_new_error( 'get_store__error', $e );
			} catch ( Throwable $e ) {
				self::add_new_error( 'get_store__error', $e );
			}
		}
		self::$needs_mage_translate = false;
		return self::$store;
	}

	/**
	 * @global array $m2i_options Since 1.2.5.1, runs from root main setting added.
	 *
	 * @return bool
	 */
	static public function is_mage_runs_from_root() {
		global $m2i_options;
		return $m2i_options['mage_runs_from_root'] == 'on';
	}

	/**
	 * Add new error via Error helper object from Exception and error code.
	 *
	 * @since 1.2.5
	 *
	 * @param string $error_code Error code used in WP_Error
	 * @param Exception|Throwable $error
	 */
	static protected function add_new_error( $error_code, $error ) {
		self::$error_helper->add_error_to_errors_container( new WP_Error(
			$error_code,
			$error->getMessage(),
			$error->getTraceAsString()
		) );
		if ( WP_DEBUG ) {
			error_log( $error->getMessage() . "\n" . $error->getTraceAsString() );
		}
	}

}
