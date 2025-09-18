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

use ArrayAccess;
use Mandango\Mondator\Extension;

/**
 * DocumentArrayAccess extension.
 */
class DocumentArrayAccess extends Extension {


	protected function doClassProcess() {
		$this->definitions['document_base']->addInterface(ArrayAccess::class);

		$this->processTemplate($this->definitions['document_base'], file_get_contents(__DIR__ . '/templates/DocumentArrayAccess.php.twig'));
	}


}
