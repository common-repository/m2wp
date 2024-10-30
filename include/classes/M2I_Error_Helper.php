<?php

defined( 'ABSPATH' ) || exit;

/**
 *
 * Used as a Worker in various classes
 *
 * @class M2I_Error_Helper
 * @category Class, Core
 */
class M2I_Error_Helper {

	/**
	 * @var WP_Error Collects errors during work of the class
	 */
	protected $errors_container;

	/**
	 * The Constructor (by default doesn't require any params be passed).
	 */
	public function __construct() {
		$this->errors_container = new WP_Error;
	}

	/**
	 * Get the error container object
	 *
	 * @return WP_Error
	 */
	public function get_errors_container() {
		return $this->errors_container;
	}

	/**
	 * @return bool True if the worker has any errors
	 */
	public function has_errors() {
		return $this->errors_container->errors ? true : false;
	}

	/**
	 * Add WP_Error to errors container
	 *
	 * @param WP_Error $wp_error
	 *
	 * @return $this
	 */
	public function add_error_to_errors_container( WP_Error $wp_error ) {
		$this->errors_container->add(
			$wp_error->get_error_code(),
			$wp_error->get_error_message(),
			$wp_error->get_error_data()
		);

		return $this;
	}

	/**
	 * Remove all errors in the container via codes
	 *
	 * @return $this
	 */
	public function remove_all_errors() {
		foreach ( $this->errors_container->get_error_codes() as $error_code ) {
			$this->errors_container->remove( $error_code );
		}

		return $this;
	}

}