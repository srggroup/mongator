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
 * IdentityMapInterface.
 */
interface IdentityMapInterface {


	/**
	 * Set a document.
	 *
	 * @param mixed    $id       The document Id.
	 * @param Document $document The document.
	 */
	public function set($id, Document $document);


	/**
	 * Returns if exists a document.
	 *
	 * @param mixed $id The document id.
	 * @return bool If exists or not the document.
	 */
	public function has($id);


	/**
	 * Returns a document.
	 *
	 * @param mixed $id The document Id.
	 * @return Document The document.
	 */
	public function get($id);


	/**
	 * Returns all documents.
	 *
	 * @return array The documents.
	 */
	public function all();


	/**
	 * Returns all the documents by reference.
	 *
	 * @return array The documents by reference.
	 */
	public function &allByReference();


	/**
	 * Remove a document.
	 *
	 * @param mixed $id The document Id.
	 */
	public function remove($id);


	/**
	 * Clear the documents.
	 */
	public function clear();


}
