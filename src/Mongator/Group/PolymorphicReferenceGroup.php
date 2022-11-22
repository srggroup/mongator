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
 * PolymorphicReferenceGroup.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
class PolymorphicReferenceGroup extends PolymorphicGroup
{
    /**
     * Constructor.
     *
     * @param string                              $discriminatorField The discriminator field.
     * @param \Mongator\Document\AbstractDocument $parent             The parent document.
     * @param string                              $field              The reference field.
     * @param array|Boolean                       $discriminatorMap   The discriminator map if exists, otherwise false.
     *
     * @api
     */
    public function __construct($discriminatorField, $parent, $field, $discriminatorMap = false)
    {
        parent::__construct($discriminatorField);

        $this->getArchive()->set('parent', $parent);
        $this->getArchive()->set('field', $field);
        $this->getArchive()->set('discriminatorMap', $discriminatorMap);
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
     * Returns the discriminator map.
     *
     * @return array|Boolean The discriminator map.
     *
     * @api
     */
    public function getDiscriminatorMap()
    {
        return $this->getArchive()->get( 'discriminatorMap');
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSavedData()
    {
        return (array) $this->getParent()->get($this->getField());
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitializeSaved($data)
    {
        $parent = $this->getParent();
        $mongator = $parent->getMongator();

        $discriminatorField = $this->getDiscriminatorField();
        $discriminatorMap = $this->getDiscriminatorMap();

        $ids = array();
        foreach ($data as $datum) {
            if ($discriminatorMap) {
                $documentClass = $discriminatorMap[$datum[$discriminatorField]];
            } else {
                $documentClass = $datum[$discriminatorField];
            }
            $ids[$documentClass][] = $datum['id'];
        }

        $documents = array();
        foreach ($ids as $documentClass => $documentClassIds) {
            foreach ((array) $mongator->getRepository($documentClass)->findById($documentClassIds) as $document) {
                $documents[] = $document;
            }
        }

        return $documents;
    }
}
