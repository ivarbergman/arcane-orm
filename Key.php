<?php


class Key extends Base implements IteratorAggregate
{

  public $attribute;

  public function __construct($entity, $attribute_names)
  {
    foreach ($attribute_names as $name)
      {	
	$this->attribute[] = $entity->_attr[$name];
      }	
  }

  public function value()
  {
    $str = array();
    foreach ($this->attribute as $a)
      {	
	$str[] = $a->value ;
      }	
    $str = implode($str, ",");
    return $str;
  }

  public function sql()
  {
    $str = array();
    foreach ($this->attribute as $a)
      {	
	$str[] = $a->sql() ;
      }	
    return implode($str, ",");
  }


  public function __toString()
  {
    return $this->sql();
  }

  function eq($val) {  return $this->__is_equal($val);  }
  function __is_equal($val) {
    
    if (!is_array($val))
      {
	$val = array($val);
      }
    foreach ($this as $attr)
      {	
	$a = array_shift($val);
	$attr->eq = $a;
      }	
    return true;
  }

  public function getIterator() 
  {
    return new KeyIterator($this);
  }
}


class KeyIterator implements Iterator
{

  private $attribute;

  public function __construct($primaryKey)
  {
    $this->attribute = &$primaryKey->attribute;
  }

  public function rewind()
  {
    reset($this->attribute);
  }
  
    public function current()
    {
        $var = current($this->attribute);
        return $var;
    }
  
    public function key() 
    {
        $var = key($this->attribute);
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->attribute);
        return $var;
    }
  
    public function valid()
    {
        $key = key($this->attribute);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
}

