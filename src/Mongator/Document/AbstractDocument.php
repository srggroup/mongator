<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Document;

use Exception;
use Mongator\Archive;
use Mongator\Mongator;
use ReflectionClass;
use ReflectionObject;

/**
 * The abstract class for documents.
 */
abstract class AbstractDocument {


	private $mongator;

	private $archive;

	public $data = [];

	protected $fieldsModified = [];

	protected $onceEnvents = [];

	protected $savingReferences = false;

	protected $eventPattern;


	/**
	 * Load the full document from the database, in case some fields were not loaded when
	 * it was first read. Previously modified fields are not overwritten.
	 *
	 * This method updates the "fields in query" information.
	 */
	abstract public function loadFull();


	/**
	 * Check whether the field $field was recovered in the query that was used to get the data
	 * to populate the object.
	 *
	 * @param  string $field the field name
	 * @return bool whether the field was present
	 */
	abstract public function isFieldInQuery($field);


	/**
	 * @param Mongator $mongator The Mongator.
	 */
	public function __construct(Mongator $mongator) {
		$this->setMongator($mongator);

		$this->archive = new Archive();
	}


	/**
	 * Return the Archive object
	 *
	 * @return Archive the archive object from this document
	 */
	public function getArchive() {
		return $this->archive;
	}


