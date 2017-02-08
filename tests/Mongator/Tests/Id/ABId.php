<?php

namespace Mongator\Tests\Id;

use MongoDB\BSON\ObjectID;

class ABId
{
    public $a;
    public $b;

    public function __construct($id = null)
    {
        if ($id === null) {
            $this->a = new ObjectID();
            $this->b = new ObjectID();
        } else {
            $this->a = $id['a'];
            $this->b = $id['b'];
        }
    }

    public function __toString()
    {
        return sprintf('a:%s:b:%s', $this->a, $this->b);
    }
}
