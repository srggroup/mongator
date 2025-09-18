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
use MongoCode;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\Driver\Cursor;
use MongoDB\UpdateResult;
use RuntimeException;

/**
 * The base class for repositories.
 */
abstract class Repository {


	/*
	 * Setted by the generator.
	 */
	protected $documentClass;

	protected $isFile;

	protected $connectionName;

	protected $collectionName;

	private $mongator;

	private $identityMap;

	private $connection;

	private $collection;


	/**
	 * Converts an id to use in Mongo.
	 *
	 * @param mixed $id An id.
	 * @return mixed The id to use in Mongo.
	 */
	abstract public function idToMongo($id);


	/**
	 * @param Mongator $mongator The Mongator.
	 */
	public function __construct(Mongator $mongator) {
		$this->mongator = $mongator;
		$this->identityMap = new IdentityMap();
	}


	/**
	 * Returns the Mongator.
	 *
	 * @return Mongator The Mongator.
	 */
	public function getMongator() {
		return $this->mongator;
	}


	/**
	 * Returns the identity map.
	 *
	 * @return IdentityMapInterface The identity map.
	 */
	public function getIdentityMap() {
		return $this->identityMap;
	}


	/**
	 * Returns the document class.
	 *
	 * @return string The document class.
	 */
	public function getDocumentClass() {
		return $this->documentClass;
	}


	/**
	 * Returns the metadata.
	 *
	 * @return array The metadata.
	 */
	public function getMetadata() {
		return $this->mongator->getMetadataFactory()->getClass($this->documentClass);
	}


	/**
	 * Returns if the document is a file (if it uses GridFS).
	 *
	 * @return bool If the document is a file.
	 */
	public function isFile() {
		return $this->isFile;
	}


	/**
	 * Returns the connection name, or null if it is the default.
	 *
	 * @return string|null The connection name.
	 */
	public function getConnectionName() {
		return $this->connectionName;
	}


	/**
	 * Returns the collection name.
	 *
	 * @return string The collection name.
	 */
	public function getCollectionName() {
		return $this->collectionName;
	}


	/**
	 * Returns the connection.
	 *
	 * @return ConnectionInterface The connection.
	 */
	public function getConnection() {
		if (!$this->connection) {
			if ($this->connectionName) {
				$this->connection = $this->mongator->getConnection($this->connectionName);
			} else {
				$this->connection = $this->mongator->getDefaultConnection();
			}
		}

		return $this->connection;
	}


	/**
	 * Returns the collection.
	 *
	 * @return Collection The collection.
	 */
	public function getCollection() {
		if (!$this->collection) {
			// gridfs
			if ($this->isFile) {
				$this->collection = $this->getConnection()->getMongoDB()->getGridFS($this->collectionName);
				// normal
			} else {
				$this->collection = $this->getConnection()->getMongoDB()->selectCollection($this->collectionName);
			}
		}

		return $this->collection;
	}


	/**
	 * Create a query for the repository document class.
	 *
	 * @param array $criteria The criteria for the query (optional).
	 * @return Query The query.
	 */
	public function createQuery(array $criteria = []) {
		$class = $this->documentClass . 'Query';
		$query = new $class($this);
		$query->criteria($criteria);

		return $query;
	}


	/**
	 * Converts an array of ids to use in Mongo.
	 *
	 * @param array $ids An array of ids.
	 * @return array The array of ids converted.
	 */
	public function idsToMongo(array $ids) {
		foreach ($ids as &$id) {
			$id = $this->idToMongo($id);
		}

		return $ids;
	}


	/**
	 * Find documents by id.
	 *
	 * @param array $ids An array of ids.
	 * @return array An array of documents.
	 */
	public function findById(array $ids) {
		$ids = $this->idsToMongo($ids);

		$documents = [];
		$remaining = [];
		foreach ($ids as $id) {
			if ($this->identityMap->has($id)) {
				$documents[(string) $id] = $this->identityMap->get($id);
			} else {
				$remaining[] = $id;
			}
		}

		if (count($documents) === count($ids)) {
			return $documents;
		}

		return array_merge(
			$documents,
			$this->createQuery(['_id' => ['$in' => $remaining]])->all()
		);
	}


	/**
	 * Returns one document by id.
	 *
	 * @param mixed $id An id.
	 * @return Document|null The document or null if it does not exist.
	 */
	public function findOneById($id) {
		$id = $this->idToMongo($id);

		if ($this->identityMap->has($id)) {
			return $this->identityMap->get($id);
		}

		return $this->createQuery(['_id' => $id])->one();
	}


	/**
	 * Count documents.
	 *
	 * @param array $query The query (opcional, by default an empty array).
	 * @return int The number of documents.
	 */
	public function count(array $query = []) {
		return $this->getCollection()->countDocuments($query);
	}


