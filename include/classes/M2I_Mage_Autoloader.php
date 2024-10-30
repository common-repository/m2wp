<?php

defined( 'ABSPATH' ) || exit;

use Magento\Framework\Code\Generator;

/**
 * To load class without any error if it is going from the WP side.
 *
 * @since 1.2
 * @category Class, Core
 * @author ModernModules
 */
class M2I_Mage_Autoloader extends Magento\Framework\Code\Generator\Autoloader {

	/**
	 * Load specified class name and generate it if necessary.
	 * Do it in the lighter way to prevent conflicts with WP plugins, which also use spl_autoload_register()
	 *
	 * @param string $className
	 *
	 * @return bool True if class was loaded
	 */
	public function load( $className ) {
		if ( ! class_exists( $className ) ) {
			try {
				$res = Generator::GENERATION_ERROR != $this->_generator->generateClass( $className );
				return $res;
			} catch ( LogicException $logic_exception ) {
				if ( WP_DEBUG ) {
					/* Log to the debug.log (WordPress) */
					error_log( $logic_exception->getMessage() . "\n" . $logic_exception->getTraceAsString() );
				}
				return false;
			}
		}

		return true;
	}
}