<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Query;

use ArrayIterator;
use ArrayObject;
use Countable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Mongator\Document\Document;
use Mongator\Repository;
use MongoCursor;
use MongoDB\BSON\ObjectID;
use Traversable;

/**
 * Query.
 */
abstract class Query implements Countable, IteratorAggregate {


	private $repository;

	private $hash;

	private $criteria;

	private $fields;

	private $references;

	private $sort;

	private $limit;

	private $skip;

	private $batchSize;

	private $hint;

	private $snapshot;

	private $timeout;

	private $text;


	/**
	 * Returns all the results.
	 *
	 * @return array An array with all the results.
	 */
	abstract public function all();


	/**
	 * @param Repository $repository The repository of the document class to query.
	 */
	public function __construct(Repository $repository) {
		$this->repository = $repository;

		$hash = $this->repository->getDocumentClass();

		if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
			$debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		} else {
			$debugBacktrace = debug_backtrace();
		}
		foreach ($debugBacktrace as $value) {
			if (isset($value['function'])) {
				$hash .= $value['function'];
			}
			if (isset($value['class'])) {
				$hash .= $value['class'];
			}
			if (isset($value['type'])) {
				$hash .= $value['type'];
			}
			if (isset($value['file'])) {
				$hash .= $value['file'];
			}
			if (isset($value['line'])) {
				$hash .= $value['line'];
			}
		}
		$this->hash = md5($hash);

		$this->criteria = [];
		$this->fields = [];
		$this->references = [];
		$this->snapshot = false;

		$cache = $this->getFullCache();
		if (isset($cache['fields'])) {
			$this->fields = $cache['fields'];
		}

