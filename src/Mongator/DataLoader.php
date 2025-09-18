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

use RuntimeException;

/**
 * Class to load data from an array..
 */
class DataLoader {


	private $mongator;


	/**
	 * @param Mongator $mongator The Mongator.
	 */
	public function __construct(Mongator $mongator) {
		$this->setMongator($mongator);
	}


	/**
	 * Set the Mongator.
	 *
	 * @param Mongator $mongator The Mongator.
	 */
	public function setMongator(Mongator $mongator) {
		$this->mongator = $mongator;
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
	 * Load data.
	 *
	 * @param array $data  The data to load.
	 * @param bool  $purge If purge the databases before load the data.
	 * @throws RuntimeException If the Mongator's UnitOfWork has pending operations.
	 */
	public function load(array $data, $purge = false) {
		// has pending
		if ($this->mongator->getUnitOfWork()->hasPending()) {
			throw new RuntimeException('The Mongator\'s Unit of Work has pending operations.');
		}

		// purge
		if ($purge) {
			foreach ($this->mongator->getAllRepositories() as $repository) {
				$repository->getCollection()->drop();
			}
		}

		// vars
		$mongator = $this->mongator;
		$documents = [];

		$maps = [];
		foreach ($data as $class => $datum) {
			$maps[$class] = $mongator->getRepository($class)->getMetadata();
		}

		$referencesOne = [];
		$referencesMany = [];
		foreach ($maps as $class => $metadata) {
			$referencesOne[$class] = $metadata['referencesOne'];
			$referencesMany[$class] = $metadata['referencesMany'];

			$map = $metadata;
			while ($map['inheritance']) {
				$inheritanceClass = $map['inheritance']['class'];
				$map = $mongator->getRepository($inheritanceClass)->getMetadata();
				$referencesOne[$class] = array_merge($map['referencesOne'], $referencesOne[$class]);
				$referencesMany[$class] = array_merge($map['referencesMany'], $referencesMany[$class]);
			}
		}

		// process function
		$process = static function ($class, $key) use (&$process, $mongator, &$data, &$documents, &$referencesOne, &$referencesMany) {
			static $processed = [];

			if (isset($processed[$class][$key])) {
				return;
			}

			if (!isset($data[$class][$key])) {
				throw new RuntimeException(sprintf('The document "%s" of the class "%s" does not exist.', $key, $class));
			}
			$datum = $data[$class][$key];

			$documents[$class][$key] = $document = new $class($mongator);

			// referencesOne
			foreach ($referencesOne[$class] as $name => $reference) {
				if (!isset($datum[$name])) {
					continue;
				}

				$process($reference['class'], $datum[$name]);

				if (!isset($documents[$reference['class']][$datum[$name]])) {
					throw new RuntimeException(sprintf('The reference "%s" (%s) for the class "%s" does not exists.', $datum[$name], $name, $class));
				}
				$document->set($name, $documents[$reference['class']][$datum[$name]]);
				unset($datum[$name]);
			}

			// referencesMany
			foreach ($referencesMany[$class] as $name => $reference) {
				if (!isset($datum[$name])) {
					continue;
				}

				$refs = [];
				foreach ($datum[$name] as $value) {
					$process($reference['class'], $value);

					if (!isset($documents[$reference['class']][$value])) {
						throw new RuntimeException(sprintf('The reference "%s" (%s) for the class "%s" does not exists.', $value, $name, $class));
					}
					$refs[] = $documents[$reference['class']][$value];
				}
				$document->get($name)->add($refs);
				unset($datum[$name]);
			}

			// document
			$document->fromArray($datum);
			$mongator->persist($document);

			$processed[$class][$key] = true;
			unset($data[$class][$key]);
		};

		// process
		foreach ($data as $class => $datum) {
			foreach (array_keys($datum) as $key) {
				$process($class, $key);
			}
		}

		// flush
		$this->mongator->flush();
	}


}
