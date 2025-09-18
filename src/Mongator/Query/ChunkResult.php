<?php

namespace Mongator\Query;

use ArrayObject;
use Closure;

class ChunkResult extends ArrayObject {


	private $total;


	public function setTotal($total) {
		$this->total = $total;
	}


	public function getData() {
		return $this->getArrayCopy();
	}


	public function getTotal() {
		if ($this->total instanceof Closure) {
			$this->total = $this->total->__invoke();
		}

		return $this->total;
	}


}
