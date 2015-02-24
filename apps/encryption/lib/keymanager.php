<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 1:20 PM
 * @copyright 2015 ownCloud, Inc.
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

namespace OCA\Encryption;


use OC\Files\View;
use OCP\Encryption\IKeyStorage;

class KeyManager implements IKeyStorage {


	/**
	 * @var View
	 */
	private $view;

	/**
	 * @var string
	 */
	private $keysBaseDir = '/encryption/keys/';
	/**
	 * @var string
	 */
	private $encryptionBaseDir = '/encryption';
	/**
	 * @var string
	 */
	private $publicKeyDir = '/encryption/public_keys';
	/**
	 * @var array
	 */
	private $keyCache = [];

	public function __construct(View $view) {

		$this->view = $view;
	}

	/**
	 * Check if a recovery key exists
	 *
	 * @param $recoveryKeyId
	 * @return bool
	 */
	public function recoveryKeyExists($recoveryKeyId) {
		if ($recoveryKeyId) {
			$result = ($this->view->file_exists($this->publicKeyDir . '/' . $recoveryKeyId . ".publicKey")
				&& $this->view->file_exists($this->encryptionBaseDir . '/' . $recoveryKeyId . ".privateKey"));
			return $result;
		}
		return false;
	}

	/**
	 * get user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getUserKey($uid, $keyid) {
		// TODO: Implement getUserKey() method.
	}

	/**
	 * get file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getFileKey($path, $keyid) {
		// TODO: Implement getFileKey() method.
	}

	/**
	 * get system-wide user specific key, e.g something like a key for public
	 * link shares
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getSystemUserKey($uid, $keyid) {
		// TODO: Implement getSystemUserKey() method.
	}

	/**
	 * get system-wide file keys, e.g. from a external storage mounted
	 * by the admin for multiple users
	 *
	 * @param string $path path to file
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getSystemFileKey($path, $keyid) {
		// TODO: Implement getSystemFileKey() method.
	}

	/**
	 * set user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 * @param mixed $key
	 */
	public function setUserKey($uid, $keyid, $key) {
		// TODO: Implement setUserKey() method.
	}

	/**
	 * set file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyid id of the key
	 * @param mixed $key
	 */
	public function setFileKey($path, $keyid) {
		// TODO: Implement setFileKey() method.
	}

	/**
	 * set system-wide user specific key, e.g something like a key for public
	 * link shares
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 * @param mixed $key
	 *
	 * @return mixed key
	 */
	public function setSystemUserKey($uid, $keyid, $key) {
		// TODO: Implement setSystemUserKey() method.
	}

	/**
	 * set system-wide file keys, e.g. from a external storage mounted
	 * by the admin for multiple users
	 *
	 * @param string $path path to file
	 * @param string $keyid id of the key
	 * @param mixed $key
	 */
	public function setSystemFileKey($path, $keyid) {
		// TODO: Implement setSystemFileKey() method.
	}

	public function checkRecoveryPassword($user, $keyID, $password) {
		$keyStore = \OC::$server->getEncryptionKeyStorage();

		$recoveryKey = $keyStore->getSystemUserKey($user, $keyID);
		$decryptedRecoveryKey = (new Crypt())->decryptPrivateKey($recoveryKey, $password);

		if ($decryptedRecoveryKey) {
			return true;
		}
		return false;
	}

}
