<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Extension;

use Mandango\Mondator\Extension;

/**
 * DocumentPropertyOverloading extension.
 */
class DocumentPropertyOverloading extends Extension {


	protected function doClassProcess() {
		$this->processTemplate($this->definitions['document_base'], file_get_contents(__DIR__ . '/templates/DocumentPropertyOverloading.php.twig'));
	}


}
