<?php

class Base
{
  public $_oid;
  public $_database = null;
  public $_name;
  public $_alias;
  public $_rev = array();
  public $_rel = array();
  public $_pk = "";
  public $_col = array();
  public $_uuid = false;
  public $_auto = false;
  public $_readonly = array();

  public $_pdox;
  public $_active;
  public $_bridge;

  public function __construct()
  {

  }

  public function new_attribute($name, $value = null)
  {
    $a = new Attribute($name, $value);
    $vars = get_class_vars(__CLASS__);
    foreach ($vars as $name => $v)
      {
	$a->$name =& $this->$name;
      }
    $a->alias = $this->alias().'__'.$a->name;

    return $a;
  }


  public function condition($lh, $op, $rh)
  {
    $this->_bridge->add_condition($lh, $op, $rh);
  }

  public function database($database = null)
  {
    if (is_null($database))
      {
	return $this->_database;    
      }

    $this->_database = $database;
  }

  function name()
  {
    if ($this->_database)
      {
	return $this->_database .'.'.$this->_name;
      }
    return $this->_name;
  }

  function nameBase()
  {
    $n = $this->name();
    $n = preg_replace('/[-_0-9]+$/','', $n);
    return $n;
  }

  function nameVariant()
  {
    $n = $this->name();
    $n = preg_replace('/^.*[^0-9]/','', $n);
    return $n;
  }

  function alias()
  {
    if ($this->_database)
      {
	return $this->_database .'__'.$this->_alias;
      }
    return $this->_alias;
  }

  function bridge()
  {
    return $this->_bridge;
  }

  function active()
  {
    return $this->_active;
  }

  function single()
  {
    return $this->_bridge->is_single_entity();
  }

  function query_count()
  {
    return APdo::$query_count;
  }

  function ___pk()
  {
    $result = array();
    $pk = $this->_pk;
    if (count($pk) > 0)
      {
	$result[$pk] = $this->_attr[$pk]->value;	
      }
    if (count($pk) == 1)
      {
	$result = $this->_attr[$pk];	
      }
    return $result;
  }

  function col()
  {
    return $this->_col;
  }

  function readonly()
  {
    return $this->_readonly;
  }

}
