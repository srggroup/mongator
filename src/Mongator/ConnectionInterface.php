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

use MongoDB\Client;
use MongoDB\Database;

/**
 * ConnectionInterface.
 */
interface ConnectionInterface {


	/**
	 * Returns the mongo connection object.
	 *
	 * @return Client The mongo collection object.
	 */
	public function getMongo();


	/**
	 * Returns the database object.
	 *
	 * @return Database The database object.
	 */
	public function getMongoDB();


}
