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
use MongoDB\Driver\Cursor;

/**
 * The base class for repositories.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
abstract class Repository
{
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
     * Constructor.
     *
     * @param \Mongator\Mongator $mongator The Mongator.
     *
     * @api
     */
    public function __construct(Mongator $mongator)
    {
        $this->mongator = $mongator;
        $this->identityMap = new IdentityMap();
    }

    /**
     * Returns the Mongator.
     *
     * @return \Mongator\Mongator The Mongator.
     *
     * @api
     */
    public function getMongator()
    {
        return $this->mongator;
    }

    /**
     * Returns the identity map.
     *
     * @return \Mongator\IdentityMapInterface The identity map.
     *
     * @api
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
    }

    /**
     * Returns the document class.
     *
     * @return string The document class.
     *
     * @api
     */
    public function getDocumentClass()
    {
        return $this->documentClass;
    }

    /**
     * Returns the metadata.
     *
     * @return array The metadata.
     *
     * @api
     */
    public function getMetadata()
    {
        return $this->mongator->getMetadataFactory()->getClass($this->documentClass);
    }

    /**
     * Returns if the document is a file (if it uses GridFS).
     *
     * @return boolean If the document is a file.
     *
     * @api
     */
    public function isFile()
    {
        return $this->isFile;
    }

    /**
     * Returns the connection name, or null if it is the default.
     *
     * @return string|null The connection name.
     *
     * @api
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Returns the collection name.
     *
     * @return string The collection name.
     *
     * @api
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * Returns the connection.
     *
     * @return \Mongator\ConnectionInterface The connection.
     *
     * @api
     */
    public function getConnection()
    {
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
     * @return \MongoDB\Collection The collection.
     *
     * @api
     */
    public function getCollection()
    {
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
     *
     * @return Query The query.
     *
     * @api
     */
    public function createQuery(array $criteria = array())
    {
        $class = $this->documentClass.'Query';
        $query = new $class($this);
        $query->criteria($criteria);

        return $query;
    }

    /**
     * Converts an id to use in Mongo.
     *
     * @param mixed $id An id.
     *
     * @return mixed The id to use in Mongo.
     */
    abstract public function idToMongo($id);

    /**
     * Converts an array of ids to use in Mongo.
     *
     * @param array $ids An array of ids.
     *
     * @return array The array of ids converted.
     */
    public function idsToMongo(array $ids)
    {
        foreach ($ids as &$id) {
            $id = $this->idToMongo($id);
        }

        return $ids;
    }

    /**
     * Find documents by id.
     *
     * @param array $ids An array of ids.
     *
     * @return array An array of documents.
     *
     * @api
     */
    public function findById(array $ids)
    {
        $ids = $this->idsToMongo($ids);

        $documents = array();
        $remaining = array();
        foreach ($ids as $id) {
            if ($this->identityMap->has($id)) {
                $documents[(string) $id] = $this->identityMap->get($id);
            } else {
                $remaining[] = $id;
            }
        }

        if (count($documents) == count($ids)) {
            return $documents;
        }

        return array_merge(
            $documents,
            $this->createQuery(array('_id' => array('$in' => $remaining)))->all()
        );
    }

    /**
     * Returns one document by id.
     *
     * @param mixed $id An id.
     *
     * @return \Mongator\Document\Document|null The document or null if it does not exist.
     *
     * @api
     */
    public function findOneById($id)
    {
        $id = $this->idToMongo($id);

        if ($this->identityMap->has($id)) {
            return $this->identityMap->get($id);
        }

        return $this->createQuery(array('_id' => $id))->one();
    }

    /**
     * Count documents.
     *
     * @param array $query The query (opcional, by default an empty array).
     *
     * @return integer The number of documents.
     *
     * @api
     */
    public function count(array $query = array())
    {
        return $this->getCollection()->countDocuments($query);
    }

    /**
     * Updates documents.
     *
     * @param array $query     The query.
     * @param array $newObject The new object.
     * @param array $options   The options for the update operation (optional).
     *
     * @return mixed The result of the update collection method.
     */
    public function update(array $query, array $newObject, array $options = array())
    {
        return $this->getCollection()->updateMany($query, $newObject, $options);
    }

    /**
     * Remove documents.
     *
     * @param array $query   The query (optional, by default an empty array).
     * @param array $options The options for the remove operation (optional).
     *
     * @return mixed The result of the remove collection method.
     *
     * @api
     */
    public function remove(array $query = array(), array $options = array())
    {
        return $this->getCollection()->deleteMany($query, $options);
    }

    /**
     * Ensure the indexes to the database
     *
     * @param boolean $delete (optional) true by default drop unknown and old indexes
     *
     * @return boolean
     *
     * @api
     */
    public function createIndexes($delete = true)
    {
        $indexManager = new IndexManager($this);

        return $indexManager->commit($delete);
    }

    /**
     * Shortcut to the collection group method.
     *
     * @param mixed $keys    The keys.
     * @param array $initial The initial value.
     * @param mixed $reduce  The reduce function.
     * @param array $options The options (optional).
     *
     * @return array The result
     *
     * @see \MongoDB\Collection::command()
	 *
	 * @deprecated MongoDB\Collection::group() is not implemented yet, use command()
     *
     * @api
     */
    public function group($keys, array $initial, $reduce, array $options = array())
    {
        return $this->getCollection()->group($keys, $initial, $reduce, $options);
    }

    /**
     * Shortcut to make a distinct command.
     *
     * @param string $field   The field.
     * @param array  $query   The query (optional).
     * @param array  $options Extra options for the command (optional).
     *
     * @return array The results.
     *
     * @api
     */
    public function distinct($field, array $query = array(), $options = array())
    {
        return $this->getCollection()->distinct($field, $query);
    }

    /**
     * Search text content stored in the text index.
     *
     * @param string  $search   A string of terms that MongoDB parses and uses to query the text index.
     * @param array   $filter   (optional) A query array, you can use any valid MongoDB query
     * @param array   $fields   (optional) Allows you to limit the fields returned by the query to only those specified.
     * @param integer $limit    (optional) Specify the maximum number of documents to include in the response.
     * @param string  $language (optional) Specify the language that determines for the search the list of stop words and the rules for the stemmer and tokenizer.
     * @param array   $options  Extra options for the command (optional).
     *
     * @return array The results.
     *
     * @api
     */
    public function text($search, array $filter = array(), $fields = array(), $limit = null, $language = null, $options = array())
    {
        $command = array(
            'text'     => $this->getCollectionName(),
            'search'   => $search,
            'filter'   => $filter,
            'project'  => $fields,
            'limit'    => $limit,
            'language' => $language
        );

        return $this->command($command, $options);
    }

    /**
     * Shortcut to make map reduce.
     *
     * @param mixed $map     The map function.
     * @param mixed $reduce  The reduce function.
     * @param array $out     The out.
     * @param array $query   The query (optional).
     * @param array $options Extra options for the command (optional).
     *
     * @return array With the
     *
     * @throws \RuntimeException If the database returns an error.
     */
    public function mapReduce(
            $map, $reduce, array $out, array $query = array(),
            array $command = array(), $options = array())
    {
        $command = array_merge($command, array(
            'mapreduce' => $this->getCollectionName(),
            'map'       => is_string($map) ? new \MongoCode($map) : $map,
            'reduce'    => is_string($reduce) ? new \MongoCode($reduce) : $reduce,
            'out'       => $out,
            'query'     => $query,
        ));

        $result = $this->command($command, $options);

        if (isset($out['inline']) && $out['inline']) {
            return $result['results'];
        }

        return $this->getConnection()->getMongoDB()->selectCollection($result['result'])->find();
    }

    /**
     * Shortcut to make an aggregation.
     *
     * @param array $pipeline   The pipeline for aggregation
     * @param array $options    Extra options for the command (optional).
     *
     * @return array With the aggregation
     *
     * @throws \RuntimeException If the database returns an error.
     */
    public function aggregate(array $pipeline, array $options = array())
    {
        $command = array(
            'aggregate' => $this->getCollectionName(),
            'pipeline' => $pipeline
        );
        $result = $this->command($command, $options);

        return $result['result'];
    }

    private function command($command, $options)
    {
		/**
		 * @var $result Cursor
		 */
        $result = $this->getConnection()->getMongoDB()->command($command, $options);

        $result = $result->toArray()[0];
	
		if (!isset($result['ok']) || !$result['ok']) {
			throw new \RuntimeException($result['errmsg']);
		}
		
        return $result;
    }
}
