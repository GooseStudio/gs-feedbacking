<?php


namespace GooseStudio\Feedbacking;

use Exception;
use RuntimeException;
use SodiumException;

class Encryption {

	public const ENCRYPTED_PREFIX  = '$t1$';
	public const ENCRYPTED_VERSION = 1;

	/**
	 * @param $secret
	 * @return bool
	 */
	public static function is_encrypted( $secret ): bool {
		if ( strlen( $secret ) < 40 ) {
			return false;
		}
		if ( strpos( $secret, self::ENCRYPTED_PREFIX ) !== 0 ) {
			return false;
		}
		return true;
	}

	/**
	 * Encrypt a TOTP secret.
	 *
	 * @param string $secret TOTP secret.
	 * @param int    $user_id User ID.
	 * @param int    $version (Optional) Version ID.
	 *
	 * @throws SodiumException From sodium_compat or ext/sodium.
	 * @throws Exception Throws exception if random_bytes fails.
	 * @return string
	 */
	public static function encrypt( string $secret, int $user_id, int $version = self::ENCRYPTED_VERSION ): string {
		$prefix     = self::get_version_header( $version );
		$nonce      = random_bytes( 24 );
		$ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
			$secret,
			self::serialize_aad( $prefix, $nonce, $user_id ),
			$nonce,
			self::get_key( $version )
		);
        // @codingStandardsIgnoreStart
        return self::ENCRYPTED_PREFIX . base64_encode( $nonce . $ciphertext );
        // @codingStandardsIgnoreEnd
	}

	/**
	 * Decrypt a TOTP secret.
	 *
	 * Version information is encoded with the ciphertext and thus omitted from this function.
	 *
	 * @param string $encrypted Encrypted TOTP secret.
	 * @param int    $user_id User ID.
	 * @return string
	 * @throws RuntimeException Decryption failed.
	 */
	public static function decrypt( string $encrypted, int $user_id ): string {
		if ( strlen( $encrypted ) < 4 ) {
			throw new RuntimeException( 'Message is too short to be encrypted' );
		}
		$prefix  = substr( $encrypted, 0, 4 );
		$version = self::get_version_id( $prefix );
		if ( 1 === $version ) {
            // @codingStandardsIgnoreStart
            $decoded    = base64_decode( substr( $encrypted, 4 ) );
            // @codingStandardsIgnoreEnd
			$nonce      = RandomCompat_substr( $decoded, 0, 24 );
			$ciphertext = RandomCompat_substr( $decoded, 24 );
			try {
				$decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
					$ciphertext,
					self::serialize_aad( $prefix, $nonce, $user_id ),
					$nonce,
					self::get_key( $version )
				);
			} catch ( SodiumException $ex ) {
				throw new RuntimeException( 'Decryption failed', 0, $ex );
			}
		} else {
			throw new RuntimeException( 'Unknown version: ' . $version );
		}

		// If we don't have a string, throw an exception because decryption failed.
		if ( ! is_string( $decrypted ) ) {
			throw new RuntimeException( 'Could not decrypt TOTP secret' );
		}
		return $decrypted;
	}

	/**
	 * Serialize the Additional Authenticated Data for TOTP secret encryption.
	 *
	 * @param string $prefix Version prefix.
	 * @param string $nonce Encryption nonce.
	 * @param int    $user_id User ID.
	 * @return string
	 */
	public static function serialize_aad( string $prefix, string $nonce, int $user_id ): string {
		return $prefix . $nonce . pack( 'N', $user_id );
	}
	/**
	 * Get the version prefix from a given version number.
	 *
	 * @param int $number Version number.
	 * @return string
	 * @throws RuntimeException For incorrect versions.
	 */
	private static function get_version_header( $number = self::ENCRYPTED_VERSION ): ?string {
		if ( 1 === $number ) {
			return '$t1$';
		}

		throw new RuntimeException( 'Incorrect version number: ' . $number );
	}

	/**
	 * Get the version prefix from a given version number.
	 *
	 * @param string $prefix Version prefix.
	 * @return int
	 * @throws RuntimeException For incorrect versions.
	 */
	private static function get_version_id( $prefix = self::ENCRYPTED_PREFIX ): int {
		if ( '$t1$' === $prefix ) {
			return 1;
		}

		throw new RuntimeException( 'Incorrect version identifier: ' . $prefix );
	}

	/**
	 * Get the encryption key for encrypting TOTP secrets.
	 *
	 * @param int $version Key derivation strategy.
	 *
	 * @return string
	 * @throws RuntimeException For incorrect versions.
	 */
	private static function get_key( int $version = self::ENCRYPTED_VERSION ): string {
		if ( defined( 'GS_SF_SECURE_AUTH_SALT' ) ) {
			$salt = GS_SF_SECURE_AUTH_SALT;
		} else {
			$salt = SECURE_AUTH_SALT;
		}

		if ( 1 === $version ) {
			return hash_hmac( 'sha256', $salt, 'gs-sf-encryption', true );
		}
		throw new RuntimeException( 'Incorrect version number: ' . $version );
	}
}
