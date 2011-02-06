<?php 

/**
 * @see AF_Loader
 */
require_once 'AF/Loader.php';

abstract class AF_Crypt_Factory {
	
	const AF_CRYPT_FACTORY_METHOD = '_af_crypt_factory_method';
	
	const AF_CRYPT_FACTORY_METHOD_MCRYPT = '_af_crypt_factory_method_mcrypt';
	const AF_CRYPT_FACTORY_METHOD_XOR = '_af_crypt_factory_method_xor';
	
	const AF_CRYPT_FACTORY_MCRYPT_ALGO = '_af_crypt_factory_mcrypt_algo';
	const AF_CRYPT_FACTORY_MCRYPT_ALGO_RIJNDAEL_256 = 'rijndael-256';
	
	const AF_CRYPT_FACTORY_MCRYPT_MODE = '_af_crypt_factory_mcrypt_mode';
	const AF_CRYPT_FACTORY_MCRYPT_MODE_CTR = 'ctr';
	const AF_CRYPT_FACTORY_MCRYPT_MODE_CBC = 'cbc';
	
	const AF_CRYPT_FACTORY_MCRYPT_ALGO_DIR = '_af_crypt_factory_mcrypt_algo_dir';
	const AF_CRYPT_FACTORY_MCRYPT_MODE_DIR = '_af_crypt_factory_mcrypt_mode_dir';
	
	const AF_CRYPT_FACTORY_MCRYPT_ALGO_DEFAULT = self::AF_CRYPT_FACTORY_MCRYPT_ALGO_RIJNDAEL_256;
	const AF_CRYPT_FACTORY_MCRYPT_MODE_DEFAULT = self::AF_CRYPT_FACTORY_MCRYPT_MODE_CTR;
	
	const AF_CRYPT_FACTORY_METHOD_DEFAULT = self::AF_CRYPT_FACTORY_METHOD_XOR;

	public static function getEngine($options = false) {
		
		// Turning $options to empty array if no options are specified
		if ($options === false) {
			$options = array();
		}
		elseif (!is_array($options)) {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception("Illegal encrypt options format.");
		}
		
		if (isset($options[self::AF_CRYPT_FACTORY_METHOD]) && !empty($options[self::AF_CRYPT_FACTORY_METHOD])) {
			$options[self::AF_CRYPT_FACTORY_METHOD] = strtolower($options[self::AF_CRYPT_FACTORY_METHOD]);
			if ($options[self::AF_CRYPT_FACTORY_METHOD] == self::AF_CRYPT_FACTORY_METHOD_MCRYPT) {
				if (function_exists('mcrypt_module_open')) {
					
					if (!isset($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO_DIR]) || empty($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO_DIR])) {
						$options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO_DIR] = ini_get('mcrypt.algorithms_dir') ? ini_get('mcrypt.algorithms_dir') : '';
					}
					
					if (!isset($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE_DIR]) || empty($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE_DIR])) {
						$options[self::AF_CRYPT_FACTORY_MCRYPT_MODE_DIR] = ini_get('mcrypt.modes_dir') ? ini_get('mcrypt.modes_dir') : '';
					}
					
					$available_operation_modes = mcrypt_list_modes($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE_DIR]);
					
					if (isset($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE]) && !empty($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE])) {
						if (!in_array($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE],$available_operation_modes)) {
							require_once 'AF/Crypt/Exception.php';
							throw new AF_Crypt_Exception('This mcrypt mode of operation "'.$options[self::AF_CRYPT_FACTORY_MCRYPT_MODE].'" was not found in modes of operation directory.');
						}
						
						if ($options[self::AF_CRYPT_FACTORY_MCRYPT_MODE] != self::AF_CRYPT_FACTORY_MCRYPT_MODE_CBC &&
							$options[self::AF_CRYPT_FACTORY_MCRYPT_MODE] != self::AF_CRYPT_FACTORY_MCRYPT_MODE_CTR) {
								require_once 'AF/Crypt/Exception.php';
								throw new AF_Crypt_Exception('A non-supported mcrypt mode of operation "'.$options[self::AF_CRYPT_FACTORY_MCRYPT_MODE].'" was specified.');
						}
					}
					else {
						// Setting default value for mode
						$options[self::AF_CRYPT_FACTORY_MCRYPT_MODE] = self::AF_CRYPT_FACTORY_MCRYPT_MODE_DEFAULT;
					}
					
					$available_algos = mcrypt_list_algorithms($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO_DIR]);
					
					if (isset($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO]) && !empty($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO])) {
						if (!in_array($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO], $available_algos)) {
							require_once 'AF/Crypt/Exception.php';
							throw new AF_Crypt_Exception('This mcrypt algorithm was not found in algorithms directory.');
						}
						
						// CHECKING AGAINST SUPPORTED ALGORITHMS.
						## NEW ALGORITHMS SHOULD BE ADDED HERE.
						if ($options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO] != self::AF_CRYPT_FACTORY_MCRYPT_ALGO_RIJNDAEL_256) {
							require_once 'AF/Crypt/Exception.php';
							throw new AF_Crypt_Exception('A non-supported algorithm "'.$options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO].'" was specified.');
						}
					}
					else {
						// Setting default value for algorithm
						$options[self::AF_CRYPT_FACTORY_MCRYPT_ALGO] = self::AF_CRYPT_FACTORY_MCRYPT_ALGO_DEFAULT;
					}
				}
				else {
					require_once 'AF/Crypt/Exception.php';
					throw new AF_Crypt_Exception('Extensions necessary for mcrypt are not present on this system.');
				}
				
				$adapter_name = 'AF_Crypt_Mcrypt';
			}
			elseif ($options[self::AF_CRYPT_FACTORY_METHOD] == self::AF_CRYPT_FACTORY_METHOD_XOR) {
				$adapter_name = 'AF_Crypt_XOR';
			}
			else {
				require_once 'AF/Crypt/Exception.php';
				throw new AF_Crypt_Exception('A non-supported encrypt method "'.$options[self::AF_CRYPT_FACTORY_METHOD].'" was specified.');
			}
		}
		else {
			// Using default method when method not set in options
			$adapter_name = 'AF_Crypt_XOR';
		}
		
		// This throws an exception if the specified class cannot be loaded.
		AF_Loader::loadClass($adapter_name);
		
		// We instantiate the adapter
		$adapter = new $adapter_name($options);
		
		return $adapter;
	}	
}