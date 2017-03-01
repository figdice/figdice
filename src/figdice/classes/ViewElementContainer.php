<?php

namespace figdice\classes;

class ViewElementContainer extends ViewElement
{

    public function __construct(array $children, ViewElementTag $parent = null)
    {
        parent::__construct();
        $this->parent = $parent;
        $this->children = $children;
        foreach ($this->children as $child) {
            $child->parent = $this;
        }
    }

    /** @var ViewElement[] */
    public $children;

    /**
     * @param Context $context
     *
     * @return string
     */
    public function render(Context $context)
    {
        $result = '';
        foreach ($this->children as $child) {
            $result .= $child->render($context);
        }
        return $result;
    }

    public function appendCDataSibling($cdata)
    {
    }
}
