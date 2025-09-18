<?php

namespace Mongator\Query;

class Chunk implements ChunkInterface {


	public $sortFields;

	public $page;

	public $pageSize;


	public function __construct($sortFields = null, $page = null, $pageSize = null) {
		$this->set($sortFields, $page, $pageSize);
	}


	public function set($sortFields, $page, $pageSize) {
		if (($sortFields !== null) && !is_array($sortFields)) {
			$sortFields = [$sortFields => 1];
		}

		$this->sortFields = $sortFields;
		$this->page = $page;
		$this->pageSize = $pageSize;

		return $this;
	}


	public function getResult(Query $query) {
		$this->applyTo($query);

		$result = $this->createChunkResult($query->all());
		$result->setTotal(static function () use ($query) {
			return $query->count();
		});

		return $result;
	}


	private function applyTo(Query $query) {
		if ($this->sortFields !== null) {
			$query->sort($this->sortFields);
		}

		if ($this->page !== null) {
			$query
				->skip($this->pageSize * $this->page)
				->limit($this->pageSize);
		}
	}


	protected function createChunkResult($data) {
		return new ChunkResult($data);
	}


}
