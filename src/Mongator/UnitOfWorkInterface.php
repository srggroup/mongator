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

use Mongator\Document\Document;

/**
 * UnitOfWorkInterface.
 */
interface UnitOfWorkInterface {


	/**
	 * Persist a document.
	 *
	 * @param Document|array $documents A document or an array of documents.
	 */
	public function persist($documents);


	/**
	 * Remove a document.
	 *
	 * @param Document|array $documents A document or an array of documents.
	 */
	public function remove($documents);


	/**
	 * Commit pending persist and remove operations.
	 */
	public function commit();


	/**
	 * Clear the pending operations
	 */
	public function clear();


}
