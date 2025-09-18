<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Group;

use Mongator\Document\AbstractDocument;

/**
 * PolymorphicReferenceGroup.
 */
class PolymorphicReferenceGroup extends PolymorphicGroup {


	/**
	 * @param string                              $discriminatorField The discriminator field.
	 * @param AbstractDocument $parent             The parent document.
	 * @param string                              $field              The reference field.
	 * @param array|bool                       $discriminatorMap   The discriminator map if exists, otherwise false.
	 */
	public function __construct($discriminatorField, $parent, $field, $discriminatorMap = false) {
		parent::__construct($discriminatorField);

		$this->getArchive()->set('parent', $parent);
		$this->getArchive()->set('field', $field);
		$this->getArchive()->set('discriminatorMap', $discriminatorMap);
	}


	/**
	 * Returns the parent document.
	 *
	 * @return AbstractDocument The parent document.
	 */
	public function getParent() {
		return $this->getArchive()->get('parent');
	}


	/**
	 * Returns the reference field.
	 *
	 * @return string The reference field.
	 */
	public function getField() {
		return $this->getArchive()->get('field');
	}


	/**
	 * Returns the discriminator map.
	 *
	 * @return array|bool The discriminator map.
	 */
	public function getDiscriminatorMap() {
		return $this->getArchive()->get('discriminatorMap');
	}


	protected function doInitializeSavedData() {
		return (array) $this->getParent()->get($this->getField());
	}


	protected function doInitializeSaved($data) {
		$parent = $this->getParent();
		$mongator = $parent->getMongator();

		$discriminatorField = $this->getDiscriminatorField();
		$discriminatorMap = $this->getDiscriminatorMap();

		$ids = [];
		foreach ($data as $datum) {
			if ($discriminatorMap) {
				$documentClass = $discriminatorMap[$datum[$discriminatorField]];
			} else {
				$documentClass = $datum[$discriminatorField];
			}
			$ids[$documentClass][] = $datum['id'];
		}

		$documents = [];
		foreach ($ids as $documentClass => $documentClassIds) {
			foreach ((array) $mongator->getRepository($documentClass)->findById($documentClassIds) as $document) {
				$documents[] = $document;
			}
		}

		return $documents;
	}


}
