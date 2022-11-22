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

use MongoDB\Driver\Cursor;

class Result implements \Iterator, \Countable, \Serializable
{
    private $count;
    private $iterator;

    public function __construct($input)
    {
    	if ( $input instanceof Cursor) return $this->iterator = new \IteratorIterator($input);
        if ( $input instanceOf \Iterator ) return $this->iterator = $input;
        if ( $input instanceOf \ArrayObject ) return $this->iterator = $input->getIterator();

        throw new \InvalidArgumentException(sprintf(
            'Invalid object, must be instance of Iterator or ArrayObject, instance of %s given',
            get_class($input)
        ));
    }

    /**
     * Return the iterator
     *
     * @return Iterator
     *
     * @api
     */
    public function getIterator()
    {
        return $this->iterator;
    }

    /**
     * Return the current element
     *
     * @return mixed
     *
     * @api
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * Return the current element
     *
     * @return mixed
     *
     * @api
     */
    public function count(): int
    {
        if ( !$this->count ) return $this->iterator->count();

        if ($this->count instanceOf \Closure) {
            $this->count = $this->count->__invoke();
        }

        return $this->count;
    }

    /**
     * Return the current element
     *
     * @return mixed
     *
     * @api
     */
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    /**
     * Return the key of the current element
     *
     * @return string
     *
     * @api
     */
    public function key(): mixed
    {
        return $this->iterator->key();
    }

    /**
     * Move forward to next element
     *
     * @api
     */
    public function next(): void
    {
        $this->iterator->next();
    }

    /**
     * Rewind the Iterator to the first elemen
     *
     * @api
     */
    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean
     *
     * @api
     */
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    /**
     * String representation of object
     *
     * @return string
     *
     * @api
     */
    public function serialize()
    {
        $array = array(
            'count' => $this->count(),
            'data' => iterator_to_array($this->iterator)
        );

        return serialize($array);
    }

    /**
     * Constructs the object
     *
     * @api
     */
    public function unserialize($data)
    {
        $array = unserialize($data);
        $this->count = $array['count'];
        $this->iterator = new \ArrayIterator($array['data']);
    }
	
	
	/**
	 * @return array|string
	 */
	public function __serialize(): array{
		return $this->serialize();
	}
	
	
	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function __unserialize(array $data): void{
		$this->unserialize($data);
	}
}