		if (isset($cache['references'])) {
			$this->references = $cache['references'];
		}
	}


	/**
	 * Adds criteria to find by id
	 *
	 * @param mixed $id the id to find
	 */
	public function findById($id) {
		$id = $this->getRepository()->idToMongo($id);

		return $this->mergeCriteria(['_id' => $id]);
	}


	/**
	 * Adds criteria to find by id
	 *
	 * @param array $ids the array of ids to find
	 */
	public function findByIds(array $ids) {
		$ids = $this->getRepository()->idsToMongo($ids);

		return $this->mergeCriteria(['_id' => ['$in' => $ids]]);
	}


	/**
	 * Returns the repository.
	 *
	 * @return Repository The repository.
	 */
	public function getRepository() {
		return $this->repository;
	}


	/**
	 * Returns the query hash.
	 *
	 * @return string The query hash.
	 */
	public function getHash() {
		return $this->hash;
	}


	public function getFullCache() {
		if (!$cache = $this->repository->getMongator()->getFieldsCache()) {
			return null;
		}

		return $cache->get($this->hash);
	}


	/**
	 * Set the criteria.
	 *
	 * @param array $criteria The criteria.
	 * @return Query The query instance (fluent interface).
	 */
	public function criteria(array $criteria) {
		$this->criteria = $criteria;

		return $this;
	}


	/**
	 * Merges a criteria with the current one.
	 *
	 * @param array $criteria The criteria.
	 * @return Query The query instance (fluent interface).
	 */
	public function mergeCriteria(array $criteria) {
		$this->criteria = $this->criteria === null ? $criteria : array_merge($this->criteria, $criteria);

		return $this;
	}


	/**
	 * Returns the criteria.
	 *
	 * @return array The criteria.
	 */
	public function getCriteria() {
		return $this->criteria;
	}


	/**
	 * Set the fields.
	 *
	 * @param array $fields The fields.
	 * @return Query The query instance (fluent interface).
	 */
	public function fields($fields) {
		$this->fields = $fields;

		return $this;
	}


	/**
	 * Returns the fields.
	 *
	 * @return array The fields.
	 */
	public function getFields() {
		return $this->fields;
	}


	/**
	 * Set the references.
	 *
	 * @param array $references The references.
	 * @return Query The query instance (fluent interface).
	 * @throws InvalidArgumentException If the references are not an array or null.
	 */
	public function references($references) {
		if ($references !== null && !is_array($references)) {
			throw new InvalidArgumentException(sprintf('The references "%s" are not valid.', $references));
		}

		$this->references = $references;

		return $this;
	}


	/**
	 * Returns the references.
	 *
	 * @return array The references.
	 */
	public function getReferences() {
		return $this->references;
	}


	/**
	 * Set the sort.
	 *
	 * @param array|null $sort The sort.
	 * @return Query The query instance (fluent interface).
	 * @throws InvalidArgumentException If the sort is not an array or null.
	 */
	public function sort($sort) {
		if ($sort !== null && !is_array($sort)) {
			throw new InvalidArgumentException(sprintf('The sort "%s" is not valid.', $sort));
		}

		$this->sort = $sort;

		return $this;
	}


	/**
	 * Returns the sort.
	 *
	 * @return array The sort.
	 */
	public function getSort() {
		return $this->sort;
	}


	/**
	 * Set the limit.
	 *
	 * @param int|null $limit The limit.
	 * @return Query The query instance (fluent interface).
	 * @throws InvalidArgumentException If the limit is not a valid integer or null.
	 */
	public function limit($limit) {
		if ($limit !== null) {
			if (!is_numeric($limit) || $limit !== (int) $limit) {
				throw new InvalidArgumentException('The limit is not valid.');
			}
			$limit = (int) $limit;
		}

		$this->limit = $limit;

		return $this;
	}


	/**
	 * Returns the limit.
	 *
	 * @return int|null The limit.
	 */
	public function getLimit() {
		return $this->limit;
	}


	/**
	 * Set the skip.
	 *
	 * @param int|null $skip The skip.
	 * @return Query The query instance (fluent interface).
	 * @throws InvalidArgumentException If the skip is not a valid integer, or null.
	 */
	public function skip($skip) {
		if ($skip !== null) {
			if (!is_numeric($skip) || $skip !== (int) $skip) {
				throw new InvalidArgumentException('The skip is not valid.');
			}
			$skip = (int) $skip;
		}

		$this->skip = $skip;

		return $this;
	}


	/**
	 * Returns the skip.
	 *
	 * @return int|null The skip.
	 */
	public function getSkip() {
		return $this->skip;
	}


	/**
	 * Set the batch size.
	 *
	 * @param int|null $batchSize The batch size.
	 * @return Query The query instance (fluent interface).
	 */
	public function batchSize($batchSize) {
		if ($batchSize !== null) {
			if (!is_numeric($batchSize) || $batchSize !== (int) $batchSize) {
				throw new InvalidArgumentException('The batchSize is not valid.');
			}
			$batchSize = (int) $batchSize;
		}

		$this->batchSize = $batchSize;

		return $this;
	}


	/**
	 * Returns the batch size.
	 *
	 * @return int|null The batch size.
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}


	/**
	 * Set the hint.
	 *
	 * @return Query The query instance (fluent interface).
	 */
	public function hint($hint) {
		if ($hint !== null && !is_array($hint)) {
			throw new InvalidArgumentException(sprintf('The hint "%s" is not valid.', $hint));
		}

		$this->hint = $hint;

		return $this;
	}


	/**
	 * Returns the hint.
	 *
	 * @return array|null The hint.
	 */
	public function getHint() {
		return $this->hint;
	}


	/**
	 * Set if the snapshot mode is used.
	 *
	 * @param bool $snapshot If the snapshot mode is used.
	 * @return Query The query instance (fluent interface).
	 */
	public function snapshot($snapshot) {
		if (!is_bool($snapshot)) {
			throw new InvalidArgumentException('The snapshot is not a boolean.');
		}

		$this->snapshot = $snapshot;

		return $this;
	}


	/**
	 * Returns if the snapshot mode is used.
	 *
	 * @return bool If the snapshot mode is used.
	 */
	public function getSnapshot() {
		return $this->snapshot;
	}


	/**
	 * Set the timeout.
	 *
	 * @param int|null $timeout The timeout of the cursor.
	 * @return Query The query instance (fluent interface).
	 */
	public function timeout($timeout) {
		if ($timeout !== null) {
			if (!is_numeric($timeout) || $timeout !== (int) $timeout) {
				throw new InvalidArgumentException('The timeout is not valid.');
			}
			$timeout = (int) $timeout;
		}

		$this->timeout = $timeout;

		return $this;
	}


	/**
	 * Returns the timeout.
	 *
	 * @return int|null The timeout.
	 */
	public function getTimeout() {
		return $this->timeout;
	}


	/**
	 * Set the text search criterias. The text methods requires a text index.
	 *
	 * @param string $search     A string of terms
	 * @param int $requiredScore (optional) All the documents with less score will be omitted
	 * @param int $language      (optional) Specify the language that determines for the search the list of stop words and the rules for the stemmer and tokenizer.
	 *                           If not specified, the search uses the default language of the index.
	 * @return Query The query instance (fluent interface).
	 */
	public function text($search, $requiredScore = null, $language = null) {
		if ($search === null) {
			$this->text = null;
		} else {
			$this->text = [
				'search'        => $search,
				'requiredScore' => $requiredScore,
				'language'      => $language,
			];
		}

		return $this;
	}


	/**
	 * Returns the text search criterias.
	 *
	 * @return array|null The text search criterias.
	 */
	public function getText() {
		return $this->text;
	}


	/**
	 * Returns an \ArrayIterator with all results (implements \IteratorAggregate interface).
	 *
	 * @return ArrayIterator An \ArrayIterator with all results.
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator($this->all());
	}


	/**
	 * Returns one result.
	 *
	 * @return Document|null A document or null if there is no any result.
	 */
	public function one() {
		$currentLimit = $this->limit;
		$results = $this->limit(1)->all();
		$this->limit = $currentLimit;

		return $results ? array_shift($results) : null;
	}


	/**
	 * Count the number of results of the query.
	 *
	 * @return int The number of results of the query.
	 */
	public function count(): int {
		$collection = $this->getRepository()->getCollection();

		return $collection->countDocuments($this->criteria);
	}


	/**
	 * Create a cursor with the data of the query.
	 *
	 * @return Result A cursor with the data of the query.
	 */
	public function createCursor() {

		$cursor = $this->repository->getCollection()->find($this->criteria, $this->generateOptionsForFind());

		return new Result($cursor);
	}


	/**
	 * Create an ArrayObject with a result's text command of the query.
	 *
	 * @return Result A iterable object with the data of the query.
	 */
	public function createResult() {
		if (!$this->text) {
			return false;
		}

		[$search, $requiredScore, $language] = array_values($this->text);

		$limit = $this->limit;
		if ($this->skip && $this->limit) {
			$limit += $this->skip;
		}

		$options = [];
		if ($this->timeout) {
			$options['timeout'] = $this->timeout;
		}

		$fields = $this->fields;

		$response = $this->repository->text(
			$search,
			$this->criteria,
			$fields,
			$limit,
			$language,
			$options
		);

		$result = new ArrayObject();
		foreach ($response['results'] as $index => $document) {
			if ($requiredScore && $requiredScore > $document['score']) {
				continue;
			}
			if ($this->skip && $index < $this->skip) {
				continue;
			}

			$result[(string) $document['obj']['_id']] = $document['obj'];
		}

		return new Result($result);
	}


	/**
	 * Execute the query to the server, if text method was used will return an ArrayObject
	 * if not will return a MongoCursor
	 *
	 * @return ArrayObject|MongoCursor A iterable object with the data of the query.
	 */
	public function execute() {
		if ($this->text) {
			return $this->createResult();
		} else {
			return $this->createCursor();
		}
	}


	/**
	 * Generate a unique key for this query
	 *
	 * @return string md5
	 */
	public function generateKey($includeHash = true) {
		$keys = [];

		$keys['vars'] = get_object_vars($this);
		$keys['class'] = static::class;
		$keys['metadata'] = $this->repository->getMetadata();
		$keys['dbname'] = $this->repository->getConnection()->getDbName();

		unset($keys['vars']['repository']);
		if (!$includeHash) {
			unset($keys['vars']['hash']);
		}

		return md5(serialize($keys));
	}


	/**
	 * Generate options for collection find query
	 *
	 * @return array
	 */
	protected function generateOptionsForFind() {
		$options = [];
		if ($this->fields) {
			$options['projection'] = $this->fields;
		}
		if ($this->limit) {
			$options['limit'] = $this->limit;
		}
		if ($this->skip) {
			$options['skip'] = $this->skip;
		}
		if ($this->sort) {
			$options['sort'] = $this->sort;
		}

		return $options;
	}


	protected function valueToMongoId($value) {
		if (is_string($value)) {
			return new ObjectID($value);
		}

		if (!is_object($value)) {
			$this->throwBadReferenceException();
		}

		if ($value instanceof ObjectID) {
			return $value;
		}
		if ($value instanceof Document) {
			return $value->getId();
		}

		$this->throwBadReferenceException();
	}


	protected function throwBadReferenceException() {
		throw new Exception('Document or ObjectID needed for reference query');
	}


}
