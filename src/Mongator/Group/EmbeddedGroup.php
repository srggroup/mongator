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

use Mongator\Document\Document;

/**
 * EmbeddedGroup.
 */
class EmbeddedGroup extends Group {


	/**
	 * Set the root and path of the embedded group.
	 *
	 * @param Document $root The root document.
	 * @param string                      $path The path.
	 */
	public function setRootAndPath(Document $root, $path) {
		$this->getArchive()->set('root_and_path', ['root' => $root, 'path' => $path]);

		foreach ($this->getAdd() as $key => $document) {
			$document->setRootAndPath($root, $path . '._add' . $key);
		}
	}


	/**
	 * Returns the root and the path.
	 */
	public function getRootAndPath() {
		return $this->getArchive()->getOrDefault('root_and_path', null);
	}


	public function add($documents) {
		parent::add($documents);

		if ($rap = $this->getRootAndPath()) {
			foreach ($this->getAdd() as $key => $document) {
				$document->setRootAndPath($rap['root'], $rap['path'] . '._add' . $key);
			}
		}
	}


	/**
	 * Set the saved data.
	 *
	 * @param array $data The saved data.
	 */
	public function setSavedData($data) {
		$this->getArchive()->set('saved_data', $data);
	}


	/**
	 * Returns the saved data.
	 *
	 * @return array|null The saved data or null if it does not exist.
	 */
	public function getSavedData() {
		return $this->getArchive()->getOrDefault('saved_data', null);
	}


	protected function doInitializeSavedData() {
		$rap = $this->getRootAndPath();
		$rap['root']->addFieldCache($rap['path']);

		$data = $this->getSavedData();
		if ($data !== null) {
			return $data;
		}

		return [];
	}


	protected function doInitializeSaved($data) {
		$documentClass = $this->getDocumentClass();
		$rap = $this->getRootAndPath();
		$mongator = $rap['root']->getMongator();

		$saved = [];
		foreach ($data as $key => $datum) {
			if ($datum === null) {
				continue;
			}

			$saved[] = $document = $mongator->create($documentClass);
			$document->setDocumentData($datum);
			$document->setRootAndPath($rap['root'], $rap['path'] . '.' . $key);
		}

		return $saved;
	}


}
