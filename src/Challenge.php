<?php

namespace Bruteforce;

class Challenge {

	/**
	 * @var array Key=>Data store of the challenge (data may be encrypted or unencrypted depending on config)
	 */
	public $data = [];

	/**
	 * @var array Key=>Data store of the challenge fully unencrypted. Will be destroyed before serialize into Cache or Log
	 */
	public $unencryptedData = [];

	/**
	 * @var array a list of keys in that are encrypted
	 */
	public $encryptedKeyNames = [];

	/**
	 * @param string $keyName
	 * @param string $data
	 * @param bool $hashed
	 *
	 * @return void
	 */
	public function addData(string $keyName, string $data, bool $hashed): void {
		$this->unencryptedData[$keyName] = $data;
		$this->data[$keyName] = $hashed ? password_hash($data, PASSWORD_DEFAULT) : $data;
		if ($hashed) {
			$this->encryptedKeyNames[] = $keyName;
		}
	}

	/**
	 * @param \Bruteforce\Challenge $oldChallenge
	 * @param bool $onlyFirstKey
	 *
	 * @return bool
	 */
	public function matchesAnOldChallenge(Challenge $oldChallenge, $onlyFirstKey = false): bool {
	    if (!$this->unencryptedData && !$oldChallenge->unencryptedData) {
	        return true;
        }


		if ($onlyFirstKey) { // check if first keys match
		    if (!$this->unencryptedData && array_key_first($this->unencryptedData) !== array_key_first($oldChallenge->unencryptedData)) {
		        return false;
            }
        } else { // check all keys match
            if(array_keys($this->unencryptedData) !== array_keys($oldChallenge->data)) {
                return false;
            }
        }

		foreach ($this->unencryptedData as $keyName => $datum) {
			if ($oldChallenge->isKeyEncrypted($keyName)) {
				if (!password_verify($datum, $oldChallenge->data[$keyName])) {
					return false;
				}
			} else {
				if ($datum !== $oldChallenge->data[$keyName]) {
					return false;
				}
			}
			if ($onlyFirstKey) {
				return true;
			}
		}
		return true;
	}

	/**
	 * @param string $keyName
	 *
	 * @return bool
	 */
	private function isKeyEncrypted(string $keyName): bool {
		return in_array($keyName, $this->encryptedKeyNames, true);
	}

	/**
	 * Return an array contain property names that you want included in object serialization
	 *
	 * @return array
	 */
	public function __sleep() {
		return ['data', 'encryptedKeyNames'];
	}

}
