<?php

class ArcaneDB
{

  /* an instance of a Bridge */
  public $_bridge;
  public $_pdox;
  public $_database;

  /* holds a singleton instance of the ArcaneDB class */
  public static $current;

  /* holds a singleton instances of database specific ArcaneDB class */
  public static $databases = array();

  function __construct($pdox, $bridge)
  {
    $this->_bridge = $bridge;
    $this->_pdox = $pdox;
  }


  /* Get the singleton instance of this class. */
  public static function get($pdox = null)
  {
    //Log::dbg("Arcane::get");
    if (!self::$current)
      {	
	$pdox = $pdox ? $pdox : APdo::get();

	$bridge = new MySql();
	self::$current = new ArcaneDB($pdox, $bridge);
	Schema::load($pdox);
      }
    return self::$current;
  }

  /* Get the singleton instance of this class. */
  public static function db($name)
  {
    //Log::dbg("Arcane::db($name)");
    if (!isset(self::$databases[$name]))
      {	
	$pdox = APdo::get($GLOBALS['DB'][$name], $name);
	$bridge = new MySql();
	self::$databases[$name] = new ArcaneDB($pdox, $bridge);
	Schema::load($pdox);
      }
    return self::$databases[$name];
  }

  /* Get the singleton instance of this class. */
  public static function information_schema($db)
  {
    $prop = $GLOBALS['DB'][$db];
    $prop['DB_DATABASE'] = 'information_schema';
    $dbname = $db.'_information_schema';
    //Log::dbg($GLOBALS['DB']);
    //Log::dbg($db);
    //Log::dbg($prop);

    if (!isset(self::$databases[$dbname]))
      {	
	//Log::dbg('new ArcaneDB');
	$pdox = APdo::get($prop, $dbname);
	$bridge = new MySql();
	self::$databases[$dbname] = new ArcaneDB($pdox, $bridge);
      }
    return self::$databases[$dbname];
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
    $db = new ArcaneDB($pdox, $bridge);
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

    if ($this->_database)
      {
	$e->database($this->_database);
	$this->_database = null;
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

    if ($this->_database)
      {
	$e->database($this->_database);
	$this->_database = null;
      }
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

    if ($this->_database)
      {
	$e->database($this->_database);
	$this->_database = null;
      }
    return $e;
  }

  public function get_entity_class($name)
  {

    $database = is_null($this->_database) ? $this->_pdox->database_alias : $this->_database;
    $c = $name;
    if (class_exists($c))
      {
	return $c;
      }

    $c = $GLOBALS["ARCANE_CLASS_PREFIX"]. $database.'__'.$name;
    if (class_exists($c))
      {
	return $c;
      }
    
    return null;
  }

  function __get($db)
  {   
    $this->_database = $db;
    return $this;
  }

  public function __call($name, $args) {
    return $this->entity($name, $args, $this->_pdox, $this->_bridge);
  }

}

?>
