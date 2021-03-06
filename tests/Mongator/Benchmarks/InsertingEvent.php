<?php

/*
 * This file is part of Mongator.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Benchmarks;

class InsertingEvent extends AthleticEvent
{
    /**
     * @iterations 500
     */
    public function simpleDocument()
    {
        $this->buildSimpleDocument()->save();
    }

    /**
     * @iterations 500
     */
    public function simpleNestedDocument()
    {
        $this->buildSimpleNestedDocument()->save();
    }

    /**
     * @iterations 500
     */
    public function complexDocument()
    {
        $this->buildComplexDocument()->save();
    }

    /**
     * @iterations 500
     */
    public function complexNestedDocument()
    {
        $this->buildComplexNestedDocument()->save();
    }
}