	/**
	 * Updates documents.
	 *
	 * @param array $query     The query.
	 * @param array $newObject The new object.
	 * @param array $options   The options for the update operation (optional).
	 * @return UpdateResult The result of the update collection method.
	 */
	public function update(array $query, array $newObject, array $options = []) {
		return $this->getCollection()->updateMany($query, $newObject, $options);
	}


	/**
	 * Remove documents.
	 *
	 * @param array $query   The query (optional, by default an empty array).
	 * @param array $options The options for the remove operation (optional).
	 * @return DeleteResult The result of the remove collection method.
	 */
	public function remove(array $query = [], array $options = []) {
		return $this->getCollection()->deleteMany($query, $options);
	}


	/**
	 * Ensure the indexes to the database
	 *
	 * @param bool $delete (optional) true by default drop unknown and old indexes
	 * @return bool
	 */
	public function createIndexes($delete = true) {
		$indexManager = new IndexManager($this);

		return $indexManager->commit($delete);
	}


	/**
	 * Shortcut to the collection group method.
	 *
	 * @deprecated MongoDB\Collection::group() is not implemented yet, use command()
	 *
	 * @see        Collection::command
	 *
	 * @param mixed $keys    The keys.
	 * @param array $initial The initial value.
	 * @param mixed $reduce  The reduce function.
	 * @param array $options The options (optional).
	 * @return array The result
	 */
	public function group($keys, array $initial, $reduce, array $options = []) {
		return $this->getCollection()->group($keys, $initial, $reduce, $options);
	}


	/**
	 * Shortcut to make a distinct command.
	 *
	 * @param string $field The field.
	 * @param array $query  The query (optional).
	 * @return array The results.
	 */
	public function distinct($field, array $query = []) {
		return $this->getCollection()->distinct($field, $query);
	}


	/**
	 * Search text content stored in the text index.
	 *
	 * @param string $search   A string of terms that MongoDB parses and uses to query the text index.
	 * @param array $filter    (optional) A query array, you can use any valid MongoDB query
	 * @param array $fields    (optional) Allows you to limit the fields returned by the query to only those specified.
	 * @param int $limit       (optional) Specify the maximum number of documents to include in the response.
	 * @param string $language (optional) Specify the language that determines for the search the list of stop words and the rules for the stemmer and tokenizer.
	 * @param array $options   Extra options for the command (optional).
	 * @return array The results.
	 */
	public function text($search, array $filter = [], $fields = [], $limit = null, $language = null, $options = []) {
		$command = [
			'text'     => $this->getCollectionName(),
			'search'   => $search,
			'filter'   => $filter,
			'project'  => $fields,
			'limit'    => $limit,
			'language' => $language,
		];

		return $this->command($command, $options);
	}


	/**
	 * Shortcut to make map reduce.
	 *
	 * @param mixed $map     The map function.
	 * @param mixed $reduce  The reduce function.
	 * @param array $out     The out.
	 * @param array $query   The query (optional).
	 * @param array $command Extra command options (optional).
	 * @param array $options Extra options for the command (optional).
	 * @return array With the
	 * @throws RuntimeException If the database returns an error.
	 */
	public function mapReduce(
		$map,
		$reduce,
		array $out,
		array $query = [],
		array $command = [],
		$options = []
	) {
		$command = array_merge($command, [
			'mapreduce' => $this->getCollectionName(),
			'map'       => is_string($map) ? new MongoCode($map) : $map,
			'reduce'    => is_string($reduce) ? new MongoCode($reduce) : $reduce,
			'out'       => $out,
			'query'     => $query,
		]);

		$result = $this->command($command, $options);

		if (isset($out['inline']) && $out['inline']) {
			return $result['results'];
		}

		return $this->getConnection()->getMongoDB()->selectCollection($result['result'])->find();
	}


	/**
	 * Shortcut to make an aggregation.
	 *
	 * @param array $pipeline The pipeline for aggregation
	 * @param array $options  Extra options for the command (optional).
	 * @return array With the aggregation
	 * @throws RuntimeException If the database returns an error.
	 */
	public function aggregate(array $pipeline, array $options = []) {
		/** @var Cursor $result */
		$result = $this->getConnection()->getMongoDB()->selectCollection($this->getCollectionName())->aggregate($pipeline, $options);

		return $result->toArray();
	}


	private function command($command, $options) {
		$result = $this->getConnection()->getMongoDB()->command($command, $options);

		$result = $result->toArray()[0];

		if (!isset($result['ok']) || !$result['ok']) {
			throw new RuntimeException($result['errmsg']);
		}

		return $result;
	}


}
