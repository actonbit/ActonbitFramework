<?php

/**
 * @see AF_Loader
 */
require_once 'AF/Loader.php';

class AF_DAO_Default {
	
	// The constants used for description of the 
	// configuration options available for the DA Object.
	const AF_DAO_DB_NAME   			= '_af_dao_db_name';
	const AF_DAO_IDENTIFIER 		= '_af_dao_identifier';
	const AF_DAO_IDENTIFIER_VALUE 	= '_af_dao_identifier_value';
	const AF_DAO_CONDITIONS 		= '_af_dao_conditions';
	const AF_DAO_ALIAS 				= '_af_dao_alias';
	const AF_DAO_RESULTTYPE 		= '_af_dao_resulttype';
	const AF_DAO_NO_UPDATE 			= '_af_dao_no_update';
	
	protected $_options;
	
	protected $_controller = null;
	protected $_controller_id = null;
	protected $_controller_name = null;
	protected $_db = null;
	protected $_to = null;
	
	public function __construct($controller, $options = false) {
		if (is_object($controller) && is_subclass_of($controller, 'AF_Controller_Abstract')) {
			$this->_controller = $controller;
		}
		else {
			require_once 'AF_Exception.php';
			throw new AF_Exception("Passed object is not an instance of type AF_Controller_Abstract.");
		}
		
		$this->_controller_id = $this->getControllerId();
		$this->_controller_name = $this->getControllerName();
		
		// Reading in the options
		$this->_checkOptions($options);
		
		if (isset($this->_options[self::AF_DAO_DB_NAME]) && $this->_options[self::AF_DAO_DB_NAME]) {
			$this->_db = AF_Registry::get($this->_options[self::AF_DAO_DB_NAME]);
		}
		
		$this->_to = AF_TO_Factory::getTO($this->_controller);
	}
	
	/**
	 * Here we check if any changes were made to the data of the controller 
	 * object and save these in the Database.
	 * @return 
	 * @access public
	 */
	public function __destruct() {
		$this->_update();
	}
	
	protected function _update() {
		if ($this->_to->isChanged()) {
			$table_name = strtolower($this->_controller_name);
			$attributes = $this->_to->dump();
			$bind = null;
			foreach ($attributes AS $name => $value) {
				if (!in_array($name,$this->_options[self::AF_DAO_NO_UPDATE])) {
					$bind[$name] = $value;
				}
			}
			if ($bind) {
				$condition = array($this->_options[self::AF_DAO_IDENTIFIER] => $this->_options[self::AF_DAO_IDENTIFIER_VALUE]);
//				$condition = $this->_options[self::AF_DAO_IDENTIFIER] . ' = ' . $this->_options[self::AF_DAO_IDENTIFIER_VALUE];
				$this->_db->update($table_name, $bind, $condition);
			}
		}
	}
	