	/**
	 * Set the Mongator.
	 *
	 * @return Mongator The Mongator.
	 */
	public function setMongator(Mongator $mongator) {
		return $this->mongator = $mongator;
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
	 * Returns the document metadata.
	 *
	 * @return array The document metadata.
	 */
	public function getMetadata() {
		return $this->getMongator()->getMetadataFactory()->getClass($this::class);
	}


	/**
	 * Returns the document data.
	 *
	 * @return array The document data.
	 */
	public function getDocumentData() {
		return $this->data;
	}


	/**
	 * Returns if the document is modified.
	 *
	 * @return bool If the document is modified.
	 */
	public function isModified() {
		if (isset($this->data['fields'])) {
			foreach (array_keys($this->data['fields']) as $name) {
				if ($this->isFieldModified($name)) {
					return true;
				}
			}
		}

		if (isset($this->data['embeddedsOne'])) {
			foreach ($this->data['embeddedsOne'] as $name => $embedded) {
				if ($embedded && $embedded->isModified()) {
					return true;
				}
				if ($this->isEmbeddedOneChanged($name)) {
					$root = null;
					if ($this instanceof Document) {
						$root = $this;
					} elseif ($rap = $this->getRootAndPath()) {
						$root = $rap['root'];
					}
					if ($root && !$root->isNew()) {
						return true;
					}
				}
			}
		}

		if (isset($this->data['embeddedsMany'])) {
			foreach ($this->data['embeddedsMany'] as $group) {
				$add = $group->getAdd();
				foreach ($add as $document) {
					if ($document->isModified()) {
						return true;
					}
				}
				$root = null;
				if ($this instanceof Document) {
					$root = $this;
				} elseif ($rap = $this->getRootAndPath()) {
					$root = $rap['root'];
				}
				if ($root && !$root->isNew()) {
					if ($group->getRemove()) {
						return true;
					}
					if ($group->getClear()) {
						return true;
					}
				}
				if ($group->isSavedInitialized()) {
					if ($add) {
						return true;
					}
					foreach ($group->getSaved() as $document) {
						if ($document->isModified()) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}


	/**
	 * Clear the document modifications, that is, they will not be modifications apart from here.
	 */
	public function clearModified() {
		if (isset($this->data['fields'])) {
			$this->clearFieldsModified();
		}

		if (isset($this->data['embeddedsOne'])) {
			$this->clearEmbeddedsOneChanged();
			foreach ($this->data['embeddedsOne'] as $embedded) {
				if ($embedded) {
					$embedded->clearModified();
				}
			}
		}

		if (isset($this->data['embeddedsMany'])) {
			foreach ($this->data['embeddedsMany'] as $group) {
				$group->markAllSaved();
			}
		}
	}


	/**
	 * Returns if a field is modified.
	 *
	 * @param string $name The field name.
	 * @return bool If the field is modified.
	 */
	public function isFieldModified($name) {
		return isset($this->fieldsModified[$name]) || array_key_exists($name, $this->fieldsModified);
	}


	/**
	 * Returns the original value of a field.
	 *
	 * @param string $name The field name.
	 * @return mixed The original value of the field.
	 */
	public function getOriginalFieldValue($name) {
		if ($this->isFieldModified($name)) {
			return $this->fieldsModified[$name];
		}

		if (isset($this->data['fields'][$name])) {
			return $this->data['fields'][$name];
		}

		return null;
	}


	/**
	 * Returns an array with the fields modified, the field name as key and the original value as value.
	 *
	 * @return array An array with the fields modified.
	 */
	public function getFieldsModified() {
		return $this->fieldsModified;
	}


	/**
	 * Clear the modifications of fields, that is, they will not be modifications apart from here.
	 */
	public function clearFieldsModified() {
		$this->fieldsModified = [];
	}


	/**
	 * Returns if an embedded one is changed.
	 *
	 * @param string $name The embedded one name.
	 * @return bool If the embedded one is modified.
	 */
	public function isEmbeddedOneChanged($name) {
		if (!isset($this->data['embeddedsOne'])) {
			return false;
		}

		if (!isset($this->data['embeddedsOne'][$name]) && !array_key_exists($name, $this->data['embeddedsOne'])) {
			return false;
		}

		return $this->getArchive()->has('embedded_one.' . $name);
	}


	/**
	 * Returns the original value of an embedded one.
	 *
	 * @param string $name The embedded one name.
	 * @return mixed The embedded one original value.
	 */
	public function getOriginalEmbeddedOneValue($name) {
		if ($this->getArchive()->has('embedded_one.' . $name)) {
			return $this->getArchive()->get('embedded_one.' . $name);
		}

		if (isset($this->data['embeddedsOne'][$name])) {
			return $this->data['embeddedsOne'][$name];
		}

		return null;
	}


	/**
	 * Returns an array with the embedded ones changed, with the embedded name as key and the original embedded value as value.
	 *
	 * @return array An array with the embedded ones changed.
	 */
	public function getEmbeddedsOneChanged() {
		$embeddedsOneChanged = [];
		if (isset($this->data['embeddedsOne'])) {
			foreach (array_keys($this->data['embeddedsOne']) as $name) {
				if ($this->isEmbeddedOneChanged($name)) {
					$embeddedsOneChanged[$name] = $this->getOriginalEmbeddedOneValue($name);
				}
			}
		}

		return $embeddedsOneChanged;
	}


	/**
	 * Clear the embedded ones changed, that is, they will not be changed apart from here.
	 */
	public function clearEmbeddedsOneChanged() {
		if (isset($this->data['embeddedsOne'])) {
			foreach (array_merge($this->data['embeddedsOne']) as $name) {
				$this->getArchive()->remove('embedded_one.' . $name);
			}
		}
	}


	/**
	 * Register a callback as preInsert event.
	 */
	public function registerOncePreInsertEvent($event) {
		$this->registerOnceEvent($event, 'pre-insert');
	}


	/**
	 * Register a callback as postInsert event.
	 */
	public function registerOncePostInsertEvent($event) {
		$this->registerOnceEvent($event, 'post-insert');
	}


	/**
	 * Register a callback as preUpdate event.
	 */
	public function registerOncePreUpdateEvent($event) {
		$this->registerOnceEvent($event, 'pre-update');
	}


	/**
	 * Register a callback as postUpdate event.
	 */
	public function registerOncePostUpdateEvent($event) {
		$this->registerOnceEvent($event, 'post-update');
	}


	/**
	 * Triggers the pre instert events
	 */
	public function preInsertEvent() {
		$this->oncePreInsertEvent();
		$this->dispatchEvent('pre.insert');
	}


	/**
	 * Triggers the post insert events
	 */
	public function postInsertEvent() {
		$this->oncePostInsertEvent();
		$this->dispatchEvent('post.insert');
	}


	/**
	 * Triggers the pre update events
	 */
	public function preUpdateEvent() {
		$this->oncePreUpdateEvent();
		$this->dispatchEvent('pre.update');
	}


	/**
	 * Triggers the post update events
	 */
	public function postUpdateEvent() {
		$this->oncePostUpdateEvent();
		$this->dispatchEvent('post.update');
	}


	/**
	 * Triggers the pre delete events
	 */
	public function preDeleteEvent() {
		$this->dispatchEvent('pre.delete');
	}


	/**
	 * Triggers the post delete events
	 */
	public function postDeleteEvent() {
		$this->dispatchEvent('post.delete');
	}


	/**
	 * Returns an array with the document info to debug.
	 *
	 * @return array An array with the document info.
	 */
	public function debug() {
		$info = [];

		$metadata = $this->getMetadata();

		$referenceFields = [];
		foreach (array_merge($metadata['referencesOne'], $metadata['referencesMany']) as $reference) {
			$referenceFields[] = $reference['field'];
		}

		// fields
		foreach (array_keys($metadata['fields']) as $name) {
			if (in_array($name, $referenceFields)) {
				continue;
			}
			$info['fields'][$name] = $this->{'get' . ucfirst($name)}();
		}

		// referencesOne
		foreach ($metadata['referencesOne'] as $name => $referenceOne) {
			$info['referencesOne'][$name] = $this->{'get' . ucfirst($referenceOne['field'])}();
		}

		// referencesMany
		foreach ($metadata['referencesMany'] as $name => $referenceMany) {
			$info['referencesMany'][$name] = $this->{'get' . ucfirst($referenceMany['field'])}();
		}

		// embeddedsOne
		foreach (array_keys($metadata['embeddedsOne']) as $name) {
			$embedded = $this->{'get' . ucfirst($name)}();
			$info['embeddedsOne'][$name] = $embedded ? $embedded->debug() : null;
		}

		// embeddedsMany
		foreach (array_keys($metadata['embeddedsMany']) as $name) {
			$info['embeddedsMany'][$name] = [];
			foreach ($this->{'get' . ucfirst($name)}() as $key => $value) {
				$info['embeddedsMany'][$name][$key] = $value->debug();
			}
		}

		return $info;
	}


	protected function registerOnceEvent($event, $type) {
		if (!is_callable($event)) {
			throw new Exception('Event parameter must be a callable');
		}

		if (!isset($this->onceEnvents[$type])) {
			$this->onceEnvents[$type] = [];
		}

		$this->onceEnvents[$type][] = $event;
	}


	protected function dispatchEvent($eventName) {
		$event = new Event($this);
		$fullEventName = sprintf($this->eventPattern, $eventName);

		$this->mongator->dispatchEvent($fullEventName, $event);
	}


	private function oncePreInsertEvent() {
		$this->executeOnceEvent('pre-insert');
	}


	private function oncePostInsertEvent() {
		$this->executeOnceEvent('post-insert');
	}


	private function oncePreUpdateEvent() {
		$this->executeOnceEvent('pre-update');
	}


	private function oncePostUpdateEvent() {
		$this->executeOnceEvent('post-update');
	}


	private function executeOnceEvent($type) {
		if (!isset($this->onceEnvents[$type])) {
			return;
		}

		foreach ($this->onceEnvents[$type] as $callback) {
			$callback($this);
		}

		$this->onceEnvents[$type] = [];
	}


	/**
	 * Sleep - prepare the object for serialization
	 */
	public function __sleep() {
		$rc = new ReflectionObject($this);

		$names = [];
		$filter = ['Mongator'];

		while ($rc instanceof ReflectionClass) {
			foreach ($rc->getProperties() as $prop) {
				if (!in_array($prop->getName(), $filter)) {
					$names[] = $prop->getName();
				}
			}

			$rc = $rc->getParentClass();
		}

		return array_unique($names);
	}


}
