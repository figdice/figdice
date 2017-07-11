<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
 * @package FigDice
 *
 * This file is part of FigDice.
 *
 *
 *
 */

namespace figdice\classes;

class ViewElementCData extends ViewElement implements \Serializable {

    public function __construct($cdata = null, ViewElementTag $parent = null)
    {
        parent::__construct();
        if (null !== $cdata) {
            $this->outputBuffer = $cdata;
        }
        if (null !== $parent) {
            $this->parent = $parent;
        }
    }

    public function render(Context $context) {
        return $this->outputBuffer;
    }
    public function appendCDataSibling($cdata) {
        $this->outputBuffer .= $cdata;
    }

    public function serialize()
    {
        return serialize($this->outputBuffer);
    }
    public function unserialize($serialized)
    {
        $this->outputBuffer = unserialize($serialized);
    }
}
