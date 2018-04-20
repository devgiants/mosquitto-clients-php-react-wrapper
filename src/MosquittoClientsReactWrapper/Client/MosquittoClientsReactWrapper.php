<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 20/04/18
 * Time: 10:57
 */

namespace devgiants\MosquittoClientsReactWrapper\Client;

use devgiants\MosquittoClientsReactWrapper\Exception\MosquittoClientsMissingException;


/**
 * Class MosquittoClientsReactWrapper
 * @package devgiants\MosquittoClientsReactWrapper\Client
 */
class MosquittoClientsReactWrapper {

	/**
	 * @param string $configFilename
	 *
	 * @return static
	 * @throws MosquittoClientsMissingException
	 */
	public static function create(string $configFilename) {
		if(!static::hasDependency()) {
			throw new MosquittoClientsMissingException("Mosquitto clients are not installed. Try apt-get install mosquitto-clients");
		}

		return new static([]);
	}


	/**
	 * MosquittoClientsReactWrapper constructor.
	 *
	 * @param array $data the configuration data
	 */
	private function __construct(array $data) {

	}

	/**
	 * Check if mosquitto clients are available
	 * @return bool
	 */
	protected static function hasDependency() : bool {
		return (`command -v mosquitto_sub` !== null) && (`command -v mosquitto_pub` !== null);
	}
}