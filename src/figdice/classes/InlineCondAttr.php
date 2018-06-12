<?php

namespace figdice\classes;

/**
 * Manual:
 * If an XML attribute is in the shape:
 *   attribute="|condExpr|valExpr"
 *
 * then it is rendered conditionally on the evaluated condExpr at run-time,
 * and if true, the value is the eval of valExpr.
 *
 * If condExpr evalutes to false, the attribute is not rendered at all.
 */
class InlineCondAttr
{
    public $cond;
    public $val;
    /**
     * @param string $condExpr
     * @param string $valExpr
     */
    public function __construct($condExpr, $valExpr)
    {
        $this->cond = $condExpr;
        $this->val = $valExpr;
    }
}
