<?php declare(strict_types=1);

	namespace Marwa\Application\Security;
	/**
	 * Class Hash
	 * Provides methods for hashing and verifying data securely.
	 */
	class Hash  {


		public function __construct() {
			// Constructor logic if needed
		}
		/**
		 * * Hashes the given data using a secure algorithm.
		 * * @param string $data The data to hash.
		 * * @param array $options Optional parameters for the hashing algorithm.
		 * * @return string The hashed data.
		 * * @throws \Exception If the hashing algorithm is not supported.
		 * * @example $hash = Hash::make('password123');
		 * * @example $hash = Hash::make('password123', ['cost' => 12]);
		 * * @see https://www.php.net/manual/en/function.password-hash.php
		 */
		public function make(string $data,array $options=[]): string {
			// Implement hashing logic here
			if(empty($options)) {
				$options = ['cost' => 12]; // Default options
			}
			return password_hash($data, PASSWORD_BCRYPT,$options); // Example using bcrypt
		}
		/**
		 * * Verifies the given data against a hash.
		 * * @param string $data The data to verify.
		 * * @param string $hash The hash to verify against.
		 * * @return bool True if the data matches the hash, false otherwise.
		 * * @throws \Exception If the hash is not valid.
		 * * @example $isValid = Hash::check('password123', $hash);
		 * * @see https://www.php.net/manual/en/function.password-verify.php	
		 */
		public function check(string $data, string $hash): bool {
			// Implement verification logic here
			return password_verify($data, $hash); // Example using bcrypt verification
		}
		
		/**	
		 * * Checks if the given hash needs to be rehashed.
		 * * @param string $hash The hash to check.
		 * * @param array $options Optional parameters for the hashing algorithm.
		 * * @return bool True if the hash needs to be rehashed, false otherwise.
		 * * @throws \Exception If the hash is not valid.
		 * * @example $needsRehash = Hash::needsRehash($hash, ['cost' => 12]);
		 * * @see https://www.php.net/manual/en/function.password-needs-rehash.php
		 */
		public  function needsRehash(string $hash, array $options = []): bool {
			// Check if the hash needs to be rehashed
			if(empty($options)) {
				$options = ['cost' => 12]; // Default options
			}
			return password_needs_rehash($hash, PASSWORD_BCRYPT, $options); // Example using bcrypt
		}
	
	}
