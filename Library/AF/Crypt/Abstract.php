<?php 

class AF_Crypt_Abstract {
	
	protected $_options;
	
	public function __construct($options) {
		$this->_options = $options;
		if (strtolower(get_class($this)) == 'af_crypt_mcrypt') {
			$this->_prepare();
		}
	}
	
	/**
	 * Abstract method to be implemented by child class.
	 */
	abstract public function encrypt($plain_text, $key);
	
	/**
	 * Abstract method to be implemented by child class.
	 */
	abstract public function decrypt($cipher_text, $key);
	
	protected function _prepare() {}
	
}