<?php

namespace Mongator\Query;

use ArrayObject;
use MongoDB\BSON\ObjectID;

abstract class LocalChunk implements ChunkInterface {


	protected $filterFuncName;

	protected $filterFields;

	protected $localSortingStrategies;

	protected $page;

	protected $pageSize;

	protected $ordering = 1;

	protected $valueFunc = null;

	protected $fields = [];

	protected $filterFunc = null;

	protected $dbSortFields;

	protected $cache = [];

	/** @var array<int>|mixed */
	private mixed $sortStrategy;


	public function set($sortStrategy, $page, $pageSize) {
		$this->setSortStrategy($sortStrategy);
		$this->setPagination($page, $pageSize);

		return $this;
	}


	public function setPagination($page, $pageSize) {
		$this->page = $page;
		$this->pageSize = $pageSize;

		return $this;
	}


	public function setSortStrategy($sortStrategy) {
		if (!is_array($sortStrategy)) {
			$sortStrategy = [$sortStrategy => 1];
		}
		$this->sortStrategy = $sortStrategy;
	}


	final public function getResult(Query $query) {
		$this->prepareForQuery();
		[$total, $selectedIds] = $this->getSelectedIds($query);
		$data = $this->findByIds($query, $selectedIds);

		$result = $this->createChunkResult($data);
		$result->setTotal($total);

		return $result;
	}


	public function setCacheStorage(ArrayObject $cache) {
		$this->cache = $cache;
	}


	private function setLocalSort($valueFunc, $ordering) {
		$this->ordering = $ordering;
		$this->valueFunc = $valueFunc;
	}


	protected function setDBSort($sortFields) {
		if (!is_array($sortFields)) {
			$sortFields = [$sortFields => 1];
		}
		$this->dbSortFields = $sortFields;

		return $this;
	}


	private function setFields(array $fields) {
		$this->fields = $fields;
	}


	protected function prepareForQuery() {
		$sortField = array_keys($this->sortStrategy);
		$sortField = $sortField[0];
		if (isset($this->localSortingStrategies[$sortField])) {
			[$valueFuncName, $fields] = $this->localSortingStrategies[$sortField];

			$this->setFields(array_merge($this->filterFields, $fields));
			$this->setLocalSort($valueFuncName, $this->sortStrategy[$sortField]);
		} else {
			$this->setFields($this->filterFields);
			$this->setDBSort($this->sortStrategy);
		}
	}


	protected function getSelectedIds(Query $query) {
		if ($this->valueFunc) {
			return $this->getLocallySelectedIds($query);
		} else {
			return $this->getDBSelectedIds($query);
		}
	}


	protected function getLocallySelectedIds($query) {
		[$total, $elems] = $this->getLocallySortedIds($query);
		$ids = $this->getPage($elems);

		return [$total, $ids];
	}


	protected function getLocallySortedIds(Query $query) {
		$valuedElems = [];
		$total = 0;

		foreach ($this->getElems($query) as $id => $elem) {
			if ($this->filterFuncName && !$this->{$this->filterFuncName}($elem)) {
				continue;
			}
			$value = $this->{$this->valueFunc}($elem);

			if ($value !== null) {
				$valuedElems[$id] = $value * $this->ordering;
				$total++;
			}
		}

		asort($valuedElems, SORT_NUMERIC);

		return [$total, array_keys($valuedElems)];
	}


	protected function getDBSelectedIds($query) {
		$query->sort($this->dbSortFields);
		foreach (array_keys($this->dbSortFields) as $field) {
			$query->mergeCriteria([$field => ['$exists' => 1]]);
		}

		$elems = $this->getElems($query);
		$ids = [];

		$all = !$this->pageSize;
		$start = $this->page * $this->pageSize;
		$end = $start + $this->pageSize;
		$count = 0;
		$total = 0;
		foreach ($elems as $id => $elem) {
			if (!$this->filterFuncName || $this->{$this->filterFuncName}($elem)) {
				$total++;
				if ($all || (($count >= $start) && ($count < $end))) {
					$ids[] = new ObjectID($id);
				}
				$count++;
			}
		}

		return [$total, $ids];
	}


	private function getElems(Query $query) {
		if ($this->fields) {
			$query = $query->fields($this->fields);
		}

		$key = $query->generateKey();
		if (!isset($this->cache[$key])) {
			$this->cache[$key] = [];
			foreach ($query->execute() as $id => $record) {
				$this->cache[$key][$id] = $record;
			}
		}

		return $this->cache[$key];
	}


	protected function getPage($valuedElems) {
		if (!$this->pageSize) {
			return array_values($valuedElems);
		}

		return array_values(
			array_slice($valuedElems, $this->page * $this->pageSize, $this->pageSize)
		);
	}


	protected function findByIds($query, $ids) {
		$selected = $query->getRepository()->findById($ids);

		$data = [];
		foreach ($ids as $id) {
			$data[] = $selected[(string) $id];
		}

		return $data;
	}


	protected function createChunkResult($data) {
		return new ChunkResult($data);
	}


}
