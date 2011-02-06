<?php

/**
 * @see AF_Loader
 */
require_once 'AF/Loader.php';

abstract class AF_DAO_Factory {
	
	public static function getDAO($controller = false, $options = false) {
		if (empty($controller) || !is_object($controller)) {
			require_once 'AF/Exception.php';
			throw new AF_Exception("Controller object inaccessible while instantiating model.");
		}
		
		$controller_name = null;
		if (in_array('getControllerName', get_class_methods(get_class($controller)))) {
			$controller_name = $controller->getControllerName();
		}
		else {
			require_once 'AF/Exception.php';
			throw new AF_Exception("Controller name inaccessible while instantiating model.");
		}
		
		if ($controller_name && AF_Loader::isReadable($controller_name.'DAO.php')) {
			$dao_name = $controller_name . 'DAO';
			// This throws an exception if the specified class cannot be loaded.
			AF_Loader::loadClass($dao_name);
		}
		else {
			require_once 'AF/DAO/Default.php';
			$dao_name = 'AF_DAO_Default';
		}
		
		// We instantiate the DAO
		$dao = new $dao_name($controller, $options);
		
		return $dao;
	}		
}