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
 * ReferenceGroup.
 */
class ReferenceGroup extends Group {


	/**
	 * @param string                              $documentClass The document class.
	 * @param AbstractDocument $parent        The parent document.
	 * @param string                              $field         The reference field.
	 */
	public function __construct($documentClass, $parent, $field) {
		parent::__construct($documentClass);

		$this->getArchive()->set('parent', $parent);
		$this->getArchive()->set('field', $field);
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
	 * Creates and returns a query to query the referenced elements.
	 */
	public function createQuery() {
		return $this->getParent()->getMongator()->getRepository($this->getDocumentClass())->createQuery([
			'_id' => ['$in' => $this->doInitializeSavedData()],
		]);
	}


	protected function doInitializeSavedData() {
		return (array) $this->getParent()->{'get' . ucfirst($this->getField())}();
	}


	protected function doInitializeSaved($data) {
		return $this->getParent()->getMongator()->getRepository($this->getDocumentClass())->findById($data);
	}


}
