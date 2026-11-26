<?php

namespace App\Core\Db\Expr;

use App\Core\Db\DbAwareInterface;
use App\Core\Db\DbAwareTrait;
use App\Core\Db\Query;

class NotIn implements ExprInterface, DbAwareInterface
{
    use DbAwareTrait;
    
    private $field;
    private $values;

    function __construct($field = null, $values = [])
    {
        $this->field = $field;
        $this->values = $values;
    }

    function build()
    {
        if (empty($this->values)) {
            return '1=0'; // Return a condition that is always false if there are no values
        }

        if ($this->values instanceof Query) {
            return $this->field . ' NOT IN (' . $this->values->build() . ') ';
        } else {
            return $this->field . ' NOT IN (' . implode(', ', array_map([$this->db, 'escape'], $this->values)) . ') ';
        }
    }
}