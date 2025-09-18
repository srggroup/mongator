<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Id;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Container of id generators.
 */
class IdGeneratorContainer {


	private static $map = [
		'none'     => NoneIdGenerator::class,
		'native'   => NativeIdGenerator::class,
		'sequence' => SequenceIdGenerator::class,
	];

	private static $idGenerators = [];


	/**
	 * Returns whether or not an id generator exists.
	 *
	 * @param string $name The name.
	 * @return bool Whether or not the id generator exists.
	 */
	public static function has($name) {
		return isset(static::$map[$name]);
	}


	/**
	 * Add an id generator.
	 *
	 * @param string $name  The name.
	 * @param string $class The class.
	 * @throws InvalidArgumentException If the id generator already exists.
	 * @throws InvalidArgumentException If the class is not a subclass of Mongator\Id\IdGenerator.
	 * @throws ReflectionException
	 */
	public static function add($name, $class) {
		if (static::has($name)) {
			throw new InvalidArgumentException(sprintf('The id generator "%s" already exists.', $name));
		}

		$r = new ReflectionClass($class);
		if (!$r->isSubclassOf(BaseIdGenerator::class)) {
			throw new InvalidArgumentException(sprintf('The class "%s" is not a subclass of Mongator\Id\BaseIdGenerator.', $class));
		}

		static::$map[$name] = $class;
	}


	/**
	 * Returns an id generator.
	 *
	 * @param string $name The name.
	 * @return BaseIdGenerator The id generator.
	 * @throws InvalidArgumentException If the id generator does not exists.
	 */
	public static function get($name) {
		if (!isset(static::$idGenerators[$name])) {
			if (!static::has($name)) {
				throw new InvalidArgumentException(sprintf('The id generator "%s" does not exists.', $name));
			}

			static::$idGenerators[$name] = new static::$map[$name]();
		}

		return static::$idGenerators[$name];
	}


	/**
	 * Remove an id generator.
	 *
	 * @param string $name The name.
	 * @throws InvalidArgumentException If the id generator does not exists.
	 */
	public static function remove($name) {
		if (!static::has($name)) {
			throw new InvalidArgumentException(sprintf('The id generator "%s" does not exists.', $name));
		}

		unset(static::$map[$name], static::$idGenerators[$name]);
	}


	/**
	 * Reset the id generators.
	 */
	public static function reset() {
		static::$map = [
			'none'     => NoneIdGenerator::class,
			'native'   => NativeIdGenerator::class,
			'sequence' => SequenceIdGenerator::class,
		];

		static::$idGenerators = [];
	}


}
