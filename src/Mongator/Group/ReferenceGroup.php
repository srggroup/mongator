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

use Mongator\Archive;

/**
 * ReferenceGroup.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class ReferenceGroup extends Group
{
    /**
     * Constructor.
     *
     * @param string                              $documentClass The document class.
     * @param \Mongator\Document\AbstractDocument $parent        The parent document.
     * @param string                              $field         The reference field.
     *
     * @api
     */
    public function __construct($documentClass, $parent, $field)
    {
        parent::__construct($documentClass);

        $this->getArchive()->set('parent', $parent);
        $this->getArchive()->set('field', $field);
    }

    /**
     * Returns the parent document.
     *
     * @return \Mongator\Document\AbstractDocument The parent document.
     *
     * @api
     */
    public function getParent()
    {
        return $this->getArchive()->get('parent');
    }

    /**
     * Returns the reference field.
     *
     * @return string The reference field.
     *
     * @api
     */
    public function getField()
    {
        return $this->getArchive()->get('field');
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSavedData()
    {
        return (array) $this->getParent()->{'get'.ucfirst($this->getField())}();
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSaved($data)
    {
        return $this->getParent()->getMongator()->getRepository($this->getDocumentClass())->findById($data);
    }

    /**
     * Creates and returns a query to query the referenced elements.
     *
     * @api
     */
    public function createQuery()
    {
        return $this->getParent()->getMongator()->getRepository($this->getDocumentClass())->createQuery(array(
            '_id' => array('$in' => $this->doInitializeSavedData()),
        ));
    }
}
