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
	$this->name = strtolower($name);
      }
    if ($value)
      {
	$this->value = $value;
      }
    $this->children = array();
  }

  public static function create($name = null, $value = null)
  {
    $c = "Sql$name";
    if (class_exists($c))
      {
	return new $c($value);
      }
    return new SqlNode($name, $value);
  }
  function __get($name)
  {
    Log::dbg("__get($name)");
    $name = strtolower($name);
    if (!isset($this->children[$name]))
      {
	$this->__set($name, null);
      }
    return $this->children[$name];
  }

  function __set($name, $value)
  {
    Log::dbg("__set($name, $value) ");
    $name = strtolower($name);
    if (!isset($this->children[$name]))
      {
	$this->children[$name] = SqlNode::create($name);
      }

    $items = preg_split("/ */", $value);
    if (count($items) == 1)
      { 
	$this->children[$name]->value .= "-".$value;
      }
    else
      {
	foreach ($items as $i)
	  {
	    $this->children[$name]->value .= SqlNode::create(null, $i);
	  }
      }
  }

  function __toString()
  {
    $str = "";
    $str .= $this->name;
    if (isset($this->value))
      {
	$str .= " ." .$this->value;
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

    if (! $value instanceof SqlNode)
      {
	$value = SqlNode::create(null, $value);
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


class SqlSelect extends SqlNode
{

  function __construct($value = null)
  {
    parent::__construct("Select", $value = null);
  }

}
