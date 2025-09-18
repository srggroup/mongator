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
 * PolymorphicGroup.
 */
abstract class PolymorphicGroup extends AbstractGroup {


	/**
	 * @param string $discriminatorField The discriminator field.
	 */
	public function __construct($discriminatorField) {
		parent::__construct();

		$this->getArchive()->set('discriminatorField', $discriminatorField);
	}


	/**
	 * Returns the discriminator field.
	 *
	 * @return string The discriminator field.
	 */
	public function getDiscriminatorField() {
		return $this->getArchive()->get('discriminatorField');
	}


}
