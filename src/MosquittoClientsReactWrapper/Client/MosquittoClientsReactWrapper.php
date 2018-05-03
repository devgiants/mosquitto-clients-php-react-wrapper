<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 20/04/18
 * Time: 10:57
 */

namespace Devgiants\MosquittoClientsReactWrapper\Client;

use Devgiants\MosquittoClientsReactWrapper\Event\Events;
use Devgiants\MosquittoClientsReactWrapper\Exception\InternetConnectionFailure;
use Devgiants\MosquittoClientsReactWrapper\Exception\MosquittoClientsMissingConfigFileException;
use Devgiants\MosquittoClientsReactWrapper\Exception\MosquittoClientsMissingException;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;


/**
 * Class MosquittoClientsReactWrapper
 * @package Devgiants\MosquittoClientsReactWrapper\Client
 */
class MosquittoClientsReactWrapper {

	const MAX_FAIL_ACCEPTED = 10;

	const TIME_BETWEEN_TRIES = 5;

	/**
	 * @var LoopInterface
	 */
	protected $loop;

	/**
	 * @var boolean
	 */
	protected $isConnected;

	/**
	 * @var TimerInterface
	 */
	protected $connectionCheckTimer;

	use EventEmitterTrait;

	/**
	 * @param LoopInterface $loop
	 * @return static
	 * @throws MosquittoClientsMissingConfigFileException
	 * @throws MosquittoClientsMissingException
	 */
	public static function create(LoopInterface $loop) {

		// Check clients are installed
		if(!static::hasDependency()) {
			throw new MosquittoClientsMissingException("Mosquitto clients are not installed. Try apt-get install mosquitto-clients");
		}

		// Check config files presents
		if(!file_exists($_SERVER['HOME']. '/.config/mosquitto_pub')) {
			throw new MosquittoClientsMissingConfigFileException("~/.config/mosquitto_pub file is missing");
		}

		if(!file_exists($_SERVER['HOME'] . '/.config/mosquitto_sub')) {
			throw new MosquittoClientsMissingConfigFileException("~/.config/mosquitto_sub file is missing");
		}

		return new static($loop);
	}


	/**
	 * MosquittoClientsReactWrapper constructor.
	 *
	 * @param LoopInterface $loop
	 */
	private function __construct(LoopInterface $loop) {
		$this->loop = $loop;

		// Set loop for checking internet connection
		$this->connectionCheckTimer = $loop->addPeriodicTimer(static::TIME_BETWEEN_TRIES, function(){

			// Check socket opening on google.fr
			$this->isConnected = (bool) @fsockopen("www.google.fr", 80, $errNo, $errStr, 5);

			// When connected, cancel timer and emit event
			if($this->isConnected) {
				$this->loop->cancelTimer($this->connectionCheckTimer);
				$this->emit(Events::INTERNET_CONNECTION_AVAILABLE);
			}
		});
	}


	/**
	 * @param string $topic
	 * @param string $message
	 */
	public function publish(string $topic, string $message) {
		popen("mosquitto_pub -t $topic -m \"$message\"", 'r');
	}


	/**
	 * @param string $topic
	 * @param $callback
	 */
	public function subscribe(string $topic, $callback) {
		$this->on( $topic, $callback );

		$this->loop->addReadStream( popen( "mosquitto_sub -t \"$topic\"", 'r' ), function ($stream) use ($topic, $callback) {
			$message = fread($stream, 4096);
			$this->emit($topic, ['message' => trim($message)]);
		} );
	}

	/**
	 * Check if mosquitto clients are available
	 * @return bool
	 */
	protected static function hasDependency() : bool {
		return (`command -v mosquitto_sub` !== null) && (`command -v mosquitto_pub` !== null);
	}
}