<?php

class SqlNode implements ArrayAccess
{

  public $name;
  public $value;
  public $children;

  function __construct($name = null, $value = null)
  {
    if ($name)
      {
	$this->name = $name;
      }
    if ($value)
      {
	$this->value = $value;
      }
    $this->children = array();
  }

  function __get($name)
  {
    Log::dbg("__get($name)");
    if (!isset($this->children[$name]))
      {
	$this->__set($name, null);
      }
    return $this->children[$name];
  }

  function __set($name, $value)
  {
    Log::dbg("__set($name, $value) ");
    if (!isset($this->children[$name]))
      {
	$this->children[$name] = new Node($name);
      }
  }

  function __toString()
  {
    $str = "";
    $str .= $this->name;
    if (isset($this->value))
      {
	$str .= " " .$this->value;
      }
    foreach ($this->children as $c)
      {
	$str .= " " .$c;
      }
    return $str;
  }

  public function offsetSet($offset, $value) 
  {
    Log::dbg("offsetSet($offset, $value)  ");

    if (! $value instanceof Node)
      {
	$value = new Node(null, $value);
      }

    if (is_null($offset)) 
      {
	$this->children[] = $value;
      } 
    else 
      {
	$this->children[$offset] = $value;
      }
  }

  public function offsetExists($offset) 
  {
    return isset($this->children[$offset]);
  }

  public function offsetUnset($offset) 
  {
    unset($this->children[$offset]);
  }

  public function &offsetGet($offset) 
  {
    Log::dbg("offsetGet($offset) ");
    return isset($this->children[$offset]) ? $this->children[$offset] : null;
  }

}