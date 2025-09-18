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

/**
 * Group.
 */
abstract class Group extends AbstractGroup {


	/**
	 * @param string $documentClass The document class.
	 */
	public function __construct($documentClass) {
		parent::__construct();
		$this->getArchive()->set('document_class', $documentClass);
	}


	/**
	 * Returns the document class.
	 */
	public function getDocumentClass() {
		return $this->getArchive()->get('document_class');
	}


}
