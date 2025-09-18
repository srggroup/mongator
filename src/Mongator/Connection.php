<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator;

use LogicException;
use MongoDB\Client as MongoDBClient;

/**
 * Connection.
 */
class Connection implements ConnectionInterface {


	private $options;

	private $mongo;

	private $mongoDB; //@phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName


	/**
	 * @param string $server  The server.
	 * @param string $dbName  The database name.
	 * @param array  $options The \MongoClient options (optional).
	 */
	public function __construct(private $server, private $dbName, array $options = []) {
		$this->options = $options;
	}


	/**
	 * Sets the server.
	 *
	 * @param string $server The server.
	 * @throws LogicException If the mongo is initialized.
	 */
	public function setServer($server) {
		if ($this->mongo !== null) {
			throw new LogicException('The mongo is initialized.');
		}

		$this->server = $server;
	}


	/**
	 * Returns the server.
	 *
	 * @return string $server The server.
	 */
	public function getServer() {
		return $this->server;
	}


	/**
	 * Sets the db name.
	 *
	 * @param string $dbName The db name.
	 * @throws LogicException If the mongoDb is initialized.
	 */
	public function setDbName($dbName) {
		if ($this->mongoDB !== null) {
			throw new LogicException('The mongoDb is initialized.');
		}

		$this->dbName = $dbName;
	}


	/**
	 * Returns the database name.
	 *
	 * @return string The database name.
	 */
	public function getDbName() {
		return $this->dbName;
	}


	/**
	 * Sets the options.
	 *
	 * @param array $options An array of options.
	 * @throws LogicException If the mongo is initialized.
	 */
	public function setOptions(array $options) {
		if ($this->mongo !== null) {
			throw new LogicException('The mongo is initialized.');
		}

		$this->options = $options;
	}


	/**
	 * Returns the options.
	 *
	 * @return array The options.
	 */
	public function getOptions() {
		return $this->options;
	}


	public function getMongo() {
		if ($this->mongo === null) {
			$this->mongo = new MongoDBClient($this->server, $this->options);
		}

		return $this->mongo;
	}


	public function getMongoDB() {
		if ($this->mongoDB === null) {
			$this->mongoDB = $this->getMongo()->selectDatabase($this->dbName);
		}

		return $this->mongoDB;
	}


}
