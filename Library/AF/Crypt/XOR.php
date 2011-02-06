<?php 

class AF_Crypt_XOR extends AF_Crypt_Abstract {
	
	public function encrypt($plain_text, $key) {
		if (!is_string($plain_text) || empty($plain_text) || !is_string($key) || empty($key)) {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception('The string to be encrypted and the key should be non-empty strings.');
		}
		
		return base64_encode($this->_xor_process($plain_text, $key));
	}
	
	public function decrypt($cipher_text, $key) {
		if (!is_string($cipher_text) || empty($cipher_text) || !is_string($key) || empty($key)) {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception('The cipher text and the key should be non-empty strings.');
		}
		
		return $this->_xor_process(base64_decode($cipher_text), $key);
	}
	
	protected function _xor_process($string, $key) {
		$key_length = strlen($key);
		$string_length = strlen($string);
		
		for ($i=0; $i<$string_length; $i++) {
			$r_pos = $i % $key_length;
			$r = ord($string[$i]) ^ ord($key[$r_pos]);
			$string[$i] = chr($r);
		}
		
		return $string;
	}
}