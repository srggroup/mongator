<?php

/*
 * This file is part of Mongator.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Query;

use ArrayIterator;
use ArrayObject;
use Closure;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorIterator;
use MongoDB\Driver\Cursor;
use Serializable;

class Result implements Iterator, Countable, Serializable {


	private $count;

	private $iterator;


	public function __construct($input) {
		if ($input instanceof Cursor) {
			return $this->iterator = new IteratorIterator($input);
		}
		if ($input instanceof Iterator) {
			return $this->iterator = $input;
		}
		if ($input instanceof ArrayObject) {
			return $this->iterator = $input->getIterator();
		}

		throw new InvalidArgumentException(sprintf(
			'Invalid object, must be instance of Iterator or ArrayObject, instance of %s given',
			$input::class
		));
	}


	/**
	 * Return the iterator
	 *
	 * @return Iterator
	 */
	public function getIterator() {
		return $this->iterator;
	}


	/**
	 * Return the current element
	 */
	public function setCount($count) {
		$this->count = $count;
	}


	/**
	 * Return the current element
	 *
	 * @return mixed
	 */
	public function count(): int {
		if (!$this->count) {
			return $this->iterator->count();
		}

		if ($this->count instanceof Closure) {
			$this->count = $this->count->__invoke();
		}

		return $this->count;
	}


	/**
	 * Return the current element
	 */
	public function current(): mixed {
		return $this->iterator->current();
	}


	/**
	 * Return the key of the current element
	 *
	 * @return string
	 */
	public function key(): mixed {
		return $this->iterator->key();
	}


	/**
	 * Move forward to next element
	 */
	public function next(): void {
		$this->iterator->next();
	}


	/**
	 * Rewind the Iterator to the first elemen
	 */
	public function rewind(): void {
		$this->iterator->rewind();
	}


	/**
	 * Checks if current position is valid
	 */
	public function valid(): bool {
		return $this->iterator->valid();
	}


	/**
	 * String representation of object
	 *
	 * @return string
	 */
	public function serialize() {
		$array = [
			'count' => $this->count(),
			'data'  => iterator_to_array($this->iterator),
		];

		return serialize($array);
	}


	/**
	 * Constructs the object
	 */
	public function unserialize($data) {
		$array = unserialize($data);
		$this->count = $array['count'];
		$this->iterator = new ArrayIterator($array['data']);
	}


	public function __serialize(): array {
		return $this->serialize();
	}


	public function __unserialize(array $data): void {
		$this->unserialize($data);
	}


}
