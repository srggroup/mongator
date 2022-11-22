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
use Mongator\Document\Document;
use Traversable;

/**
 * AbstractGroup.
 *
 * @author Pablo Díez <pablodip@gmail.com>
 *
 * @api
 */
abstract class AbstractGroup implements \Countable, \IteratorAggregate
{
    private $saved;
    private $archive;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->archive = new Archive();
    }

    /**
     * Return the Archive object
     *
     * @return \Mongator\Archive the archive object from this group
     *
     * @api
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Adds document/s to the add queue of the group.
     *
     * @param \Mongator\Document\AbstractDocument|array $documents One or more documents.
     *
     * @api
     */
    public function add($documents)
    {

        $pendingRemove = $this->getRemove();
        if ($this->getRemove()) {
            if ($this->all()) {
                throw new \RuntimeException('Adding to group with pending remove is not permitted');
            } else {
                $this->clearAdd();
                $this->clearRemove();
                $this->saved = array();
            }
        }
        if (!is_array($documents)) {
            $documents = array($documents);
        }

        $add =& $this->getArchive()->getByRef('add', array());
        foreach ($documents as $document) {
            $add[] = $document;
        }
    }

    /**
     * Returns the add queue of the group.
     *
     * @api
     */
    public function getAdd()
    {
        return $this->getArchive()->getOrDefault('add', array());
    }

    /**
     * Clears the add queue of the group.
     *
     * @api
     */
    public function clearAdd()
    {
        $this->getArchive()->remove('add');
    }

    /**
     * Adds document/s to the remove queue of the group.
     *
     * @param \Mongator\Document\AbstractDocument|array $documents One of more documents.
     *
     * @api
     */
    public function remove($documents)
    {
        if (!is_array($documents)) {
            $documents = array($documents);
        }

        $remove =& $this->getArchive()->getByRef('remove', array());
        foreach ($documents as $document) {
            $remove[] = $document;
        }
    }

    /**
     * Clear all documents
     *
     * @api
     */
    public function clear()
    {
	    $this->getArchive()->set('clear', true);
    }

    /**
     * Returns the remove queue of the group.
     *
     * @api
     */
    public function getRemove()
    {
        return $this->getArchive()->getOrDefault('remove', array());
    }

    /**
     * Returns the clear property
     *
     * @api
     */
    public function getClear()
    {
        return $this->getArchive()->getOrDefault('clear', false);
    }

    /**
     * Clears the remove queue of the group.
     *
     * @api
     */
    public function clearRemove()
    {
        $this->getArchive()->remove('remove');
    }

    /**
     * Returns the saved documents of the group.
     */
    public function getSaved()
    {
        if (null === $this->saved) {
            $this->initializeSaved();
        }

        return $this->saved;
    }

    /**
     * Marks everything as saved, removes pending add and delete arrays
     */

    public function markAllSaved()
    {
        $this->saved = $this->all();
        foreach ($this->saved as $document) {
            $document->clearModified();
            $rap = $document->getRootAndPath();
            $rap['path'] = str_replace('._add', '.', $rap['path']);
            $document->setRootAndPath($rap['root'], $rap['path']);
        }
        $this->clearAdd();
        $this->clearRemove();
    }

    /**
     * Returns the saved + add - removed elements.
     *
     * @api
     */
    public function all()
    {
        $documents = array_merge($this->getSaved(), $this->getAdd());

        foreach ($this->getRemove() as $document) {
            if (false !== $key = array_search($document, $documents)) {
                unset($documents[$key]);
            }
        }

        return array_values($documents);
    }

    /**
     * Returns the first element from all()
     *
     * @api
     */
    public function one()
    {
        $documents = $this->all();
        if ( count($documents) == 0 ) return null;
        return $documents[0];
    }

    /**
     * Implements the \IteratorAggregate interface.
     *
     * @api
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Refresh the saved documents.
     *
     * @api
     */
    public function refreshSaved()
    {
        $this->initializeSaved();
    }

    /**
     * Initializes the saved documents.
     */
    private function initializeSaved()
    {
        $this->saved = $this->doInitializeSaved($this->doInitializeSavedData());
    }

    /**
     * Clears the saved documents.
     *
     * @api
     */
    public function clearSaved()
    {
        $this->saved = null;
    }

    /**
     * Returns if the saved documents are initialized.
     *
     * @return bool If the saved documents are initialized.
     *
     * @api
     */
    public function isSavedInitialized()
    {
        return null !== $this->saved;
    }

    /**
     * Do the initialization of the saved documents data.
     *
     * @api
     */
    abstract protected function doInitializeSavedData();

    /**
     * Do the initialization of the saved documents.
     *
     * @api
     */
    protected function doInitializeSaved($data)
    {
        return $data;
    }

    /**
     * Returns the number of all documents.
     *
     * @api
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Replace all documents.
     *
     * @param array $documents An array of documents.
     *
     * @api
     */
    public function replace(array $documents)
    {
        $this->clearAdd();


	    if($documents === []){
	        $this->clear();
		    return;
	    }

        $this->remove($this->getSaved());

        $this->clearRemove();

        $this->saved = array();

        $this->add($documents);
    }


    /**
     * Resets the group (clear adds and removed, and saved if there are adds or removed).
     *
     * @api
     */
    public function reset()
    {
        if ($this->getAdd() || $this->getRemove()) {
            $this->clearSaved();
        }
        $this->clearAdd();
        $this->clearRemove();
    }
}