	/**
	 * This method parses the options passed to the dao on instantiation. 
	 * 
	 * @param object $options The array that holds the passed options.
	 * @access protected
	 */
	protected function _checkOptions($options) {
		// Set default option values
		$this->_setDefaultOptions();
		// Parsing options
		if (!empty($options) && is_array($options)) {
			
			if (isset($options[self::AF_DAO_DB_NAME]) && trim($options[self::AF_DAO_DB_NAME])<>'') {
				$this->_options[self::AF_DAO_DB_NAME] = $options[self::AF_DAO_DB_NAME];
			}
			if (isset($options[self::AF_DAO_IDENTIFIER]) && trim($options[self::AF_DAO_IDENTIFIER])<>'') {
				$this->_options[self::AF_DAO_IDENTIFIER] = $options[self::AF_DAO_IDENTIFIER];
			}
			if (isset($options[self::AF_DAO_CONDITIONS]) && trim($options[self::AF_DAO_CONDITIONS])<>'') {
				$this->_options[self::AF_DAO_CONDITIONS] = $options[self::AF_DAO_CONDITIONS];
			}
			if (isset($options[self::AF_DAO_ALIAS]) && trim($options[self::AF_DAO_ALIAS])<>'') {
				$this->_options[self::AF_DAO_ALIAS] = $options[self::AF_DAO_ALIAS];
			}
			if (isset($options[self::AF_DAO_RESULTTYPE]) && is_numeric($options[self::AF_DAO_RESULTTYPE])) {
				$this->_options[self::AF_DAO_RESULTTYPE] = $options[self::AF_DAO_RESULTTYPE];
			}
			if (isset($options[self::AF_DAO_NO_UPDATE]) && $options[self::AF_DAO_NO_UPDATE]) {
				if (is_string($options[self::AF_DAO_NO_UPDATE]) && trim($options[self::AF_DAO_NO_UPDATE])<>'') {
					$this->_options[self::AF_DAO_NO_UPDATE] = array(trim($options[self::AF_DAO_NO_UPDATE]));
				}
				elseif (is_array($options[self::AF_DAO_NO_UPDATE]) && count($options[self::AF_DAO_NO_UPDATE])) {
					foreach ($options[self::AF_DAO_NO_UPDATE] AS $item) {
						if (is_string($item) && trim($item)<>'') {
							$this->_options[self::AF_DAO_NO_UPDATE][] = trim($item);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Here the default option values are set. Any change to the desired 
	 * default values should be done here as these are not set in any 
	 * configuration file.
	 * 
	 * @access protected
	 */
	protected function _setDefaultOptions() {
		$this->_options = array (
			self::AF_DAO_DB_NAME => 'db',
			self::AF_DAO_IDENTIFIER => 'id',
			self::AF_DAO_IDENTIFIER_VALUE => $this->_controller_id,
			self::AF_DAO_CONDITIONS => false,
			self::AF_DAO_ALIAS => false,
			self::AF_DAO_RESULTTYPE => MYSQLI_ASSOC,
			self::AF_DAO_NO_UPDATE => array(),
		);
	}
	/**
	 * 
	 * @return 
	 * @access public
	 */
	public function _init() {
		$this->getDBContent();
	}
	
	/**
	 * 
	 * @return 
	 * @access public
	 */
	public function getTO() {
		return $this->_to;
	}
	
	/**
	 * 
	 * @return 
	 * @access public
	 */
	public function getControllerId() {
		return $this->_controller->getId();
	}
	
	/**
	 * 
	 * @return 
	 * @access public
	 */
	public function getControllerName() {
		return $this->_controller->getControllerName();
	}
	
	/**
	 * This method queries the Database and retrieves all matching results
	 * in order to initialise the TO and pass the required data to the 
	 * business logic object.
	 * @access public
	 * @TODO Would possibly be good to arrange so that table name can be 
	 * configurable too.
	 */
	public function getDBContent() {
		if (in_array('fetchRowById',get_class_methods($this->_db))) {
			$table_name = strtolower($this->_controller_name);
			$id = $this->_options[self::AF_DAO_IDENTIFIER_VALUE];
			$options = array (
				AF_DB_Adapter_Abstract::AF_DB_ADAPTER_IDENTIFIER => $this->_options[self::AF_DAO_IDENTIFIER],
				AF_DB_Adapter_Abstract::AF_DB_ADAPTER_CONDITIONS => $this->_options[self::AF_DAO_CONDITIONS],
				AF_DB_Adapter_Abstract::AF_DB_ADAPTER_ALIAS => $this->_options[self::AF_DAO_ALIAS],
				AF_DB_Adapter_Abstract::AF_DB_ADAPTER_RESULTTYPE => $this->_options[self::AF_DAO_RESULTTYPE]
			);
			if ($row = $this->_db->fetchRowById($table_name, $id, $options)) {
				foreach ($row AS $key => $value) {
					$this->_to->set($key, $value);
				}
			}
		}
	}
	
	public function extractFromTO($field_name) {
		try {
			$value = $this->_to->get($field_name);
		}
		catch (AF_Exception $afe) {
			require_once 'AF_Exception.php';
			throw new AF_Exception("Error while trying to extract data from Transfer Object for key '$field_name'.");
		}
		
		$value = AF_TO_Default::isEmptyString($value) ? (string)'' : 
					(AF_TO_Default::isZero($value) ? (int)'0' : 
						(AF_TO_Default::isNull($value) ? new AF_DB_Expression('NULL') : 
							(AF_TO_Default::isFalse($value) ? (int)'0' : 
								(AF_TO_Default::isTrue($value) ? (int)'1' : 
									$value
								)
							)
						)
					);
					
		return $value;
	}
	
	
}