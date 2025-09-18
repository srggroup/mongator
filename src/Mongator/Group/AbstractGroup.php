<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Group;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Mongator\Archive;
use Mongator\Document\AbstractDocument;
use RuntimeException;
use Traversable;

/**
 * AbstractGroup.
 */
abstract class AbstractGroup implements Countable, IteratorAggregate {


	private $saved;

	private $archive;


	/**
	 * Do the initialization of the saved documents data.
	 */
	abstract protected function doInitializeSavedData();


	public function __construct() {
		$this->archive = new Archive();
	}


	/**
	 * Return the Archive object
	 *
	 * @return Archive the archive object from this group
	 */
	public function getArchive() {
		return $this->archive;
	}


	/**
	 * Adds document/s to the add queue of the group.
	 *
	 * @param AbstractDocument|array $documents One or more documents.
	 */
	public function add($documents) {

		if ($this->getRemove()) {
			if ($this->all()) {
				throw new RuntimeException('Adding to group with pending remove is not permitted');
			} else {
				$this->clearAdd();
				$this->clearRemove();
				$this->saved = [];
			}
		}
		if (!is_array($documents)) {
			$documents = [$documents];
		}

		$add = &$this->getArchive()->getByRef('add', []);
		foreach ($documents as $document) {
			$add[] = $document;
		}
	}


	/**
	 * Returns the add queue of the group.
	 */
	public function getAdd() {
		return $this->getArchive()->getOrDefault('add', []);
	}


	/**
	 * Clears the add queue of the group.
	 */
	public function clearAdd() {
		$this->getArchive()->remove('add');
	}


	/**
	 * Adds document/s to the remove queue of the group.
	 *
	 * @param AbstractDocument|array $documents One of more documents.
	 */
	public function remove($documents) {
		if (!is_array($documents)) {
			$documents = [$documents];
		}

		$remove = &$this->getArchive()->getByRef('remove', []);
		foreach ($documents as $document) {
			$remove[] = $document;
		}
	}


	/**
	 * Clear all documents
	 */
	public function clear() {
		$this->getArchive()->set('clear', true);
	}


	/**
	 * Returns the remove queue of the group.
	 */
	public function getRemove() {
		return $this->getArchive()->getOrDefault('remove', []);
	}


	/**
	 * Returns the clear property
	 */
	public function getClear() {
		return $this->getArchive()->getOrDefault('clear', false);
	}


	/**
	 * Clears the remove queue of the group.
	 */
	public function clearRemove() {
		$this->getArchive()->remove('remove');
	}


	/**
	 * Returns the saved documents of the group.
	 */
	public function getSaved() {
		if ($this->saved === null) {
			$this->initializeSaved();
		}

		return $this->saved;
	}


	/**
	 * Marks everything as saved, removes pending add and delete arrays
	 */
	public function markAllSaved() {
		$this->saved = $this->all();
		foreach ($this->saved as $document) {
			$document->clearModified();
			$rap = $document->getRootAndPath();
			$rap['path'] = str_replace('._add', '.', $rap['path']);
			$document->setRootAndPath($rap['root'], $rap['path']);
		}
		$this->clearAdd();
		$this->clearRemove();
	}


	/**
	 * Returns the saved + add - removed elements.
	 */
	public function all() {
		$documents = array_merge($this->getSaved(), $this->getAdd());

		foreach ($this->getRemove() as $document) {
			if (($key = array_search($document, $documents)) !== false) {
				unset($documents[$key]);
			}
		}

		return array_values($documents);
	}


	/**
	 * Returns the first element from all()
	 */
	public function one() {
		$documents = $this->all();
		if (count($documents) === 0) {
			return null;
		}

		return $documents[0];
	}


	/**
	 * Implements the \IteratorAggregate interface.
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator($this->all());
	}


	/**
	 * Refresh the saved documents.
	 */
	public function refreshSaved() {
		$this->initializeSaved();
	}


	/**
	 * Clears the saved documents.
	 */
	public function clearSaved() {
		$this->saved = null;
	}


	/**
	 * Returns if the saved documents are initialized.
	 *
	 * @return bool If the saved documents are initialized.
	 */
	public function isSavedInitialized() {
		return $this->saved !== null;
	}


	/**
	 * Returns the number of all documents.
	 */
	public function count(): int {
		return count($this->all());
	}


	/**
	 * Replace all documents.
	 *
	 * @param array $documents An array of documents.
	 */
	public function replace(array $documents) {
		$this->clearAdd();


		if ($documents === []) {
			$this->clear();

			return;
		}

		$this->remove($this->getSaved());

		$this->clearRemove();

		$this->saved = [];

		$this->add($documents);
	}


	/**
	 * Resets the group (clear adds and removed, and saved if there are adds or removed).
	 */
	public function reset() {
		if ($this->getAdd() || $this->getRemove()) {
			$this->clearSaved();
		}
		$this->clearAdd();
		$this->clearRemove();
	}


	/**
	 * Initializes the saved documents.
	 */
	private function initializeSaved() {
		$this->saved = $this->doInitializeSaved($this->doInitializeSavedData());
	}


	/**
	 * Do the initialization of the saved documents.
	 */
	protected function doInitializeSaved($data) {
		return $data;
	}


}
