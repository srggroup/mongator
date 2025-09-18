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
 * UnitOfWork.
 */
class UnitOfWork implements UnitOfWorkInterface {


	private $mongator;

	private $persist;

	private $remove;


	/**
	 * @param Mongator $mongator The Mongator.
	 */
	public function __construct(Mongator $mongator) {
		$this->mongator = $mongator;
		$this->persist = [];
		$this->remove = [];
	}


	/**
	 * Returns the Mongator.
	 *
	 * @return Mongator The Mongator.
	 */
	public function getMongator() {
		return $this->mongator;
	}


	public function persist($documents) {
		if (!is_array($documents)) {
			$documents = [$documents];
		}

		foreach ($documents as $document) {
			$class = $document::class;
			$oid = spl_object_hash($document);

			if (isset($this->remove[$class][$oid])) {
				unset($this->remove[$class][$oid]);
			}

			$this->persist[$class][$oid] = $document;
		}
	}


	/**
	 * Returns if a document is pending for persist.
	 *
	 * @return bool If the document is pending for persist.
	 */
	public function isPendingForPersist(Document $document) {
		return isset($this->persist[$document::class][spl_object_hash($document)]);
	}


	/**
	 * Returns if there are pending persist operations.
	 *
	 * @return bool If there are pending persist operations.
	 */
	public function hasPendingForPersist() {
		return (bool) count($this->persist);
	}


	public function remove($documents) {
		if (!is_array($documents)) {
			$documents = [$documents];
		}

		foreach ($documents as $document) {
			$class = $document::class;
			$oid = spl_object_hash($document);

			if (isset($this->persist[$class][$oid])) {
				unset($this->persist[$class][$oid]);
			}

			$this->remove[$class][$oid] = $document;
		}
	}


	/**
	 * Returns if a document is pending for remove.
	 *
	 * @return bool If the document is pending for remove.
	 */
	public function isPendingForRemove(Document $document) {
		return isset($this->remove[$document::class][spl_object_hash($document)]);
	}


	/**
	 * Returns if there are pending remove operations.
	 *
	 * @return bool If there are pending remove operations.
	 */
	public function hasPendingForRemove() {
		return (bool) count($this->remove);
	}


	/**
	 * Returns if there are pending operations.
	 *
	 * @return bool If there are pending operations.
	 */
	public function hasPending() {
		return $this->hasPendingForPersist() || $this->hasPendingForRemove();
	}


	public function commit() {
		// execute
		foreach ($this->persist as $class => $documents) {
			$this->mongator->getRepository($class)->save($documents);
		}
		foreach ($this->remove as $class => $documents) {
			$this->mongator->getRepository($class)->delete($documents);
		}

		// clear
		$this->clear();
	}


	public function clear() {
		$this->persist = [];
		$this->remove = [];
	}


}
