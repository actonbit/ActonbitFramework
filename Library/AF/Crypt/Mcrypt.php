<?php 

class AF_Crypt_Mcrypt extends AF_Crypt_Abstract {
	
	protected $_td;
	protected $_iv_size;
	protected $_key_max_size;
	
	public function __destruct() {
		if ($this->_td) {
			if (function_exists('mcrypt_module_close')) mcrypt_module_close($this->_td);
		}
	}
	
	/**
	 * The encryption method.
	 * 
	 * @param unknown_type $plain_text The string to be encrpyted.
	 * @param unknown_type $key The key to be used for the encryption.
	 * @param unknown_type $base64 Indicates whether the outcome should be base64 encoded.
	 * 
	 * @return String|binary The outcome of the encryption.
	 * 
	 * @access public
	 */
	public function encrypt($plain_text, $key) {
		// Serialising input string
		$cipher_text = serialize($plain_text);
		
		// Creating the IV
		$iv = mcrypt_create_iv($this->_iv_size, MCRYPT_RAND);
		
		// Checking that key size is appropriate and modigying accordingly if required
		if (strlen($key) > $this->_key_max_size) {
			$key = substr($key, 0, $this->_key_max_size);
		}
		
		// Initialising buffers
		if ( mcrypt_generic_init($this->_td, $key, $iv) !== 0 ) {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception('Some error occured while initialising buffers.');
		}
		
		// Encrypt
		$cipher_text = mcrypt_generic($this->_td, $cipher_text);
		// Prepend IV
		$cipher_text = $iv . $cipher_text;
		// Calculate mac
		$mac = $this->_pbkdf2($cipher_text, $key, 1000, strlen($key));
		// Append mac to cipher text
		$cipher_text .= $mac;
		
		// Clear buffers
		mcrypt_generic_deinit($this->_td);
		
		// base64 encoding the output
		$cipher_text = base64_encode($cipher_text);
		
		return $cipher_text;
	}
	
	/**
	 * The decryption method.
	 * 
	 * @param String $cipher_text The string to be decrypted.
	 * @param String $key The key to be used for the decryption.
	 * @param Boolean $base64 Indicates whether outcome should be base64 decoded.
	 * 
	 * @return String The decrypted string.
	 * 
	 * @access public
	 */
	public function decrypt($cipher_text, $key) {
		// base64 decoding the input
		$plain_text = base64_decode($cipher_text);
		
		// Checking that key size is appropriate and modigying accordingly if required
		if (strlen($key) > $this->_key_max_size) {
			$key = substr($key, 0, $this->_key_max_size);
		}
		
		// Extract IV
		$iv = substr($plain_text, 0, $this->_iv_size);
		// Extract mac
		$e_mac = substr($plain_text, strlen($plain_text)-32);
		// Calculate combined length of IV and mac
		$c_length = 32 + $this->_iv_size;
		// Extract cipher text
		$plain_text = substr($plain_text, $this->_iv_size, strlen($plain_text) - $c_length);
		
		// Reconstructing mac so we can make sure that ciphertext is not tampered
		$mac = $this->_pbkdf2($iv . $plain_text, $key, 1000, strlen($key));
		
		if ($e_mac != $mac) {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception('MAC could not be authenticated during decryption.');
		}
		
		// Initialising buffers
		if ( mcrypt_generic_init($this->_td, $key, $iv) !== 0 ) {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception('Some error occured while initialising buffers.');
		}
		
		// Decrypt
		$plain_text = mdecrypt_generic($this->_td, $plain_text);
		
		// Unserialise
		$plain_text = unserialize($plain_text);
		
		// Clear buffers
		mcrypt_generic_deinit($this->_td);
		
		
		return $plain_text;
	}
	
	/**
	 * Carries out actions necessary for the initialisation of the encryption/decryption.
	 * 
	 * @access protected
	 */
	protected function _prepare() {
		// Starting the module for given algo and mode
		if ($td = mcrypt_module_open($this->_options[AF_Crypt_Factory::AF_CRYPT_FACTORY_MCRYPT_ALGO],
									 $this->_options[AF_Crypt_Factory::AF_CRYPT_FACTORY_MCRYPT_ALGO_DIR],
									 $this->_options[AF_Crypt_Factory::AF_CRYPT_FACTORY_MCRYPT_MODE],
									 $this->_options[AF_Crypt_Factory::AF_CRYPT_FACTORY_MCRYPT_MODE_DIR])
			) {
			$this->_td = $td;
		}
		else {
			require_once 'AF/Crypt/Exception.php';
			throw new AF_Crypt_Exception('The encryption module could not be initiated!');
		}
		
		// Determining the Initialisation Vector (IV) size
		$this->_iv_size = mcrypt_enc_get_iv_size($this->_td);
		if (!$this->_iv_size) $this->_iv_size = 0;
		
		// Determining the maximum key size
		$this->_key_max_size = mcrypt_enc_get_key_size($this->_td);
	}
	
	/**
	 * PBKDF2 implementation (as described in RFC 2898);
	 * 
	 * @param String $p password
	 * @param String $s salt
	 * @param String $c iteration count (recommended 1000 or higher)
	 * @param String $kl key length
	 * @param String $a hash algorithm
	 * 
	 * @return String The derived key of correct length
	 * 
	 * @access protected
	 */
	protected function _pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {
		$hl = strlen(hash($a, null, true)); # Hash length
		$kb = ceil($kl / $hl);              # Key blocks to compute
		$dk = '';                           # Derived key

		# Create key
		for ( $block = 1; $block <= $kb; $block ++ ) {
			# Initial hash for this block
            $ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);
            # Perform block iterations
			for ( $i = 1; $i < $c; $i ++ ) {
				# XOR each iterate
				$ib ^= ($b = hash_hmac($a, $b, $p, true));
			}
			$dk .= $ib; # Append iterated block
        }
		# Return derived key of correct length
		return substr($dk, 0, $kl);
    }
	
}