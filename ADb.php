<?php

class ADB
{

  /* an instance of a Bridge */
  var $_bridge;
  var $_pdox;

  /* holds a singleton instance of the ADB class */
  public static $current;

  /* holds a singleton instances of database specific ADB class */
  public static $databases = array();

  function __construct($pdox, $bridge)
  {
    $this->_bridge = $bridge;
    $this->_pdox = $pdox;
  }


  /* Get the singleton instance of this class. */
  public static function get($pdox = null)
  {
    if (!self::$current)
      {	
	$pdox = $pdox ? $pdox : APdo::get();

	$bridge = new MySql();
	self::$current = new ADB($pdox, $bridge);
	Schema::load($pdox);
      }
    return self::$current;
  }

  /* Get the singleton instance of this class. */
  public static function db($name)
  {
    if (!isset(self::$databases[$name]))
      {	
	$pdox = APdo::get($GLOBALS['DB'][$name]);

	$bridge = new MySql();
	self::$databases[$name] = new ADB($pdox, $bridge);
	Schema::load($pdox);
      }
    return self::$databases[$name];
  }


  /* Get the singleton instance of this class. */
  public static function close($db)
  {
    $db->_bridge = null;
  }

  /* Get the singleton instance of this class. */
  public static function isolated()
  {
    $pdox = APdo::get();
    $bridge = new MySql();
    $db = new ADB($pdox, $bridge);
    Schema::load($pdox);
    return $db;
  }

  /* A static method to create a Entity instance. */
  public function entity($name, $args = null, $pdox = null, $bridge = null)
  {

    if (!$bridge)
      {
	$bridge = new MySql();
      }
    $c = self::get_entity_class($name);
    if ($c)
      {
	$e = new $c($args, $pdox, $bridge);	
      }
    else
      {
	$e = new Entity($name, $args, $pdox, $bridge);
      }
    return $e;
  }

  /* A static method to create a Entity instance. */
  public function entity_from_array($name, $data)
  {
    $c = self::get_entity_class($name);
    if ($c)
      {
	$e = new $c();
      }
    else
      {
	$e = new Entity($name);
      }
    $e->feed($data);
    $e->_count = 1;
    return $e;
  }

  /* A static method to create a Entity instance. */
  public function entity_abstract($name)
  {
    $c = self::get_entity_class($name);
    if ($c)
      {
	$e = new $c($name);
      }
    else
      {
	$e = new Entity($name);
      }
    return $e;
  }

  public function get_entity_class($name)
  {

    $c = $name;
    if (class_exists($c))
      {
	return $c;
      }

    $c = $GLOBALS["ARCANE_CLASS_PREFIX"]. "$name";
    if (class_exists($c))
      {
	return $c;
      }

    return null;
  }

  function __get($name)
  {
    return $this->entity($name, null, $this->_pdox, $this->_bridge);
  }

  public function __call($name, $args) {
    return $this->entity($name, $args, $this->_pdox, $this->_bridge);
  }

}

?>
