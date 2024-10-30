<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I_Settings class for providing of using options in the M2I plugin
 *
 * @since 1.0.5 Singleton implemented
 *
 * @method string get_active_tab()
 * @method string get_page_name()
 * @method array get_options()
 * @method array get_page_tabs()
 */
class M2I_Settings {

	/** @since 1.0.5 */
	static private $instance = null;

	/** @var array */
	protected $options;

	/** @since 1.0.5 */
	protected $page_name;

	/** @since 1.0.5 */
	protected $page_tabs;

	/** @var array Store in self callbacks objects for futher using them as output functions for <b>texts</b> options */
	public $text_callbacks;

	/** @var array Store in self callbacks objects for futher using them as output functions for <b>checkboxes</b> options */
	public $flag_callbacks;

	/**
	 *  @var array Store in self callbacks objects for futher using them as output functions for <b>selects</b> options
	 *
	 *  @since 0.4.9
	 */
	public $select_callbacks;

	/**
	 *  @var array Store fields params for using them in the <b>add_fields()</b>
	 *
	 *  @since 0.4.2
	 */
	public $fields_params;

	/**
	 * Init. once a time
	 *
	 * @since 1.0.5
	 */
	static final function init() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}
	}

	/**
	 * Return instance of current class
	 *
	 * @return M2I_Settings
	 * @since 1.0.5
	 */
	static final function get_instance() {
		return self::$instance;
	}

	/**
	 *  Constructs ability for using options and constants in the M2I plugin
	 *
	 *  @since 1.0.5 Final protected
	 */
	final protected function __construct() {
		$this->page_name = basename( __FILE__ );
		$this->page_tabs = array('m2i_settings_general' => __( 'General Settings', 'm2wp' ), 'm2i_settings_more' => __( 'Advanced Settings', 'm2wp' ));

		$this->register_filters();
		$this->register_fields_params();
		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'current_screen', array($this, 'screen') );
	}

	/**
	 *  Method for <b>admin_menu</b> hook. <br>
	 *  Start point for all methods in that object.
	 */
	function admin_menu() {
		$this->add_sections();
		$this->add_fields();
		$admin_page = add_options_page(
			__( 'Magento 2 Integration', 'm2wp' ), __( 'Magento 2 Integration', 'm2wp' ), 'manage_options', basename( __FILE__ ), array($this, 'page')
		);
		/* Adds help tab when the page loads */
		add_action( 'load-' . $admin_page, array($this, 'page_add_help_tab') );
	}

	/**
	 *  Registers all params for all fields.<br>
	 *  Some of them can be used for init. process of M2I plugin.<br>
	 *  As example: constants <i>M2I_MAGE_DIR</i>, <i>M2I_MAGE_BASE_NAME</i>.
	 *
	 *  @since 0.4.2
	 */
	function register_fields_params() {
		$section = $page = 'm2i_settings_general';

		$this->register_field_params( 'm2i_mage_dir', __( 'Absolute path to Magento 2 root directory', 'm2wp' ), 'text', $page, $section );
		$this->register_field_params( 'm2i_mage_runs_from_root', __( 'Magento 2 runs from root directory', 'm2wp' ), 'flag', $page, $section );
		$this->register_field_params( 'm2i_auto_store_selection', __( 'Auto store view selection', 'm2wp' ), 'flag', $page, $section, array(
			'description' => __( 'not recommended', 'm2wp' ),
			'dependencies' => array(
				'hide' => array('m2i_mage_store_code'),
			)
		) );
		$this->register_field_params( 'm2i_mage_store_code', __( 'Store View', 'm2wp' ), 'select', $page, $section );

		$section = 'm2i_settings_content';

		$this->register_field_params( 'm2i_mage_auto_adding', __( 'Add header/footer automatically', 'm2wp' ), 'flag', $page, $section, array(
		    'description' => __( 'recommended', 'm2wp' )
		) );

		$this->register_field_params( 'm2i_mage_header_block_name', __( 'Names for header block in layout (order preserved)', 'm2wp' ), 'select', $page, $section, array('multi_ordering' => true) );
		$this->register_field_params( 'm2i_mage_footer_block_name', __( 'Names for footer block in layout (order preserved)', 'm2wp' ), 'select', $page, $section, array('multi_ordering' => true) );

		$page = 'm2i_settings_more';
		$section = 'm2i_dom_approach';

		$this->register_field_params( 'm2i_use_mage_layout_names', __( 'Enable Magento Layout approach', 'm2wp' ), 'flag', $page, $section, array(
		    'description' => __( 'Fetch elements from Magento 2 by using Magento Layout structure, otherwise will used DOM aproach (not recommended).', 'm2wp' ),
		    'dependencies' => array(
			'hide' => array('m2i_mage_header_css_selector', 'm2i_mage_footer_css_selector'),
		    )
		) );

		$this->register_field_params( 'm2i_mage_header_css_selector', __( 'CSS selector for header section', 'm2wp' ), 'text', $page, $section );
		$this->register_field_params( 'm2i_mage_footer_css_selector', __( 'CSS selector for footer section', 'm2wp' ), 'text', $page, $section );

		$section = 'm2i_management';

		$this->register_field_params( 'm2i_use_native_dom_document', __( 'Use native DOMDocument', 'm2wp' ), 'flag', $page, $section );

		$this->register_field_params( 'm2i_mage_header_flag', __( 'Show header', 'm2wp' ), 'flag', $page, $section );
		$this->register_field_params( 'm2i_mage_footer_flag', __( 'Show footer', 'm2wp' ), 'flag', $page, $section );

		$this->register_field_params( 'm2i_mage_scripts_head_flag', __( 'Include head JS files', 'm2wp' ), 'flag', $page, $section );
		$this->register_field_params( 'm2i_mage_scripts_body_flag', __( 'Include body JS files', 'm2wp' ), 'flag', $page, $section );
		$this->register_field_params( 'm2i_mage_move_scripts_to_footer_flag', __( 'Move Magento2 JS files to footer', 'm2wp' ), 'flag', $page, $section );
        $this->register_field_params( 'm2i_scripts_to_filter', __( 'Exclude JS files', 'm2wp' ), 'text', $page, $section, array('description' => __( 'Exclude certain JS files before including JS files from Magento 2 to avoid conflicts. Separate scripts names with commas. Only scripts from', 'm2wp' ) . ' <code>' . esc_html( '<head>' ) . '</code> ' . __( 'will be filtered.', 'm2wp' )) );
		$this->register_field_params( 'm2i_mage_styles_flag', __( 'Include CSS files', 'm2wp' ), 'flag', $page, $section );
		$this->register_field_params( 'm2i_mage_js_flag', __( 'Include JS scripts', 'm2wp' ), 'flag', $page, $section );

		$this->register_field_params( 'm2i_disable_select2', __( 'Do not use select2.js lib for input fields in admin', 'm2wp' ), 'flag', $page, $section );
	}

	/**
	 *  Adds all sections for well work of M2I sub-pages
	 *
	 *  @since 0.4.2
	 */
	function add_sections() {
		$section = $page = 'm2i_settings_general';

		add_settings_section(
			$section, __( 'Configuration settings', 'm2wp' ), function() {
			echo '<p>' . __( 'Please, configure the options below to activate the integration with Magento 2. After validation make sure to save the options.', 'm2wp' ) . '</p>';
		}, $page
		);

		$section = 'm2i_settings_content';

		add_settings_section(
			$section, __( 'Automatic integration settings', 'm2wp' ), function() {
			echo '<p>' . __( 'The options below are optional and will not work with all themes. This plugin will try to automatically show styles, javascript and headers and footer in case you ask this. When this feature does not work for your setup, then please use our custom functions in your template files (see documentation at', 'm2wp' ) . ' <a href="https://wordpress.org/plugins/m2wp/#description" target="_blank">https://wordpress.org/plugins/m2wp/</a>).</p>';
		}, $page
		);

		$page = 'm2i_settings_more';
		$section = 'm2i_dom_approach';

		add_settings_section(
			$section, __( 'Alternative way to fetch Magento 2 elements', 'm2wp' ), function() {
			echo '<p>' . __( 'Only use these options when you know what you are doing. By enabling this feature we will try to fetch information from Magento 2 by using another approach. We recommend using this feature only when you get stuck with the normal approach or when you dont get the results you want.', 'm2wp' ) . '</p>';
		}, $page
		);

		$section = 'm2i_management';
		add_settings_section(
			$section, __( 'Advanced Options', 'm2wp' ), function() {
			echo '<p></p>';
		}, $page
		);
	}

	/**
	 *  Adds fields to related sections and sub-pages. Uses for this <i>$this->fields_params</i> assoc. array.
	 *
	 *  @since 0.4.2
	 */
	function add_fields() {
		foreach ( $this->fields_params as $id => $field_params ) {
			add_settings_field(
			        $id,
                    $field_params['title'],
                    $field_params['callback'],
				    $field_params['page'],
    				$field_params['section'],
				    $field_params['args']
            );
			register_setting( $field_params['page'], $id );
		}
	}

	/**
	 *  Creates a suffix from the option id
	 *
	 *  @param string $id Id of the option
	 *  @return string Id part without <i>m2i_</i> slug
	 *
	 *  @since 0.3
	 */
	protected function get_suffix( $id ) {
		return substr( $id, 4 );
	}

	/**
	 *  Generates html content for the description section under option
	 *
	 *  @param string $description Description text for an option
	 *
	 *  @since 0.4
	 */
	protected function get_description( $description ) {
		return sprintf( '<br/><p class="description">%1$s</p>', $description );
	}

	/**
	 *  Checks if defined constant can be constant with rule <b>$will_be_constant</b>
	 *
	 *  @param string $constant_id
	 *  @param bool $will_be_constant
	 *
	 *  @throws LogicException
	 *
	 *  @since 0.4
	 */
	protected function can_be_constant_exception( $constant_id, $will_be_constant ) {
		// TODO: Create specific class for M2I_Constant_Exception
		if ( defined( $constant_id ) && ! $will_be_constant ) {
			throw new LogicException( "$constant_id can not be defined. It is against the rules!" );
		}
	}

	/**
	 *  Register settings field params with callback by id to <i>$this->fields_params</i>
	 *
	 *  @param string $id Id of the option
	 *  @param string $title Tittle for the option
	 *  @param string $type Type of the option
	 *  @param string $page Page for futher displaying
	 *  @param string $section Section for futher displaying
	 *  @param array $args Arguments to be passed for the option
	 *
	 *  @since 0.4.2
	 */
	protected function register_field_params( $id, $title, $type, $page, $section, $args = array() ) {
		$property = "{$type}_callbacks";

		if ( property_exists( $this, $property ) ) {
			$method = "create_{$type}_option";

			if ( method_exists( $this, $method ) ) {
				$this->{$property}[$id] = $this->{$method}( $id );
				$this->fields_params[$id] = array(
				    'title' => $title,
				    'callback' => $this->{$property}[$id],
				    'page' => $page,
				    'section' => $section,
				    'args' => $args
				);
			}
		}
	}

	/**
	 *  Creates callback specified to checkbox(flag) option
	 *
	 *  @param string $id Id of option
	 *  @return callback
	 *
	 *  @since 0.3
	 */
	protected function create_flag_option( $id ) {
		$suffix = $this->get_suffix( $id );
		$option_value = apply_filters( 'm2i_flag_value_' . $suffix, get_option( $id ), $id );

		$this->options[$suffix] = $option_value;

		return function($args) use ($id, $option_value, $suffix) {
			echo "<input type='checkbox' id='$id' name='$id' " .
			( ! empty( $option_value ) ? 'checked=\'checked\'' : '') .
			(isset( $args['dependencies']['hide'] ) ? 'data-dependencies-hide=\'' . implode( ',', $args['dependencies']['hide'] ) . '\'' : '') .
			(isset( $args['dependencies']['show'] ) ? 'data-dependencies-show=\'' . implode( ',', $args['dependencies']['show'] ) . '\'' : '') .
			" />";

			if ( isset( $args['description'] ) ) {
				echo $this->get_description( $args['description'] );
			}

			do_action( "m2i_flag_after_output_$suffix", $id, $option_value );
		};
	}

	/**
	 *  Creates callback specified to select option
	 *
	 *  @param string $id Id of option
	 *  @return callback
	 *
	 *  @since 0.4.9
	 */
	protected function create_select_option( $id ) {
		$suffix = $this->get_suffix( $id );
		$option_value = apply_filters( 'm2i_select_checked_value_' . $suffix, get_option( $id ), $id );
		$values_filter = 'm2i_select_values_' . $suffix;

		$this->options[$suffix] = $option_value;

		return function($args) use ($id, $option_value, $values_filter, $suffix) {
			$is_multi_ordering = empty( $args['multi_ordering'] ) ? false : true;
			$main_value = ($is_multi_ordering && is_array( $option_value )) ? current( $option_value ) : $option_value;
			$values = apply_filters( $values_filter, array($main_value => $main_value) );
			printf( '<select id="%s" name="%s" %s %s>', $id, $is_multi_ordering ? $id . '[]' : $id, $is_multi_ordering ? 'data-type="multi-ordering"' : '', ($is_multi_ordering && count( $option_value ) > 1) ? 'data-others="' . esc_attr( wp_json_encode( array_slice( $option_value, 1 ) ) ) . '"' : '' );
			foreach ( $values as $s_id => $item_value ) {
				printf( '<option value="%s" %s>%s</option>', $s_id, $s_id == $main_value ? 'selected' : '', $item_value );
			}
			print('</select>' );

			if ( isset( $args['description'] ) ) {
				echo $this->get_description( $args['description'] );
			}

			do_action( "m2i_select_after_output_$suffix", $id, $option_value );
		};
	}

	/**
	 *  Creates callback specified to text option. <br>
	 *  Also can <b>define</b> the constant from the option or vice versa, if needed.
	 *
	 *  @param string $id Id of option
	 *  @return callback
	 *
	 */
	protected function create_text_option( $id ) {
		$suffix = $this->get_suffix( $id );
		$constant_id = strtoupper( $id );
		$will_be_constant = apply_filters( 'm2i_text_will_be_constant_' . $suffix, false, $id, $constant_id );

		$this->can_be_constant_exception( $constant_id, $will_be_constant );

		if ( ! ($is_constant = defined( $constant_id )) ) {
			$option_value = apply_filters( 'm2i_text_value_' . $suffix, get_option( $id ), $id );

			if ( $will_be_constant ) {
				define( $constant_id, $option_value );
			} else {
				$this->options[$suffix] = $option_value;
			}
		} else {
			$option_value = constant( $constant_id );
		}

		return function($args) use ($id, $option_value, $is_constant, $suffix) {
			$format = '<input type="text" class="regular-text code %1$s" %1$s id="%2$s" name="%2$s" value="%3$s">';
			printf( $format, $is_constant ? 'disabled' : '', $id, esc_attr( $option_value ) );

			if ( isset( $args['description'] ) ) {
				echo $this->get_description( $args['description'] );
			}

			do_action( "m2i_text_after_output_$suffix", $id, $option_value );
		};
	}

	/**
	 *  Registers filters, used in the settigns page, mainly for options
	 */
	protected function register_filters() {
		add_filter( 'm2i_text_will_be_constant_mage_dir', array($this, 'text_constants_filter'), 10, 2 );
		add_filter( 'm2i_flag_value_mage_runs_from_root', array($this, 'value_mage_flag_runs_from_root') );
		add_filter( 'm2i_select_checked_value_mage_store_code', function($value) {
			return $value === false ? 'default' : $value;
		} );
		add_filter( 'm2i_select_values_mage_store_code', array($this, 'select_values_mage_store_code') );
		/*add_action( 'm2i_select_after_output_mage_store_code', array($this, 'select_after_output_mage_store_code') );*/

		add_filter( 'm2i_flag_value_mage_auto_adding', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_flag_value_use_mage_layout_names', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_select_checked_value_mage_header_block_name', function($value) {
			return $value === false ? array('') : ( ! is_array( $value ) ? array($value) : $value);
		} );
		add_filter( 'm2i_select_checked_value_mage_footer_block_name', function($value) {
			return $value === false ? array('') : ( ! is_array( $value ) ? array($value) : $value);
		} );
		add_filter( 'm2i_select_values_mage_header_block_name', array($this, 'select_values_mage_block_name') );
		add_filter( 'm2i_select_values_mage_footer_block_name', array($this, 'select_values_mage_block_name') );
		add_filter( 'm2i_text_value_mage_dir', array($this, 'text_value_mage_dir_filter') );
		add_filter( 'm2i_text_value_mage_header_css_selector', array($this, 'text_value_mage_header_css_selector_legacy') );
		add_filter( 'm2i_text_value_mage_footer_css_selector', array($this, 'text_value_mage_footer_css_selector_legacy') );

		add_filter( 'm2i_use_native_dom_document', function( $value ) {
			return $value === false ? '' : $value;
        } );
		add_filter( 'm2i_flag_value_mage_header_flag', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_flag_value_mage_footer_flag', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_flag_value_mage_scripts_head_flag', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_flag_value_mage_scripts_body_flag', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_flag_value_mage_styles_flag', array($this, 'value_mage_flags_filter') );
		add_filter( 'm2i_flag_value_mage_js_flag', array($this, 'value_mage_flags_filter') );

		do_action( 'm2i_register_settings_filters' );
	}

	/* FILTERS */

	function text_constants_filter( $will_be_constant, $id ) {
		switch ( $id ) {
			case 'm2i_mage_dir':
			case 'm2i_mage_base_name':
				$will_be_constant = true;
		}

		return $will_be_constant;
	}

	function text_value_mage_dir_filter( $value ) {
		return empty( $value ) ? ABSPATH : $value;
	}

	/**
	 * @access private
     * Only for dev. purpose
	 */
	function select_after_output_mage_store_code() {
	    $wp_path = preg_replace( '/wp-admin.+/i', '', $_SERVER['SCRIPT_FILENAME'] );
		$directory = new RecursiveDirectoryIterator( dirname( dirname( $wp_path ) ) );
		$iterator = new RecursiveIteratorIterator( $directory );
		$regex = new RegexIterator( $iterator, '/^.+app\/autoload\.php$/i', RecursiveRegexIterator::GET_MATCH );
		echo '<p>Possible Magento2 auto loaders: </p>';
		foreach( $regex as $files ) {
		    foreach ( $files as $file ) {
			    if ( strpos( $file, 'vendor' ) === false ) {
				    echo 'Path: <b>' . $file . '</b>. Readable: <b>' . ( is_readable( $file ) ? 'yes' : 'no' ) . '</b><br/>';
			    }
		    }
        }
    }

	/**
	 * @todo Remove function in some versions
	 * @since 1.1.3
	 */
	function text_value_mage_header_css_selector_legacy( $value ) {
		return $this->text_value_mage_css_selector_legacy( $value, 'header' );
	}

	/**
	 * @todo Remove function in some versions
	 * @since 1.1.3
	 */
	function text_value_mage_footer_css_selector_legacy( $value ) {
		return $this->text_value_mage_css_selector_legacy( $value, 'footer' );
	}

	/**
	 * @todo Remove function in some versions
	 * @since 1.1.3
	 */
	protected function text_value_mage_css_selector_legacy( $value, $tag ){
		$block_tag = get_option( "m2i_mage_{$tag}_tag" );
		$block_class = get_option( "m2i_mage_{$tag}_class" );
		$legacy_selector = '';
		if ( $block_tag ) {
			$legacy_selector .= $block_tag;
		}
		if ( $block_class ) {
			$legacy_selector .= ".$block_class";
		}
		return $value === false ? $legacy_selector : $value;
	}

	function value_mage_flags_filter( $value ) {
		return $value === false ? 'on' : $value;
	}

	function select_values_mage_block_name( $value ) {
		$blocks = m2i_get_blocks();
		$values = array_merge( $value, array_combine( $blocks, $blocks ) );
		asort( $values );
		return $values;
	}

	function select_values_mage_store_code( $value ) {
		$stores = m2i_get_stores();
		foreach ( $stores as $code => $store ) {
		    $stores[$code] = "$store (code: $code)";
        }
		$values = array_merge( $value, $stores );
		$default = get_option( 'm2i_mage_default_store_code', 'default' );
		if ( empty( $stores[$default] ) ) {
			$stores[$default] = $default;
		}
		asort( $values );
		return $values;
	}

	/**
     * Filter for the runs from root setting
     *
     * @since 1.2.5.1
     *
	 * @param $value string|null|false
	 *
	 * @return string|null|false
	 */
	function value_mage_flag_runs_from_root( $value ) {
		$base_url = get_option( 'm2i_mage_base_url' );
		return $value === false ? ( strpos( $base_url, '/pub/static' ) !== false ? 'on' : false ) : $value;
    }

	/**
	 * FUNCTION OF RENDERING PAGE
	 */
	function page( $atts = array() ) {
		$active_tab = $this->get_active_tab();
		?>
		<div class="wrap">
			<div class="options-panel">
				<h2><?php echo __( 'Magento 2 Integration Settings', 'm2wp' ) ?></h2>

				<?php $this->output_page_tabs(); ?>

				<form method="post" action="options.php">
					<?php
					settings_fields( $active_tab );

					do_settings_sections( $active_tab );

					submit_button();
					?>
				</form>
			</div>
			<div class="clearfix"></div>
		</div>
		<?php
	}

	/** @since 1.1.3 */
	function page_add_help_tab () {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
		    'id'	=> 'm2i_help_tab',
		    'title'	=> __( 'Help Tab', 'm2wp' ),
		    'content'	=> sprintf(
					'<p><a href="%s" title="%s" target="_blank">%s</a></p>',
					'https://wordpress.org/plugins/m2wp/#installation',
					__( 'Go to the help link', 'm2wp' ),
					__( 'Please read our full implementation FAQ on WordPress website in case you run into problems with setting up the plugin.', 'm2wp' )
				)
		) );
	}

	/** @since 1.0.5 */
	function output_page_tabs() {
		$active_tab = $this->get_active_tab();
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->page_tabs as $key => $name ) {
			printf( '<a href="?page=%s&tab=%s" class="nav-tab%s">%s</a>', $this->page_name, $key, $active_tab === $key ? ' nav-tab-active' : '', $name );
		}
		echo '</h2>';
	}

	/** @since 1.0.5 */
	public function __call( $name, $params ) {
		if ( strpos( $name, 'get_' ) === 0 ) {
			$name = substr( $name, 4 );
			switch ( $name ) {
				case 'active_tab':
					return (isset( $_GET['tab'] ) ? $_GET['tab'] : key( $this->page_tabs ));
				default:
					if ( property_exists( $this, $name ) ) {
						return $this->{$name};
					}
					break;
			}
		}

		return null;
	}

	/** @since 0.4.9 */
	function screen() {
		$current_screen = get_current_screen();

		if ( $current_screen->id === 'settings_page_M2I_Settings' ) {
			M2I_External::launch();
		}
	}

}

M2I_Settings::init();
