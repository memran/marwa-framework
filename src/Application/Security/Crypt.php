<?php declare(strict_types=1);
	
	namespace Marwa\Application\Security;
	
	/**
	 * Class Crypt
	 * Provides methods for encryption, decryption, and secure data handling.
	 */

	class Crypt {
		
		public function __construct() {
			// Constructor logic if needed
		}
		/**
		 * Encrypts the given data using a secure algorithm.
		 * @param string $data The data to encrypt.
		 * @return string The encrypted data.
		 * @throws \Exception If encryption fails.
		 * @example $encryptedData = Crypt::encrypt('sensitive data');
		 */
		public function encrypt(string $data) {
			// Implement encryption logic here
			return base64_encode($data); // Example using base64 encoding
		}
		/**
		 * Decrypts the given data using a secure algorithm.
		 * @param string $data The data to decrypt.
		 * @return string The decrypted data.
		 * @throws \Exception If decryption fails.
		 * @example $decryptedData = Crypt::decrypt($encryptedData);
		 */
		public function decrypt(string $data) {
			// Implement decryption logic here
			return base64_decode($data); // Example using base64 decoding
		}


		/**
		 * * Encrypts a string using a secure key.
		 * * @param string $data The data to encrypt.
		 * * @param string $key The encryption key.
		 * * @return string The encrypted data.
		 * * @throws \Exception If encryption fails.
		 * * @example $encrypted = Crypt::encryptString('sensitive data', 'your-encryption-key');
		 * * @see https://www.php.net/manual/en/function.openssl-encrypt.php
		 */
		public function encryptString(string $data, string $key): string {
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
			return base64_encode($iv . $encrypted); // Prepend IV to the encrypted data
		}
		/**
		 * 	* Decrypts a string using a secure key.	
		 * * @param string $data The data to decrypt.
		 * * @param string $key The decryption key.
		 * * @return string The decrypted data.
		 * * @throws \Exception If decryption fails.
		 * * @example $decrypted = Crypt::decryptString($encryptedData, 'your-decryption-key');
		 * * @see https://www.php.net/manual/en/function.openssl-decrypt.php
		*/
		public function decryptString(string $data, string $key): string {
			$data = base64_decode($data);
			$ivLength = openssl_cipher_iv_length('aes-256-cbc');
			$iv = substr($data, 0, $ivLength);
			$encrypted = substr($data, $ivLength);
			$decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
			if ($decrypted === false) {
				throw new \Exception('Decryption failed'); // Handle decryption failure	
			}
			return $decrypted; // Return the decrypted string
		}			
	
	}
