<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Document;

use LogicException;
use MongoDB\BSON\ObjectID;
use SRG\Odm\MongatorRepository;

/**
 * The base class for documents.
 */
abstract class Document extends AbstractDocument {


	protected $isNew = true;

	protected $id;

	protected $queryFields = null;


	/**
	 * Returns the repository.
	 *
	 * @return MongatorRepository The repository.
	 */
	public function getRepository() {
		return $this->getMongator()->getRepository(static::class);
	}


	/**
	 * Set the id of the document.
	 *
	 * @param mixed $id The id.
	 * @return Document The document (fluent interface).
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}


	/**
	 * Returns the id of document.
	 *
	 * @return ObjectID|null The id of the document or null if it is new.
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * INTERNAL. Returns if the document is new.
	 *
	 * @param bool $isNew If the document is new.
	 * @return Document The document (fluent interface).
	 */
	public function setIsNew($isNew) {
		$this->isNew = (bool) $isNew;

		return $this;
	}


	/**
	 * Returns if the document is new.
	 *
	 * @return bool Returns if the document is new.
	 */
	public function isNew() {
		return $this->isNew;
	}


	/**
	 * Refresh the document data from the database.
	 *
	 * @return Document The document (fluent interface).
	 * @throws LogicException
	 */
	public function refresh() {
		if ($this->isNew()) {
			throw new LogicException('The document is new.');
		}

		$this->setDocumentData($this->getRepository()->getCollection()->findOne(['_id' => $this->getId()]), true);

		return $this;
	}


	/**
	 * Save the document.
	 *
	 * @param array $options The options for the batch insert or update operation, it depends on if the document is new or not (optional).
	 * @return Document The document (fluent interface).
	 */
	public function save(array $options = []) {
		if ($this->isNew()) {
			$this->queryFields = [];
			$batchInsertOptions = $options;
			$updateOptions = [];
		} else {
			$batchInsertOptions = [];
			$updateOptions = $options;
		}

		$this->getRepository()->save($this, $batchInsertOptions, $updateOptions);

		return $this;
	}


	/**
	 * Delete the document.
	 *
	 * @param array $options The options for the remove operation (optional).
	 */
	public function delete(array $options = []) {
		$this->getRepository()->delete($this, $options);
	}


	/**
	 * Adds a query hash.
	 *
	 * @param string $hash The query hash.
	 */
	public function addQueryHash($hash) {
		$queryHashes = &$this->getArchive()->getByRef('query_hashes', []);
		$queryHashes[] = $hash;
	}


	/**
	 * Returns the query hashes.
	 *
	 * @return array The query hashes.
	 */
	public function getQueryHashes() {
		return $this->getArchive()->getOrDefault('query_hashes', []);
	}


	/**
	 * Removes a query hash.
	 *
	 * @param string $hash The query hash.
	 */
	public function removeQueryHash($hash) {
		$queryHashes = &$this->getArchive()->getByRef('query_hashes', []);
		unset($queryHashes[array_search($hash, $queryHashes)]);
		$queryHashes = array_values($queryHashes);
	}


	/**
	 * Clear the query hashes.
	 */
	public function clearQueryHashes() {
		$this->getArchive()->remove('query_hashes');
	}


	/**
	 * Add a field cache.
	 */
	public function addFieldCache($field) {
		if (!$cache = $this->getMongator()->getFieldsCache()) {
			return null;
		}

		$field = preg_replace('/\.\d+/', '', $field);

		foreach ($this->getQueryHashes() as $hash) {
			$value = $cache->get($hash);
			if (!$value) {
				$value = [];
			}

			if (!isset($value['fields'][$field]) || (int) $value['fields'][$field] !== 1) {
				$value['fields'][$field] = 1;
				$cache->set($hash, $value);
			}
		}
	}


	/**
	 * Adds a reference cache
	 */
	public function addReferenceCache($reference) {
		if (!$cache = $this->getMongator()->getFieldsCache()) {
			return null;
		}

		foreach ($this->getQueryHashes() as $hash) {
			$value = $cache->get($hash);
			if (!$value) {
				$value = [];
			}

			if (!isset($value['references']) || !in_array($reference, $value['references'])) {
				$value['references'][] = $reference;
				$cache->set($hash, $value);
			}
		}
	}


	/**
	 * {@inheritdoc }
	 */
	public function isFieldInQuery($field) {
		if ($this->queryFields === []) {
			return true;
		}

		return isset($this->queryFields[$field]);
	}


	/**
	 * {@inheritdoc }
	 */
	public function loadFull() {

		if ($this->queryFields === [] || $this->isNew()) {
			return true;
		}

		$data = $this->getRepository()->getCollection()->findOne(['_id' => $this->getId()]);
		foreach (array_keys($this->fieldsModified) as $name) {
			unset($data[$name]);
		}

		$this->setDocumentData($data);
		$this->queryFields = [];

		return true;
	}


	/**
	 * Set the fields that were included in the query used to populate the object.
	 *
	 * @param array $fields an associative array($f1 => 1, $f2 => 1, ...)
	 */
	protected function setQueryFields(array $fields) {
		$this->queryFields = [];
		foreach ($fields as $field => $included) {
			if ($included) {
				$this->queryFields[$field] = 1;
			}
		}
	}


}
