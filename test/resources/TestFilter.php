<?php
use figdice\Filter;

class TestFilter implements Filter
{
  public function transform($buffer)
  {
    return str_replace('one', 'two', $buffer);
  }
}
