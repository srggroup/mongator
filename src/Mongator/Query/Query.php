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

use Mongator\Repository;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\Cursor;
use Traversable;

/**
 * Query.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
abstract class Query implements \Countable, \IteratorAggregate
{
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
     * Constructor.
     *
     * @param \Mongator\Repository $repository The repository of the document class to query.
     *
     * @api
     */
    public function __construct(Repository $repository)
    {
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

        $this->criteria = array();
        $this->fields = array();
        $this->references = array();
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
     *
     */
    public function findById($id)
    {
        $id = $this->getRepository()->idToMongo($id);
        return $this->mergeCriteria(array('_id' => $id));
    }

    /**
     * Adds criteria to find by id
     *
     * @param array $ids the array of ids to find
     *
     */
    public function findByIds(array $ids)
    {
        $ids = $this->getRepository()->idsToMongo($ids);
        return $this->mergeCriteria(array('_id' => array('$in' => $ids)));
    }

    /**
     * Returns the repository.
     *
     * @return \Mongator\Repository The repository.
     *
     * @api
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns the query hash.
     *
     * @return string The query hash.
     */
    public function getHash()
    {
        return $this->hash;
    }

    public function getFullCache()
    {
        if ( !$cache = $this->repository->getMongator()->getFieldsCache() ) {
            return null;
        }

        return $cache->get($this->hash);
    }

    /**
     * Set the criteria.
     *
     * @param array $criteria The criteria.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function criteria(array $criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Merges a criteria with the current one.
     *
     * @param array $criteria The criteria.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function mergeCriteria(array $criteria)
    {
        $this->criteria = null === $this->criteria ? $criteria : array_merge($this->criteria, $criteria);

        return $this;
    }

    /**
     * Returns the criteria.
     *
     * @return array The criteria.
     *
     * @api
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Set the fields.
     *
     * @param array $fields The fields.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function fields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Returns the fields.
     *
     * @return array The fields.
     *
     * @api
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set the references.
     *
     * @param array $references The references.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @throws \InvalidArgumentException If the references are not an array or null.
     *
     * @api
     */
    public function references($references)
    {
        if (null !== $references && !is_array($references)) {
            throw new \InvalidArgumentException(sprintf('The references "%s" are not valid.', $references));
        }

        $this->references = $references;

        return $this;
    }

    /**
     * Returns the references.
     *
     * @return array The references.
     *
     * @api
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * Set the sort.
     *
     * @param array|null $sort The sort.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @throws \InvalidArgumentException If the sort is not an array or null.
     *
     * @api
     */
    public function sort($sort)
    {
        if (null !== $sort && !is_array($sort)) {
            throw new \InvalidArgumentException(sprintf('The sort "%s" is not valid.', $sort));
        }

        $this->sort = $sort;

        return $this;
    }

    /**
     * Returns the sort.
     *
     * @return array The sort.
     *
     * @api
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set the limit.
     *
     * @param int|null $limit The limit.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @throws \InvalidArgumentException If the limit is not a valid integer or null.
     *
     * @api
     */
    public function limit($limit)
    {
        if (null !== $limit) {
            if (!is_numeric($limit) || $limit != (int) $limit) {
                throw new \InvalidArgumentException('The limit is not valid.');
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
     *
     * @api
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set the skip.
     *
     * @param int|null $skip The skip.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @throws \InvalidArgumentException If the skip is not a valid integer, or null.
     *
     * @api
     */
    public function skip($skip)
    {
        if (null !== $skip) {
            if (!is_numeric($skip) || $skip != (int) $skip) {
                throw new \InvalidArgumentException('The skip is not valid.');
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
     *
     * @api
     */
    public function getSkip()
    {
        return $this->skip;
    }

    /**
     * Set the batch size.
     *
     * @param int|null $batchSize The batch size.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function batchSize($batchSize)
    {
        if (null !== $batchSize) {
            if (!is_numeric($batchSize) || $batchSize != (int) $batchSize) {
                throw new \InvalidArgumentException('The batchSize is not valid.');
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
     *
     * @api
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * Set the hint.
     *
     * @param array|null The hint.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function hint($hint)
    {
        if (null !== $hint && !is_array($hint)) {
            throw new \InvalidArgumentException(sprintf('The hint "%s" is not valid.', $hint));
        }

        $this->hint = $hint;

        return $this;
    }

    /**
     * Returns the hint.
     *
     * @return array|null The hint.
     *
     * @api
     */
    public function getHint()
    {
        return $this->hint;
    }

    /**
     * Set if the snapshot mode is used.
     *
     * @param bool $snapshot If the snapshot mode is used.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function snapshot($snapshot)
    {
        if (!is_bool($snapshot)) {
            throw new \InvalidArgumentException('The snapshot is not a boolean.');
        }

        $this->snapshot = $snapshot;

        return $this;
    }

    /**
     * Returns if the snapshot mode is used.
     *
     * @return bool If the snapshot mode is used.
     *
     * @api
     */
    public function getSnapshot()
    {
        return $this->snapshot;
    }

    /**
     * Set the timeout.
     *
     * @param int|null $timeout The timeout of the cursor.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function timeout($timeout)
    {
        if (null !== $timeout) {
            if (!is_numeric($timeout) || $timeout != (int) $timeout) {
                throw new \InvalidArgumentException('The timeout is not valid.');
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
     *
     * @api
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the text search criterias. The text methods requires a text index.
     *
     * @param string $search        A string of terms
     * @param int    $requiredScore (optional) All the documents with less score will be omitted
     * @param int    $language      (optional) Specify the language that determines for the search the list of stop words and the rules for the stemmer and tokenizer. If not specified, the search uses the default language of the index.
     *
     * @return \Mongator\Query\Query The query instance (fluent interface).
     *
     * @api
     */
    public function text($search, $requiredScore = null, $language = null)
    {
        if ($search === null) {
            $this->text = null;
        } else {
            $this->text = array(
                'search' => $search,
                'requiredScore' => $requiredScore,
                'language' => $language
            );
        }

        return $this;
    }

    /**
     * Returns the text search criterias.
     *
     * @return array|null The text search criterias.
     *
     * @api
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Returns all the results.
     *
     * @return array An array with all the results.
     *
     * @api
     */
    abstract public function all();

    /**
     * Returns an \ArrayIterator with all results (implements \IteratorAggregate interface).
     *
     * @return \ArrayIterator An \ArrayIterator with all results.
     *
     * @api
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Returns one result.
     *
     * @return \Mongator\Document\Document|null A document or null if there is no any result.
     *
     * @api
     */
    public function one()
    {
        $currentLimit = $this->limit;
        $results = $this->limit(1)->all();
        $this->limit = $currentLimit;

        return $results ? array_shift($results) : null;
    }

    /**
     * Count the number of results of the query.
     *
     * @return int The number of results of the query.
     *
     * @api
     */
    public function count(): int
    {
    	$collection = $this->getRepository()->getCollection();
    	return $collection->countDocuments($this->criteria);
    }

    /**
     * Create a cursor with the data of the query.
     *
     * @return Result A cursor with the data of the query.
     */
    public function createCursor()
    {
    	
        $cursor = $this->repository->getCollection()->find($this->criteria, $this->generateOptionsForFind());

        $result = new Result($cursor);

        return $result;
    }

    /**
     * Create an ArrayObject with a result's text command of the query.
     *
     * @return Result A iterable object with the data of the query.
     */
    public function createResult()
    {
        if (!$this->text) {
            return false;
        }

        list($search, $requiredScore, $language) = array_values($this->text);

        $limit = $this->limit;
        if ($this->skip && $this->limit) {
            $limit += $this->skip;
        }

        $options = array();
        if ( $this->timeout ) $options['timeout'] = $this->timeout;

        //$fields = array();
        $fields=$this->fields;
        /*foreach ($this->fields as $key => $value) {
            if ( !is_numeric($value) ) $fields[$value] = 1;
            else $fields[$key] = $value;
        }*/

        $response = $this->repository->text(
            $search,
            $this->criteria,
            $fields,
            $limit,
            $language,
            $options
        );

        $result = new \ArrayObject;
        foreach ($response['results'] as $index => $document) {
            if ( $requiredScore && $requiredScore > $document['score'] ) continue;
            if ( $this->skip && $index < $this->skip ) continue;

            $result[(string) $document['obj']['_id']] = $document['obj'];
        }

        return new Result($result);
    }

    /**
     * Execute the query to the server, if text method was used will return an ArrayObject
     * if not will return a MongoCursor
     *
     * @return \ArrayObject|\MongoCursor A iterable object with the data of the query.
     */
    public function execute()
    {
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
    public function generateKey($includeHash = true)
    {
        $keys = array();

        $keys['vars'] = get_object_vars($this);
        $keys['class'] = get_class($this);
        $keys['metadata'] = $this->repository->getMetadata();
        $keys['dbname'] = $this->repository->getConnection()->getDbName();

        unset($keys['vars']['repository']);
        if ( !$includeHash ) unset($keys['vars']['hash']);
        return md5(serialize($keys));
    }
	
	/**
	 * Generate options for collection find query
	 * @return array
	 */
    protected function generateOptionsForFind(){
		$options = [];
		if($this->fields) $options['projection'] = $this->fields;
		if($this->limit) $options['limit'] = $this->limit;
		if($this->skip) $options['skip'] = $this->skip;
		if($this->sort) $options['sort'] = $this->sort;
		
		return $options;
	}

    protected function valueToMongoId($value)
    {
        if (is_string($value)) return new ObjectID($value);

        if (!is_object($value)) $this->throwBadReferenceException();

        if ($value instanceOf ObjectID) return $value;
        if ($value instanceOf \Mongator\Document\Document) return $value->getId();

        $this->throwBadReferenceException();
    }

    protected function throwBadReferenceException()
    {
        throw new \Exception('Document or ObjectID needed for reference query');
    }
}
